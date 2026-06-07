// Fix #8: Teams link navigation — intercept target="_blank" so links open inside Teams
// rather than spawning a new browser window. Loads Teams SDK dynamically when in iframe.
(function () {
    if (window === window.top) {
        return; // not in an iframe, nothing to do
    }

    function applyTeamsLinkFix() {
        // Intercept anchor clicks with target="_blank"
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[target="_blank"]');
            if (!link) return;
            e.preventDefault();
            if (window.microsoftTeams && microsoftTeams.app && microsoftTeams.app.openLink) {
                microsoftTeams.app.openLink(link.href);
            } else if (window.microsoftTeams && microsoftTeams.executeDeepLink) {
                microsoftTeams.executeDeepLink(link.href);
            } else {
                window.location.href = link.href;
            }
        });

        // Intercept window.open()
        var originalOpen = window.open;
        window.open = function (url, target, features) {
            if (target === '_blank' || target === undefined) {
                if (window.microsoftTeams && microsoftTeams.app && microsoftTeams.app.openLink) {
                    microsoftTeams.app.openLink(url);
                } else if (window.microsoftTeams && microsoftTeams.executeDeepLink) {
                    microsoftTeams.executeDeepLink(url);
                }
                return null;
            }
            return originalOpen.apply(this, arguments);
        };
    }

    function initTeamsSdk() {
        if (typeof microsoftTeams === 'undefined') return;

        // Support both SDK v1 (initialize callback) and v2 (app.initialize promise)
        if (microsoftTeams.app && typeof microsoftTeams.app.initialize === 'function') {
            microsoftTeams.app.initialize().then(applyTeamsLinkFix).catch(function () {
                // Not in a real Teams context — ignore
            });
        } else if (typeof microsoftTeams.initialize === 'function') {
            microsoftTeams.initialize(applyTeamsLinkFix);
        }
    }

    if (typeof microsoftTeams !== 'undefined') {
        initTeamsSdk();
    } else {
        // Dynamically load Teams SDK, then initialise
        var script = document.createElement('script');
        script.src = 'https://statics.teams.microsoft.com/sdk/v1.8.0/js/MicrosoftTeams.min.js';
        script.onload = initTeamsSdk;
        document.head.appendChild(script);
    }
})();

// License management — used by settings page only
function manageMSTeamsLicense(action) {
    var btn = $('#btn-' + action + '-license');
    var originalText = btn.text();
    btn.text('Processing...').prop('disabled', true);

    var licenseKey = $('#msteamssso-license-key').val();
    var url = $('#msteamssso-license-form').data('action-url');
    var csrf = $('#msteamssso-license-form').data('csrf');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            action: action,
            license_key: licenseKey,
            _token: csrf
        },
        success: function (response) {
            if (response.status == 'success') {
                window.location.reload();
            } else {
                alert(response.message);
                btn.text(originalText).prop('disabled', false);
            }
        },
        error: function () {
            alert('An error occurred. Please try again.');
            btn.text(originalText).prop('disabled', false);
        }
    });
}
