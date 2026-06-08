<?php

namespace Modules\MSTeamsSso\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MSTeamsSso\Services\LicenseService;

// Guard against re-definition if FreeScout loads the module more than once per process
defined('MSTEAMSSSO_MODULE') || define('MSTEAMSSSO_MODULE', 'msteamssso');

class MSTeamsSsoServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->hooks();
    }

    public function register()
    {
        $moduleVendorPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($moduleVendorPath)) {
            require_once $moduleVendorPath;
        }

        $this->app->singleton(LicenseService::class, function ($app) {
            return new LicenseService();
        });
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('msteamssso.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'msteamssso'
        );

        // Add MSTeams SSO section to the Settings left panel
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['msteamssso'] = [
                'title' => __('MSTeams SSO'),
                'icon' => 'lock',
                'order' => 300,
                'description' => __('Manage Microsoft Teams SSO settings.')
            ];
            return $sections;
        }, 15);

        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section != 'msteamssso') {
                return $settings;
            }
            $settings['msteamssso.tenant_id']      = \Option::get('msteamssso.tenant_id');
            $settings['msteamssso.client_id']      = \Option::get('msteamssso.client_id');
            $settings['msteamssso.allowed_domains'] = \Option::get('msteamssso.allowed_domains');
            $settings['license_status']             = app(LicenseService::class)->getLicenseStatus();
            return $settings;
        }, 20, 2);

        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section == 'msteamssso') {
                $licenseService = app(LicenseService::class);
                $params['license_status'] = $licenseService->getLicenseStatus();
            }
            return $params;
        }, 20, 2);

        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != 'msteamssso') {
                return $view;
            }
            return 'msteamssso::settings.msteamssso';
        }, 20, 2);

        \Eventy::addFilter('modules.show_license', function ($show, $module) {
            if (isset($module['alias']) && $module['alias'] === 'msteamssso') {
                return true;
            }
            return $show;
        }, 20, 2);

        \Eventy::addFilter('modules.license_info', function ($license_info, $module_alias) {
            if ($module_alias === 'msteamssso') {
                $licenseService = app(LicenseService::class);
                $status = $licenseService->getLicenseStatus();
                return [
                    'license'      => $status['license_key'] ?? '',
                    'activated'    => $status['valid'] ?? false,
                    'status'       => $status['status'] ?? 'inactive',
                    'expires_at'   => $status['expires_at'] ?? null,
                    'license_type' => $status['license_type'] ?? null,
                ];
            }
            return $license_info;
        }, 20, 2);

        \Eventy::addFilter('module.requires_license', function ($requires, $module) {
            if (isset($module['alias']) && $module['alias'] === 'msteamssso') {
                return true;
            }
            return $requires;
        }, 20, 2);
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/msteamssso');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/msteamssso';
        }, \Config::get('view.paths')), [$sourcePath]), 'msteamssso');
    }

    public function hooks()
    {
        // Re-add CSP to .htaccess after every FreeScout auto-update — no license gate
        \Eventy::addAction('command.after_app_update', function () {
            $this->updateHtaccessFile();
        });

        // FreeScout 1.8.219+ native CSP frame-ancestors filter (belt-and-suspenders)
        \Eventy::addFilter('app.csp_frame_ancestors', function ($ancestors) {
            $extra = [
                'https://teams.microsoft.com',
                'https://*.teams.microsoft.com',
                'https://*.skype.com',
                'https://*.whyatwork.nl',
                'https://*.cloud.microsoft',
            ];
            return array_unique(array_merge((array) $ancestors, $extra));
        });

        // Inject msteamssso.js via the javascripts filter — the correct FreeScout module
        // pattern. Fires after jQuery is loaded (layout line 284). FreeScout wraps
        // Minify::javascript() in try/catch, so a missing symlink won't break the page.
        \Eventy::addFilter('javascripts', function ($scripts) {
            $scripts[] = '/modules/msteamssso/js/msteamssso.js';
            return $scripts;
        }, 20, 1);

        // Weekly license re-validation via FreeScout scheduler
        \Eventy::addAction('schedule', function ($schedule) {
            $schedule->call(function () {
                $licenseService = app(LicenseService::class);
                $status = $licenseService->getLicenseStatus();
                if (!empty($status['license_key']) && $status['status'] !== 'no_table') {
                    $licenseService->validateLicense($status['license_key']);
                }
            })->weekly();
        }, 20, 1);
    }

    public function provides()
    {
        return [];
    }

    protected function updateHtaccessFile()
    {
        $htaccessPath = base_path('.htaccess');

        if (!file_exists($htaccessPath)) {
            return;
        }

        $currentContent = file_get_contents($htaccessPath);
        $cspLine = 'Header always set Content-Security-Policy "frame-ancestors \'self\' https://teams.microsoft.com https://*.teams.microsoft.com https://*.skype.com https://*.whyatwork.nl https://*.cloud.microsoft;"';

        if (strpos($currentContent, $cspLine) !== false) {
            return;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = base_path(".htaccess.{$timestamp}");
        copy($htaccessPath, $backupPath);

        $newContent = "\n\n<IfModule mod_headers.c>\n    {$cspLine}\n</IfModule>\n";
        file_put_contents($htaccessPath, $newContent, FILE_APPEND);

        \Log::info("MSTeamsSso: Updated .htaccess with CSP headers. Backup at {$backupPath}");
    }
}
