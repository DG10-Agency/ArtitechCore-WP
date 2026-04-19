/**
 * AI Generator JS
 * Handles AI suggestion requests and content creation via AJAX
 * 
 * @since 1.0
 */
(function ($) {
    'use strict';

    const ArtitechCore_AIGenerator = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            const self = this;

            // Step Navigation
            $(document).on('click', '.next-step', function() {
                const $current = $(this).closest('.ai-form-step');
                const stepNum = parseInt($current.data('step'));
                
                // Simple validation for step 1
                if (stepNum === 1) {
                    if (!$('#artitechcore_business_type').val() || !$('#artitechcore_business_details').val()) {
                        alert('Please fill in both the Business Niche and Mission fields.');
                        return;
                    }
                }

                self.goToStep(stepNum + 1);
            });

            $(document).on('click', '.prev-step', function() {
                const $current = $(this).closest('.ai-form-step');
                const stepNum = parseInt($current.data('step'));
                self.goToStep(stepNum - 1);
            });

            // Handle initial suggestion request
            $(document).on('submit', '#artitechcore-ai-request-form', function (e) {
                e.preventDefault();
                self.generateSuggestions($(this));
            });

            // Handle select all pages
            $(document).on('change', '#select-all-pages', function () {
                $('.artitechcore-page-checkbox').prop('checked', $(this).prop('checked'));
                self.updateButtonStates();
            });

            // Handle individual page checkboxes
            $(document).on('change', '.artitechcore-page-checkbox', function () {
                self.updateButtonStates();
            });

            // Handle creation of selected content
            $(document).on('submit', '#artitechcore-ai-creation-form', function (e) {
                e.preventDefault();
                self.createContent($(this));
            });
        },

        goToStep: function(num) {
            $('.ai-form-step').removeClass('active');
            $(`.ai-form-step[data-step="${num}"]`).addClass('active');

            // Update indicators
            $('.ai-step').removeClass('active completed');
            $(`.ai-step[data-step="${num}"]`).addClass('active');
            
            // Mark previous steps as completed
            for (let i = 1; i < num; i++) {
                $(`.ai-step[data-step="${i}"]`).addClass('completed');
            }

            // Smooth scroll to top of form
            $('html, body').animate({
                scrollTop: $('.ai-steps-indicator').offset().top - 150
            }, 300);
        },

        generateSuggestions: function ($form) {
            const self = this;
            const $results = $('#artitechcore-ai-results');
            const $overlay = $('#ai-loading-overlay');
            const $status = $('#ai-status-message');
            const $bar = $('.progress-bar-fill');

            const formData = new FormData($form[0]);
            formData.append('action', 'artitechcore_ai_generate_suggestions');

            // Show premium loading state
            $overlay.removeClass('hidden').hide().fadeIn(400);
            
            const messages = [
                'Interpreting business signals...',
                'Analyzing market competition...',
                'Defining neural post type architecture...',
                'Mapping SEO semantic relationships...',
                'Structuring custom field schemas...',
                'Finalizing ecosystem blueprint...'
            ];
            
            let msgIdx = 0;
            let progress = 0;
            const messageInterval = setInterval(() => {
                msgIdx = (msgIdx + 1) % messages.length;
                $status.fadeOut(300, function() {
                    $(this).text(messages[msgIdx]).fadeIn(300);
                });
                
                progress += Math.random() * 15;
                if (progress > 95) progress = 95;
                $bar.css('width', progress + '%');
            }, 3500);

            $.ajax({
                url: artitechcore_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000, 
                success: function (response) {
                    clearInterval(messageInterval);
                    $bar.css('width', '100%');
                    
                    if (response.success) {
                        $overlay.fadeOut(400, function() {
                            $results.hide().html(response.data.html).fadeIn(600);
                            $('html, body').animate({
                                scrollTop: $results.offset().top - 50
                            }, 500);
                        });
                    } else {
                        $overlay.fadeOut(400);
                        $results.html(`
                            <div class="notice notice-error dg10-notice-error">
                                <p>${response.data.message || 'An error occurred during generation.'}</p>
                            </div>
                        `);
                    }
                },
                error: function (jqXHR, textStatus) {
                    clearInterval(messageInterval);
                    $overlay.fadeOut(400);
                    let msg = 'Network error occurred. Please check your connection.';
                    if (textStatus === 'timeout') msg = 'Request timed out. The AI took too long to respond.';
                    
                    $results.html(`
                        <div class="notice notice-error dg10-notice-error">
                            <p>${msg}</p>
                        </div>
                    `);
                }
            });
        },

        createContent: function ($form) {
            const self = this;
            const $overlay = $('#ai-loading-overlay');
            const $status = $('#ai-status-message');
            const $bar = $('.progress-bar-fill');
            const $results = $('#artitechcore-ai-results');

            const formData = new FormData($form[0]);
            formData.append('action', 'artitechcore_ai_create_content');

            // Show premium loading state
            $overlay.removeClass('hidden').hide().fadeIn(400);
            $bar.css('width', '0%');
            
            const steps = [
                'Initializing content deployment...',
                'Building page hierarchy...',
                'Architecting custom post types...',
                'Registering meta-field schemas...',
                'Creating taxonomic bridges...',
                'Deploying AI Featured Assets...',
                'Finalizing structural integrity...'
            ];

            let currentStep = 0;
            const stepInterval = setInterval(function () {
                currentStep++;
                if (currentStep < steps.length) {
                    $status.fadeOut(300, function() {
                        $(this).text(steps[currentStep]).fadeIn(300);
                    });
                    $bar.css('width', Math.min((currentStep + 1) * 14, 95) + '%');
                }
            }, 5000);

            $.ajax({
                url: artitechcore_ai_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 600000, 
                success: function (response) {
                    clearInterval(stepInterval);
                    $bar.css('width', '100%');
                    
                    if (response.success) {
                        $overlay.fadeOut(400, function() {
                            $results.html(`
                                <div class="artitechcore-success-state" style="text-align: center; padding: 60px; background: #fff; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                                    <div style="font-size: 64px; margin-bottom: 24px; animation: float 2s ease-in-out infinite;">✨</div>
                                    <h2 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #B47CFD, #FF7FC2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">Architecture Deployed!</h2>
                                    <p style="font-size: 1.25rem; color: #6B7280; margin-bottom: 3rem;">${response.data.message || 'Your custom AI ecosystem is live and ready for production.'}</p>
                                    <div class="dg10-action-links" style="display: flex; gap: 20px; justify-content: center;">
                                        <a href="edit.php?post_type=page" class="dg10-btn dg10-btn-primary">Explore Pages</a>
                                        <a href="admin.php?page=artitechcore_cpt_manager" class="dg10-btn dg10-btn-outline">Architectural Oversight</a>
                                    </div>
                                </div>
                            `).fadeIn(600);
                            
                            $('html, body').animate({
                                scrollTop: $results.offset().top - 50
                            }, 500);
                        });
                    } else {
                        $overlay.fadeOut(400);
                        $results.append(`
                            <div class="notice notice-error dg10-notice-error">
                                <p><strong>Deployment Error:</strong> ${response.data.message || 'An error occurred.'}</p>
                            </div>
                        `);
                    }
                },
                error: function (jqXHR, textStatus) {
                    clearInterval(stepInterval);
                    $overlay.fadeOut(400);
                    $results.append(`
                        <div class="notice notice-error dg10-notice-error">
                            <p>Network failure during deployment. Please verify created pages before retrying.</p>
                        </div>
                    `);
                }
            });
        },

        updateButtonStates: function () {
            const hasSelection = $('.artitechcore-page-checkbox:checked, .artitechcore-cpt-checkbox:checked').length > 0;
            $('#artitechcore-ai-creation-form button[type="submit"]').prop('disabled', !hasSelection);
        }
    };

    $(document).ready(function () {
        ArtitechCore_AIGenerator.init();
    });

})(jQuery);
