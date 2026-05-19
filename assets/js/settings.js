jQuery(document).ready(function($) {
    const data = artitechcore_data;
    const strings = data.strings.settings;
    const commonStrings = data.strings.common;

    // DB Cleanup
    $('#artitechcore-cleanup-db').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).text(strings.cleaning);
        
        $.post(artitechcore_data.ajaxurl, {
            action: 'artitechcore_manual_db_cleanup',
            nonce: data.maintenance_nonce
        }, function(response) {
            if (response.success) {
                $('#artitechcore-cleanup-status').html('<span style="color: green;">✅ ' + response.data.message + '</span>');
            } else {
                $('#artitechcore-cleanup-status').html('<span style="color: red;">❌ ' + response.data.message + '</span>');
            }
            btn.prop('disabled', false).html(originalText);
        });
    });

    // Test Connection
    $('.artitechcore-test-conn').on('click', function() {
        var btn = $(this);
        var provider = btn.data('provider');
        var statusDiv = $('.artitechcore-test-status');
        var providerName = provider.charAt(0).toUpperCase() + provider.slice(1);

        btn.prop('disabled', true).addClass('updating-message');
        statusDiv.html('<i>' + providerName + ': ' + strings.checking + '</i>');

        $.post(artitechcore_data.ajaxurl, {
            action: 'artitechcore_test_ai_connection',
            provider: provider,
            nonce: data.ajax_nonce
        }, function(response) {
            if (response.success) {
                statusDiv.html('<span style="color: green;">✅ ' + providerName + ': ' + response.data.message + '</span>');
            } else {
                statusDiv.html('<span style="color: red;">❌ ' + providerName + ': ' + response.data.message + '</span>');
            }
            btn.prop('disabled', false).removeClass('updating-message');
        });
    });

    // Re-scan Brand Identity
    $('#artitechcore-auto-detect-brand').on('click', function() {
        var btn = $(this);
        var status = $('#artitechcore-brand-detect-status');
        
        btn.prop('disabled', true).text(strings.scanning);
        status.show().text('...');
        
        $.post(artitechcore_data.ajaxurl, {
            action: 'artitechcore_auto_detect_brand_kit',
            nonce: data.ajax_nonce
        }, function(response) {
            btn.prop('disabled', false).text(strings.rescan_brand);
            if (response.success) {
                status.html('<span style="color: green;">✅ ' + response.data.message + '</span>');
                // Reload page to show new values
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                status.html('<span style="color: red;">❌ ' + response.data.message + '</span>');
            }
        });
    });

    // Re-scan Business Info (Knowledge Base area)
    $('#artitechcore-rescan-btn').on('click', function() {
        var btn = $(this);
        var status = $('#artitechcore-rescan-status');
        
        btn.prop('disabled', true).text(strings.scanning);
        status.show().text('...');
        
        $.post(artitechcore_data.ajaxurl, {
            action: 'artitechcore_rescan_business_info',
            nonce: data.ajax_nonce
        }, function(response) {
            btn.prop('disabled', false).text('🔍 ' + strings.rescan_website);
            if (response.success) {
                status.html('<span style="color: green;">✅ ' + response.data.message + '</span>');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                status.html('<span style="color: red;">❌ ' + response.data.message + '</span>');
            }
        });
    });
});
