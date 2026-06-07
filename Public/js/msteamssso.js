(function() {
    // Only run inside an iframe (Teams)
    if (window.self === window.top) return;

    function handleLink(url) {
        try {
            const linkUrl = new URL(url, window.location.href);
            const currentHost = window.location.hostname;

            // Same-domain links (FreeScout internal navigation) —
            // just navigate within the iframe directly, no Teams SDK needed
            if (linkUrl.hostname === currentHost) {
                window.location.href = linkUrl.href;
                return;
            }

            // External links — use Teams SDK app.openLink()
            if (typeof microsoftTeams !== 'undefined' && microsoftTeams.app) {
                microsoftTeams.app.openLink(linkUrl.href).catch(() => {
                    // Fallback if openLink fails
                    window.open(linkUrl.href, '_blank');
                });
            } else {
                // SDK not available, open normally
                window.open(linkUrl.href, '_blank');
            }
        } catch(e) {
            // Invalid URL, ignore
        }
    }

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Intercept all target="_blank" link clicks
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[target="_blank"]');
            if (!link || !link.href) return;
            e.preventDefault();
            handleLink(link.href);
        }, true);

        // Intercept window.open() calls
        const originalOpen = window.open;
        window.open = function(url, target, features) {
            if (url && (target === '_blank' || target === undefined || target === null)) {
                handleLink(url);
                return null;
            }
            return originalOpen.apply(this, arguments);
        };
    });
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
