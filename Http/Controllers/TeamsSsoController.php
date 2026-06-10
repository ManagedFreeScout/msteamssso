<?php

namespace Modules\MSTeamsSso\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\User;

class TeamsSsoController extends Controller
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('msteamssso', []);
        $this->config['tenant_id'] = (string)(\Option::get('msteamssso.tenant_id') ?? '');
        $this->config['client_id']  = (string)(\Option::get('msteamssso.client_id') ?? '');
        if (empty($this->config['audience'])) {
            $this->config['audience'] = $this->config['client_id'] ?: null;
        }
    }

    public function entry()
    {
        return view('msteamssso::teams-entry');
    }

    public function fallback()
    {
        return view('msteamssso::teams-fallback');
    }

    public function login(Request $request)
    {
        \Log::info('Teams SSO login() called - client_id config: ' . json_encode($this->config['client_id'] ?? 'not set'));
        $token = $request->input('token');
        if (empty($token)) {
            return response()->json(['error' => 'missing_token'], 400);
        }

        try {
            $payload = $this->validateToken($token);
        } catch (\Exception $e) {
            Log::error('Teams SSO token validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'invalid_token', 'message' => $e->getMessage()], 400);
        }

        $email = $payload->preferred_username ?? $payload->upn ?? $payload->email ?? null;
        if (!$email) {
            return response()->json(['error' => 'no_email_claim'], 400);
        }

        // Allowed domains whitelist check
        $allowedDomains = trim((string)(\Option::get('msteamssso.allowed_domains') ?? ''));
        if (!empty($allowedDomains)) {
            $emailDomain = strtolower(substr($email, strpos($email, '@') + 1));
            $allowed = array_filter(array_map(function ($d) {
                return strtolower(trim($d));
            }, explode(',', $allowedDomains)));
            if (!empty($allowed) && !in_array($emailDomain, $allowed, true)) {
                return response()->json([
                    'error' => 'domain_not_allowed',
                    'message' => 'Your email domain is not permitted to access this system.',
                ], 403);
            }
        }

        $user = User::where('email', $email)->first();

        // Fix #4: deny unknown users instead of auto-creating them
        if (!$user) {
            return response()->json([
                'error' => 'user_not_found',
                'message' => 'No FreeScout account exists for this email address. Contact your administrator.',
            ], 403);
        }

        Auth::login($user);

        return response()->json(['success' => true]);
    }

    protected function validateToken(string $jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \Exception('malformed_jwt');
        }

        list($headerB64, $payloadB64, $signatureB64) = $parts;
        $header = json_decode($this->base64UrlDecode($headerB64));
        $payload = json_decode($this->base64UrlDecode($payloadB64));
        $signature = $this->base64UrlDecode($signatureB64);

        if (empty($header->kid)) {
            throw new \Exception('missing_kid');
        }

        $jwks = $this->getJwks();
        if (empty($jwks)) {
            throw new \Exception('jwks_unavailable');
        }

        $key = null;
        foreach ($jwks['keys'] as $k) {
            if ($k['kid'] === $header->kid) {
                $key = $k;
                break;
            }
        }
        if (!$key) {
            throw new \Exception('key_not_found');
        }

        $data = $headerB64 . '.' . $payloadB64;
        $pem = $this->buildPem($key['n'], $key['e']);
        $verified = openssl_verify($data, $signature, $pem, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new \Exception('invalid_signature');
        }

        // Validate audience
        $aud = $this->config['audience'] ?? $this->config['client_id'] ?? null;
        if ($aud) {
            $tokenAud = $payload->aud ?? null;
            \Log::info('Teams SSO debug - token aud: ' . json_encode($payload->aud ?? 'not set') . ' | checking against: ' . json_encode($aud));
            $tokenAudList = is_array($tokenAud) ? $tokenAud : [$tokenAud];
            $matched = false;
            foreach ($tokenAudList as $a) {
                if ($a === $aud || str_ends_with((string)$a, '/' . $aud)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw new \Exception('invalid_audience');
            }
        }

        // Fix #3: tenant_id must be configured — no hardcoded fallback
        $tenant = $this->config['tenant_id'] ?? null;
        if (!$tenant) {
            throw new \Exception('tenant_id_not_configured');
        }
        $tokenIss = rtrim($payload->iss ?? '', '/');

        $validIssuers = [
            rtrim('https://login.microsoftonline.com/' . $tenant . '/v2.0', '/'),
            rtrim('https://sts.windows.net/' . $tenant, '/'),
        ];

        if (!in_array($tokenIss, $validIssuers, true)) {
            throw new \Exception('invalid_issuer: ' . $tokenIss);
        }

        return $payload;
    }

    protected function getJwks(): ?array
    {
        // Fix #3: no hardcoded tenant fallback
        $tenant = $this->config['tenant_id'] ?? null;
        if (!$tenant) {
            return null;
        }

        $cacheKey = 'msteams_jwks_' . $tenant;
        $ttl = $this->config['jwks_cache_ttl'] ?? 3600;

        return Cache::remember($cacheKey, $ttl, function () use ($tenant) {
            $metadataUrl = "https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration";

            $metadataJson = @file_get_contents($metadataUrl);
            if (!$metadataJson) {
                return null;
            }
            $meta = json_decode($metadataJson, true);
            if (empty($meta['jwks_uri'])) {
                return null;
            }

            $jwksJson = @file_get_contents($meta['jwks_uri']);
            if (!$jwksJson) {
                return null;
            }

            return json_decode($jwksJson, true);
        });
    }

    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $data .= str_repeat('=', $padLen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    protected function buildPem(string $n, string $e): string
    {
        $modulus = $this->base64UrlDecode($n);
        $exponent = $this->base64UrlDecode($e);
        return $this->encodePublicKey(['modulus' => $modulus, 'publicExponent' => $exponent]);
    }

    protected function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($temp)) . $temp;
    }

    protected function encodePublicKey(array $components): string
    {
        $modulus = $components['modulus'];
        $publicExponent = $components['publicExponent'];

        $modulusEncoded = chr(0x02) . $this->encodeLength(strlen($modulus)) . $modulus;
        $exponentEncoded = chr(0x02) . $this->encodeLength(strlen($publicExponent)) . $publicExponent;

        $sequence = chr(0x30) . $this->encodeLength(strlen($modulusEncoded) + strlen($exponentEncoded)) . $modulusEncoded . $exponentEncoded;

        $rsaOID = hex2bin('300d06092a864886f70d0101010500');
        $bitString = chr(0x03) . $this->encodeLength(strlen($sequence) + 1) . chr(0x00) . $sequence;

        $publicKeyInfo = chr(0x30) . $this->encodeLength(strlen($rsaOID) + strlen($bitString)) . $rsaOID . $bitString;

        return "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($publicKeyInfo), 64) .
            "-----END PUBLIC KEY-----\n";
    }
}
