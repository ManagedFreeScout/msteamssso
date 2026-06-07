@extends('layouts.app')

@section('content')
<div class="container">
    <div id="status">Initializing Teams SSO...</div>
</div>
<script src="https://statics.teams.microsoft.com/sdk/v1.8.0/js/MicrosoftTeams.min.js"></script>
<script>
    if (typeof microsoftTeams === 'undefined') {
        document.getElementById('status').innerText = 'Microsoft Teams SDK not available. This page should run inside Teams tab.';
    } else {
        microsoftTeams.initialize(() => {
            document.getElementById('status').innerText = 'Requesting SSO token...';
            microsoftTeams.authentication.getAuthToken({
                successCallback: function (token) {
                    fetch('/teams-sso-login', {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: token })
                    }).then(function (r) {
                        if (r.ok) {
                            window.location.href = '/';
                        } else {
                            document.getElementById('status').innerText = 'SSO login failed — opening fallback.';
                            window.location.href = '/teams-fallback';
                        }
                    }).catch(function (err) {
                        document.getElementById('status').innerText = 'SSO request failed: ' + err;
                    });
                },
                failureCallback: function (error) {
                    document.getElementById('status').innerText = 'getAuthToken failed: ' + error;
                }
            });
        });
    }
</script>
@endsection
