@php
    $tenantId = $settings['msteamssso.tenant_id'] ?? '';
    $clientId = $settings['msteamssso.client_id'] ?? '';
    $missingConfig = empty($tenantId) || empty($clientId);
@endphp

<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-info">
            <strong>{{ __('MSTeams SSO') }}</strong> - {{ __('Microsoft Teams Single Sign-On') }}
        </div>
    </div>
</div>

@if($missingConfig)
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-warning">
            <strong>{{ __('Setup required') }}:</strong>
            {{ __('Please enter your Azure Tenant ID and Client ID below and save to enable SSO.') }}
        </div>
    </div>
</div>
@endif

{{-- License section --}}
@include('msteamssso::settings.partials.license')

{{-- Settings section --}}
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">{{ __('Settings') }}</div>
            <div class="panel-body">
                <form class="form-horizontal margin-top margin-bottom" method="POST" action="">
                    {{ csrf_field() }}
                    <input type="hidden" name="settings[dummy]" value="1" />

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __('Tenant ID') }}</label>
                        <div class="col-sm-6">
                            <input type="text"
                                   class="form-control input-sized-lg"
                                   name="settings[msteamssso.tenant_id]"
                                   value="{{ $settings['msteamssso.tenant_id'] ?? '' }}"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <p class="form-help">{{ __('Your Azure AD Directory (Tenant) ID — found in Azure Portal → Azure Active Directory → Overview.') }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __('Client ID') }}</label>
                        <div class="col-sm-6">
                            <input type="text"
                                   class="form-control input-sized-lg"
                                   name="settings[msteamssso.client_id]"
                                   value="{{ $settings['msteamssso.client_id'] ?? '' }}"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <p class="form-help">{{ __('Your Azure AD app registration Application (Client) ID — found in Azure Portal → App registrations.') }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">{{ __('Allowed Domains') }}</label>
                        <div class="col-sm-6">
                            <input type="text"
                                   class="form-control input-sized-lg"
                                   name="settings[msteamssso.allowed_domains]"
                                   value="{{ $settings['msteamssso.allowed_domains'] ?? '' }}"
                                   placeholder="e.g. yourdomain.com,yourcompany.nl">
                            <p class="form-help">{{ __('Comma-separated list of email domains allowed to log in via Teams SSO. Leave blank to allow all authenticated users in the tenant.') }}</p>
                        </div>
                    </div>

                    <div class="form-group margin-top-0 margin-bottom-0">
                        <div class="col-sm-6 col-sm-offset-2">
                            <button type="submit" class="btn btn-primary" name="action" value="msteamssso_save">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
