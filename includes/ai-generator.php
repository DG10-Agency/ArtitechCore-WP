<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register AJAX handlers
add_action('wp_ajax_artitechcore_ai_generate_suggestions', 'artitechcore_handle_ai_generate_suggestions_ajax');
add_action('wp_ajax_artitechcore_ai_create_content', 'artitechcore_handle_ai_create_content_ajax');

/**
 * AI generation tab content - Modernized with AJAX support
 * 
 * @since 1.0
 */
function artitechcore_ai_generation_tab() {
    ?>
    <div class="artitechcore-premium-ai-generator dg10-brand">
        <!-- AI Generator Header -->
        <div class="dg10-premium-header">
            <div class="header-content">
                <div class="header-icon-wrap">
                    <span class="header-icon">✨</span>
                </div>
                <div class="header-text">
                    <h2><?php esc_html_e('AI Ecosystem Architect', 'artitechcore'); ?></h2>
                    <p><?php esc_html_e('Transform your vision into a high-performance digital infrastructure using our advanced Neural Network strategy.', 'artitechcore'); ?></p>
                </div>
            </div>
            <div class="header-badge">
                <span class="premium-pill"><?php esc_html_e('Powered by DG10 AI', 'artitechcore'); ?></span>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="ai-steps-indicator">
            <div class="ai-step active" data-step="1">
                <span class="step-num">1</span>
                <span class="step-label"><?php esc_html_e('Profile', 'artitechcore'); ?></span>
            </div>
            <div class="ai-step-divider"></div>
            <div class="ai-step" data-step="2">
                <span class="step-num">2</span>
                <span class="step-label"><?php esc_html_e('Strategy', 'artitechcore'); ?></span>
            </div>
            <div class="ai-step-divider"></div>
            <div class="ai-step" data-step="3">
                <span class="step-num">3</span>
                <span class="step-label"><?php esc_html_e('Architect', 'artitechcore'); ?></span>
            </div>
        </div>

        <!-- Multi-Step Form Container -->
        <div class="ai-form-glass-container">
            <form id="artitechcore-ai-request-form" class="dg10-premium-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('artitechcore_ajax_nonce', 'nonce'); ?>
                
                <!-- Step 1: Business Profile -->
                <div class="ai-form-step active" data-step="1">
                    <div class="step-header">
                        <h3><?php esc_html_e('Define Your Mission', 'artitechcore'); ?></h3>
                        <p><?php esc_html_e('The more detail you provide about your niche, the more targeted the architecture will be.', 'artitechcore'); ?></p>
                    </div>
                    
                    <div class="dg10-form-row">
                        <div class="dg10-form-group">
                            <label for="artitechcore_business_type" class="dg10-form-label"><?php esc_html_e('Industry / Business Niche', 'artitechcore'); ?></label>
                            <input type="text" name="artitechcore_business_type" id="artitechcore_business_type" class="dg10-form-input" 
                                   placeholder="<?php esc_attr_e('e.g., Luxury Real Estate, Specialized Dental Surgery', 'artitechcore'); ?>" required>
                        </div>
                    </div>

                    <div class="dg10-form-group">
                        <label for="artitechcore_business_details" class="dg10-form-label"><?php esc_html_e('Mission & CORE USP', 'artitechcore'); ?></label>
                        <textarea name="artitechcore_business_details" id="artitechcore_business_details" rows="5" class="dg10-form-textarea" 
                                  placeholder="<?php esc_attr_e('What truly distinguishes your brand from the competition? Describe your primary goals...', 'artitechcore'); ?>" required></textarea>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="dg10-btn dg10-btn-primary next-step">
                            <?php esc_html_e('Continue to Strategy', 'artitechcore'); ?>
                            <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: SEO Strategy -->
                <div class="ai-form-step" data-step="2">
                    <div class="step-header">
                        <h3><?php esc_html_e('Targeting & SEO Signal', 'artitechcore'); ?></h3>
                        <p><?php esc_html_e('Align your ecosystem with the specific search intent of your primary audience.', 'artitechcore'); ?></p>
                    </div>

                    <div class="dg10-form-grid">
                        <div class="dg10-form-group">
                            <label for="artitechcore_seo_keywords" class="dg10-form-label"><?php esc_html_e('Strategic Keywords', 'artitechcore'); ?></label>
                            <input type="text" name="artitechcore_seo_keywords" id="artitechcore_seo_keywords" class="dg10-form-input" 
                                   placeholder="<?php esc_attr_e('e.g., affordable luxury, high-conversion branding', 'artitechcore'); ?>">
                            <p class="dg10-form-help"><?php esc_html_e('Enter terms that define your reach.', 'artitechcore'); ?></p>
                        </div>

                        <div class="dg10-form-group">
                            <label for="artitechcore_target_audience" class="dg10-form-label"><?php esc_html_e('Target Persona', 'artitechcore'); ?></label>
                            <input type="text" name="artitechcore_target_audience" id="artitechcore_target_audience" class="dg10-form-input" 
                                   placeholder="<?php esc_attr_e('e.g., High-Net-Worth Individuals, Tech Startup CEOs', 'artitechcore'); ?>">
                        </div>
                    </div>

                    <div class="dg10-form-group">
                        <label for="artitechcore_keywords_csv" class="dg10-form-label"><?php esc_html_e('Contextual Deep-Dive (CSV)', 'artitechcore'); ?></label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="artitechcore_keywords_csv" id="artitechcore_keywords_csv" class="dg10-form-input" accept=".csv">
                        </div>
                        <p class="dg10-form-help"><?php esc_html_e('Upload raw data for AI to extrapolate complex patterns.', 'artitechcore'); ?></p>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="dg10-btn dg10-btn-outline prev-step"><?php esc_html_e('Back', 'artitechcore'); ?></button>
                        <button type="button" class="dg10-btn dg10-btn-primary next-step">
                            <?php esc_html_e('Configure Architect', 'artitechcore'); ?>
                            <span class="btn-icon">→</span>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Architect Settings -->
                <div class="ai-form-step" data-step="3">
                    <div class="step-header">
                        <h3><?php esc_html_e('Infrastructure Settings', 'artitechcore'); ?></h3>
                        <p><?php esc_html_e('Choose how deep the AI analysis should penetrate your site structure.', 'artitechcore'); ?></p>
                    </div>

                    <div class="premium-toggle-card">
                        <div class="toggle-info">
                            <h4><?php esc_html_e('Advanced Niche Ecosystem', 'artitechcore'); ?></h4>
                            <p><?php esc_html_e('AI will define custom post types, sophisticated taxonomies, and dynamic fields precisely for your industry.', 'artitechcore'); ?></p>
                        </div>
                        <label class="premium-switch">
                            <input type="checkbox" name="artitechcore_advanced_mode" id="artitechcore_advanced_mode" value="1" checked>
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="dg10-btn dg10-btn-outline prev-step"><?php esc_html_e('Back', 'artitechcore'); ?></button>
                        <button type="submit" class="dg10-btn dg10-btn-primary glow-btn">
                            <span class="btn-text"><?php esc_html_e('Launch AI Architect', 'artitechcore'); ?></span>
                            <span class="btn-icon">⚡</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Loading State Overlay -->
            <div id="ai-loading-overlay" class="ai-overlay hidden">
                <div class="ai-neural-loader">
                    <div class="loader-ring"></div>
                    <div class="loader-center">🤖</div>
                </div>
                <h3><?php esc_html_e('Analyzing Ecosystem Potential...', 'artitechcore'); ?></h3>
                <p id="ai-status-message"><?php esc_html_e('Interpreting business signals and SEO signals.', 'artitechcore'); ?></p>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div id="artitechcore-ai-results" class="premium-results-container">
            <!-- Results will be injected here -->
        </div>
    </div>
    <?php
}

/**
 * Handle AI suggestion generation AJAX request
 * 
 * @since 1.0
 */
function artitechcore_handle_ai_generate_suggestions_ajax() {
    // Verify nonce
    if (!wp_verify_nonce(isset($_POST['nonce']) ? $_POST['nonce'] : '', 'artitechcore_ajax_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'artitechcore')));
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'artitechcore')));
        return;
    }

    $business_type = isset($_POST['artitechcore_business_type']) ? sanitize_text_field(wp_unslash($_POST['artitechcore_business_type'])) : '';
    $business_details = isset($_POST['artitechcore_business_details']) ? sanitize_textarea_field(wp_unslash($_POST['artitechcore_business_details'])) : '';
    $seo_keywords = isset($_POST['artitechcore_seo_keywords']) ? sanitize_text_field(wp_unslash($_POST['artitechcore_seo_keywords'])) : '';
    $target_audience = isset($_POST['artitechcore_target_audience']) ? sanitize_text_field(wp_unslash($_POST['artitechcore_target_audience'])) : '';
    $advanced_mode = isset($_POST['artitechcore_advanced_mode']) && $_POST['artitechcore_advanced_mode'] == '1';
    
    // Combine keywords with CSV if uploaded
    $csv_keywords = '';
    if (isset($_FILES['artitechcore_keywords_csv']) && !empty($_FILES['artitechcore_keywords_csv']['tmp_name'])) {
        $csv_keywords = artitechcore_process_keywords_csv($_FILES['artitechcore_keywords_csv']);
    }
    
    $all_keywords = trim($seo_keywords);
    if (!empty($csv_keywords)) {
        $all_keywords = !empty($all_keywords) ? $all_keywords . ', ' . $csv_keywords : $csv_keywords;
    }

    ob_start();
    if ($advanced_mode) {
        artitechcore_generate_advanced_content_with_ai($business_type, $business_details, $all_keywords, $target_audience);
    } else {
        artitechcore_generate_pages_with_ai($business_type, $business_details, $all_keywords, $target_audience);
    }
    $html = ob_get_clean();
    
    if (empty($html) || strpos($html, 'notice-error') !== false) {
        wp_send_json_error(array('html' => $html, 'message' => __('AI generation failed.', 'artitechcore')));
    }

    wp_send_json_success(array('html' => $html));
}

/**
 * Handle content creation AJAX request
 * 
 * @since 1.0
 */
function artitechcore_handle_ai_create_content_ajax() {
    // Check nonce
    if (!wp_verify_nonce(isset($_POST['nonce']) ? $_POST['nonce'] : '', 'artitechcore_ajax_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'artitechcore')));
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'artitechcore')));
        return;
    }

    $generate_images = isset($_POST['artitechcore_generate_images']) && $_POST['artitechcore_generate_images'] == '1';
    $message = '';

    if (isset($_POST['artitechcore_selected_pages'])) {
        $selected_pages = array_map('wp_unslash', $_POST['artitechcore_selected_pages']);
        
        // Handle both simple and advanced page creation
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'advanced') {
            $selected_cpts = isset($_POST['artitechcore_selected_cpts']) ? array_map('wp_unslash', $_POST['artitechcore_selected_cpts']) : array();
            
            // Pass data directly - validation happens inside the function
            artitechcore_create_advanced_content($selected_pages, $selected_cpts, $generate_images);
            $message = sprintf(__('Successfully created %d pages and %d custom post types.', 'artitechcore'), count($selected_pages), count($selected_cpts));
        } else {
            artitechcore_create_suggested_pages($selected_pages, $generate_images);
            $message = sprintf(__('Successfully created %d pages.', 'artitechcore'), count($selected_pages));
        }
    } else {
        wp_send_json_error(array('message' => __('No content selected for creation.', 'artitechcore')));
    }

    wp_send_json_success(array('message' => $message));
}

// Generate pages with AI
function artitechcore_generate_pages_with_ai($business_type, $business_details, $seo_keywords = '', $target_audience = '') {
    try {
        // Input validation
        if (empty($business_type) || empty($business_details)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Business type and details are required for AI generation.', 'artitechcore') . '</p></div>';
            return;
        }

        // Sanitize inputs
        $business_type = sanitize_text_field($business_type);
        $business_details = sanitize_textarea_field($business_details);
        $seo_keywords = sanitize_text_field($seo_keywords);
        $target_audience = sanitize_text_field($target_audience);

        // Validate input lengths
        if (strlen($business_type) > 100) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Business type must be 100 characters or less.', 'artitechcore') . '</p></div>';
            return;
        }

        if (strlen($business_details) > 1000) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Business details must be 1000 characters or less.', 'artitechcore') . '</p></div>';
            return;
        }

        $provider = get_option('artitechcore_ai_provider', 'openai');
        $api_key = get_option('artitechcore_' . $provider . '_api_key');

        if (empty($api_key)) {
            echo '<div class="notice notice-error"><p>' . esc_html(sprintf(__('Please enter your %s API key in the Settings tab.', 'artitechcore'), ucfirst($provider))) . '</p></div>';
            return;
        }

        // Validate API key format
        if (!artitechcore_validate_api_key($api_key, $provider)) {
            echo '<div class="notice notice-error"><p>' . esc_html(sprintf(__('Invalid %s API key format. Please check your API key.', 'artitechcore'), ucfirst($provider))) . '</p></div>';
            return;
        }

        // Rate limiting check
        if (!artitechcore_check_ai_rate_limit($provider)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Too many AI requests. Please wait a moment before trying again.', 'artitechcore') . '</p></div>';
            return;
        }

        // Call the appropriate function based on the selected provider
        $suggested_pages = [];
        $error_message = '';
        
        switch ($provider) {
            case 'openai':
                $suggested_pages = artitechcore_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'gemini':
                $suggested_pages = artitechcore_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'deepseek':
                $suggested_pages = artitechcore_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            default:
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid AI provider selected.', 'artitechcore') . '</p></div>';
                return;
        }

        if (empty($suggested_pages)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Could not generate page suggestions. Please check your API key and try again.', 'artitechcore') . '</p></div>';
            return;
        }

        // Log successful generation
        artitechcore_log_ai_generation('page_suggestions', $provider, true, count($suggested_pages));

    } catch (Exception $e) {
        // Log error
        artitechcore_log_ai_generation('page_suggestions', $provider ?? 'unknown', false, 0, $e->getMessage());
        echo '<div class="notice notice-error"><p>' . esc_html__('An error occurred during AI generation. Please try again.', 'artitechcore') . '</p></div>';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore AI Generation Error: ' . $e->getMessage());
        }
        return;
    }

    // Filter out Privacy Policy page since WordPress creates it automatically
    $suggested_pages = array_filter($suggested_pages, function($page_line) {
        return stripos($page_line, 'Privacy Policy') === false;
    });

    echo '<div class="artitechcore-suggestions-results">';
    echo '<div class="results-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">';
    echo '<h3>✨ ' . esc_html__('AI-Architected Ecosystem:', 'artitechcore') . '</h3>';
    echo '<div class="results-actions" style="display: flex; align-items: center; gap: 15px;">';
    echo '<span style="font-size: 13px; color: var(--color-text-secondary);">' . esc_html__('Select all pages:', 'artitechcore') . '</span>';
    echo '<input type="checkbox" id="select-all-pages" checked>';
    echo '</div>';
    echo '</div>';

    echo '<form id="artitechcore-ai-creation-form" method="post" action="">';
    wp_nonce_field('artitechcore_ajax_nonce', 'nonce');
    echo '<input type="hidden" name="action_type" value="simple">';
    
    echo '<div class="artitechcore-suggestion-grid">';
    
    foreach ($suggested_pages as $page_line) {
        $excerpt = '';
        $page_title = $page_line;
        
        if (strpos($page_line, ':+') !== false) {
            list($page_title, $excerpt) = explode(':+', $page_line, 2);
            $excerpt = trim($excerpt);
        }
        
        $depth = 0;
        $display_title = $page_title;
        while (substr($display_title, 0, 1) === '-') {
            $display_title = substr($display_title, 1);
            $depth++;
        }
        $display_title = trim($display_title);
        
        // Dynamic icons based on title keywords
        $icon = '📄';
        $lower_title = strtolower($display_title);
        if (strpos($lower_title, 'service') !== false) $icon = '🛠️';
        elseif (strpos($lower_title, 'about') !== false) $icon = '🏢';
        elseif (strpos($lower_title, 'contact') !== false) $icon = '📧';
        elseif (strpos($lower_title, 'blog') !== false || strpos($lower_title, 'news') !== false) $icon = '📰';
        elseif (strpos($lower_title, 'portfolio') !== false || strpos($lower_title, 'work') !== false) $icon = '🎨';
        elseif (strpos($lower_title, 'faq') !== false) $icon = '❓';
        elseif (strpos($lower_title, 'home') !== false) $icon = '🏠';
        elseif (strpos($lower_title, 'guide') !== false || strpos($lower_title, 'how') !== false) $icon = '📘';

        $card_classes = 'artitechcore-suggestion-card is-selected';
        if ($depth > 0) $card_classes .= ' is-child';
        
        echo '<div class="' . esc_attr($card_classes) . '" data-page="' . esc_attr($page_line) . '">';
        echo '<div class="card-select-wrap"><input type="checkbox" name="artitechcore_selected_pages[]" value="' . esc_attr($page_line) . '" class="artitechcore-page-checkbox" checked></div>';
        echo '<span class="card-icon">' . $icon . '</span>';
        echo '<h4 class="card-title">' . esc_html($display_title) . '</h4>';
        echo '<p class="card-excerpt">' . esc_html($excerpt) . '</p>';
        echo '<div class="card-meta">';
        echo '<span class="neural-pill">' . esc_html__('SEO Optimized', 'artitechcore') . '</span>';
        if ($depth === 0) {
            echo '<span class="neural-pill">' . esc_html__('Pillar Page', 'artitechcore') . '</span>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    $provider = get_option('artitechcore_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<div class="artitechcore-options dg10-card" style="margin-top: 32px; padding: 24px; background: rgba(180, 124, 253, 0.03); border: 1px dashed var(--color-primary);">';
    echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
    echo '<div>';
    echo '<h4 style="margin: 0 0 4px 0;">🖼️ ' . esc_html__('Visual Enhancement', 'artitechcore') . '</h4>';
    echo '<p style="margin: 0; font-size: 13px; color: var(--color-text-secondary);">' . esc_html__('Automatically generate high-converting featured images for each page.', 'artitechcore') . '</p>';
    echo '</div>';
    echo '<label class="premium-switch">';
    echo '<input type="checkbox" name="artitechcore_generate_images" id="artitechcore_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<span class="slider round"></span>';
    echo '</label>';
    echo '</div>';
    
    if ($is_deepseek) {
        echo '<p class="dg10-form-help dg10-text-danger" style="margin-top: 12px; font-weight: 600;">⚠️ ' . esc_html__('Note: Image generation is currently not supported with DeepSeek.', 'artitechcore') . '</p>';
    }
    echo '</div>';
    
    echo '<div class="dg10-form-actions" style="margin-top: 32px; position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px; border-radius: var(--radius-md); backdrop-filter: blur(5px); display: flex; justify-content: center;">';
    echo '<button type="submit" class="dg10-btn dg10-btn-primary glow-btn" style="padding: 15px 40px; font-size: 16px;">';
    echo '<span class="nav-icon">🚀</span> ';
    echo esc_html__('Deploy Selected Strategy', 'artitechcore');
    echo '</button>';
    echo '</div>';

    echo '<div class="artitechcore-creation-status" style="display: none; margin-top: 20px;"></div>';
    echo '</form>';
    echo '</div>';
}

// Get page suggestions from OpenAI API
function artitechcore_get_openai_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for OpenAI API call.', 'artitechcore'));
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 1000,
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for OpenAI API.', 'artitechcore'));
        }

        $response = artitechcore_safe_ai_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => ARTITECHCORE_API_TIMEOUT,
        ], 'openai');

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
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

        $pages_str = $decoded_response['choices'][0]['message']['content'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from OpenAI API.', 'artitechcore'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from OpenAI API.', 'artitechcore'));
        }

        return $pages;

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore OpenAI API Error: ' . $e->getMessage());
        }
        return [];
    }
}

// Get page suggestions from Gemini API
function artitechcore_get_gemini_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for Gemini API call.', 'artitechcore'));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for Gemini API.', 'artitechcore'));
        }

        $response = artitechcore_safe_ai_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'timeout' => ARTITECHCORE_API_TIMEOUT,
        ], 'gemini');

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
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

        $pages_str = $decoded_response['candidates'][0]['content']['parts'][0]['text'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from Gemini API.', 'artitechcore'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from Gemini API.', 'artitechcore'));
        }

        return $pages;

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore Gemini API Error: ' . $e->getMessage());
        }
        return [];
    }
}

// Get page suggestions from DeepSeek API
function artitechcore_get_deepseek_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    try {
        // Input validation
        if (empty($api_key) || empty($business_type) || empty($business_details)) {
            throw new Exception(__('Missing required parameters for DeepSeek API call.', 'artitechcore'));
        }

        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        // Build enhanced SEO prompt
        $seo_context = '';
        if (!empty($seo_keywords)) {
            $seo_context .= "SEO Keywords: {$seo_keywords}. ";
        }
        if (!empty($target_audience)) {
            $seo_context .= "Target Audience: {$target_audience}. ";
        }
        
        $prompt = "## ROLE & CONTEXT
You are an expert SEO strategist and information architect specializing in website structure optimization for maximum search visibility and user experience.

## BUSINESS CONTEXT
- **Industry**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## TASK OBJECTIVE
Generate a comprehensive list of essential website pages that will establish topical authority and semantic relevance for this business. For each page, provide:
1. Page Title (use hyphens '-' for nesting child pages to indicate hierarchy)
2. SEO-optimized Meta Description (separated by ':+' from the title)

## STRATEGIC REQUIREMENTS

### 1. TOPICAL AUTHORITY ARCHITECTURE
- Create content clusters around core topics
- Establish pillar pages with supporting child pages
- Ensure comprehensive coverage of the business domain
- Include both commercial and informational intent pages

### 2. SEMANTIC SEO IMPLEMENTATION
- Use natural language variations of target keywords
- Incorporate related concepts and entities
- Build semantic relationships between pages
- Avoid keyword stuffing - focus on contextual relevance

### 3. EEAT OPTIMIZATION
- Demonstrate expertise through comprehensive content planning
- Show authoritativeness by covering all essential business aspects
- Build trust with transparent, valuable content
- Include experience-based content where relevant

### 4. USER INTENT MATCHING
- Commercial intent pages (services, products, pricing)
- Informational intent pages (guides, resources, FAQs)
- Navigational intent pages (contact, about, locations)
- Transactional intent pages (checkout, booking, quotes)

### 5. TECHNICAL SEO CONSIDERATIONS
- Logical URL structure with proper hierarchy
- Internal linking opportunities between related pages
- Mobile-first content approach
- Fast-loading, user-friendly page types

## SEO OPTIMIZATION REQUIREMENTS
- **Page Titles**: Must include primary keywords naturally, be compelling, and accurately describe the page content
- **Meta Descriptions**: 155-160 characters, include primary keywords naturally, be compelling and encourage click-throughs
- **Keyword Placement**: Use keywords in titles and descriptions without stuffing - make it sound natural
- **User Intent**: Match the search intent for each keyword (informational, commercial, navigational)

## OUTPUT FORMAT
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting (e.g., '-Services:-+[description]' for child pages)

## CONTEXT-AWARE PAGE SELECTION GUIDELINES:
- **Analyze the business context** and only suggest pages that make sense for this specific business type
- **Use common sense**: A portfolio website doesn't need a Pricing page, an e-commerce site does
- **Consider user intent**: Focus on pages that match what users would actually search for
- **Semantic relationships**: Create pages that build topical authority through related content clusters
- **Business model awareness**: Service businesses need different pages than product businesses or informational sites

## FLEXIBLE STRUCTURE PRINCIPLES:
- **Main Pages**: Use logical hierarchy based on business needs (not fixed templates)
- **Child Pages**: Only nest when there's a clear semantic relationship
- **Avoid unnecessary pages**: Don't include pages that don't serve a clear purpose for this business
- **User-centric**: Focus on what the target audience actually needs to find

## SMART PAGE SELECTION EXAMPLES:
- **Portfolio Website**: Home, About, Portfolio, Services, Contact, Testimonials, Blog
- **E-commerce Store**: Home, Shop, Product Categories, About, Contact, FAQ, Shipping, Returns
- **Service Business**: Home, Services, About, Contact, Testimonials, Blog, FAQ
- **Informational Site**: Home, Resources, Blog, About, Contact, Glossary, Tutorials

## OUTPUT FORMAT:
Return only the list in this exact format:
[SEO-optimized Page Title with primary keywords]:+[Meta Description - 155-160 characters, compelling, includes primary keyword naturally]

Use hyphens for nesting only when there's a clear hierarchical relationship

Focus on creating a website architecture that makes sense for THIS specific business, not a generic template. Use semantic SEO principles and common sense to determine which pages are actually needed.

Focus on creating a complete website architecture that will rank well and convert visitors.";

        $body = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 1000,
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for DeepSeek API.', 'artitechcore'));
        }

        $response = artitechcore_safe_ai_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => ARTITECHCORE_API_TIMEOUT,
        ], 'deepseek');

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from DeepSeek API.', 'artitechcore'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from DeepSeek API.', 'artitechcore'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown DeepSeek API error.', 'artitechcore');
            throw new Exception(sprintf(__('DeepSeek API error: %s', 'artitechcore'), $error_message));
        }

        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            throw new Exception(__('Unexpected response format from DeepSeek API.', 'artitechcore'));
        }

        $pages_str = $decoded_response['choices'][0]['message']['content'];
        if (empty($pages_str)) {
            throw new Exception(__('Empty content received from DeepSeek API.', 'artitechcore'));
        }

        $pages = array_map('trim', explode("\n", $pages_str));
        $pages = array_filter($pages, function($page) {
            return !empty($page) && strpos($page, ':+') !== false;
        });

        if (empty($pages)) {
            throw new Exception(__('No valid page suggestions received from DeepSeek API.', 'artitechcore'));
        }

        return $pages;

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore DeepSeek API Error: ' . $e->getMessage());
        }
        return [];
    }
}

// Create suggested pages
function artitechcore_create_suggested_pages($pages, $generate_images = false) {
    try {
        // Input validation
        if (empty($pages) || !is_array($pages)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('No pages provided for creation.', 'artitechcore') . '</p></div>';
            return;
        }

        $created_count = 0;
        $failed_count = 0;
        $parent_id_stack = [];
        $errors = [];

        foreach ($pages as $page_line) {
            if (empty($page_line)) continue;

            try {
                $excerpt = '';
                if (strpos($page_line, ':+') !== false) {
                    list($page_title, $excerpt) = explode(':+', $page_line, 2);
                    $excerpt = trim($excerpt);
                } else {
                    $page_title = $page_line;
                }

                // Validate page title
                if (empty($page_title)) {
                    $errors[] = __('Empty page title found, skipping.', 'artitechcore');
                    continue;
                }

                $depth = 0;
                while (substr($page_title, 0, 1) === '-') {
                    $page_title = substr($page_title, 1);
                    $depth++;
                }
                $page_title = trim($page_title);

                // Validate title length
                if (strlen($page_title) > 200) {
                    $errors[] = sprintf(__('Page title too long (over 200 characters): %s', 'artitechcore'), substr($page_title, 0, 50) . '...');
                    continue;
                }

                $parent_id = ($depth > 0 && isset($parent_id_stack[$depth - 1])) ? $parent_id_stack[$depth - 1] : 0;

                // Validate parent exists if specified
                if ($parent_id > 0 && !get_post($parent_id)) {
                    $errors[] = sprintf(__('Parent page not found for: %s', 'artitechcore'), $page_title);
                    $parent_id = 0; // Reset to root level
                }

                // Generate SEO-optimized slug
                $post_name = artitechcore_generate_seo_slug($page_title);
                
                $new_page = array(
                    'post_title'   => $page_title,
                    'post_name'    => $post_name,
                    'post_content' => '',
                    'post_status'  => 'draft',
                    'post_type'    => 'page',
                    'post_parent'  => $parent_id,
                    'post_excerpt' => $excerpt,
                );
                
                $page_id = wp_insert_post($new_page);

                if ($page_id && !is_wp_error($page_id)) {
                    $created_count++;
                    
                    // Generate and set featured image if enabled
                    if ($generate_images) {
                        try {
                            artitechcore_generate_and_set_featured_image($page_id, $page_title);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate image for "%s": %s', 'artitechcore'), $page_title, $e->getMessage());
                        }
                    }
                    
                    // Generate schema markup for the new page
                    $auto_generate = get_option('artitechcore_auto_schema_generation', true);
                    if ($auto_generate) {
                        try {
                            artitechcore_generate_schema_markup($page_id);
                        } catch (Exception $e) {
                            $errors[] = sprintf(__('Failed to generate schema for "%s": %s', 'artitechcore'), $page_title, $e->getMessage());
                        }
                    }
                    
                    $parent_id_stack[$depth] = $page_id;
                    $parent_id_stack = array_slice($parent_id_stack, 0, $depth + 1);
                } else {
                    $failed_count++;
                    $error_message = is_wp_error($page_id) ? $page_id->get_error_message() : __('Unknown error', 'artitechcore');
                    $errors[] = sprintf(__('Failed to create page "%s": %s', 'artitechcore'), $page_title, $error_message);
                }

            } catch (Exception $e) {
                $failed_count++;
                $errors[] = sprintf(__('Error processing page "%s": %s', 'artitechcore'), $page_line, $e->getMessage());
            }
        }

        // Display results
        if ($created_count > 0) {
            $message = sprintf(
                __('%d pages created successfully as drafts.', 'artitechcore'),
                absint($created_count)
            );
            if ($generate_images) {
                $message .= ' ' . __('Featured images generated with AI.', 'artitechcore');
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        if ($failed_count > 0) {
            $error_message = sprintf(__('%d pages failed to create.', 'artitechcore'), absint($failed_count));
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }

        // Log errors if any
        if (!empty($errors)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ArtitechCore Page Creation Errors: ' . implode('; ', $errors));
            }
        }

        // Log successful creation
        artitechcore_log_ai_generation('page_creation', 'manual', true, $created_count);

    } catch (Exception $e) {
        echo '<div class="notice notice-error"><p>' . __('An error occurred during page creation. Please try again.', 'artitechcore') . '</p></div>';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore Page Creation Error: ' . $e->getMessage());
        }
        artitechcore_log_ai_generation('page_creation', 'manual', false, 0, $e->getMessage());
    }
}

// Generate and set featured image using AI
function artitechcore_generate_and_set_featured_image($post_id, $page_title, $override_brand_color = null) {
    try {
        // Input validation
        if (empty($post_id) || empty($page_title) || !is_numeric($post_id)) {
            throw new Exception(__('Missing or invalid parameters for image generation.', 'artitechcore'));
        }

        $post_id = absint($post_id);
        if ($post_id <= 0) {
            throw new Exception(__('Invalid post ID for image generation.', 'artitechcore'));
        }

        // Verify post exists
        if (!get_post($post_id)) {
            throw new Exception(__('Post not found for image generation.', 'artitechcore'));
        }

        $provider = get_option('artitechcore_ai_provider', 'openai');
        $api_key = get_option('artitechcore_' . $provider . '_api_key');
        
        // Check if third param is the brand kit array
        $brand_kit = is_array($override_brand_color) ? $override_brand_color : artitechcore_get_brand_kit();
        $brand_color = is_array($override_brand_color) ? ($override_brand_color['primary_color'] ?? '#4A90E2') : ($override_brand_color ?: get_option('artitechcore_brand_color', '#4A90E2'));
        
        if (empty($api_key)) {
            throw new Exception(__('API key not configured for image generation.', 'artitechcore'));
        }
        
        // Skip if provider is DeepSeek (no image generation support)
        if ($provider === 'deepseek') {
            throw new Exception(__('Image generation not supported with DeepSeek provider.', 'artitechcore'));
        }
        
        // Rate limiting check for image generation
        if (!artitechcore_check_ai_rate_limit($provider)) {
            throw new Exception(__('Too many AI requests. Please wait a moment before trying again.', 'artitechcore'));
        }
        
        // Validate brand color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_color)) {
            $brand_color = '#4A90E2'; // Default fallback
        }
        
        $design_style = $brand_kit['design_aesthetic'] ?? 'modern';
        $image_style = $brand_kit['image_style'] ?? 'abstract';
        $brand_name = $brand_kit['brand_name'] ?? 'The company';

        // Generate image prompt
        $prompt = "## IMAGE CREATION BRIEF
Create a professional featured image for a webpage titled: '{$page_title}' for the brand '{$brand_name}'.

## STYLE & AESTHETIC REQUIREMENTS
- **General Style**: {$design_style}
- **Image Style**: {$image_style}
- **Color Palette**: Primary color: {$brand_color} with complementary tones
- **Mood**: Professional, clean, engaging but not distracting
- **Composition**: Balanced, with visual hierarchy that supports text overlay

## TECHNICAL SPECIFICATIONS
- **Aspect Ratio**: 16:9 (standard for featured images)
- **Resolution**: High-quality, sharp details
- **Text Readability**: Design should allow for clear text overlay
- **Brand Alignment**: Reflect the professional nature of the content

## CREATIVE DIRECTION
- Incorporate the primary color {$brand_color} organically.
- Create visual interest without being too busy or distracting.
- Ensure the image works well as a background for white text overlay.
- Maintain a professional aesthetic matching the brand style.

## USAGE CONTEXT
This image will be used as a featured image for a webpage, so it should:
- Be visually appealing but not overpower the content
- Work well at various sizes (thumbnail to full-width)
- Convey professionalism and relevance to the page topic
- Have adequate contrast for text readability.";
        
        // Call the appropriate image generation API
        $image_url = '';
        switch ($provider) {
            case 'openai':
                $image_url = artitechcore_generate_openai_image($prompt, $api_key);
                break;
            case 'gemini':
                $image_url = artitechcore_generate_gemini_image($prompt, $api_key);
                break;
            default:
                throw new Exception(__('Unsupported provider for image generation.', 'artitechcore'));
        }
        
        if (empty($image_url)) {
            throw new Exception(__('Failed to generate image URL from AI provider.', 'artitechcore'));
        }

        // Validate image URL
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            throw new Exception(__('Invalid image URL received from AI provider.', 'artitechcore'));
        }
        
        // Generate SEO-optimized image metadata based on page title
        $image_title = "Featured Image for " . sanitize_text_field($page_title);
        
        // Extract primary keywords from page title for alt text
        $keywords = artitechcore_extract_primary_keywords($page_title);
        $image_alt = "Visual representation of " . $keywords . " concept";
        
        $image_description = "AI-generated featured image showcasing themes related to " . sanitize_text_field($page_title);
        
        // Use the enhanced featured image setting function with metadata
        $result = artitechcore_set_featured_image($post_id, $image_url, $image_title, $image_alt, $image_description);
        
        if (!$result) {
            throw new Exception(__('Failed to set featured image for post.', 'artitechcore'));
        }

        return true;
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore Image Generation Error: ' . $e->getMessage());
        }
        return false;
    }
}

// Generate image using OpenAI DALL-E
function artitechcore_generate_openai_image($prompt, $api_key) {
    try {
        // Input validation
        if (empty($prompt) || empty($api_key)) {
            throw new Exception(__('Missing required parameters for OpenAI image generation.', 'artitechcore'));
        }

        $url = 'https://api.openai.com/v1/images/generations';
        
        $body = json_encode([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard'
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Failed to encode request data for OpenAI image generation.', 'artitechcore'));
        }
        
        // API Retry Loop (P1-5)
        $max_retries = 3;
        $response = artitechcore_safe_ai_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => $body,
            'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120, // Global constant or default
        ], 'openai');

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            throw new Exception(__('Empty response received from OpenAI image generation.', 'artitechcore'));
        }

        $decoded_response = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from OpenAI image generation.', 'artitechcore'));
        }

        if (isset($decoded_response['error'])) {
            $error_message = isset($decoded_response['error']['message']) ? $decoded_response['error']['message'] : __('Unknown OpenAI image generation error.', 'artitechcore');
            throw new Exception(sprintf(__('OpenAI image generation error: %s', 'artitechcore'), $error_message));
        }

        if (!isset($decoded_response['data'][0]['url'])) {
            throw new Exception(__('Unexpected response format from OpenAI image generation.', 'artitechcore'));
        }

        $image_url = $decoded_response['data'][0]['url'];
        if (empty($image_url)) {
            throw new Exception(__('Empty image URL received from OpenAI.', 'artitechcore'));
        }

        return $image_url;
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore OpenAI Image Generation Error: ' . $e->getMessage());
        }
        return '';
    }
}

// Generate image using Google Gemini
function artitechcore_generate_gemini_image($prompt, $api_key) {
    // Note: As of current implementation, Gemini doesn't have a direct image generation API like DALL-E
    // This function is a placeholder for future implementation when Gemini releases image generation
    // For now, we'll return empty string to indicate no image generation
    return '';
}

// Process keywords from CSV file
function artitechcore_process_keywords_csv($file) {
    try {
        // Input validation
        if (!isset($file) || !is_array($file)) {
            throw new Exception(__('Invalid file data provided.', 'artitechcore'));
        }

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception(__('No temporary file found for CSV processing.', 'artitechcore'));
        }

        // Security check: Verify file was uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception(__('Security check failed: File was not uploaded via standard HTTP POST.', 'artitechcore'));
        }

        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => __('File exceeds upload_max_filesize directive.', 'artitechcore'),
                UPLOAD_ERR_FORM_SIZE => __('File exceeds MAX_FILE_SIZE directive.', 'artitechcore'),
                UPLOAD_ERR_PARTIAL => __('File was only partially uploaded.', 'artitechcore'),
                UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'artitechcore'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder.', 'artitechcore'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'artitechcore'),
                UPLOAD_ERR_EXTENSION => __('File upload stopped by extension.', 'artitechcore'),
            ];
            $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('Unknown upload error.', 'artitechcore');
            throw new Exception($error_message);
        }

        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            throw new Exception(__('Only CSV files are allowed.', 'artitechcore'));
        }

        // Check file size (limit to 1MB)
        if ($file['size'] > 1048576) {
            throw new Exception(__('File size must be less than 1MB.', 'artitechcore'));
        }

        // Validate file exists and is readable
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            throw new Exception(__('File is not accessible for reading.', 'artitechcore'));
        }
        
        $keywords = [];
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle === false) {
            throw new Exception(__('Failed to open CSV file for reading.', 'artitechcore'));
        }

        $line_count = 0;
        $max_lines = 1000; // Limit to prevent memory issues
        
        while (($data = fgetcsv($handle)) !== false) {
            $line_count++;
            
            // Prevent processing too many lines
            if ($line_count > $max_lines) {
                fclose($handle);
                throw new Exception(sprintf(__('CSV file has too many lines. Maximum allowed: %d', 'artitechcore'), $max_lines));
            }

            if (!is_array($data)) {
                continue; // Skip invalid rows
            }

            foreach ($data as $cell) {
                $cell = trim($cell);
                if (!empty($cell)) {
                    // Validate cell length
                    if (strlen($cell) > 200) {
                        continue; // Skip overly long keywords
                    }

                    // Handle both comma-separated values and individual keywords
                    if (strpos($cell, ',') !== false) {
                        $split_keywords = array_map('trim', explode(',', $cell));
                        foreach ($split_keywords as $keyword) {
                            if (!empty($keyword) && strlen($keyword) <= 200) {
                                $keywords[] = sanitize_text_field($keyword);
                            }
                        }
                    } else {
                        $keywords[] = sanitize_text_field($cell);
                    }
                }
            }
        }
        fclose($handle);
        
        if (empty($keywords)) {
            throw new Exception(__('No valid keywords found in CSV file.', 'artitechcore'));
        }
        
        // Remove duplicates and empty values
        $keywords = array_unique(array_filter($keywords));
        
        if (empty($keywords)) {
            throw new Exception(__('No valid keywords remaining after processing.', 'artitechcore'));
        }

        // Limit total keywords to prevent issues
        if (count($keywords) > 500) {
            $keywords = array_slice($keywords, 0, 500);
        }
        
        return implode(', ', $keywords);

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore CSV Processing Error: ' . $e->getMessage());
        }
        return '';
    }
}

// Extract primary keywords from page title for SEO optimization
if (!function_exists('artitechcore_extract_primary_keywords')) {
    function artitechcore_extract_primary_keywords($title) {
        try {
            if (empty($title)) {
                return '';
            }

            // Remove common stop words and extract meaningful keywords
            $stop_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
            
            // Clean the title and split into words
            $words = preg_split('/\s+/', strtolower($title));
            $words = array_map('trim', $words);
            
            // Remove stop words and short words
            $keywords = array_filter($words, function($word) use ($stop_words) {
                return !in_array($word, $stop_words) && strlen($word) > 2 && !is_numeric($word);
            });
            
            // Remove duplicates and return the first 3-4 keywords
            $keywords = array_unique($keywords);
            $keywords = array_slice($keywords, 0, 4);
            
            return implode(' ', $keywords) ?: sanitize_text_field($title);
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ArtitechCore Keyword Extraction Error: ' . $e->getMessage());
            }
            return sanitize_text_field($title);
        }
    }
}

// Validate API key format
if (!function_exists('artitechcore_validate_api_key')) {
    function artitechcore_validate_api_key($api_key, $provider) {
        // Simple validation - just check if key exists and has reasonable length
        // Strict format validation often fails with new/varied key formats
        if (empty($api_key)) {
            return false;
        }
        
        // All major AI providers have keys at least 20 characters
        // The actual validity will be verified when the API call is made
        return strlen($api_key) >= 20;
    }
}

// Check AI rate limiting per provider
if (!function_exists('artitechcore_check_ai_rate_limit')) {
    function artitechcore_check_ai_rate_limit($provider = null) {
        try {
            // Get current provider if not specified
            if (empty($provider)) {
                $provider = get_option('artitechcore_ai_provider', 'openai');
            }
            
            // Validate provider
            $valid_providers = ['openai', 'gemini', 'deepseek'];
            if (!in_array($provider, $valid_providers)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ArtitechCore Rate Limit: Invalid provider specified: ' . $provider);
            }
                return true; // Allow on invalid provider to prevent blocking
            }
            
            $user_id = get_current_user_id();
            if (empty($user_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ArtitechCore Rate Limit: No user ID found');
            }
                return true; // Allow for non-logged-in users (shouldn't happen in admin)
            }
            
            // Create provider-specific rate limit key
            $rate_limit_key = 'artitechcore_ai_rate_limit_' . $provider . '_' . $user_id;
            $rate_limit_data = get_transient($rate_limit_key);
            
            $current_time = time();
            
            if ($rate_limit_data === false) {
                // No rate limit data, create new entry
                $rate_limit_data = [
                    'count' => 1,
                    'first_request' => $current_time,
                    'last_request' => $current_time,
                    'reset_time' => $current_time + 60
                ];
                set_transient($rate_limit_key, $rate_limit_data, 60);
                return true;
            }
            
            // Check if the rate limit window has expired (1 minute)
            if ($current_time >= $rate_limit_data['reset_time']) {
                // Reset the rate limit window
                $rate_limit_data = [
                    'count' => 1,
                    'first_request' => $current_time,
                    'last_request' => $current_time,
                    'reset_time' => $current_time + 60
                ];
                set_transient($rate_limit_key, $rate_limit_data, 60);
                return true;
            }
            
            // Check if we're within the rate limit (10 requests per minute per provider)
            if ($rate_limit_data['count'] >= 10) {
                // Log rate limit exceeded
                error_log(sprintf(
                    'ArtitechCore Rate Limit Exceeded: User %d, Provider %s, Count %d, Reset in %d seconds',
                    $user_id,
                    $provider,
                    $rate_limit_data['count'],
                    $rate_limit_data['reset_time'] - $current_time
                ));
                return false;
            }
            
            // Increment counter and update last request time
            $rate_limit_data['count']++;
            $rate_limit_data['last_request'] = $current_time;
            set_transient($rate_limit_key, $rate_limit_data, 60);
            
            return true;
            
        } catch (Exception $e) {
            error_log('ArtitechCore Rate Limit Check Error: ' . $e->getMessage());
            return true; // Allow on error to prevent blocking users
        }
    }
}

// Get rate limit status for a specific provider
if (!function_exists('artitechcore_get_rate_limit_status')) {
    function artitechcore_get_rate_limit_status($provider = null) {
        try {
            // Get current provider if not specified
            if (empty($provider)) {
                $provider = get_option('artitechcore_ai_provider', 'openai');
            }
            
            $user_id = get_current_user_id();
            if (empty($user_id)) {
                return null;
            }
            
            $rate_limit_key = 'artitechcore_ai_rate_limit_' . $provider . '_' . $user_id;
            $rate_limit_data = get_transient($rate_limit_key);
            
            if ($rate_limit_data === false) {
                return [
                    'provider' => $provider,
                    'count' => 0,
                    'limit' => 10,
                    'reset_time' => null,
                    'time_remaining' => null,
                    'is_limited' => false
                ];
            }
            
            $current_time = time();
            $time_remaining = max(0, $rate_limit_data['reset_time'] - $current_time);
            
            return [
                'provider' => $provider,
                'count' => $rate_limit_data['count'],
                'limit' => 10,
                'reset_time' => $rate_limit_data['reset_time'],
                'time_remaining' => $time_remaining,
                'is_limited' => $rate_limit_data['count'] >= 10
            ];
            
        } catch (Exception $e) {
            error_log('ArtitechCore Rate Limit Status Error: ' . $e->getMessage());
            return null;
        }
    }
}

// Test rate limiting functionality (for debugging purposes)
if (!function_exists('artitechcore_test_rate_limiting')) {
    function artitechcore_test_rate_limiting($provider = 'openai') {
        try {
            $results = [];
            
            // Test multiple requests to see rate limiting in action
            for ($i = 1; $i <= 12; $i++) {
                $allowed = artitechcore_check_ai_rate_limit($provider);
                $status = artitechcore_get_rate_limit_status($provider);
                
                $results[] = [
                    'request' => $i,
                    'allowed' => $allowed,
                    'count' => $status['count'],
                    'is_limited' => $status['is_limited'],
                    'time_remaining' => $status['time_remaining']
                ];
                
                // Small delay to simulate real usage
                usleep(100000); // 0.1 second
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log('ArtitechCore Rate Limit Test Error: ' . $e->getMessage());
            return false;
        }
    }
}

// Log AI generation activities
if (!function_exists('artitechcore_log_ai_generation')) {
    function artitechcore_log_ai_generation($type, $provider, $success, $count = 0, $error_message = '') {
        try {
            global $wpdb;
            
            // Log to error log for debugging (this always works)
            $user_id = get_current_user_id();
            $log_message = sprintf(
                'ArtitechCore Generation: Type=%s, Provider=%s, Success=%s, Count=%d, User=%d',
                $type,
                $provider,
                $success ? 'Yes' : 'No',
                $count,
                $user_id
            );
            
            if (!$success && !empty($error_message)) {
                $log_message .= ', Error=' . $error_message;
            }
            
            error_log($log_message);
            
            // Try to insert into custom table - use correct column names from activation schema
            // The table schema uses: page_id, generation_type, ai_provider, tokens_used, success, error_message
            $table_name = $wpdb->prefix . 'artitechcore_generation_logs';
            
            // Check if table exists before trying to insert
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));
            
            if ($table_exists) {
                // Use column names from the actual table schema
                $log_data = [
                    'page_id' => 0, // Not tracking specific page
                    'generation_type' => sanitize_text_field($type),
                    'ai_provider' => sanitize_text_field($provider),
                    'tokens_used' => absint($count),
                    'success' => $success ? 1 : 0,
                    'error_message' => sanitize_text_field($error_message),
                ];
                
                // Suppress errors - logging failure shouldn't break the feature
                $wpdb->suppress_errors(true);
                $wpdb->insert($table_name, $log_data);
                $wpdb->suppress_errors(false);
            }
            
        } catch (Exception $e) {
            // Silently fail - logging errors shouldn't break the main feature
            error_log('ArtitechCore Logging Error: ' . $e->getMessage());
        }
    }
}

// ===== ADVANCED MODE FUNCTIONALITY =====

// Generate advanced content with AI (pages + custom post types)
function artitechcore_generate_advanced_content_with_ai($business_type, $business_details, $seo_keywords = '', $target_audience = '') {
    try {
        error_log('ArtitechCore: Entering artitechcore_generate_advanced_content_with_ai');
        
        // Input validation
        if (empty($business_type) || empty($business_details)) {
            error_log('ArtitechCore: Input validation failed - empty business_type or business_details');
            echo '<div class="notice notice-error"><p>' . __('Business type and details are required for advanced AI generation.', 'artitechcore') . '</p></div>';
            return;
        }

        // Sanitize inputs
        $business_type = sanitize_text_field($business_type);
        $business_details = sanitize_textarea_field($business_details);
        $seo_keywords = sanitize_text_field($seo_keywords);
        $target_audience = sanitize_text_field($target_audience);
        
        error_log('ArtitechCore: Inputs sanitized - business_type: ' . substr($business_type, 0, 50));

        // Validate input lengths
        if (strlen($business_type) > 100) {
            echo '<div class="notice notice-error"><p>' . __('Business type must be 100 characters or less.', 'artitechcore') . '</p></div>';
            return;
        }

        if (strlen($business_details) > 2000) {
            echo '<div class="notice notice-error"><p>' . __('Business details must be 2000 characters or less.', 'artitechcore') . '</p></div>';
            return;
        }

        $provider = get_option('artitechcore_ai_provider', 'openai');
        $api_key = get_option('artitechcore_' . $provider . '_api_key');
        
        error_log('ArtitechCore: Provider: ' . $provider . ', API key exists: ' . (!empty($api_key) ? 'YES (length: ' . strlen($api_key) . ')' : 'NO'));

        if (empty($api_key)) {
            error_log('ArtitechCore: API key is empty for provider: ' . $provider);
            echo '<div class="notice notice-error"><p>' . sprintf(__('Please enter your %s API key in the Settings tab.', 'artitechcore'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }

        // Validate API key format
        if (!artitechcore_validate_api_key($api_key, $provider)) {
            error_log('ArtitechCore: API key validation failed for provider: ' . $provider);
            echo '<div class="notice notice-error"><p>' . sprintf(__('Invalid %s API key format. Please check your API key.', 'artitechcore'), esc_html(ucfirst($provider))) . '</p></div>';
            return;
        }
        
        error_log('ArtitechCore: API key validated successfully');

        // Rate limiting check
        if (!artitechcore_check_ai_rate_limit($provider)) {
            error_log('ArtitechCore: Rate limit exceeded for provider: ' . $provider);
            echo '<div class="notice notice-error"><p>' . __('Too many AI requests. Please wait a moment before trying again.', 'artitechcore') . '</p></div>';
            return;
        }
        
        error_log('ArtitechCore: Rate limit check passed, calling AI provider: ' . $provider);

        // Get advanced content suggestions from AI
        $advanced_suggestions = [];
        switch ($provider) {
            case 'openai':
                $advanced_suggestions = artitechcore_get_openai_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'gemini':
                $advanced_suggestions = artitechcore_get_gemini_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            case 'deepseek':
                $advanced_suggestions = artitechcore_get_deepseek_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key);
                break;
            default:
                error_log('ArtitechCore: Invalid AI provider: ' . $provider);
                echo '<div class="notice notice-error"><p>' . __('Invalid AI provider selected.', 'artitechcore') . '</p></div>';
                return;
        }
        
        error_log('ArtitechCore: AI call completed. Response type: ' . gettype($advanced_suggestions) . ', empty: ' . (empty($advanced_suggestions) ? 'YES' : 'NO'));

        if (empty($advanced_suggestions)) {
            error_log('ArtitechCore: AI returned empty suggestions');
            echo '<div class="notice notice-warning"><p>' . __('Could not generate advanced content suggestions. Please check your API key and try again.', 'artitechcore') . '</p></div>';
            return;
        }

        // Parse the AI response
        $parsed_suggestions = artitechcore_parse_advanced_ai_response($advanced_suggestions);
        
        if (empty($parsed_suggestions['pages']) && empty($parsed_suggestions['custom_post_types'])) {
            echo '<div class="notice notice-warning"><p>' . __('No content suggestions were generated. Please try with more detailed business information.', 'artitechcore') . '</p></div>';
            return;
        }

        // Log successful generation
        $total_suggestions = count($parsed_suggestions['pages']) + count($parsed_suggestions['custom_post_types']);
        artitechcore_log_ai_generation('advanced_content', $provider, true, $total_suggestions);

        // Display the suggestions
        artitechcore_display_advanced_content_suggestions($parsed_suggestions);

    } catch (Exception $e) {
        // Log error
        artitechcore_log_ai_generation('advanced_content', $provider ?? 'unknown', false, 0, $e->getMessage());
        echo '<div class="notice notice-error"><p>' . __('An error occurred during advanced AI generation. Please try again.', 'artitechcore') . '</p></div>';
        error_log('ArtitechCore Advanced AI Generation Error: ' . $e->getMessage());
    }
}

// Get advanced suggestions from OpenAI API
function artitechcore_get_openai_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $prompt = artitechcore_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'model' => 'gpt-4o-mini', // Optimized for speed (Instant generation)
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 8000,
    ]);

    $response = artitechcore_safe_ai_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
    ], 'openai');

    if (is_wp_error($response)) {
        error_log('ArtitechCore OpenAI API Error: ' . $response->get_error_message());
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        return $response_body['choices'][0]['message']['content'];
    }

    return [];
}

// Get advanced suggestions from Gemini API
function artitechcore_get_gemini_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;
    
    $prompt = artitechcore_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
    ]);

    error_log('ArtitechCore: Making Gemini API request...');

    $response = artitechcore_safe_ai_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
    ], 'gemini');

    if (is_wp_error($response)) {
        error_log('ArtitechCore Gemini API Error: ' . $response->get_error_message());
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($response_body['error'])) {
        error_log('ArtitechCore Gemini API Error: ' . json_encode($response_body['error']));
        return [];
    }

    if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $response_body['candidates'][0]['content']['parts'][0]['text'];
        error_log('ArtitechCore: Gemini API returned text (' . strlen($text) . ' chars)');
        return $text;
    }

    error_log('ArtitechCore: Gemini API response missing expected structure: ' . json_encode(array_keys($response_body ?? [])));
    return [];
}

// Get advanced suggestions from DeepSeek API
function artitechcore_get_deepseek_advanced_suggestions($business_type, $business_details, $seo_keywords, $target_audience, $api_key) {
    $url = 'https://api.deepseek.com/v1/chat/completions';
    
    $prompt = artitechcore_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience);

    $body = json_encode([
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 8000,
    ]);

    $response = artitechcore_safe_ai_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
    ], 'deepseek');

    if (is_wp_error($response)) {
        error_log('ArtitechCore DeepSeek API Error: ' . $response->get_error_message());
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        return $response_body['choices'][0]['message']['content'];
    }

    return [];
}

// Build the advanced AI prompt for dynamic business analysis
function artitechcore_build_advanced_ai_prompt($business_type, $business_details, $seo_keywords, $target_audience) {
    return "## ROLE & CONTEXT
You are a World-Class Digital Transformation Architect and SEO Expert (Fortune 500 level). Your goal is to architect the perfect, high-performance content ecosystem for a specific business.
Your output must be PRACTICAL, IMPLEMENTABLE, and focused purely on GROWTH and CONVERSION.

## BUSINESS CONTEXT
- **Business Type**: {$business_type}
- **Business Details**: {$business_details}
- **Target Audience**: {$target_audience}
- **Primary Keywords**: {$seo_keywords}

## STRATEGIC DIRECTIVES (WORLD CLASS STANDARD)

### 1. HIGH-CONVERSION PAGE ARCHITECTURE
- Identify the exact pages needed to dominate this niche (Limit to 5-7 essential pages).
- **Landing Pages**: Suggest specific high-converting landing pages (e.g., 'Free Consultation', 'Case Studies').
- **Legal/Trust**: Don't forget trust-building pages (e.g., 'Our Process', 'Why Choose Us').
- **SEO Silos**: Suggest pages that act as pillar content.

### 2. PRACTICAL CUSTOM POST TYPES (CPTs)
- Don't just suggest 'Blogs'. Suggest *business-critical* data structures.
- **IMPORTANT**: Do NOT suggest CPTs for Taxonomies or Categories (e.g., 'Activity Types', 'Skills', 'Genres'). Instead, add these as `select` Custom Fields within the main CPT OR create proper Custom Taxonomies.
- Examples: 'Success Stories' (not just Case Studies), 'Staff Profiles' (with specialty fields), 'Locations' (for local SEO).
- **Custom Fields**: SUGGEST AT LEAST 3-5 PRACTICAL FIELDS that drive business value (e.g., 'Price', 'Duration', 'rating'). Avoid generic 'text' fields if specific types fit.
- **Taxonomies**: Explicitly list related taxonomies in the `taxonomies` array for each CPT. Include 'category' or 'post_tag' ONLY if relevant.

### 3. PRACTICAL TAXONOMIES (Categorization)
- Suggest appropriate taxonomies for the content.
- Use Taxonomies for grouping content (e.g., 'Activity Types', 'Skills', 'Genres', 'Locations').
- Do NOT create CPTs for these. Create Taxonomies.

### 4. CONVERSION FOCUSED REASONING
- Explain WHY this page exists. (e.g., 'To capture top-of-funnel leads via specific problem-solving').

## STRICT OUTPUT FORMAT (JSON ONLY)
Return purely the JSON object. No markdown fences. No chatter.

{
  \"business_analysis\": {
    \"business_model\": \"Concise 1-sentence summary of how they make money.\",
    \"content_needs\": \"Specific content gaps to fill.\",
    \"target_audience_insights\": \"Key pain points of the audience.\"
  },
  \"standard_pages\": [
    {
      \"title\": \"Page Title (SEO Optimized)\",
      \"meta_description\": \"Compelling click-through description (155-160 chars).\",
      \"reasoning\": \"Strategic value of this page.\",
      \"hierarchy_level\": 0
    }
  ],
  \"custom_post_types\": [
    {
      \"name\": \"slug\",
      \"label\": \"Plural Name\",
      \"description\": \"Internal description.\",
      \"reasoning\": \"Why this architecture is critical for scale.\",
      \"custom_fields\": [
        {
          \"name\": \"field_slug\",
          \"label\": \"Field Label\",
          \"type\": \"text|textarea|select|image|url|number|date|boolean\",
          \"description\": \"Usage instruction.\",
          \"required\": true
        }
      ],
      \"taxonomies\": [\"category\", \"post_tag\", \"custom_tax_slug\"],
      \"sample_entries\": [
        {
          \"title\": \"High-Value Example Title\",
          \"content\": \"2-sentence summary of a perfect example entry.\"
        }
      ]
    }
  ],
  \"custom_taxonomies\": [
    {
      \"name\": \"taxonomy_slug\",
      \"singular_label\": \"Singular Name\",
      \"plural_label\": \"Plural Name\",
      \"post_types\": [\"cpt_slug_1\", \"cpt_slug_2\"],
      \"hierarchical\": true
    }
  ]
}";
}

// Parse the AI response into structured data
function artitechcore_parse_advanced_ai_response($ai_response) {
    $parsed = [
        'pages' => [],
        'custom_post_types' => [],
        'custom_taxonomies' => []
    ];

    // Handle empty or array response (should be string)
    if (empty($ai_response)) {
        error_log('ArtitechCore: AI response is empty');
        return $parsed;
    }

    if (is_array($ai_response)) {
        error_log('ArtitechCore: AI response is already an array (unexpected)');
        return $parsed;
    }

    $response_text = $ai_response;

    // Strip markdown code fences if present (```json ... ```)
    $response_text = preg_replace('/```json\s*/i', '', $response_text);
    $response_text = preg_replace('/```\s*$/m', '', $response_text);
    $response_text = preg_replace('/^```\s*/m', '', $response_text);
    $response_text = trim($response_text);

    // Log the cleaned response for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('ArtitechCore: Cleaned AI response (first 500 chars): ' . substr($response_text, 0, 500));
    }

    // Try to extract JSON from the response
    $json_start = strpos($response_text, '{');
    $json_end = strrpos($response_text, '}');
    
    if ($json_start === false || $json_end === false) {
        error_log('ArtitechCore: Could not find JSON brackets in AI response');
        return $parsed;
    }

    $json_string = substr($response_text, $json_start, $json_end - $json_start + 1);
    $data = json_decode($json_string, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('ArtitechCore: JSON decode error: ' . json_last_error_msg());
        error_log('ArtitechCore: Failed JSON (first 1000 chars): ' . substr($json_string, 0, 1000));
        return $parsed;
    }

    if ($data) {
        $parsed['pages'] = isset($data['standard_pages']) ? $data['standard_pages'] : [];
        $parsed['custom_post_types'] = isset($data['custom_post_types']) ? $data['custom_post_types'] : [];
        $parsed['custom_taxonomies'] = isset($data['custom_taxonomies']) ? $data['custom_taxonomies'] : [];
        $parsed['business_analysis'] = isset($data['business_analysis']) ? $data['business_analysis'] : [];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore: Parsed ' . count($parsed['pages']) . ' pages and ' . count($parsed['custom_post_types']) . ' CPTs');
        }
    }

    return $parsed;
}

// Display advanced content suggestions
/**
 * Display advanced content suggestions - Modernized
 * 
 * @since 1.0
 */
function artitechcore_display_advanced_content_suggestions($suggestions) {
    echo '<div class="artitechcore-advanced-suggestions">';
    
    // Display business analysis if available
    if (!empty($suggestions['business_analysis'])) {
        echo '<div class="artitechcore-business-analysis dg10-card">';
        echo '<h3>📊 ' . esc_html__('Business Intelligence Analysis', 'artitechcore') . '</h3>';
        echo '<div class="analysis-content">';
        if (isset($suggestions['business_analysis']['business_model'])) {
            echo '<p><strong>' . esc_html__('Strategic Model:', 'artitechcore') . '</strong> ' . esc_html($suggestions['business_analysis']['business_model']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['content_needs'])) {
            echo '<p><strong>' . esc_html__('Content Strategy:', 'artitechcore') . '</strong> ' . esc_html($suggestions['business_analysis']['content_needs']) . '</p>';
        }
        if (isset($suggestions['business_analysis']['target_audience_insights'])) {
            echo '<p><strong>' . esc_html__('Audience Insights:', 'artitechcore') . '</strong> ' . esc_html($suggestions['business_analysis']['target_audience_insights']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
    }

    echo '<form id="artitechcore-ai-creation-form" method="post" action="">';
    wp_nonce_field('artitechcore_ai_ajax', 'nonce');
    echo '<input type="hidden" name="action_type" value="advanced">';

    // Display standard pages
    if (!empty($suggestions['pages'])) {
        echo '<div class="artitechcore-pages-section" style="margin-top: 32px;">';
        echo '<div class="results-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
        echo '<h3>📄 ' . esc_html__('Core Architecture: Pages', 'artitechcore') . '</h3>';
        echo '<div class="results-actions" style="display: flex; align-items: center; gap: 10px;">';
        echo '<span style="font-size: 13px; color: var(--color-text-secondary);">' . esc_html__('Select all:', 'artitechcore') . '</span>';
        echo '<input type="checkbox" id="select-all-pages" checked>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="artitechcore-suggestion-grid">';
        foreach ($suggestions['pages'] as $page) {
            // Dynamic icons based on title keywords
            $icon = '📄';
            $lower_title = strtolower($page['title']);
            if (strpos($lower_title, 'service') !== false) $icon = '🛠️';
            elseif (strpos($lower_title, 'about') !== false) $icon = '🏢';
            elseif (strpos($lower_title, 'contact') !== false) $icon = '📧';
            elseif (strpos($lower_title, 'blog') !== false || strpos($lower_title, 'news') !== false) $icon = '📰';
            
            echo '<div class="artitechcore-suggestion-card is-selected" data-page="' . esc_attr(json_encode($page)) . '">';
            echo '<div class="card-select-wrap"><input type="checkbox" name="artitechcore_selected_pages[]" value="' . esc_attr(json_encode($page)) . '" class="artitechcore-page-checkbox" checked></div>';
            echo '<span class="card-icon">' . $icon . '</span>';
            echo '<h4 class="card-title">' . esc_html($page['title']) . '</h4>';
            echo '<p class="card-excerpt">' . esc_html($page['meta_description']) . '</p>';
            echo '<div class="card-meta">';
            echo '<span class="neural-pill">' . esc_html__('SEO Strategy', 'artitechcore') . '</span>';
            echo '<span class="neural-pill" title="' . esc_attr($page['reasoning']) . '">' . esc_html__('View Logic', 'artitechcore') . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Display custom post types
    if (!empty($suggestions['custom_post_types'])) {
        echo '<div class="artitechcore-cpts-section" style="margin-top: 48px;">';
        echo '<h3>🏗️ ' . esc_html__('Dynamic Ecosystem: Custom Post Types', 'artitechcore') . '</h3>';
        
        echo '<div class="artitechcore-suggestion-grid">';
        foreach ($suggestions['custom_post_types'] as $cpt) {
            echo '<div class="artitechcore-suggestion-card is-selected" style="border-left: 4px solid var(--color-primary);">';
            echo '<div class="card-select-wrap"><input type="checkbox" name="artitechcore_selected_cpts[]" value="' . esc_attr(json_encode($cpt)) . '" class="artitechcore-cpt-checkbox" checked></div>';
            echo '<span class="card-icon">🏗️</span>';
            echo '<h4 class="card-title">' . esc_html($cpt['label']) . ' <code style="font-size: 11px; opacity: 0.7;">' . esc_html($cpt['name']) . '</code></h4>';
            echo '<p class="card-excerpt">' . esc_html($cpt['description']) . '</p>';
            
            if (!empty($cpt['custom_fields'])) {
                echo '<div class="card-meta" style="margin-top: 12px; gap: 4px;">';
                foreach (array_slice($cpt['custom_fields'], 0, 3) as $field) {
                    echo '<span class="neural-pill" style="font-size: 9px;">+' . esc_html($field['label']) . '</span>';
                }
                if (count($cpt['custom_fields']) > 3) {
                    echo '<span class="neural-pill" style="font-size: 9px;">+' . (count($cpt['custom_fields']) - 3) . ' more</span>';
                }
                echo '</div>';
            }
            
            echo '<div class="card-meta" style="margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(180, 124, 253, 0.1);">';
            echo '<span class="neural-pill" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2);">' . esc_html__('Advanced Entity', 'artitechcore') . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Display custom taxonomies
    if (!empty($suggestions['custom_taxonomies'])) {
        echo '<div class="artitechcore-cpts-section" style="margin-top: 48px;">';
        echo '<h3>🏷️ ' . esc_html__('Semantic Intelligence: Taxonomies', 'artitechcore') . '</h3>';
        
        echo '<div class="artitechcore-suggestion-grid">';
        foreach ($suggestions['custom_taxonomies'] as $tax) {
            echo '<div class="artitechcore-suggestion-card is-selected" style="border-left: 4px solid #6366F1; min-height: 150px;">';
            echo '<div class="card-select-wrap"><input type="checkbox" name="artitechcore_selected_taxonomies[]" value="' . esc_attr(json_encode($tax)) . '" class="artitechcore-cpt-checkbox" checked></div>';
            echo '<span class="card-icon">🏷️</span>';
            echo '<h4 class="card-title">' . esc_html($tax['plural_label']) . '</h4>';
            echo '<div class="card-meta">';
            echo '<span class="neural-pill">' . esc_html($tax['name']) . '</span>';
            if (!empty($tax['post_types'])) {
                echo '<span class="neural-pill" style="background: rgba(99, 102, 241, 0.1); color: #4F46E5;">' . esc_html(implode(', ', $tax['post_types'])) . '</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Add image generation checkbox
    $provider = get_option('artitechcore_ai_provider', 'openai');
    $is_deepseek = $provider === 'deepseek';
    
    echo '<div class="artitechcore-options dg10-card" style="margin-top: 48px; padding: 24px; background: rgba(180, 124, 253, 0.03); border: 1px dashed var(--color-primary);">';
    echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
    echo '<div>';
    echo '<h4 style="margin: 0 0 4px 0;">🖼️ ' . esc_html__('AI Visual Generation', 'artitechcore') . '</h4>';
    echo '<p style="margin: 0; font-size: 13px; color: var(--color-text-secondary);">' . esc_html__('Automatically create premium featured images for each new structure.', 'artitechcore') . '</p>';
    echo '</div>';
    echo '<label class="premium-switch">';
    echo '<input type="checkbox" name="artitechcore_generate_images" id="artitechcore_generate_images" value="1" ' . checked(true, !$is_deepseek, false) . '>';
    echo '<span class="slider round"></span>';
    echo '</label>';
    echo '</div>';
    
    if ($is_deepseek) {
        echo '<p class="dg10-form-help dg10-text-danger" style="margin-top: 12px; font-weight: 600;">⚠️ ' . esc_html__('DeepSeek does not currently support image generation.', 'artitechcore') . '</p>';
    }
    echo '</div>';

    echo '<div class="dg10-form-actions" style="margin-top: 48px; position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px; border-radius: var(--radius-md); backdrop-filter: blur(5px); display: flex; justify-content: center;">';
    echo '<button type="submit" class="dg10-btn dg10-btn-primary glow-btn" style="padding: 15px 40px; font-size: 16px;">';
    echo '<span class="nav-icon">🚀</span> ';
    echo esc_html__('Deploy Selected Strategy', 'artitechcore');
    echo '</button>';
    echo '</div>';

    echo '<div class="artitechcore-creation-status" style="display: none; margin-top: 24px;"></div>';
    echo '</form>';
    echo '</div>';
}

// Handle creation of advanced content (pages + custom post types)
if (isset($_POST['action']) && $_POST['action'] == 'create_advanced_content' && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_create_advanced_content')) {
    if (isset($_POST['artitechcore_selected_pages']) && is_array($_POST['artitechcore_selected_pages'])) {
        $selected_pages = array_map('sanitize_text_field', wp_unslash($_POST['artitechcore_selected_pages']));
        $selected_cpts = isset($_POST['artitechcore_selected_cpts']) ? array_map('sanitize_text_field', wp_unslash($_POST['artitechcore_selected_cpts'])) : [];
        $selected_taxonomies = isset($_POST['artitechcore_selected_taxonomies']) ? array_map('sanitize_text_field', wp_unslash($_POST['artitechcore_selected_taxonomies'])) : [];
        $generate_images = isset($_POST['artitechcore_generate_images']) && $_POST['artitechcore_generate_images'] == '1';
        artitechcore_create_advanced_content($selected_pages, $selected_cpts, $generate_images, $selected_taxonomies);
    }
}

// Create advanced content (pages + custom post types)
function artitechcore_create_advanced_content($pages, $custom_post_types, $generate_images = false, $custom_taxonomies = []) {
    set_time_limit(0); // Prevent timeouts for complex generation logic
    error_log('ArtitechCore: Starting artitechcore_create_advanced_content');
    $created_pages = 0;
    $created_cpts = 0;
    $parent_id_stack = [];

    // Create standard pages
    error_log('ArtitechCore: Processing ' . count($pages) . ' pages');
    foreach ($pages as $index => $page_data) {
        // Handle both JSON strings and already decoded arrays
        if (is_string($page_data)) {
            $page = json_decode($page_data, true);
        } else {
            $page = $page_data;
        }
        if (!$page || !is_array($page)) {
            error_log("ArtitechCore: Invalid page data at index $index");
            continue;
        }

        $page_title = $page['title'];
        error_log("ArtitechCore: Creating page: $page_title");
        
        $meta_description = $page['meta_description'];
        $hierarchy_level = isset($page['hierarchy_level']) ? $page['hierarchy_level'] : 0;

        $parent_id = ($hierarchy_level > 0 && isset($parent_id_stack[$hierarchy_level - 1])) ? $parent_id_stack[$hierarchy_level - 1] : 0;

        // Generate SEO-optimized slug
        $post_name = artitechcore_generate_seo_slug($page_title);
        
        $new_page = array(
            'post_title'   => $page_title,
            'post_name'    => $post_name,
            'post_content' => '',
            'post_status'  => 'draft',
            'post_type'    => 'page',
            'post_parent'  => $parent_id,
            'post_excerpt' => $meta_description,
        );
        $page_id = wp_insert_post($new_page);

        if ($page_id) {
            $created_pages++;
            error_log("ArtitechCore: Page created (ID: $page_id)");
            
            // Generate and set featured image if enabled
            if ($generate_images) {
                error_log("ArtitechCore: Generating image for page $page_id");
                artitechcore_generate_and_set_featured_image($page_id, $page_title);
            }
            
            // Generate schema markup for the new page
            $auto_generate = get_option('artitechcore_auto_schema_generation', true);
            if ($auto_generate) {
                error_log("ArtitechCore: Generating schema for page $page_id");
                // Pass false to disable expensive AI analysis during bulk creation
                artitechcore_generate_schema_markup($page_id, false);
            }
            
            $parent_id_stack[$hierarchy_level] = $page_id;
            $parent_id_stack = array_slice($parent_id_stack, 0, $hierarchy_level + 1);
        } else {
            error_log("ArtitechCore: Failed to insert page: $page_title");
        }
    }

    // Create custom taxonomies FIRST (so CPTs can link to them)
    if (!empty($custom_taxonomies)) {
        error_log('ArtitechCore: Processing ' . count($custom_taxonomies) . ' custom taxonomies');
        $existing_taxonomies = get_option('artitechcore_dynamic_taxonomies', array());
        $updated_taxonomies = false;

        foreach ($custom_taxonomies as $index => $tax_data) {
             if (is_string($tax_data)) {
                $tax = json_decode($tax_data, true);
            } else {
                $tax = $tax_data;
            }

            if (!$tax || !is_array($tax) || empty($tax['name'])) {
                continue;
            }

            $slug = sanitize_key($tax['name']);
            // Avoid reserved terms
             if (taxonomy_exists($slug) && !isset($existing_taxonomies[$slug])) {
                 // Try to make unique if conflict
                 $slug = $slug . '_custom';
                 $tax['name'] = $slug;
             }

            $existing_taxonomies[$slug] = $tax;
            $updated_taxonomies = true;

            // Register immediately
            if (function_exists('artitechcore_register_dynamic_taxonomy')) {
                artitechcore_register_dynamic_taxonomy($tax);
            }
        }

        if ($updated_taxonomies) {
            update_option('artitechcore_dynamic_taxonomies', $existing_taxonomies);
            // Flush rules
            update_option('artitechcore_flush_rewrite_rules', true);
             error_log("ArtitechCore: Taxonomies saved and registered.");
        }
    }

    // Create custom post types (linked to taxonomies)
    error_log('ArtitechCore: Processing ' . count($custom_post_types) . ' custom post types');
    foreach ($custom_post_types as $index => $cpt_data) {
        // Handle both JSON strings and already decoded arrays
        if (is_string($cpt_data)) {
            $cpt = json_decode($cpt_data, true);
        } else {
            $cpt = $cpt_data;
        }
        if (!$cpt || !is_array($cpt)) {
            error_log("ArtitechCore: Invalid CPT data at index $index");
            continue;
        }
        
        error_log("ArtitechCore: Registering CPT: " . ($cpt['name'] ?? 'unknown'));

        if (artitechcore_register_dynamic_custom_post_type($cpt)) {
            $created_cpts++;
            error_log("ArtitechCore: CPT registered successfully");
            
            // Create sample entries if specified
            if (!empty($cpt['sample_entries'])) {
                error_log("ArtitechCore: Creating sample entries for CPT");
                artitechcore_create_sample_cpt_entries($cpt);
            }
        } else {
            error_log("ArtitechCore: Failed to register CPT");
        }
    }

    error_log("ArtitechCore: Finished content creation. Pages: $created_pages, CPTs: $created_cpts");

    // Display success message
    $message_parts = [];
    if ($created_pages > 0) {
        $message_parts[] = sprintf('%d pages created successfully as drafts.', $created_pages);
    }
    if ($created_cpts > 0) {
        $message_parts[] = sprintf('%d custom post types registered successfully.', $created_cpts);
    }
    if ($generate_images && $created_pages > 0) {
        $message_parts[] = 'Featured images generated with AI.';
    }

    if (!empty($message_parts)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . implode(' ', $message_parts) . '</p></div>';
    }
}

// Note: artitechcore_register_dynamic_custom_post_type function has been moved to custom-post-type-manager.php
// This ensures proper integration between AI generation and CPT management

// Note: artitechcore_register_custom_fields function has been moved to custom-post-type-manager.php
// This ensures proper integration and eliminates code duplication

// Note: Custom field rendering has been moved to custom-post-type-manager.php
// The new implementation includes enhanced security, accessibility, and more field types

// Create sample entries for custom post types
function artitechcore_create_sample_cpt_entries($cpt_data) {
    $post_type = sanitize_key($cpt_data['name']);
    
    foreach ($cpt_data['sample_entries'] as $entry) {
        $post_data = array(
            'post_title' => sanitize_text_field($entry['title']),
            'post_content' => sanitize_textarea_field($entry['content']),
            'post_status' => 'draft',
            'post_type' => $post_type,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !empty($cpt_data['custom_fields'])) {
            // Set sample values for custom fields
            foreach ($cpt_data['custom_fields'] as $field) {
                $sample_value = artitechcore_generate_sample_field_value($field);
                update_post_meta($post_id, $field['name'], $sample_value);
            }
        }
    }
}

// Generate sample values for custom fields
function artitechcore_generate_sample_field_value($field) {
    switch ($field['type']) {
        case 'number':
            return rand(1, 100);
        case 'date':
            return date('Y-m-d');
        case 'url':
            return 'https://example.com';
        case 'image':
            return 'https://via.placeholder.com/400x300';
        case 'textarea':
            return 'This is a sample ' . strtolower($field['label']) . ' entry.';
        default:
            return 'Sample ' . $field['label'];
    }
}
