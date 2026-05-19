/**
 * ArtitechCore Taxonomy Management JavaScript
 * Handles AJAX operations for custom taxonomies
 * 
 * @package ArtitechCore
 * @version 1.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const data = window.ARTITECHCORE_data || {};
        const strings = data.strings ? data.strings.taxonomy : {};

        // Create Taxonomy Form Submission
        $('#Artitech-Core-taxonomy-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.html();
            
            $btn.prop('disabled', true).html(strings.creating || 'Creating...');
            
            $.ajax({
                url: data.ajaxurl || ajaxurl,
                method: 'POST',
                data: {
                    action: 'ARTITECHCORE_create_taxonomy_ajax',
                    nonce: $form.find('input[name="_wpnonce"]').val(),
                    taxonomy_name: $('#taxonomy_name').val(),
                    taxonomy_singular_label: $('#taxonomy_singular_label').val(),
                    taxonomy_plural_label: $('#taxonomy_plural_label').val(),
                    taxonomy_post_types: $('input[name="taxonomy_post_types[]"]:checked').map(function() { return this.value; }).get(),
                    taxonomy_hierarchical: $('#taxonomy_hierarchical').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || strings.error_generic || 'Error creating taxonomy.');
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert(strings.error_generic || 'Server error.');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Delete Taxonomy
        $(document).on('click', '.Artitech-Core-delete-taxonomy', function() {
            var taxonomy = $(this).data('taxonomy');
            if (!confirm(strings.delete_confirm || 'Are you sure you want to delete this taxonomy?')) return;
            
            $.ajax({
                url: data.ajaxurl || ajaxurl,
                method: 'POST',
                data: {
                    action: 'ARTITECHCORE_delete_taxonomy_ajax',
                    nonce: data.delete_taxonomy_nonce,
                    taxonomy: taxonomy
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || strings.error_generic || 'Error deleting taxonomy.');
                    }
                }
            });
        });
    });

})(jQuery);
