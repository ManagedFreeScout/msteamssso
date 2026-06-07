@extends('layouts.app')

@section('content')
<div class="container">
    <div id="status">Initializing Teams SSO...</div>
</div>
<script src="https://res.cdn.office.net/teams-js/2.19.0/js/MicrosoftTeams.min.js"></script>
<script>
    if (typeof microsoftTeams === 'undefined') {
        document.getElementById('status').innerText = 'Microsoft Teams SDK not available. This page should run inside a Teams tab.';
    } else {
        microsoftTeams.app.initialize().then(function () {
            document.getElementById('status').innerText = 'Requesting SSO token...';
            return microsoftTeams.authentication.getAuthToken();
        }).then(function (token) {
            return fetch('/teams-sso-login', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token })
            });
        }).then(function (r) {
            if (r.ok) {
                window.location.href = '/';
            } else {
                document.getElementById('status').innerText = 'SSO login failed — opening fallback.';
                window.location.href = '/teams-fallback';
            }
        }).catch(function (error) {
            document.getElementById('status').innerText = 'SSO failed: ' + (error.message || error);
        });
    }
</script>
@endsection
