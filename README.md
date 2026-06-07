# MSTeams SSO Module
## README — for Claude Code sessions

> Read this file at the start of every Claude Code session working on this module.
> Update the Session Log at the end of every session.
> Update the Changelog when a new version is packaged.

---

## ⚠️ CRITICAL PATTERN — settings save (READ FIRST)

Settings are saved via FreeScout's **NATIVE settings handler**, NOT a custom route.

- The settings form submits to `""` (FreeScout's own settings controller), NOT a custom `msteamssso.settings.save` route
- Input names are `settings[msteamssso.tenant_id]`, `settings[msteamssso.client_id]`, `settings[msteamssso.allowed_domains]`
- Include a hidden field `<input type="hidden" name="settings[dummy]" value="1">` to trigger the save
- The submit button uses `name="action" value="msteamssso_save"`
- FreeScout's built-in handler calls `\Option::set()` automatically for each `settings[key]`
- `TeamsSsoController` reads them back via `\Option::get('msteamssso.tenant_id')` etc.

**This mirrors the working CfsAssist module. DO NOT build a custom save route/controller — every attempt to do so failed (cost ~2 hours on 6 Jun 2026). The reference working pattern is in the CfsAssist module — read it before touching settings save logic.**

The general lesson: when this module needs to do something another working module (CfsAssist, AdvancedPrint) already does, COPY the working pattern first. Do not engineer a new one.

---

## What this module does

Allows FreeScout support agents to log into FreeScout directly from a Microsoft Teams tab using Single Sign-On (SSO) — no password required. When a Teams tab opens FreeScout, the module requests an SSO token from Teams, validates it against Azure AD, and logs the agent in automatically.

The module is fully working and deployed — FreeScout runs as a proper Teams tab app visible in the Teams sidebar. The Azure AD app registration, Teams app manifest and Microsoft Partner Center setup are all in place.

**Target market:** Any Microsoft 365 / Teams organisation running self-hosted FreeScout. Distributed via Microsoft Teams app store. StackPros is an MS ISP partner — app store submission pathway is ready.

---

## Module identity

- **Name:** MSTeams SSO
- **Alias:** msteamssso
- **Current version:** 1.1.7
- **Required FreeScout version:** >= 1.8.202 (command.after_app_update hook)
- **Recommended FreeScout version:** >= 1.8.219 (CSP warning support)
- **Product ID (licensing server):** [TO BE ASSIGNED on stackpros.io DLM]
- **License server:** https://stackpros.io
- **Module constant:** MSTEAMSSSO_MODULE (renamed from SAMPLE_MODULE in v1.1.0)
- **Author:** StackPros
- **Author URL:** https://stackpros.io

---

## FreeScout troubleshooting references — READ BEFORE GUESSING

When FreeScout breaks, consult these FIRST. The documentation has repeatedly saved hours of guesswork.

1. **Clearing the cache (4 methods — method 4 is manual rm, most reliable on shared hosting):**
   https://github.com/freescout-help-desk/freescout/wiki/Clearing-the-Cache

2. **Full troubleshooting guide:**
   https://github.com/freescout-help-desk/freescout/wiki/Installation-Guide#11-troubleshooting

3. **Top menu / JS not working:**
   https://github.com/freescout-help-desk/freescout/wiki/Installation-Guide#-if-styles-or-javascripts-are-working-incorrectly-top-menu-is-not-working

4. **Module won't activate/deactivate (cache owned by wrong user):**
   https://github.com/freescout-help-desk/freescout/wiki/Installation-Guide#-im-activating-deactivating-a-module-but-it-stays-inactive-active

5. **Debugging (writing to logs):**
   https://github.com/freescout-help-desk/freescout/wiki/Debugging

### Manual cache clear (method 4 — use when Whoops blocks everything)
```bash
cd /home/ur122417/domains/support.stackpros.io/public_html
rm -f bootstrap/cache/config.php
rm -rf storage/framework/cache/data/*
rm -f storage/framework/views/*
rm -f storage/framework/sessions/*
rm -f public/js/builds/*
rm -f public/css/builds/*
php artisan freescout:clear-cache
```

### If a deleted/renamed module still throws "ServiceProvider not found"
Old BAK folders inside /Modules/ get scanned. Remove them entirely:
```bash
rm -rf /home/ur122417/domains/support.stackpros.io/public_html/Modules/BAK-*
```
Then manual cache clear (method 4 above).

---

## Essential documentation to read before making changes

Before writing ANY code, read ALL of these:

1. FreeScout Modules Development Guide: https://docs.google.com/document/d/e/2PACX-1vSLbWqFvwlTvr_akQ9hu52-TfRJ9J-0HhpMuuvxHq5ch9qkI6HoGat8Y2mDxyMTasFX2ijSybNFCkBx/pub
2. FreeScout GitHub wiki: https://github.com/freescout-help-desk/freescout/wiki
3. FreeScout releases/changelog: https://github.com/freescout-help-desk/freescout/releases
4. FreeScout community modules: https://github.com/freescout-help-desk/freescout/wiki/Community-Modules
5. FreeScout modules list: https://freescout.net/modules/
6. Microsoft Teams JS SDK: https://learn.microsoft.com/en-us/microsoftteams/platform/tabs/how-to/using-teams-client-sdk
7. Microsoft Teams SSO: https://learn.microsoft.com/en-us/microsoftteams/platform/tabs/how-to/authentication/tab-sso-overview
8. Azure AD token validation: https://learn.microsoft.com/en-us/azure/active-directory/develop/access-tokens
9. Teams app store submission: https://learn.microsoft.com/en-us/microsoftteams/platform/concepts/deploy-and-publish/appsource/publish
10. Teams app manifest: https://learn.microsoft.com/en-us/microsoftteams/platform/resources/schema/manifest-schema

Also read the working CfsAssist and ApiWebhooks modules on the FreeScout server for proven hook and settings patterns:
- ssh -p 26 ur122417@stackpros.io 'cat /home/ur122417/domains/support.stackpros.io/public_html/Modules/CfsAssist/...'
- A copy of CfsAssist is also at /var/www/modules-dev/CfsAssist/ on the VPS

---

## Module auto-update support

FreeScout checks for updates using two `module.json` fields (added FreeScout 1.8.174). Both now point at the **managedfreescout** GitHub org:

```json
"latestVersionUrl":    "https://raw.githubusercontent.com/managedfreescout/msteamssso/main/version.txt",
"latestVersionZipUrl": "https://github.com/managedfreescout/msteamssso/releases/latest/download/msteamssso.zip"
```

- `version.txt` — plain text, just the version number (e.g. `1.1.7`), committed at the repo root. FreeScout compares this to the installed version and shows an update button if newer.
- `msteamssso.zip` — release asset uploaded to each GitHub Release. Must have `MSTeamsSso/` folder at root so FreeScout extracts correctly into `/Modules/MSTeamsSso/`.

**Status:** `module.json` fields updated. Endpoints become active once the GitHub repo and first Release are published (see *Releasing a new version* below).

---

## File structure

```
MSTeamsSso/
├── module.json                          # Manifest (incl. auto-update URLs)
├── composer.json                        # PHP dependencies
├── start.php                            # Bootstrap (empty)
├── README.md / README-install.md
├── Config/config.php                    # License server, product ID (NO tenant/client defaults from v1.1.3)
├── Http/
│   ├── routes.php
│   └── Controllers/
│       ├── MSTeamsSsoController.php     # Settings page + license mgmt
│       └── TeamsSsoController.php       # SSO login flow
├── Models/MSTeamsSsoLicense.php         # License model (modules_licenses table)
├── Providers/MSTeamsSsoServiceProvider.php
├── Resources/views/
│   ├── teams-entry.blade.php
│   ├── teams-fallback.blade.php
│   └── settings/
│       ├── msteamssso.blade.php         # Settings form (native FreeScout save pattern)
│       └── partials/license.blade.php
├── Services/LicenseService.php
└── Public/
    ├── js/msteamssso.js
    └── img/msteamssso-icon.png
```

---

## How it works

### SSO Flow
1. Teams tab loads `/teams-entry` (TeamsSsoController@entry)
2. teams-entry.blade.php loads Microsoft Teams JS SDK v1.8.0
3. SDK calls getAuthToken() — Teams provides a JWT
4. Token POSTed to `/teams-sso-login` (TeamsSsoController@login)
5. validateToken() fetches Azure AD JWKS, verifies RSA-SHA256 signature
6. Email claim extracted (preferred_username / upn / email)
7. FreeScout User looked up by email — if found, Auth::login(); if NOT found, 403 (no auto-create as of v1.1.0)
8. Allowed-domains whitelist checked (if configured) — domain not in list = denied
9. Redirect to FreeScout home

### Settings (tenant_id, client_id, allowed_domains)
Stored in DB via FreeScout's native settings handler (see CRITICAL PATTERN at top). Read via \Option::get('msteamssso.KEY'). Editable in Settings → MSTeams SSO — NO SSH/.env editing required (this was fixed in v1.1.3–v1.1.7).

### License System
Shared modules_licenses table, module_alias = 'msteamssso', validated against stackpros.io DLM. Features gated behind $isLicensed in ServiceProvider. Weekly revalidation via scheduler (v1.1.0).

### CSP / Teams iframe embedding
- `command.after_app_update` hook re-adds CSP frame-ancestors to .htaccess after FreeScout updates
- Also registers `app.csp_frame_ancestors` filter for FreeScout 1.8.219+ native CSP
- Confirmed working on mijn.host LiteSpeed; Android + Windows 11 Teams desktop both load FreeScout

### Current production .htaccess CSP block
```apache
<IfModule mod_headers.c>
    Header always set Content-Security-Policy "frame-ancestors 'self' https://teams.microsoft.com https://*.teams.microsoft.com https://*.skype.com https://*.whyatwork.nl;"
</IfModule>
```

---

## Azure AD & Teams App Setup

Already in place for StackPros:
- Azure app registered (SSO permissions)
- Teams app manifest with contentUrl → https://support.stackpros.io/teams-entry
- App in StackPros Teams org; FreeScout visible as a Teams tab
- Confirmed: Android mobile ✅, Windows 11 desktop ✅

For CUSTOMERS:
- Each needs own Azure AD app registration
- Teams admin installs from app store
- Customer enters Tenant ID + Client ID in Settings → MSTeams SSO (DB-stored, no .env editing)
- Customer adds CSP header to their server config / .htaccess

Teams app package = 3 files zipped (manifest.json, color.png 192x192, outline.png 32x32). StackPros dev has the working WhyAtWork + FreeScout manifests as templates.

---

## Known issues / remaining work

### Still to fix
1. **Links open in OS browser (Chrome) instead of staying in Teams iframe.** The JS intercept (msteamssso.js via javascripts filter) is present but not yet working. NEEDS A SEPARATE FOCUSED SESSION. v1.1.1 and v1.1.2 attempts at this BROKE the whole module (see history) — be very careful, change one thing, test, package. Use window.self !== window.top to detect iframe; intercept target="_blank" + window.open() override; only when Teams SDK present.
2. **Auto-update endpoints not live** — host version.txt + latest.zip (GitHub, pending dev confirmation of StackPros org).
3. **Consumer key/secret still in Config/config.php** — move server-side before public distribution.
4. **README-install.md** — needs proper customer step-by-step (install module → enter Azure IDs in Settings → configure CSP → install Teams app).

### Lessons learned (do not repeat)
- FreeScout auto-deactivates modules on reinstall (1.8.221+) — always re-activate in Manage → Modules after install.
- Old BAK-* folders in /Modules/ are scanned and cause "ServiceProvider not found" — delete them.
- Settings cache owned by wrong user blocks deactivation — manual cache clear as the hosting user works.
- DO NOT build custom settings save — use FreeScout's native handler (CRITICAL PATTERN at top).
- \Option::get() returns null (not the default) when key absent — always cast/coalesce ?? '' before string ops.
- moduleConfig() / moduleConfigSave() helpers do NOT exist in this FreeScout — use \Option::get/set.

---

## Environment / deployment

- FreeScout: support.stackpros.io (mijn.host shared hosting, LiteSpeed, PHP 8.3.30, FreeScout 1.8.223)
- Module on FreeScout server: /home/ur122417/domains/support.stackpros.io/public_html/Modules/MSTeamsSso/
- Dev working copy on VPS: /var/www/modules-dev/MSTeamsSso/
- VPS IP: 62.129.138.112
- SSH FreeScout: ssh -p 26 ur122417@stackpros.io
- SSH VPS: ssh root@62.129.138.112
- DB (FreeScout): ur122417_free833

### Releasing a new version

Use the release script — it bumps `module.json`, updates `version.txt`, and creates the zip in one command:

```bash
/var/www/modules-dev/release-module.sh MSTeamsSso 1.1.8
```

**Then publish to GitHub:**
1. On VPS:
   ```bash
   cd /var/www/modules-dev/MSTeamsSso
   git add module.json version.txt
   git commit -m "Release v1.1.8"
   git push origin main
   ```
2. Download zip: `scp root@62.129.138.112:/tmp/msteamssso.zip ~/Downloads/msteamssso.zip`
3. On GitHub → `managedfreescout/msteamssso` → Releases → Draft a new release:
   - Tag: `v1.1.8`  ·  Title: `v1.1.8`
   - Upload `msteamssso.zip` as a release asset — **name must be exactly `msteamssso.zip`**
   - Publish release

FreeScout auto-update picks up the new version on next check (compares `version.txt` to installed version).

#### One-time GitHub repo setup (run once when org is ready)
```bash
cd /var/www/modules-dev/MSTeamsSso
git init
git remote add origin git@github.com:managedfreescout/msteamssso.git
git add .
git commit -m "Initial commit — v1.1.7"
git branch -M main
git push -u origin main
# Then on GitHub: create Release v1.1.7 and upload /tmp/msteamssso.zip as msteamssso.zip
```

### Installing on FreeScout server (the reliable sequence)
1. scp root@62.129.138.112:/tmp/MSTeamsSso_v1.x.x.zip ~/Downloads/  (from laptop)
2. DirectAdmin File Manager → DELETE existing /Modules/MSTeamsSso/ folder COMPLETELY
3. Upload zip, extract — files must land in /Modules/MSTeamsSso/ (no nested folder)
4. ssh -p 26 ur122417@stackpros.io
5. cd /home/ur122417/domains/support.stackpros.io/public_html
6. Manual cache clear (method 4 above)
7. Manage → Modules → ACTIVATE MSTeamsSso (it deactivates on reinstall)
8. Hard refresh browser (Ctrl+Shift+R)

---

## Changelog

| Version | Date | What changed |
|---|---|---|
| 1.0.0 | Jun 2026 | Initial release — SSO, license, CSP via .htaccess |
| 1.1.0 | Jun 2026 | Security pass: no auto-create users, allowed-domains whitelist, config key fix, removed hardcoded tenant/client fallbacks, weekly license revalidation, auto-update URLs, app.csp_frame_ancestors filter, renamed SAMPLE_MODULE → MSTEAMSSSO_MODULE |
| 1.1.7 | Jun 2026 | Settings save reworked to use FreeScout's NATIVE handler (CfsAssist pattern) after custom-route attempts (1.1.1–1.1.6) failed. Tenant ID + Client ID now editable in UI, DB-stored, no .env/SSH needed. Generic placeholder text. amber (not red) setup-required banner. |

(Intermediate 1.1.1–1.1.6 were iterations toward the working settings save and are superseded by 1.1.7.)

---

## Next version plans

In priority order, each as a SEPARATE focused session (one change, test, package):
1. Fix links opening outside Teams (Known Issue #1) — careful JS work
2. Stand up auto-update endpoints (GitHub, StackPros org)
3. Move consumer key/secret out of Config/config.php
4. Write proper README-install.md for customers
5. Final security review before Teams app store submission

---

## Session log

| Date | What was done | What's next |
|---|---|---|
| Jun 2026 | README created; module fully analysed | Fix v1.1 issues |
| Jun 2026 | v1.1.0 security pass (11 fixes); CSP manually re-added; confirmed working Android + Win11 desktop | Test, fix links |
| 6 Jun 2026 | Long debugging session. v1.1.1–1.1.6 attempts at custom settings-save all failed (Whoops / no save / broke top menu). Root cause: must use FreeScout NATIVE settings handler, not a custom route. v1.1.7 mirrors CfsAssist pattern — settings now save correctly, Tenant/Client ID editable in UI. Also learned: BAK folders break module loading; modules auto-deactivate on reinstall; manual cache clear (method 4) is the reliable recovery. | Links-in-Teams fix (separate session); auto-update endpoints; README-install.md |
| 7 Jun 2026 | Auto-update via GitHub: updated module.json latestVersionUrl/latestVersionZipUrl to managedfreescout org. Created version.txt (1.1.7). Wrote /var/www/modules-dev/release-module.sh (bumps version, updates version.txt, packages alias.zip). Documented full GitHub release workflow in README. | Create managedfreescout/msteamssso repo; run git init + first push; publish v1.1.7 Release |
