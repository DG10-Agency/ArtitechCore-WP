<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// ============================================
// ARTITECHCORE WEBSITE BUILDER - CONSTANTS
// ============================================
// Generation Limits
const ARTITECHCORE_MAX_PAGES_PER_BATCH = 50;         // Absolute max pages per generation
const ARTITECHCORE_MAX_PAGES_PER_TYPE = 20;          // Max per page type in one go
const ARTITECHCORE_API_TIMEOUT = 120;                // Seconds
const ARTITECHCORE_QUEUE_EXPIRY = 48 * HOUR_IN_SECONDS;

// AI Provider Costs (in USD per unit)
const ARTITECHCORE_COST = [
    'openai' => [
        'content_per_page' => 0.002,    // GPT-4o
        'image' => 0.040,               // DALL-E 3
    ],
    'gemini' => [
        'content_per_page' => 0.001,    // Gemini Flash
        'image' => 0,                   // No image generation
    ],
    'deepseek' => [
        'content_per_page' => 0.0007,   // DeepSeek
        'image' => 0,                   // No image generation
    ]
];

// AI Model Parameters
const ARTITECHCORE_AI_TEMPERATURE = 0.7;
const ARTITECHCORE_AI_MAX_TOKENS = 4000;
const ARTITECHCORE_IMAGE_MODEL = 'dall-e-3';
const ARTITECHCORE_IMAGE_SIZE = '1024x1024';

// ============================================

/**
 * AI Website Builder
 *
 * Generates complete, brand-consistent websites with AI.
 * Includes wizard UI, content generation, and image creation.
 */

// Define Website Blueprints (Archetypes)
function artitechcore_get_website_blueprints() {
    return [
        'dental' => [
            'name' => 'Dental Clinic',
            'icon' => '🦷',
            'description' => 'Perfect for dental practices, orthodontists, and oral health clinics.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'about', 'title' => 'About Us'],
                ['type' => 'services', 'title' => 'Dental Services', 'count' => 5],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'testimonials', 'title' => 'Patient Testimonials'],
                ['type' => 'faq', 'title' => 'FAQs'],
                ['type' => 'blog', 'title' => 'Blog'],
            ]
        ],
        'law_firm' => [
            'name' => 'Law Firm',
            'icon' => '⚖️',
            'description' => 'Ideal for law firms, attorneys, and legal practices.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'practice_areas', 'title' => 'Practice Areas', 'count' => 3],
                ['type' => 'attorneys', 'title' => 'Our Attorneys', 'count' => 3],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'resources', 'title' => 'Legal Resources'],
                ['type' => 'blog', 'title' => 'Legal Insights'],
            ]
        ],
        'restaurant' => [
            'name' => 'Restaurant',
            'icon' => '🍽️',
            'description' => 'For restaurants, cafes, bistros, and food service businesses.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'menu', 'title' => 'Menu'],
                ['type' => 'about', 'title' => 'About Us'],
                ['type' => 'reservations', 'title' => 'Reservations'],
                ['type' => 'gallery', 'title' => 'Gallery'],
                ['type' => 'contact', 'title' => 'Contact & Hours'],
            ]
        ],
        'ecommerce' => [
            'name' => 'E-commerce Store',
            'icon' => '🛒',
            'description' => 'For online stores, retail businesses, and product vendors.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'shop', 'title' => 'Shop'],
                ['type' => 'product_categories', 'title' => 'Product Categories', 'count' => 3],
                ['type' => 'about', 'title' => 'About Us'],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'faq', 'title' => 'FAQ & Shipping'],
                ['type' => 'blog', 'title' => 'Blog'],
            ]
        ],
        'service_business' => [
            'name' => 'Service Business',
            'icon' => '💼',
            'description' => 'For agencies, consultants, freelancers, and service providers.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'services', 'title' => 'Services', 'count' => 3],
                ['type' => 'about', 'title' => 'About'],
                ['type' => 'case_studies', 'title' => 'Case Studies', 'count' => 2],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'blog', 'title' => 'Blog'],
            ]
        ],
        'portfolio' => [
            'name' => 'Portfolio / Personal',
            'icon' => '🎨',
            'description' => 'For creatives, designers, photographers, and personal branding.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'portfolio', 'title' => 'Portfolio', 'count' => 6],
                ['type' => 'about', 'title' => 'About Me'],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'resume', 'title' => 'Resume / CV'],
            ]
        ],
        'corporate' => [
            'name' => 'Corporate',
            'icon' => '🏢',
            'description' => 'For corporations, enterprises, and B2B organizations.',
            'pages' => [
                ['type' => 'home', 'title' => 'Home'],
                ['type' => 'about', 'title' => 'About Us'],
                ['type' => 'solutions', 'title' => 'Solutions', 'count' => 3],
                ['type' => 'investors', 'title' => 'Investors'],
                ['type' => 'contact', 'title' => 'Contact'],
                ['type' => 'news', 'title' => 'News & Press'],
            ]
        ],
        'custom' => [
            'name' => 'Custom Build',
            'icon' => '🔧',
            'description' => 'Build from scratch - you define every page manually.',
            'pages' => [], // User will define
        ]
    ];
}

/**
 * Main Website Builder Tab
 */
function artitechcore_website_builder_tab() {
    // Check if brand kit is configured
    $brand_kit = artitechcore_get_brand_kit();
    $has_brand = !empty($brand_kit['brand_name']) || !empty($brand_kit['description']);

    // Get selected blueprint from session or default
    $selected_blueprint = isset($_GET['blueprint']) ? sanitize_key($_GET['blueprint']) : '';

    // Get custom pages if blueprint is custom
    $custom_pages = [];
    if ($selected_blueprint === 'custom') {
        $custom_pages = isset($_GET['custom_pages']) ? explode("\n", sanitize_textarea_field($_GET['custom_pages'])) : [];
        $custom_pages = array_filter(array_map('trim', $custom_pages));
    }

    ?>
    <div class="wrap artitechcore-website-builder dg10-brand">
        <h1><?php esc_html_e('AI Website Builder', 'artitechcore'); ?></h1>
        <p class="description"><?php esc_html_e('Generate a complete, brand-consistent website in minutes. Select a blueprint, customize your pages, and let AI build your site.', 'artitechcore'); ?></p>

        <?php if (!$has_brand): ?>
        <div class="notice notice-warning" style="margin: 20px 0;">
            <p>
                <strong><?php esc_html_e('Brand Kit Required', 'artitechcore'); ?></strong><br>
                <?php esc_html_e('Please configure your Brand Kit first to ensure generated pages match your brand identity.', 'artitechcore'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=settings')); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e('Go to Brand Kit', 'artitechcore'); ?>
                </a>
            </p>
        </div>
        <?php return; ?>
        <?php endif; ?>

        <div class="artitechcore-builder-container" style="display: flex; gap: 20px; flex-wrap: wrap;">

            <!-- Step 1: Choose Blueprint -->
            <div class="artitechcore-blueprint-selection" style="flex: 1; min-width: 300px;">
                <h2><?php esc_html_e('Step 1: Choose Your Website Blueprint', 'artitechcore'); ?></h2>
                <p><?php esc_html_e('Select the blueprint that best matches your business type:', 'artitechcore'); ?></p>

                <div class="artitechcore-blueprint-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                    <?php
                    $blueprints = artitechcore_get_website_blueprints();
                    foreach ($blueprints as $key => $bp):
                        ?>
                        <div class="artitechcore-blueprint-card" data-blueprint="<?php echo esc_attr($key); ?>" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: border-color 0.2s;">
                            <div style="font-size: 2em; margin-bottom: 10px;"><?php echo esc_html($bp['icon']); ?></div>
                            <h3 style="margin: 0 0 10px 0; font-size: 1.2em;"><?php echo esc_html($bp['name']); ?></h3>
                            <p style="margin: 0; font-size: 0.9em; color: #666;"><?php echo esc_html($bp['description']); ?></p>
                            <ul style="margin-top: 10px; font-size: 0.85em; color: #333; padding-left: 20px;">
                                <?php foreach ($bp['pages'] as $page): ?>
                                    <li><?php echo esc_html($page['title']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="artitechcore-blueprint-selection-status" style="margin-top: 20px;">
                    <button type="button" id="artitechcore-continue-to-customize" class="button button-primary" disabled>
                        <?php esc_html_e('Continue to Customize →', 'artitechcore'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: Customize -->
            <div class="artitechcore-customize-panel" style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; display: none;">
                <h2><?php esc_html_e('Step 2: Customize Your Pages', 'artitechcore'); ?></h2>
                <div id="artitechcore-customize-content">
                    <!-- Dynamic content based on selected blueprint -->
                    <p><?php esc_html_e('Select a blueprint above to continue.', 'artitechcore'); ?></p>
                </div>
                
                <div id="artitechcore-custom-blueprint-extra" style="display: none; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <label><strong><?php esc_html_e('Add Custom Pages (One per line):', 'artitechcore'); ?></strong></label><br>
                    <textarea id="artitechcore-custom-pages-list" rows="5" style="width: 100%; margin-top: 5px;" placeholder="<?php esc_attr_e("Contact Us\nOur Team\nFAQ", 'artitechcore'); ?>"></textarea>
                    <p class="description"><?php esc_html_e('Each line will be created as a new page.', 'artitechcore'); ?></p>
                    <button type="button" id="artitechcore-apply-custom-pages" class="button button-secondary" style="margin-top: 10px;">
                        <?php esc_html_e('Apply Custom Pages', 'artitechcore'); ?>
                    </button>
                </div>
            </div>

        </div>

        <!-- Step 3: Generate -->
        <div class="artitechcore-generate-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; display: none;">
            <h2><?php esc_html_e('Step 3: Generate Your Website', 'artitechcore'); ?></h2>

            <div class="artitechcore-generation-options" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 200px;">
                    <label><strong><?php esc_html_e('Generate Images?', 'artitechcore'); ?></strong></label><br>
                    <label class="dg10-checkbox-label">
                        <input type="checkbox" id="artitechcore-generate-images" checked>
                        <?php esc_html_e('Yes, generate featured images with AI (requires OpenAI API key)', 'artitechcore'); ?>
                    </label>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label><strong><?php esc_html_e('Publish as', 'artitechcore'); ?></strong></label><br>
                    <select id="artitechcore-publish-status" style="max-width: 200px;">
                        <option value="draft"><?php esc_html_e('Drafts (recommended)', 'artitechcore'); ?></option>
                        <option value="publish"><?php esc_html_e('Published immediately', 'artitechcore'); ?></option>
                        <option value="private"><?php esc_html_e('Private (hidden from public)', 'artitechcore'); ?></option>
                    </select>
                </div>
            </div>

            <div class="artitechcore-cost-estimate" style="background: #e8f4fd; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <strong><?php esc_html_e('Estimated Cost:', 'artitechcore'); ?></strong>
                <span id="artitechcore-cost-estimate">Calculating...</span>
            </div>

            <button type="button" id="artitechcore-start-generation" class="button button-primary button-hero">
                <span style="font-size: 1.2em;">🚀</span>
                <?php esc_html_e('Generate My Website', 'artitechcore'); ?>
            </button>

            <div id="artitechcore-generation-progress" style="display: none; margin-top: 20px;">
                <div class="artitechcore-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="background: #ddd; height: 30px; border-radius: 15px; overflow: hidden; position: relative;">
                    <div class="artitechcore-progress-fill" style="background: linear-gradient(135deg, #4A90E2, #6C63FF); height: 100%; width: 0%; transition: width 0.3s;"></div>
                    <div class="artitechcore-progress-text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold;">0%</div>
                </div>
                <p id="artitechcore-progress-status" style="margin-top: 10px; font-style: italic;">Initializing...</p>
                <div id="artitechcore-progress-control" style="margin-top: 10px; display: none;">
                    <button type="button" id="artitechcore-cancel-generation" class="button button-secondary"><?php esc_html_e('Cancel Generation', 'artitechcore'); ?></button>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div id="artitechcore-generation-results" style="margin-top: 30px; display: none;"></div>

    </div>

    <script>
    jQuery(document).ready(function($) {
        const blueprints = <?php echo json_encode(artitechcore_get_website_blueprints()); ?>;
        let selectedBlueprint = null;
        let customPageCounts = {};

        // Helper to escape HTML for XSS prevention (P2-4)
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Blueprint card click handler
        $('.artitechcore-blueprint-card').on('click', function() {
            $('.artitechcore-blueprint-card').css('border-color', '#ddd');
            $(this).css('border-color', '#4A90E2');
            selectedBlueprint = $(this).data('blueprint');
            $('#artitechcore-continue-to-customize').prop('disabled', false).data('blueprint', selectedBlueprint);
        });

        // Continue button
        $('#artitechcore-continue-to-customize').on('click', function() {
            const blueprint = $(this).data('blueprint');
            showCustomizePanel(blueprint);
            
            if (blueprint === 'custom') {
                $('#artitechcore-custom-blueprint-extra').show();
            } else {
                $('#artitechcore-custom-blueprint-extra').hide();
            }
        });

        // Apply custom pages button
        $('#artitechcore-apply-custom-pages').on('click', function() {
            const lines = $('#artitechcore-custom-pages-list').val().split('\n');
            const customPages = [];
            
            lines.forEach(line => {
                const title = line.trim();
                if (title) {
                    customPages.push({
                        type: 'custom',
                        title: title,
                        count: 1
                    });
                }
            });
            
            if (customPages.length > 0) {
                blueprints['custom'].pages = customPages;
                showCustomizePanel('custom');
            } else {
                alert('<?php echo esc_js(__('Please enter at least one page title.', 'artitechcore')); ?>');
            }
        });

        function showCustomizePanel(blueprint) {
            const bp = blueprints[blueprint];
            const $content = $('#artitechcore-customize-content');
            const $customizePanel = $('.artitechcore-customize-panel');
            const $generateSection = $('.artitechcore-generate-section');

            $customizePanel.show();
            $generateSection.show();

            let html = '<form id="artitechcore-page-customization" class="artitechcore-customization-form">';
            html += '<p><strong>Selected Blueprint:</strong> ' + bp.name + '</p>';
            html += '<p><strong>Pages to be created:</strong></p>';
            html += '<div class="artitechcore-page-list" style="max-height: 400px; overflow-y: auto;">';

            bp.pages.forEach((page, index) => {
                html += '<div class="artitechcore-page-item" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">';
                html += '<label><strong>' + page.title + '</strong></label>';

                if (page.count) {
                    html += '<div style="margin-top: 5px;">';
                    html += '<label>Number of pages: </label>';
                    html += '<input type="number" name="page_count[' + index + ']" value="' + page.count + '" min="1" max="20" style="width: 60px; text-align: center;" class="artitechcore-page-count-input" data-page-index="' + index + '">';
                    html += '</div>';
                }

                html += '</div>';
            });

            html += '</div>';
            html += '</form>';

            $content.html(html);
            updateCostEstimate();

            // Recalculate cost when counts change
            $('.artitechcore-page-count-input').on('change', function() {
                updateCostEstimate();
                recalcTotalPages();
            });
        }

        function updateCostEstimate() {
            if (!selectedBlueprint) return;

            const bp = blueprints[selectedBlueprint];
            let totalPages = bp.pages.length;

            // Add counts for pages with 'count' property
            bp.pages.forEach(page => {
                if (page.count) {
                    const input = $('.artitechcore-page-count-input[data-page-index="' + bp.pages.indexOf(page) + '"]');
                    const count = parseInt(input.val()) || page.count;
                    totalPages += (count - 1); // Already counted once, so add (count - 1)
                }
            });

            const generateImages = $('#artitechcore-generate-images').is(':checked');
            const imagesPerPage = generateImages ? totalPages : 0;

            // Get provider and costs from PHP (passed as JS object)
            const providerCosts = {
                openai: { content: 0.002, image: 0.040, label: 'OpenAI' },
                gemini: { content: 0.001, image: 0, label: 'Gemini' },
                deepseek: { content: 0.0007, image: 0, label: 'DeepSeek' }
            };

            // Current provider from settings (passed from PHP)
            const currentProvider = '<?php echo esc_js(get_option('artitechcore_ai_provider', 'openai')); ?>';
            const costs = providerCosts[currentProvider] || providerCosts.openai;

            const contentCost = (totalPages * costs.content).toFixed(4);
            const imageCost = (imagesPerPage * costs.image).toFixed(4);
            const totalCost = (parseFloat(contentCost) + parseFloat(imageCost)).toFixed(4);

            let html = totalPages + ' pages × $' + costs.content.toFixed(4) + '/page = $' + contentCost;
            if (generateImages && costs.image > 0) {
                html += ' + ' + imagesPerPage + ' images × $' + costs.image.toFixed(4) + '/image = $' + imageCost;
            } else if (generateImages && costs.image === 0) {
                html += ' + Images not available with ' + costs.label + ' provider';
            }
            html += ' = <strong>$' + totalCost + '</strong> <small>(' + costs.label + ')</small>';

            $('#artitechcore-cost-estimate').html(html);
        }

        function recalcTotalPages() {
            // Update cost when page counts change
            updateCostEstimate();
        }

        // Generate button
        $('#artitechcore-start-generation').on('click', function() {
            if (!selectedBlueprint) {
                alert('<?php esc_html_e('Please select a blueprint first.', 'artitechcore'); ?>');
                return;
            }

            if (!confirm('<?php esc_html_e('This will create new pages on your site. Continue?', 'artitechcore'); ?>')) {
                return;
            }

            startGeneration();
        });

        let jobPollingInterval = null;
        let currentJobId = null;

        function startGeneration() {
            const $btn = $('#artitechcore-start-generation');
            const $progress = $('#artitechcore-generation-progress');
            const $fill = $('.artitechcore-progress-fill');
            const $text = $('.artitechcore-progress-text');
            const $status = $('#artitechcore-progress-status');
            const $results = $('#artitechcore-generation-results');
            const $control = $('#artitechcore-progress-control');

            $btn.prop('disabled', true);
            $progress.show();
            $control.hide();
            $('#artitechcore-cancel-generation').prop('disabled', false).text('<?php esc_js(esc_html__('Cancel Generation', 'artitechcore')); ?>');
            $results.hide().empty();
            $status.text('<?php esc_html_e('Queueing generation job...', 'artitechcore'); ?>');
            $fill.css('width', '0%');
            $text.text('0%');

            // Gather page configuration
            const blueprintKey = selectedBlueprint;
            const generateImages = $('#artitechcore-generate-images').is(':checked');
            const publishStatus = $('#artitechcore-publish-status').val();

            // Collect custom page counts
            const pageConfigs = [];
            const bp = blueprints[blueprintKey];
            bp.pages.forEach((page, index) => {
                let count = 1;
                if (page.count) {
                    const inputVal = parseInt($('[name="page_count[' + index + ']"]').val()) || page.count;
                    count = Math.max(1, Math.min(20, inputVal));
                }
                pageConfigs.push({
                    type: page.type,
                    title: page.title,
                    count: count
                });
            });

            // Send job to queue
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'artitechcore_build_website',
                    nonce: '<?php echo wp_create_nonce("artitechcore_website_builder_nonce"); ?>',
                    blueprint: blueprintKey,
                    page_configs: JSON.stringify(pageConfigs),
                    generate_images: generateImages ? 1 : 0,
                    publish_status: publishStatus
                },
                success: function(res) {
                    if (res.success && res.data.job_id) {
                        currentJobId = res.data.job_id;
                        $status.text('<?php esc_html_e('Job queued. Starting processing...', 'artitechcore'); ?>');
                        $control.show();
                        // Start polling for status
                        startJobPolling(currentJobId);
                    } else {
                        handleGenerationError(res.data || {message: '<?php esc_html_e('Unknown error', 'artitechcore'); ?>'});
                        $btn.prop('disabled', false);
                        $control.hide();
                    }
                },
                error: function(xhr, status, error) {
                    handleGenerationError({message: '<?php esc_html_e('Connection error. Please try again.', 'artitechcore'); ?>'});
                    $btn.prop('disabled', false);
                }
            });
        }

        function startJobPolling(jobId) {
            if (jobPollingInterval) {
                clearInterval(jobPollingInterval);
            }

            jobPollingInterval = setInterval(function() {
                $.ajax({
                    url: ajaxurl,
                    method: 'GET',
                    data: {
                        action: 'artitechcore_builder_job_status',
                        job_id: jobId,
                        nonce: '<?php echo wp_create_nonce("artitechcore_builder_status_nonce"); ?>'
                    },
                    success: function(res) {
                        if (res.success) {
                            const data = res.data;
                            const percent = data.progress || 0;
                            const $fill = $('.artitechcore-progress-fill');
                            const $text = $('.artitechcore-progress-text');
                            const $status = $('#artitechcore-progress-status');

                            $('.artitechcore-progress-bar').attr('aria-valuenow', percent);
                            $fill.css('width', percent + '%');
                            $text.text(percent + '%');

                            if (data.status === 'processing' || data.status === 'pending') {
                                const completed = data.completed || 0;
                                const total = data.total || 0;
                                $status.text('<?php echo esc_js(__('Processing...', 'artitechcore')); ?> ' + completed + '/' + total + ' <?php echo esc_js(__('pages completed', 'artitechcore')); ?>');
                            } else if (data.status === 'complete') {
                                clearInterval(jobPollingInterval);
                                jobPollingInterval = null;

                                $status.text('<?php esc_html_e('Generation complete!', 'artitechcore'); ?>');
                                $fill.css('width', '100%');
                                $text.text('100%');

                                // Show results
                                showGenerationResults(data);
                                $('#artitechcore-start-generation').prop('disabled', false);
                                $('#artitechcore-progress-control').hide();
                            } else if (data.status === 'failed') {
                                clearInterval(jobPollingInterval);
                                jobPollingInterval = null;

                                handleGenerationError({
                                    message: '<?php esc_html_e('Generation failed. Please check logs.', 'artitechcore'); ?>',
                                    errors: data.errors || []
                                });
                                $('#artitechcore-start-generation').prop('disabled', false);
                                $('#artitechcore-progress-control').hide();
                            } else if (data.status === 'cancelled') {
                                clearInterval(jobPollingInterval);
                                jobPollingInterval = null;
                                $status.text('<?php esc_html_e('Generation cancelled.', 'artitechcore'); ?>');
                                $('#artitechcore-start-generation').prop('disabled', false);
                                $('#artitechcore-progress-control').hide();
                            }
                        } else {
                            // Job not found or error
                            if (res.data && res.data.message && res.data.message.indexOf('not found') !== -1) {
                                clearInterval(jobPollingInterval);
                                jobPollingInterval = null;
                                handleGenerationError({message: '<?php esc_html_e('Job expired or not found.', 'artitechcore'); ?>'});
                                $('#artitechcore-start-generation').prop('disabled', false);
                            }
                        }
                    },
                    error: function() {
                        // Polling error - job might still be running, keep trying
                        console.log('ArtitechCore: Status check failed, will retry...');
                    }
                });
            }, 3000); // Poll every 3 seconds
        }

        function handleGenerationError(errorData) {
            const $status = $('#artitechcore-progress-status');
            const $results = $('#artitechcore-generation-results');

            $status.text('<?php echo esc_js(__('Error occurred', 'artitechcore')); ?>');

            let errorHtml = '<div class="notice notice-error"><h3><?php echo esc_js(__('Generation Failed', 'artitechcore')); ?></h3><ul>';

            if (errorData.errors && Array.isArray(errorData.errors)) {
                errorData.errors.forEach(function(err) {
                    errorHtml += '<li>' + escapeHtml(err) + '</li>';
                });
            } else if (errorData.message) {
                errorHtml += '<li>' + escapeHtml(errorData.message) + '</li>';
            } else {
                errorHtml += '<li><?php echo esc_js(__('Unknown error occurred', 'artitechcore')); ?></li>';
            }

            errorHtml += '</ul></div>';
            $results.html(errorHtml).show();
        }

        function showGenerationResults(data) {
            const $results = $('#artitechcore-generation-results');
            // Support both flat structure and nested summary (P2-11)
            const summary = data.summary || data;
            const pages = parseInt(summary.completed || 0, 10); 
            const images = parseInt(summary.images_generated || 0, 10);

            let html = '<div class="notice notice-success"><h3><?php echo esc_js(__('Generation Complete!', 'artitechcore')); ?></h3>';
            
            // LATE ESCAPING & I18N (Compliance Fix)
            let pagesText = '<?php echo esc_js(__('%d pages created', 'artitechcore')); ?>'.replace('%d', pages);
            html += '<p>' + pagesText;
            
            if (images > 0) {
                let imagesText = '<?php echo esc_js(__(' and %d featured images generated', 'artitechcore')); ?>'.replace('%d', images);
                html += imagesText;
            }
            html += '.</p>';

            // Show cost info
            if (data.cost) {
                let costTotal = parseFloat(data.cost.total).toFixed(4);
                let contentCost = parseFloat(data.cost.content_cost).toFixed(4);
                let imageCost = parseFloat(data.cost.image_cost).toFixed(4);
                
                let costText = '<?php echo esc_js(__('Estimated cost: $%s (Content: $%s, Images: $%s)', 'artitechcore')); ?>'
                    .replace('%s', costTotal)
                    .replace('%s', contentCost)
                    .replace('%s', imageCost);
                    
                html += '<p>' + costText + '</p>';
            }

            // Show warnings/errors if any
            if (summary.errors && summary.errors.length > 0) {
                html += '<h4><?php echo esc_js(__('Warnings:', 'artitechcore')); ?></h4><ul>';
                summary.errors.slice(0, 5).forEach(function(err) {
                    html += '<li>' + escapeHtml(err) + '</li>';
                });
                if (summary.errors.length > 5) {
                    let moreErrorsText = '<?php echo esc_js(__('... and %d more. Check error logs for details.', 'artitechcore')); ?>'
                        .replace('%d', (summary.errors.length - 5));
                    html += '<li>' + moreErrorsText + '</li>';
                }
                html += '</ul>';
            }

            html += '<p><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="button button-primary button-large"><?php echo esc_js(__('View All Pages', 'artitechcore')); ?></a></p>';
            html += '</div>';

            $results.html(html).show();
        }

        // Cancel Job Handler
        $('#artitechcore-cancel-generation').on('click', function() {
            if (!currentJobId) return;
            
            $(this).prop('disabled', true).text('<?php echo esc_js(__('Cancelling...', 'artitechcore')); ?>');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'artitechcore_builder_cancel_job',
                    job_id: currentJobId,
                    nonce: '<?php echo wp_create_nonce("artitechcore_website_builder_nonce"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        $('#artitechcore-progress-status').text('<?php echo esc_js(__('Generation cancelled.', 'artitechcore')); ?>');
                        $('#artitechcore-start-generation').prop('disabled', false);
                        $('#artitechcore-progress-control').hide();
                        if (jobPollingInterval) {
                            clearInterval(jobPollingInterval);
                            jobPollingInterval = null;
                        }
                    } else {
                        // Reset button if cancellation failed
                        $('#artitechcore-cancel-generation').prop('disabled', false).text('<?php esc_js(esc_html__('Cancel Generation', 'artitechcore')); ?>');
                        alert(res.data.message || '<?php echo esc_js(__('Failed to cancel job', 'artitechcore')); ?>');
                    }
                },
                error: function() {
                    $('#artitechcore-cancel-generation').prop('disabled', false).text('<?php esc_js(esc_html__('Cancel Generation', 'artitechcore')); ?>');
                    alert('<?php echo esc_js(__('Network error while cancelling', 'artitechcore')); ?>');
                }
            });
        });

        // Update cost estimate when image checkbox changes
        $('#artitechcore-generate-images').on('change', updateCostEstimate);
    });
    </script>

    <style>
    .artitechcore-blueprint-card:hover {
        border-color: #4A90E2 !important;
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.15);
    }
    .artitechcore-blueprint-card.selected {
        border-color: #4A90E2 !important;
        background: #f0f7ff;
    }
    </style>
    <?php
}

// AJAX handler for website generation
function artitechcore_ajax_build_website() {
    // Verify nonce first (security best practice)
    if (!check_ajax_referer('artitechcore_website_builder_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
        return;
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions. You must be an administrator to use this feature.']);
        return;
    }

    // Sanitize and collect inputs
    $blueprint = isset($_POST['blueprint']) ? sanitize_key($_POST['blueprint']) : '';
    $page_configs_raw = isset($_POST['page_configs']) ? wp_unslash($_POST['page_configs']) : '[]';
    $page_configs = json_decode($page_configs_raw, true);

    // Validate JSON parsing
    if (is_null($page_configs) && json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid page configuration data. Please try again.']);
        return;
    }

    $generate_images = isset($_POST['generate_images']) && $_POST['generate_images'] == 1;
    $publish_status = isset($_POST['publish_status']) ? sanitize_key($_POST['publish_status']) : 'draft';
    $publish_status = in_array($publish_status, ['draft', 'publish', 'private', 'pending']) ? $publish_status : 'draft';

    // SECURITY: Fetch brand kit server-side to prevent client-side manipulation
    // Fallback to provided data if server-side fetch fails (unlikely)
    $brand_kit = artitechcore_get_brand_kit();
    if (empty($brand_kit) || empty($brand_kit['brand_name'])) {
        // Fallback or handle error
        $brand_kit_json = isset($_POST['brand_kit']) ? wp_unslash($_POST['brand_kit']) : '{}';
        $brand_kit = json_decode($brand_kit_json, true);
        
        if (is_null($brand_kit) && json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid Brand Kit data. Please check your Brand Kit settings.']);
            return;
        }
    }

    // Validate the request
    $validation_errors = artitechcore_validate_website_builder_request($blueprint, $page_configs, $brand_kit);
    if (!empty($validation_errors)) {
        wp_send_json_error([
            'message' => 'Cannot proceed due to validation errors.',
            'errors' => $validation_errors
        ]);
        return;
    }

    // Check if provider image generation is compatible
    $provider = get_option('artitechcore_ai_provider', 'openai');
    if ($generate_images && $provider === 'deepseek') {
        wp_send_json_error([
            'message' => 'Image generation requires OpenAI API key. DeepSeek does not support DALL-E. ' .
                       'Either switch to OpenAI/Gemini provider or disable image generation.'
        ]);
        return;
    }

    if ($generate_images && empty(get_option('artitechcore_openai_api_key'))) {
        wp_send_json_error([
            'message' => 'Image generation requires an OpenAI API key. ' .
                       'Please add your OpenAI API key in Settings, or disable image generation.'
        ]);
        return;
    }

    // Check that content provider API key is set
    $provider_api_key = null;
    switch ($provider) {
        case 'openai':
            $provider_api_key = get_option('artitechcore_openai_api_key');
            break;
        case 'gemini':
            $provider_api_key = get_option('artitechcore_gemini_api_key');
            break;
        case 'deepseek':
            $provider_api_key = get_option('artitechcore_deepseek_api_key');
            break;
    }

    if (empty($provider_api_key)) {
        wp_send_json_error([
            'message' => sprintf(
                'API key for %s is not configured. Please add your %s API key in Settings.',
                ucfirst($provider),
                ucfirst($provider)
            )
        ]);
        return;
    }

    // Check cost against user monthly limit (if set)
    $cost_estimate = artitechcore_calculate_generation_cost($page_configs, $generate_images, $provider);
    $monthly_limit = get_option('artitechcore_monthly_cost_limit', null);
    
    if ($monthly_limit !== null) {
        $current_month = date('Y-m');
        $usage = get_option('artitechcore_monthly_usage_' . $current_month, ['spent' => 0]);
        $spent_so_far = isset($usage['spent']) ? (float)$usage['spent'] : 0.0;
        
        if (($spent_so_far + $cost_estimate['total']) > $monthly_limit) {
            wp_send_json_error([
                'message' => sprintf(
                    'Estimated cost ($%.4f) plus current monthly spend ($%.4f) exceeds your limit of $%.2f.',
                    $cost_estimate['total'],
                    $spent_so_far,
                    $monthly_limit
                ),
                'cost' => $cost_estimate
            ]);
            return;
        }
    }

    // Warn about high cost (> $5)
    if ($cost_estimate['total'] > 5) {
        // We don't block it, but we could add a warning to response
        // For now, just log it
        error_log(sprintf(
            'ArtitechCore: High-cost generation initiated - $%.4f for %d pages',
            $cost_estimate['total'],
            array_sum(array_column($page_configs, 'count'))
        ));
    }

    try {
        // Use background job queue instead of synchronous processing
        $queue = new ArtitechCore_Website_Builder_Queue();
        $queue->add_job($blueprint, $page_configs, $brand_kit, $generate_images, $publish_status, [
            'user_id' => get_current_user_id(),
            'cost_estimate' => $cost_estimate,
            'total_pages' => array_sum(array_column($page_configs, 'count'))
        ]);
        $job_id = $queue->dispatch();

        $response_data = [
            'message' => __('Website generation started in background. You will receive notifications when complete.', 'artitechcore'),
            'job_id' => $job_id,
            'status_url' => admin_url('admin-ajax.php?action=artitechcore_builder_job_status'),
            'cost' => $cost_estimate,
            'pages_total' => array_sum(array_column($page_configs, 'count')),
            'status' => 'queued'
        ];

        wp_send_json_success($response_data);

    } catch (Exception $e) {
        error_log('ArtitechCore Build Website Error: ' . $e->getMessage() . ' - Blueprint: ' . $blueprint . ' - Configs: ' . json_encode($page_configs));
        wp_send_json_error([
            'message' => 'Failed to queue website generation: ' . $e->getMessage(),
            'error_type' => 'queue_failed'
        ]);
    }
}
add_action('wp_ajax_artitechcore_build_website', 'artitechcore_ajax_build_website');

/**
 * Build a complete website
 */
function artitechcore_build_website($blueprint, $page_configs, $brand_kit, $generate_images = false, $publish_status = 'draft') {
    $blueprints = artitechcore_get_website_blueprints();
    $blueprint_data = $blueprints[$blueprint] ?? null;

    if (!$blueprint_data) {
        throw new Exception(__('Invalid blueprint selected', 'artitechcore'));
    }

    $pages_created = 0;
    $images_generated = 0;
    $errors = [];
    $created_pages = []; // Track individual page results

    foreach ($page_configs as $config) {
        $type = $config['type'];
        $count = $config['count'];

        for ($i = 1; $i <= $count; $i++) {
            $page_title = null;
            $page_result = [
                'type' => $type,
                'instance' => $i,
                'status' => 'pending',
                'title' => null,
                'id' => null,
                'image_generated' => false,
                'error' => null
            ];

            try {
                // Generate page content
                $page_data = artitechcore_generate_page_content($type, $brand_kit, $i);

                // Build title with numbering if multiple
                $title = $page_data['title'];
                if ($count > 1) {
                    $title .= ' ' . $i;
                }
                $page_title = $title;

                // Prepare post arguments with filter for customization
                $css_preamble = sprintf(
                    '<style>:root { --ac-primary: %s; --ac-secondary: %s; --ac-accent: %s; --ac-heading-font: "%s", sans-serif; --ac-body-font: "%s", sans-serif; }</style>',
                    esc_attr($brand_kit['primary_color']),
                    esc_attr($brand_kit['secondary_color']),
                    esc_attr($brand_kit['accent_color']),
                    esc_attr($brand_kit['heading_font']),
                    esc_attr($brand_kit['body_font'])
                );

                $post_args = [
                    'post_title'    => $title,
                    'post_name'     => sanitize_title($title),
                    'post_content'  => $css_preamble . "\n" . $page_data['content'],
                    'post_status'   => $publish_status,
                    'post_type'     => 'page',
                    'post_excerpt'  => $page_data['meta_description'] ?? '',
                ];

                $page_id = false;
                $existing_post = get_page_by_title($title, OBJECT, 'page');
                if ($existing_post && in_array($existing_post->post_status, ['publish', 'draft', 'pending', 'private'])) {
                    $page_id = $existing_post->ID;
                    $page_result['note'] = 'Skipped creation (already exists)';
                } else {
                    /**
                     * Filter post arguments before page creation
                     *
                     * @filter artitechcore_page_generation_post_args
                     * @param array $post_args WordPress post insertion arguments
                     * @param string $page_type Type of page being generated
                     * @param array $brand_kit Brand configuration
                     */
                    $post_args = apply_filters('artitechcore_page_generation_post_args', $post_args, $type, $brand_kit);

                    // Create the page
                    $page_id = wp_insert_post($post_args);
                }

                if ($page_id && !is_wp_error($page_id)) {
                    $pages_created++;
                    $page_result['status'] = 'success';
                    $page_result['id'] = $page_id;
                    $page_result['title'] = $title;

                    // Generate featured image
                    $image_success = false;
                    if ($generate_images && !has_post_thumbnail($page_id)) {
                        try {
                            // This will use existing image generation
                            $image_success = artitechcore_generate_and_set_featured_image_for_page($page_id, $title, $type, $brand_kit);
                            if ($image_success) {
                                $images_generated++;
                            }
                        } catch (Exception $e) {
                            error_log('ArtitechCore: Image generation failed for page ' . $title . ': ' . $e->getMessage());
                        }
                    }
                    $page_result['image_generated'] = $image_success;

                    // Generate schema
                    if (get_option('artitechcore_auto_schema_generation', true)) {
                        try {
                            artitechcore_generate_schema_markup($page_id);
                        } catch (Exception $e) {
                            error_log('ArtitechCore: Schema generation failed for page ' . $title . ': ' . $e->getMessage());
                            // Don't fail the page for schema errors
                        }
                    }
                } else {
                    $page_result['status'] = 'failed';
                    $page_result['error'] = is_wp_error($page_id) ? $page_id->get_error_message() : 'Unknown error';
                }
            } catch (Exception $e) {
                $error_msg = $type . ' (instance ' . $i . '): ' . $e->getMessage();
                $errors[] = $error_msg;
                $page_result['status'] = 'failed';
                $page_result['error'] = $e->getMessage();
                error_log('ArtitechCore: Failed to generate page: ' . $error_msg);
            }

            $created_pages[] = $page_result;
        }
    }

    return [
        'pages_created' => $pages_created,
        'images_generated' => $images_generated,
        'errors' => $errors,
        'created_pages' => $created_pages
    ];
}

/**
 * Validate website builder request to prevent abuse and ensure data integrity
 */
function artitechcore_validate_website_builder_request($blueprint, $page_configs, $brand_kit) {
    $errors = [];

    // Get available blueprints
    $blueprints = artitechcore_get_website_blueprints();

    // Validate blueprint exists
    if (!isset($blueprints[$blueprint])) {
        $errors[] = 'Invalid website blueprint selected.';
        return $errors;
    }

    // Special handling for custom blueprint (P2-5)
    if ($blueprint === 'custom') {
        if (empty($page_configs) || !is_array($page_configs)) {
            $errors[] = 'No page configuration provided for custom build.';
        }
        return $errors; // Custom blueprints don't have predefined types to validate against
    }

    // Validate page_configs structure
    if (empty($page_configs) || !is_array($page_configs)) {
        $errors[] = 'No page configuration provided.';
        return $errors;
    }

    // Get valid page types for this blueprint
    $blueprint_data = $blueprints[$blueprint];
    $valid_types = array_column($blueprint_data['pages'], 'type');

    $total_pages = 0;
    $type_counts = [];

    foreach ($page_configs as $index => $config) {
        if (!is_array($config)) {
            $errors[] = "Invalid configuration at position " . ($index + 1) . ".";
            continue;
        }

        // Validate page type exists in blueprint
        if (empty($config['type']) || !in_array($config['type'], $valid_types)) {
            $errors[] = "Invalid page type: " . ($config['type'] ?? 'unknown') . " for blueprint " . $blueprint_data['name'] . ".";
        }

        // Validate count is a positive integer
        $count = isset($config['count']) ? intval($config['count']) : 1;
        if ($count < 1) {
            $errors[] = "Page count must be at least 1 for " . ($config['type'] ?? 'unknown') . ".";
        }

        // Per-type maximum
        if ($count > ARTITECHCORE_MAX_PAGES_PER_TYPE) {
            $errors[] = "Cannot create more than 20 pages of type " . ($config['type'] ?? 'unknown') . " in a single generation.";
        }

        $total_pages += $count;
        $type_counts[$config['type']] = ($type_counts[$config['type']] ?? 0) + $count;
    }

    // Enforce absolute maximum across all page types
    if ($total_pages > ARTITECHCORE_MAX_PAGES_PER_BATCH) {
        $errors[] = 'Total pages cannot exceed 50 per generation. Please split into multiple batches.';
    }

    // Validate brand kit has minimum required content
    if (is_array($brand_kit)) {
        $has_brand_name = !empty($brand_kit['brand_name']);
        $has_description = !empty($brand_kit['description']);
        $has_tagline = !empty($brand_kit['tagline']);

        if (!$has_brand_name && !$has_description && !$has_tagline) {
            $errors[] = 'Brand Kit must contain at least a brand name, description, or tagline. Please configure your Brand Kit in Settings.';
        }

        // Validate brand colors are valid hex if set
        if (!empty($brand_kit['primary_color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_kit['primary_color'])) {
            $errors[] = 'Invalid primary color format in Brand Kit.';
        }
        if (!empty($brand_kit['secondary_color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_kit['secondary_color'])) {
            $errors[] = 'Invalid secondary color format in Brand Kit.';
        }
        if (!empty($brand_kit['accent_color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_kit['accent_color'])) {
            $errors[] = 'Invalid accent color format in Brand Kit.';
        }

        // Validate fonts are non-empty strings if set
        if (!empty($brand_kit['heading_font']) && !is_string($brand_kit['heading_font'])) {
            $errors[] = 'Heading font must be a text value.';
        }
        if (!empty($brand_kit['body_font']) && !is_string($brand_kit['body_font'])) {
            $errors[] = 'Body font must be a text value.';
        }
    } else {
        $errors[] = 'Invalid Brand Kit data. Please configure your Brand Kit in Settings.';
    }

    return $errors;
}

/**
 * Calculate estimated cost for generation
 */
function artitechcore_calculate_generation_cost($page_configs, $generate_images, $provider = null) {
    $provider = $provider ?: get_option('artitechcore_ai_provider', 'openai');

    // Use global constant cost configuration
    $global_costs = ARTITECHCORE_COST;
    $cost_config = $global_costs[$provider] ?? $global_costs['openai'];

    $total_pages = 0;
    foreach ($page_configs as $config) {
        $total_pages += isset($config['count']) ? intval($config['count']) : 1;
    }

    $content_cost = $total_pages * $cost_config['content_per_page'];
    $image_cost = $generate_images ? ($total_pages * $cost_config['image']) : 0;

    return [
        'content_cost' => $content_cost,
        'image_cost' => $image_cost,
        'total' => $content_cost + $image_cost,
        'currency' => 'USD'
    ];
}

/**
 * Generate page content via AI
 * This is the core content generation engine
 */
function artitechcore_generate_page_content($page_type, $brand_kit, $instance = 1) {
    // Get the AI provider and API key
    $provider = get_option('artitechcore_ai_provider', 'openai');

    switch ($provider) {
        case 'gemini':
            $api_key = get_option('artitechcore_gemini_api_key');
            return artitechcore_generate_page_content_with_gemini($page_type, $brand_kit, $instance, $api_key);
            break;

        case 'deepseek':
            $api_key = get_option('artitechcore_deepseek_api_key');
            return artitechcore_generate_page_content_with_openai($page_type, $brand_kit, $instance, $api_key, 'https://api.deepseek.com/v1/chat/completions');
            break;

        case 'openai':
        default:
            $api_key = get_option('artitechcore_openai_api_key');
            return artitechcore_generate_page_content_with_openai($page_type, $brand_kit, $instance, $api_key);
            break;
    }
}

/**
 * Generate page content with OpenAI-compatible API (OpenAI, DeepSeek, etc.)
 */
function artitechcore_generate_page_content_with_openai($page_type, $brand_kit, $instance, $api_key, $endpoint = 'https://api.openai.com/v1/chat/completions') {
    if (empty($api_key)) {
        throw new Exception(__('API key not configured', 'artitechcore'));
    }

    $prompt = artitechcore_build_page_generation_prompt($page_type, $brand_kit, $instance);

    $url = $endpoint;
    
    $model_name = strpos($endpoint, 'deepseek') !== false ? 'deepseek-chat' : 'gpt-4o';

    $request_data = [
        'model' => $model_name,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert WordPress developer and content creator. You must respond in valid JSON format only.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => ARTITECHCORE_AI_TEMPERATURE,
        'max_tokens' => ARTITECHCORE_AI_MAX_TOKENS,
    ];
    
    if ($model_name === 'gpt-4o') {
        $request_data['response_format'] = ['type' => 'json_object'];
    }

    $body = json_encode($request_data);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('Failed to encode request data for OpenAI API.', 'artitechcore'));
    }

    // API Retry Loop (P1-5)
    $max_retries = 3;
    $retry_count = 0;
    $response = null;

    while ($retry_count < $max_retries) {
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => ARTITECHCORE_API_TIMEOUT,
        ]);

        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                break; // Success
            }
            
            // Only retry on rate limits (429) or server errors (5xx)
            if ($response_code !== 429 && ($response_code < 500 || $response_code > 599)) {
                break; // Fatal error, don't retry
            }
        }

        $retry_count++;
        if ($retry_count < $max_retries) {
            // Exponential backoff: 2s, 4s
            sleep(pow(2, $retry_count));
        }
    }

    if (is_wp_error($response)) {
        throw new Exception(sprintf(__('OpenAI API request failed after %d attempts: %s', 'artitechcore'), $max_retries, $response->get_error_message()));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $error_message = wp_remote_retrieve_response_message($response);
        throw new Exception(sprintf(__('OpenAI API returned error %d after %d attempts: %s', 'artitechcore'), $response_code, $max_retries, $error_message));
    }

    $response_body = wp_remote_retrieve_body($response);
    if (empty($response_body)) {
        throw new Exception(__('Empty response received from OpenAI API.', 'artitechcore'));
    }

    $decoded_response = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('Invalid JSON response from OpenAI API.', 'artitechcore'));
    }

    if (isset($decoded_response['error'])) {
        $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown OpenAI API error.', 'artitechcore');
        throw new Exception(sprintf(__('OpenAI API error: %s', 'artitechcore'), $error_message));
    }

    if (!isset($decoded_response['choices'][0]['message']['content'])) {
        throw new Exception(__('Unexpected response format from OpenAI API.', 'artitechcore'));
    }

    $html_content = trim($decoded_response['choices'][0]['message']['content']);

    // Remove markdown code blocks if present (some models still wrap JSON in markdown)
    $response_content = trim($decoded_response['choices'][0]['message']['content']);
    $response_content = preg_replace('/^```(?:json)?\s*/i', '', $response_content);
    $response_content = preg_replace('/\s*```$/', '', $response_content);
    $response_content = trim($response_content);

    if (empty($response_content)) {
        throw new Exception(__('Empty content received from OpenAI API.', 'artitechcore'));
    }
    
    $parsed_response = json_decode($response_content, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($parsed_response['content'])) {
        $title = $parsed_response['title'] ?? $page_type;
        $html_content = $parsed_response['content'];
        $meta_description = $parsed_response['meta_description'] ?? '';
    } else {
        // Fallback for broken JSON
        $html_content = $response_content;
        $title = $page_type;
        $meta_description = '';
    }

    // Extract title from HTML if not provided
    if (empty($title) || $title === $page_type) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html_content, $matches)) {
            $title = sanitize_text_field(html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8'));
        } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html_content, $matches)) {
            $title = sanitize_text_field(html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES, 'UTF-8'));
        }
    }
    
    // Limit title length for DB safety and UX
    $title = mb_substr(sanitize_text_field($title), 0, 200);

    // SECURITY: Sanitize AI-generated HTML to prevent XSS
    $html_content = wp_kses($html_content, artitechcore_get_allowed_html_tags());

    // Defense in depth: also strip any <script> tags that might have gotten through
    $html_content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html_content);

    // Generate meta description
    if (empty($meta_description)) {
        $meta_description = $brand_kit['description'];
        if (empty($meta_description) && !empty($brand_kit['tagline'])) {
            $meta_description = $brand_kit['tagline'];
        }
    }
    
    // Fallback trimming for body tag instances
    $html_content = preg_replace('/^.*<body[^>]*>\s*/is', '', $html_content);
    $html_content = preg_replace('/\s*<\/body>.*$/is', '', $html_content);

    return [
        'title' => $title,
        'content' => trim($html_content),
        'meta_description' => sanitize_text_field($meta_description)
    ];
}

/**
 * Generate page content with Gemini
 */
function artitechcore_generate_page_content_with_gemini($page_type, $brand_kit, $instance, $api_key) {
    if (empty($api_key)) {
        throw new Exception(__('Gemini API key not configured', 'artitechcore'));
    }

    $prompt = artitechcore_build_page_generation_prompt($page_type, $brand_kit, $instance);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

    $body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => ARTITECHCORE_AI_TEMPERATURE,
            'maxOutputTokens' => ARTITECHCORE_AI_MAX_TOKENS,
        ]
    ]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('Failed to encode request data for Gemini API.', 'artitechcore'));
    }

    // API Retry Loop (P1-5)
    $max_retries = 3;
    $retry_count = 0;
    $response = null;

    while ($retry_count < $max_retries) {
        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'timeout' => ARTITECHCORE_API_TIMEOUT,
        ]);

        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                break; // Success
            }
            
            // Only retry on rate limits (429) or server errors (5xx)
            if ($response_code !== 429 && ($response_code < 500 || $response_code > 599)) {
                break; // Fatal error, don't retry
            }
        }

        $retry_count++;
        if ($retry_count < $max_retries) {
            // Exponential backoff: 2s, 4s
            sleep(pow(2, $retry_count));
        }
    }

    if (is_wp_error($response)) {
        throw new Exception(sprintf(__('Gemini API request failed after %d attempts: %s', 'artitechcore'), $max_retries, $response->get_error_message()));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $error_message = wp_remote_retrieve_response_message($response);
        throw new Exception(sprintf(__('Gemini API returned error %d after %d attempts: %s', 'artitechcore'), $response_code, $max_retries, $error_message));
    }

    $response_body = wp_remote_retrieve_body($response);
    if (empty($response_body)) {
        throw new Exception(__('Empty response received from Gemini API.', 'artitechcore'));
    }

    $decoded_response = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('Invalid JSON response from Gemini API.', 'artitechcore'));
    }

    if (isset($decoded_response['error'])) {
        $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown Gemini API error.', 'artitechcore');
        throw new Exception(sprintf(__('Gemini API error: %s', 'artitechcore'), $error_message));
    }

    if (!isset($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception(__('Unexpected response format from Gemini API.', 'artitechcore'));
    }

    $response_content = trim($decoded_response['candidates'][0]['content']['parts'][0]['text']);

    // Remove markdown code blocks if present
    $response_content = preg_replace('/^```(?:json)?\s*/i', '', $response_content);
    $response_content = preg_replace('/\s*```$/', '', $response_content);
    $response_content = trim($response_content);

    if (empty($response_content)) {
        throw new Exception(__('Empty content received from Gemini API.', 'artitechcore'));
    }

    $parsed_response = json_decode($response_content, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($parsed_response['content'])) {
        $title = $parsed_response['title'] ?? $page_type;
        $html_content = $parsed_response['content'];
        $meta_description = $parsed_response['meta_description'] ?? '';
    } else {
        // Fallback for broken JSON
        $html_content = $response_content;
        $title = $page_type;
        $meta_description = '';
    }

    // Extract title from HTML if missing
    if (empty($title) || $title === $page_type) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html_content, $matches)) {
            $title = sanitize_text_field(html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8'));
        } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html_content, $matches)) {
            $title = sanitize_text_field(html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES, 'UTF-8'));
        }
    }
    
    // Limit title length
    $title = mb_substr(sanitize_text_field($title), 0, 200);

    // SECURITY: Sanitize AI-generated HTML to prevent XSS
    $html_content = wp_kses($html_content, artitechcore_get_allowed_html_tags());
    $html_content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html_content);

    // Generate meta description
    if (empty($meta_description)) {
        $meta_description = $brand_kit['description'];
        if (empty($meta_description) && !empty($brand_kit['tagline'])) {
            $meta_description = $brand_kit['tagline'];
        }
    }
    
    // Fallback trimming for body tag instances
    $html_content = preg_replace('/^.*<body[^>]*>\s*/is', '', $html_content);
    $html_content = preg_replace('/\s*<\/body>.*$/is', '', $html_content);

    return [
        'title' => $title,
        'content' => trim($html_content),
        'meta_description' => sanitize_text_field($meta_description)
    ];
}

/**
 * Get allowed HTML tags for AI-generated content (P2-8)
 * Ensures consistent sanitization across all AI providers.
 */
function artitechcore_get_allowed_html_tags() {
    return apply_filters('artitechcore_allowed_html_tags', [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true, 'style' => true],
        'abbr' => ['title' => true],
        'b' => [],
        'blockquote' => ['cite' => true],
        'br' => [],
        'cite' => [],
        'code' => [],
        'dd' => [],
        'dl' => [],
        'dt' => [],
        'em' => [],
        'h1' => ['id' => true, 'class' => true, 'style' => true],
        'h2' => ['id' => true, 'class' => true, 'style' => true],
        'h3' => ['id' => true, 'class' => true, 'style' => true],
        'h4' => ['id' => true, 'class' => true, 'style' => true],
        'h5' => ['id' => true, 'class' => true, 'style' => true],
        'h6' => ['id' => true, 'class' => true, 'style' => true],
        'i' => [],
        'img' => ['src' => true, 'alt' => true, 'class' => true, 'loading' => true, 'width' => true, 'height' => true, 'style' => true],
        'li' => [],
        'ol' => ['class' => true, 'style' => true],
        'p' => ['class' => true, 'style' => true],
        'pre' => ['class' => true, 'style' => true],
        'strong' => [],
        'ul' => ['class' => true, 'style' => true],
        'section' => ['class' => true, 'style' => true],
        'header' => ['class' => true, 'style' => true],
        'main' => ['class' => true, 'style' => true],
        'footer' => ['class' => true, 'style' => true],
        'div' => ['class' => true, 'style' => true],
        'span' => ['class' => true, 'style' => true],
        'style' => [], // Allow AI-generated brand styles
        'form' => ['method' => true, 'action' => true, 'class' => true, 'style' => true],
        'input' => ['type' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'required' => true, 'class' => true, 'style' => true],
        'textarea' => ['name' => true, 'placeholder' => true, 'required' => true, 'rows' => true, 'class' => true, 'style' => true],
        'button' => ['type' => true, 'class' => true, 'style' => true],
        'table' => ['class' => true, 'style' => true],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['scope' => true, 'class' => true, 'style' => true],
        'td' => ['class' => true, 'style' => true],
        'nav' => ['class' => true, 'style' => true],
        'article' => ['class' => true, 'style' => true],
        'aside' => ['class' => true, 'style' => true],
        'figure' => ['class' => true, 'style' => true],
        'figcaption' => ['class' => true, 'style' => true],
    ]);
}

/**
 * Build the AI prompt for page generation
 */
function artitechcore_build_page_generation_prompt($page_type, $brand_kit, $instance) {
    // This will be a comprehensive prompt based on page type
    $voice_text = '';
    switch ($brand_kit['brand_voice']) {
        case 'professional': $voice_text = 'professional, authoritative, polished'; break;
        case 'casual': $voice_text = 'casual, friendly, conversational'; break;
        case 'innovative': $voice_text = 'innovative, cutting-edge, forward-thinking'; break;
        case 'trustworthy': $voice_text = 'trustworthy, reliable, reassuring'; break;
        case 'friendly': $voice_text = 'warm, approachable, friendly'; break;
        case 'luxury': $voice_text = 'luxury, exclusive, premium, sophisticated'; break;
        default: $voice_text = 'professional';
    }

    $aesthetic_text = '';
    switch ($brand_kit['design_aesthetic']) {
        case 'minimal': $aesthetic_text = 'minimalist with lots of white space, clean lines, simple layout'; break;
        case 'bold': $aesthetic_text = 'bold, high-contrast, visually striking, impactful'; break;
        case 'corporate': $aesthetic_text = 'corporate, traditional, professional, conservative'; break;
        case 'playful': $aesthetic_text = 'playful, colorful, fun, energetic, whimsical'; break;
        case 'luxury': $aesthetic_text = 'luxury, elegant, sophisticated, premium materials'; break;
        case 'modern': $aesthetic_text = 'modern, contemporary, clean, sleek'; break;
        default: $aesthetic_text = 'modern and clean';
    }

    $background_style = isset($brand_kit['background_style']) ? $brand_kit['background_style'] : 'light';
    $image_style = isset($brand_kit['image_style']) ? $brand_kit['image_style'] : 'photorealistic';

    $prompt = "You are an expert web designer and copywriter.

BRAND IDENTITY:
- Business: {$brand_kit['brand_name']}
- Description: {$brand_kit['description']}
- Voice: {$voice_text}
- Design Style: {$aesthetic_text}
- Colors: Primary {$brand_kit['primary_color']}, Secondary {$brand_kit['secondary_color']}, Accent {$brand_kit['accent_color']}
- Fonts: Headings {$brand_kit['heading_font']}, Body {$brand_kit['body_font']}
- Background Style: {$background_style}
- Preferred Image Style: {$image_style}

TASK:
Generate content for a {$page_type} page. This is instance {$instance} of this page type. Make sure it is unique and does not duplicate content from other instances.

REQUIREMENTS:
1. Use semantic HTML5 (<section>, <header>, <main>, <footer>)
2. DO NOT include <html>, <head>, or <body> tags. Output ONLY the content that goes inside the <body> tag, as this will be embedded directly in a WordPress page.
3. Mobile-responsive design.
4. Write in a {$voice_text} tone.
5. Design should be {$aesthetic_text}.
6. Use real content that makes sense for a {$page_type} page.

OUTPUT FORMAT:
Return a valid JSON object. DO NOT wrap it in markdown code blocks. The JSON must have exactly these three keys:
{
  \"title\": \"The SEO-friendly title of the page\",
  \"meta_description\": \"A unique, SEO-friendly meta description for this specific page (max 160 chars)\",
  \"content\": \"The raw HTML content string (no markdown, no html/head/body tags)\"
}";

    /**
     * Allow developers to modify the AI prompt before it's sent to the API
     *
     * @filter artitechcore_page_generation_prompt
     * @param string $prompt The generated prompt
     * @param string $page_type Type of page being generated
     * @param array $brand_kit Brand configuration array
     * @param int $instance Instance number (for multi-page types)
     */
    $prompt = apply_filters('artitechcore_page_generation_prompt', $prompt, $page_type, $brand_kit, $instance);

    return $prompt;
}

/**
 * Generate and set featured image for a page
 */
function artitechcore_generate_and_set_featured_image_for_page($post_id, $page_title, $page_type, $brand_kit) {
    // Use the existing image generation from ai-generator.php with enhanced brand kit
    if (!function_exists('artitechcore_generate_and_set_featured_image')) {
        error_log('ArtitechCore: Image generation function not available. Make sure ai-generator.php is loaded.');
        return false;
    }

    // Get OpenAI API key (required for DALL-E)
    $openai_key = get_option('artitechcore_openai_api_key');
    if (empty($openai_key)) {
        error_log('ArtitechCore: OpenAI API key not set for image generation');
        return false;
    }

    try {
        // Call existing image generation function with color parameter to avoid option race condition
        $brand_color = !empty($brand_kit['primary_color']) ? $brand_kit['primary_color'] : null;
        $result = artitechcore_generate_and_set_featured_image($post_id, $page_title, $brand_color);
        return $result;
    } catch (Exception $e) {
        error_log('ArtitechCore: Image generation failed for page ' . $page_title . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * AJAX handler: Get job status for async generation
 */
function artitechcore_ajax_get_job_status() {
    check_ajax_referer('artitechcore_builder_status_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $job_id = isset($_GET['job_id']) ? sanitize_key($_GET['job_id']) : '';

    if (empty($job_id)) {
        wp_send_json_error(['message' => 'No job ID specified']);
        return;
    }

    $job_data = ArtitechCore_Website_Builder_Queue::get_job_status($job_id);

    if (!$job_data) {
        wp_send_json_error(['message' => 'Job not found or has expired']);
        return;
    }

    // Calculate progress percentage
    $progress = $job_data['total'] > 0 ? round(($job_data['completed'] / $job_data['total']) * 100) : 0;

    wp_send_json_success([
        'job_id' => $job_id,
        'status' => $job_data['status'],
        'progress' => $progress,
        'completed' => $job_data['completed'],
        'total' => $job_data['total'],
        'errors' => $job_data['errors'] ?? [],
        'results' => $job_data['results'] ?? [],
        'started_at' => $job_data['started_at'] ?? null,
        'completed_at' => $job_data['completed_at'] ?? null
    ]);
}
add_action('wp_ajax_artitechcore_builder_job_status', 'artitechcore_ajax_get_job_status');

/**
 * AJAX handler: Cancel a running job
 */
function artitechcore_ajax_cancel_job() {
    check_ajax_referer('artitechcore_website_builder_nonce', 'nonce'); // Cancel still uses main nonce as it's a state change

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $job_id = isset($_POST['job_id']) ? sanitize_key($_POST['job_id']) : '';

    if (empty($job_id)) {
        wp_send_json_error(['message' => 'No job ID specified']);
        return;
    }

    $result = ArtitechCore_Website_Builder_Queue::cancel_job($job_id);

    if ($result) {
        wp_send_json_success(['message' => 'Job cancelled successfully']);
    } else {
        wp_send_json_error(['message' => 'Job not found or could not be cancelled']);
    }
}
add_action('wp_ajax_artitechcore_builder_cancel_job', 'artitechcore_ajax_cancel_job');

