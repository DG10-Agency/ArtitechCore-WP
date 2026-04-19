<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function artitechcore_extract_first_email($text) {
    if (!is_string($text) || $text === '') {
        return '';
    }
    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
        $email = sanitize_email($m[0]);
        return is_email($email) ? $email : '';
    }
    return '';
}

function artitechcore_extract_first_phone($text) {
    if (!is_string($text) || $text === '') {
        return '';
    }
    // Prefer tel: links if present
    if (preg_match('/tel:\s*([0-9+\-\(\)\s\.]{7,})/i', $text, $m)) {
        return trim($m[1]);
    }
    // General phone patterns (more flexible than US-only)
    if (preg_match('/(?:\+?\d{1,3}[\s\-\.]?)?(?:\(?\d{2,4}\)?[\s\-\.]?)?\d{3,4}[\s\-\.]?\d{3,4}/', $text, $m)) {
        $phone = trim($m[0]);
        // Avoid matching years/short numbers
        return (strlen(preg_replace('/\D+/', '', $phone)) >= 7) ? $phone : '';
    }
    return '';
}

function artitechcore_extract_possible_address($text) {
    if (!is_string($text) || $text === '') {
        return '';
    }
    $text = preg_replace('/\s+/', ' ', trim($text));

    // Common street suffixes; not country-specific but helps a lot for footers
    $street_suffix = '(Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Drive|Dr|Lane|Ln|Court|Ct|Way|Parkway|Pkwy|Place|Pl|Terrace|Ter|Circle|Cir|Highway|Hwy)';

    // Example: "123 Main St, City, State 12345" (state/zip optional)
    $pattern = '/\b\d{1,6}\s+[^,]{3,60}\s+' . $street_suffix . '\b(?:[^,]{0,40})?(?:,\s*[^,]{2,40}){1,3}\b/i';
    if (preg_match($pattern, $text, $m)) {
        return trim($m[0]);
    }

    // Fallback: "Address: ..." patterns
    if (preg_match('/\b(address|location)\b\s*[:\-]\s*([^|]{10,120})/i', $text, $m)) {
        return trim($m[2]);
    }

    return '';
}

function artitechcore_scan_widget_areas_for_contact_info(&$detected) {
    if (!function_exists('wp_get_sidebars_widgets')) {
        return;
    }

    $sidebars = wp_get_sidebars_widgets();
    if (!is_array($sidebars) || empty($sidebars)) {
        return;
    }

    $widget_text_blobs = [];
    foreach ($sidebars as $sidebar_id => $widget_ids) {
        if (!is_array($widget_ids) || empty($widget_ids) || $sidebar_id === 'wp_inactive_widgets') {
            continue;
        }

        foreach ($widget_ids as $widget_id) {
            if (!is_string($widget_id) || strpos($widget_id, '-') === false) {
                continue;
            }
            [$base, $num] = array_pad(explode('-', $widget_id, 2), 2, '');
            $num = absint($num);
            if (!$base || !$num) {
                continue;
            }

            $opt = get_option('widget_' . $base, []);
            if (!is_array($opt) || empty($opt[$num])) {
                continue;
            }

            $instance = $opt[$num];
            if (!is_array($instance)) {
                continue;
            }

            // Common fields in text/custom_html widgets
            foreach (['text', 'content', 'title'] as $key) {
                if (!empty($instance[$key]) && is_string($instance[$key])) {
                    $widget_text_blobs[] = $instance[$key];
                }
            }
        }
    }

    if (empty($widget_text_blobs)) {
        return;
    }

    $combined = implode("\n\n", $widget_text_blobs);

    // Try to capture mailto / tel raw values too
    if (empty($detected['email'])) {
        $mailto = '';
        if (preg_match('/mailto:\s*([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $combined, $m)) {
            $mailto = sanitize_email($m[1]);
            if (is_email($mailto)) {
                $detected['email'] = $mailto;
            }
        }
        if (empty($detected['email'])) {
            $detected['email'] = artitechcore_extract_first_email(wp_strip_all_tags($combined));
        }
    }

    if (empty($detected['phone'])) {
        $detected['phone'] = artitechcore_extract_first_phone($combined);
        if (empty($detected['phone'])) {
            $detected['phone'] = artitechcore_extract_first_phone(wp_strip_all_tags($combined));
        }
    }

    if (empty($detected['address'])) {
        $detected['address'] = artitechcore_extract_possible_address(wp_strip_all_tags($combined));
    }
}

/**
 * Auto-detect business information from WordPress
 * Scans WP core settings, pages, WooCommerce, and SEO plugins
 * @return array Detected business information
 */
function artitechcore_auto_detect_business_info() {
    $detected = [
        'name' => '',
        'description' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'facebook' => '',
        'twitter' => '',
        'linkedin' => '',
    ];

    // 1. WordPress Core
    $detected['name'] = get_bloginfo('name');
    $detected['description'] = get_bloginfo('description');
    $detected['email'] = get_option('admin_email', '');

    // 2. Check WooCommerce settings if active
    if (class_exists('WooCommerce')) {
        $wc_address = get_option('woocommerce_store_address', '');
        $wc_address_2 = get_option('woocommerce_store_address_2', '');
        $wc_city = get_option('woocommerce_store_city', '');
        $wc_postcode = get_option('woocommerce_store_postcode', '');
        
        $address_parts = array_filter([$wc_address, $wc_address_2, $wc_city, $wc_postcode]);
        if (!empty($address_parts)) {
            $detected['address'] = implode(', ', $address_parts);
        }
    }

    // 3. Check Yoast SEO if active
    if (defined('WPSEO_VERSION')) {
        $yoast_social = get_option('wpseo_social', []);
        if (!empty($yoast_social['facebook_site'])) {
            $detected['facebook'] = $yoast_social['facebook_site'];
        }
        if (!empty($yoast_social['twitter_site'])) {
            $detected['twitter'] = 'https://twitter.com/' . ltrim($yoast_social['twitter_site'], '@');
        }
        if (!empty($yoast_social['linkedin_url'])) {
            $detected['linkedin'] = $yoast_social['linkedin_url'];
        }
        // Yoast company name
        $yoast_titles = get_option('wpseo_titles', []);
        if (!empty($yoast_titles['company_name'])) {
            $detected['name'] = $yoast_titles['company_name'];
        }
    }

    // 4. Check RankMath SEO if active
    if (class_exists('RankMath')) {
        $rm_local = get_option('rank-math-options-titles', []);
        if (!empty($rm_local['local_business_name'])) {
            $detected['name'] = $rm_local['local_business_name'];
        }
        if (!empty($rm_local['phone'])) {
            $detected['phone'] = $rm_local['phone'];
        }
        if (!empty($rm_local['local_address'])) {
            $detected['address'] = $rm_local['local_address'];
        }
    }

    // 5. Scan Contact/About pages for phone, email, address
    $pages_to_scan = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 20,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    foreach ($pages_to_scan as $page) {
        $title_lower = strtolower($page->post_title);
        $slug_lower = strtolower($page->post_name);
        
        // Look for contact or about pages
        if (strpos($title_lower, 'contact') !== false || strpos($slug_lower, 'contact') !== false ||
            strpos($title_lower, 'about') !== false || strpos($slug_lower, 'about') !== false) {
            
            $content = wp_strip_all_tags($page->post_content);
            
            // Extract phone (if not already found)
            if (empty($detected['phone'])) {
                $detected['phone'] = artitechcore_extract_first_phone($content);
            }
            
            // Extract address patterns (if not already found)
            if (empty($detected['address'])) {
                $detected['address'] = artitechcore_extract_possible_address($content);
            }

            // Extract email (prefer actual content over admin_email)
            if (empty($detected['email']) || $detected['email'] === get_option('admin_email', '')) {
                $found_email = artitechcore_extract_first_email($content);
                if (!empty($found_email)) {
                    $detected['email'] = $found_email;
                }
            }
            
            // Extract better description from About page
            if ((strpos($title_lower, 'about') !== false || strpos($slug_lower, 'about') !== false) && empty($detected['description'])) {
                $excerpt = wp_trim_words($content, 50, '...');
                if (strlen($excerpt) > 50) {
                    $detected['description'] = $excerpt;
                }
            }
        }
    }

    // 6. Scan widget areas (common place for footer contact blocks)
    artitechcore_scan_widget_areas_for_contact_info($detected);

    // 6. Scan menus for social links
    $nav_menus = wp_get_nav_menus();
    foreach ($nav_menus as $menu) {
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        if ($menu_items) {
            foreach ($menu_items as $item) {
                $url = strtolower($item->url);
                if (empty($detected['facebook']) && strpos($url, 'facebook.com') !== false) {
                    $detected['facebook'] = $item->url;
                }
                if (empty($detected['twitter']) && (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false)) {
                    $detected['twitter'] = $item->url;
                }
                if (empty($detected['linkedin']) && strpos($url, 'linkedin.com') !== false) {
                    $detected['linkedin'] = $item->url;
                }
            }
        }
    }

    return $detected;
}

/**
 * AJAX handler for re-scanning website
 */
function artitechcore_ajax_rescan_business_info() {
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $detected = artitechcore_auto_detect_business_info();
    
    // Save detected values to options
    // Save detected values to options with sanitization
    update_option('artitechcore_business_name', sanitize_text_field($detected['name']));
    update_option('artitechcore_business_description', sanitize_textarea_field($detected['description']));
    update_option('artitechcore_business_address', sanitize_text_field($detected['address']));
    update_option('artitechcore_business_phone', sanitize_text_field($detected['phone']));
    update_option('artitechcore_business_email', sanitize_email($detected['email']));
    update_option('artitechcore_business_social_facebook', esc_url_raw($detected['facebook']));
    update_option('artitechcore_business_social_twitter', esc_url_raw($detected['twitter']));
    update_option('artitechcore_business_social_linkedin', esc_url_raw($detected['linkedin']));
    
    wp_send_json_success([
        'message' => __('Business information detected and saved!', 'artitechcore'),
        'data' => $detected
    ]);
}
add_action('wp_ajax_artitechcore_rescan_business_info', 'artitechcore_ajax_rescan_business_info');

/**
 * Handle manual database cleanup AJAX
 */
function artitechcore_ajax_manual_db_cleanup() {
    check_ajax_referer('artitechcore_maintenance_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'artitechcore')]);
    }

    global $wpdb;
    
    // 1. Delete all rate limit transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_artitechcore_ai_rate_limit_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_artitechcore_ai_rate_limit_%'");
    
    // 2. Delete stale generation statuses
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_artitechcore_task_status_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_artitechcore_task_status_%'");
    
    wp_send_json_success(['message' => __('Database hygiene routine completed successfully.', 'artitechcore')]);
}
add_action('wp_ajax_artitechcore_manual_db_cleanup', 'artitechcore_ajax_manual_db_cleanup');

/**
 * Handle AI Connection Test AJAX
 */
function artitechcore_ajax_test_ai_connection() {
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'artitechcore')]);
    }

    $provider = isset($_POST['provider']) ? sanitize_key($_POST['provider']) : '';
    $api_key = '';

    switch ($provider) {
        case 'openai':
            $api_key = get_option('artitechcore_openai_api_key');
            $url = 'https://api.openai.com/v1/models';
            break;
        case 'gemini':
            $api_key = get_option('artitechcore_gemini_api_key');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
            break;
        case 'deepseek':
            $api_key = get_option('artitechcore_deepseek_api_key');
            $url = 'https://api.deepseek.com/models';
            break;
        default:
            wp_send_json_error(['message' => __('Invalid provider.', 'artitechcore')]);
    }

    if (empty($api_key)) {
        wp_send_json_error(['message' => __('API key is missing.', 'artitechcore')]);
    }

    $args = [
        'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
        ]
    ];
    
    if ($provider === 'gemini') {
        $args['headers'] = []; // Gemini uses API key in URL
    }

    $response = artitechcore_safe_ai_remote_get($url, $args, $provider);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        wp_send_json_success(['message' => __('Connection Successful!', 'artitechcore')]);
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $msg = isset($body['error']['message']) ? $body['error']['message'] : wp_remote_retrieve_response_message($response);
        wp_send_json_error(['message' => sprintf(__('Error %d: %s', 'artitechcore'), $code, $msg)]);
    }
}
add_action('wp_ajax_artitechcore_test_ai_connection', 'artitechcore_ajax_test_ai_connection');

/**
 * Auto-detect on plugin activation (if settings are empty)
 */
function artitechcore_maybe_auto_detect_on_load() {
    // Only run once if business name is empty
    if (empty(get_option('artitechcore_business_name', ''))) {
        $detected = artitechcore_auto_detect_business_info();
        
        // Only save if we actually detected something (with sanitization)
        if (!empty($detected['name'])) {
            update_option('artitechcore_business_name', sanitize_text_field($detected['name']));
        }
        if (!empty($detected['description'])) {
            update_option('artitechcore_business_description', sanitize_textarea_field($detected['description']));
        }
        if (!empty($detected['address'])) {
            update_option('artitechcore_business_address', sanitize_text_field($detected['address']));
        }
        if (!empty($detected['phone'])) {
            update_option('artitechcore_business_phone', sanitize_text_field($detected['phone']));
        }
        if (!empty($detected['email'])) {
            update_option('artitechcore_business_email', sanitize_email($detected['email']));
        }
        if (!empty($detected['facebook'])) {
            update_option('artitechcore_business_social_facebook', esc_url_raw($detected['facebook']));
        }
        if (!empty($detected['twitter'])) {
            update_option('artitechcore_business_social_twitter', esc_url_raw($detected['twitter']));
        }
        if (!empty($detected['linkedin'])) {
            update_option('artitechcore_business_social_linkedin', esc_url_raw($detected['linkedin']));
        }
    }
}
add_action('admin_init', 'artitechcore_maybe_auto_detect_on_load');


// Register settings with sanitization callbacks
function artitechcore_register_settings() {
    register_setting('artitechcore_settings_group', 'artitechcore_ai_provider', 'sanitize_key');
    register_setting('artitechcore_settings_group', 'artitechcore_openai_api_key', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_gemini_api_key', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_deepseek_api_key', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_brand_color', 'sanitize_hex_color');
    register_setting('artitechcore_settings_group', 'artitechcore_sitemap_url', 'esc_url_raw');
    register_setting('artitechcore_settings_group', 'artitechcore_ai_rate_limit', 'absint');
    register_setting('artitechcore_settings_group', 'artitechcore_auto_schema_generation', 'absint');
    register_setting('artitechcore_settings_group', 'artitechcore_persist_on_uninstall', 'absint');
    
    // Content Enhancer Settings
    register_setting('artitechcore_settings_group', 'artitechcore_ce_enabled', 'absint');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_post_types', 'artitechcore_sanitize_array');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_persist_features', 'artitechcore_sanitize_array');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_kt_heading', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_conclusion_heading', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_cta_mode', 'sanitize_key');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_cta_shortcode', 'wp_kses_post');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_cta_native_fields', 'artitechcore_sanitize_array');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_cta_native_email', 'sanitize_email');
    register_setting('artitechcore_settings_group', 'artitechcore_ce_cta_native_button', 'sanitize_text_field');

    // Business Information Settings
    register_setting('artitechcore_settings_group', 'artitechcore_business_name', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_business_description', 'sanitize_textarea_field');
    register_setting('artitechcore_settings_group', 'artitechcore_business_address', 'sanitize_textarea_field');
    register_setting('artitechcore_settings_group', 'artitechcore_business_phone', 'sanitize_text_field');
    register_setting('artitechcore_settings_group', 'artitechcore_business_email', 'sanitize_email');
    register_setting('artitechcore_settings_group', 'artitechcore_business_social_facebook', 'esc_url_raw');
    register_setting('artitechcore_settings_group', 'artitechcore_business_social_twitter', 'esc_url_raw');
    register_setting('artitechcore_settings_group', 'artitechcore_business_social_linkedin', 'esc_url_raw');

    // Brand Kit Settings
    register_setting('artitechcore_settings_group', 'artitechcore_brand_kit', 'artitechcore_sanitize_brand_kit');
}
add_action('admin_init', 'artitechcore_register_settings');

// Settings tab content
function artitechcore_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields('artitechcore_settings_group');
        do_settings_sections('artitechcore-main');
        submit_button();
        ?>
    </form>
    <?php
}

// Add settings section and fields
function artitechcore_settings_init() {
    add_settings_section(
        'artitechcore_settings_section',
        __('AI Settings', 'artitechcore'),
        'artitechcore_settings_section_callback',
        'artitechcore-main'
    );

    add_settings_field(
        'artitechcore_ai_provider',
        __('AI Provider', 'artitechcore'),
        'artitechcore_ai_provider_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_openai_api_key',
        __('OpenAI API Key', 'artitechcore'),
        'artitechcore_openai_api_key_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_gemini_api_key',
        __('Gemini API Key', 'artitechcore'),
        'artitechcore_gemini_api_key_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_deepseek_api_key',
        __('DeepSeek API Key', 'artitechcore'),
        'artitechcore_deepseek_api_key_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_ai_rate_limit',
        __('AI Rate Limit', 'artitechcore'),
        'artitechcore_ai_rate_limit_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_brand_color',
        __('Brand Color', 'artitechcore'),
        'artitechcore_brand_color_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    add_settings_field(
        'artitechcore_sitemap_url',
        __('Sitemap URL', 'artitechcore'),
        'artitechcore_sitemap_url_callback',
        'artitechcore-main',
        'artitechcore_settings_section'
    );

    // Brand Kit Section (for AI Website Builder)
    add_settings_section(
        'artitechcore_brand_kit_section',
        __('Brand Kit (AI Website Builder)', 'artitechcore'),
        'artitechcore_brand_kit_section_callback',
        'artitechcore-main'
    );

    add_settings_field(
        'artitechcore_brand_kit_auto_detect',
        '',
        'artitechcore_brand_kit_auto_detect_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_name',
        __('Brand Name', 'artitechcore'),
        'artitechcore_brand_name_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_tagline',
        __('Tagline / Headline', 'artitechcore'),
        'artitechcore_brand_tagline_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_description',
        __('Brand Description', 'artitechcore'),
        'artitechcore_brand_description_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_colors',
        __('Brand Colors', 'artitechcore'),
        'artitechcore_brand_colors_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_typography',
        __('Typography', 'artitechcore'),
        'artitechcore_brand_typography_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_brand_voice',
        __('Brand Voice', 'artitechcore'),
        'artitechcore_brand_voice_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_design_aesthetic',
        __('Design Aesthetic', 'artitechcore'),
        'artitechcore_design_aesthetic_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    add_settings_field(
        'artitechcore_image_style',
        __('Image Style', 'artitechcore'),
        'artitechcore_image_style_callback',
        'artitechcore-main',
        'artitechcore_brand_kit_section'
    );

    // Schema settings section
    add_settings_section(
        'artitechcore_schema_settings_section',
        __('Schema Settings', 'artitechcore'),
        'artitechcore_schema_settings_section_callback',
        'artitechcore-main'
    );

    add_settings_field(
        'artitechcore_auto_schema_generation',
        __('Auto Schema Generation', 'artitechcore'),
        'artitechcore_auto_schema_generation_callback',
        'artitechcore-main',
        'artitechcore_schema_settings_section'
    );
    
    add_settings_field(
        'artitechcore_persist_on_uninstall',
        __('Persistence on Uninstall', 'artitechcore'),
        'artitechcore_persist_on_uninstall_callback',
        'artitechcore-main',
        'artitechcore_schema_settings_section'
    );

    // Maintenance Section
    add_settings_section(
        'artitechcore_maintenance_section',
        __('Maintenance & Production Hardening', 'artitechcore'),
        'artitechcore_maintenance_section_callback',
        'artitechcore-main'
    );

    add_settings_field(
        'artitechcore_db_maintenance',
        __('Database Hygiene', 'artitechcore'),
        'artitechcore_db_maintenance_callback',
        'artitechcore-main',
        'artitechcore_maintenance_section'
    );

    // Business Information Section
    add_settings_section(
        'artitechcore_business_settings_section',
        __('Business Information (Knowledge Base)', 'artitechcore'),
        'artitechcore_business_settings_section_callback',
        'artitechcore-main'
    );

    add_settings_field('artitechcore_business_name', __('Business Name', 'artitechcore'), 'artitechcore_business_name_callback', 'artitechcore-main', 'artitechcore_business_settings_section');
    add_settings_field('artitechcore_business_description', __('Business Description / Knowledge', 'artitechcore'), 'artitechcore_business_description_callback', 'artitechcore-main', 'artitechcore_business_settings_section');
    add_settings_field('artitechcore_business_address', __('Business Address', 'artitechcore'), 'artitechcore_business_address_callback', 'artitechcore-main', 'artitechcore_business_settings_section');
    add_settings_field('artitechcore_business_phone', __('Phone Number', 'artitechcore'), 'artitechcore_business_phone_callback', 'artitechcore-main', 'artitechcore_business_settings_section');
    add_settings_field('artitechcore_business_email', __('Email Address', 'artitechcore'), 'artitechcore_business_email_callback', 'artitechcore-main', 'artitechcore_business_settings_section');
    add_settings_field('artitechcore_business_social', __('Social Media Links', 'artitechcore'), 'artitechcore_business_social_callback', 'artitechcore-main', 'artitechcore_business_settings_section');

    // Content Enhancer Section
    add_settings_section(
        'artitechcore_ce_settings_section',
        __('AI Content Enhancer & CTA', 'artitechcore'),
        'artitechcore_ce_settings_section_callback',
        'artitechcore-main'
    );

    add_settings_field('artitechcore_ce_enabled', __('Enable Content Enhancer', 'artitechcore'), 'artitechcore_ce_enabled_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_post_types', __('Supported Post Types', 'artitechcore'), 'artitechcore_ce_post_types_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_persist_features', __('Persist Features on Uninstall', 'artitechcore'), 'artitechcore_ce_persist_features_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_kt_heading', __('Key Takeaways Heading', 'artitechcore'), 'artitechcore_ce_kt_heading_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_conclusion_heading', __('Conclusion Heading', 'artitechcore'), 'artitechcore_ce_conclusion_heading_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_cta_mode', __('CTA Form Mode', 'artitechcore'), 'artitechcore_ce_cta_mode_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_cta_shortcode', __('Global CTA Shortcode', 'artitechcore'), 'artitechcore_ce_cta_shortcode_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
    add_settings_field('artitechcore_ce_cta_native', __('Native CTA Configuration', 'artitechcore'), 'artitechcore_ce_cta_native_callback', 'artitechcore-main', 'artitechcore_ce_settings_section');
}
add_action('admin_init', 'artitechcore_settings_init');

// Array Sanitizer
function artitechcore_sanitize_array($input) {
    return is_array($input) ? array_map('sanitize_text_field', $input) : [];
}

// Section callback
function artitechcore_settings_section_callback() {
    echo '<p>' . esc_html__('Select your preferred AI provider and enter the corresponding API key. Set your brand color for AI-generated featured images and configure the sitemap URL for menu generation.', 'artitechcore') . '</p>';
}

// AI Provider field callback
function artitechcore_ai_provider_callback() {
    $provider = get_option('artitechcore_ai_provider', 'openai');
    ?>
    <select name="artitechcore_ai_provider" class="artitechcore-ai-provider-select">
        <option value="openai" <?php selected($provider, 'openai'); ?>>🤖 OpenAI (GPT-4)</option>
        <option value="gemini" <?php selected($provider, 'gemini'); ?>>🧠 Google Gemini</option>
        <option value="deepseek" <?php selected($provider, 'deepseek'); ?>>⚡ DeepSeek</option>
    </select>
    <p class="description"><?php esc_html_e('Choose your preferred AI provider. Each has different strengths and pricing models. We Strictly recommend using OpenAI for best results.', 'artitechcore'); ?></p>
    <?php
}

// OpenAI API Key field callback
function artitechcore_openai_api_key_callback() {
    $api_key = get_option('artitechcore_openai_api_key');
    echo '<input type="password" name="artitechcore_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
    echo ' <button type="button" class="button artitechcore-test-conn" data-provider="openai">' . __('Test OpenAI', 'artitechcore') . '</button>';
}

// Gemini API Key field callback
function artitechcore_gemini_api_key_callback() {
    $api_key = get_option('artitechcore_gemini_api_key');
    echo '<input type="password" name="artitechcore_gemini_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
    echo ' <button type="button" class="button artitechcore-test-conn" data-provider="gemini">' . __('Test Gemini', 'artitechcore') . '</button>';
}

// DeepSeek API Key field callback
function artitechcore_deepseek_api_key_callback() {
    $api_key = get_option('artitechcore_deepseek_api_key');
    echo '<input type="password" name="artitechcore_deepseek_api_key" value="' . esc_attr($api_key) . '" class="regular-text" autocomplete="off">';
    echo ' <button type="button" class="button artitechcore-test-conn" data-provider="deepseek">' . __('Test DeepSeek', 'artitechcore') . '</button>';
    echo '<div class="artitechcore-test-status" style="margin-top: 5px;"></div>';
}

// AI Rate Limit field callback
function artitechcore_ai_rate_limit_callback() {
    $value = get_option('artitechcore_ai_rate_limit', 20);
    ?>
    <input type="number" name="artitechcore_ai_rate_limit" value="<?php echo esc_attr($value); ?>" class="small-text" min="1" max="100">
    <p class="description"><?php esc_html_e('Maximum number of AI requests allowed per minute per user. Helps prevent API abuse and unexpected costs.', 'artitechcore'); ?></p>
    <?php
}

// Brand Color field callback
function artitechcore_brand_color_callback() {
    $brand_color = get_option('artitechcore_brand_color', '#b47cfd');
    ?>
    <input type="color" name="artitechcore_brand_color" value="<?php echo esc_attr($brand_color); ?>" class="regular-text">
    <p class="description"><?php esc_html_e('Select your brand\'s primary color. This will be used for AI-generated featured images.', 'artitechcore'); ?></p>
    <?php
}

// Sitemap URL field callback
function artitechcore_sitemap_url_callback() {
    $sitemap_url = get_option('artitechcore_sitemap_url', '');
    ?>
    <input type="url" name="artitechcore_sitemap_url" value="<?php echo esc_attr($sitemap_url); ?>" class="regular-text" placeholder="https://yoursite.com/sitemap.xml">
    <p class="description"><?php esc_html_e('Enter the URL of your sitemap page. This will be used in the universal bottom menu.', 'artitechcore'); ?></p>
    <?php
}

// Schema settings section callback
function artitechcore_schema_settings_section_callback() {
    echo '<p>' . esc_html__('Configure schema.org markup generation settings for your pages.', 'artitechcore') . '</p>';
}

// Auto Schema Generation field callback
function artitechcore_auto_schema_generation_callback() {
    $auto_generate = get_option('artitechcore_auto_schema_generation', true);
    ?>
    <label>
        <input type="checkbox" name="artitechcore_auto_schema_generation" value="1" <?php checked($auto_generate, true); ?>>
        <?php esc_html_e('Automatically generate schema markup when pages are created or updated', 'artitechcore'); ?>
    </label>
    <p class="description"><?php esc_html_e('When enabled, schema markup will be automatically generated for all pages when they are saved.', 'artitechcore'); ?></p>
    <?php
}

// Persistence on Uninstall field callback
function artitechcore_persist_on_uninstall_callback() {
    $persist = get_option('artitechcore_persist_on_uninstall', 0);
    ?>
    <label>
        <input type="checkbox" name="artitechcore_persist_on_uninstall" value="1" <?php checked($persist, true); ?>>
        <strong><?php esc_html_e('Keep Schema functional after plugin removal', 'artitechcore'); ?></strong>
    </label>
    <p class="description"><?php esc_html_e('If enabled, a tiny bridge file will be created in wp-content/mu-plugins/ to ensure your schemas continue to work even if you delete this plugin.', 'artitechcore'); ?></p>
    <?php
}

// Business Information Section Callback - with Re-Scan button
function artitechcore_business_settings_section_callback() {
    ?>
    <p><?php esc_html_e('Your business details are auto-detected from WordPress. This information is used to generate accurate, context-aware schema markup.', 'artitechcore'); ?></p>
    <p>
        <button type="button" id="artitechcore-rescan-btn" class="button button-secondary">
            🔍 <?php esc_html_e('Re-Scan Website', 'artitechcore'); ?>
        </button>
        <span id="artitechcore-rescan-status" style="margin-left: 10px;"></span>
    </p>
    <?php
}


// Business Name Callback
function artitechcore_business_name_callback() {
    $value = get_option('artitechcore_business_name', get_bloginfo('name'));
    ?>
    <input type="text" name="artitechcore_business_name" value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description"><?php esc_html_e('Your official business or organization name.', 'artitechcore'); ?></p>
    <?php
}

// Business Description / Knowledge Callback
function artitechcore_business_description_callback() {
    $value = get_option('artitechcore_business_description', '');
    ?>
    <textarea name="artitechcore_business_description" rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
    <p class="description"><?php esc_html_e('A detailed description of your business, services, products, and unique value proposition. The AI uses this as a knowledge base to generate more accurate schema.', 'artitechcore'); ?></p>
    <?php
}

// Business Address Callback
function artitechcore_business_address_callback() {
    $value = get_option('artitechcore_business_address', '');
    ?>
    <textarea name="artitechcore_business_address" rows="3" class="large-text" placeholder="123 Business Street, City, State, ZIP"><?php echo esc_textarea($value); ?></textarea>
    <p class="description"><?php esc_html_e('Your physical business address (used for LocalBusiness schema).', 'artitechcore'); ?></p>
    <?php
}

// Business Phone Callback
function artitechcore_business_phone_callback() {
    $value = get_option('artitechcore_business_phone', '');
    ?>
    <input type="tel" name="artitechcore_business_phone" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="+1-555-123-4567">
    <p class="description"><?php esc_html_e('Your business phone number.', 'artitechcore'); ?></p>
    <?php
}

// Business Email Callback
function artitechcore_business_email_callback() {
    $value = get_option('artitechcore_business_email', get_option('admin_email'));
    ?>
    <input type="email" name="artitechcore_business_email" value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description"><?php esc_html_e('Your primary business email address.', 'artitechcore'); ?></p>
    <?php
}

// Business Social Media Callback
function artitechcore_business_social_callback() {
    $facebook = get_option('artitechcore_business_social_facebook', '');
    $twitter = get_option('artitechcore_business_social_twitter', '');
    $linkedin = get_option('artitechcore_business_social_linkedin', '');
    ?>
    <p><label>Facebook: <input type="url" name="artitechcore_business_social_facebook" value="<?php echo esc_attr($facebook); ?>" class="regular-text" placeholder="https://facebook.com/yourpage"></label></p>
    <p><label>Twitter/X: <input type="url" name="artitechcore_business_social_twitter" value="<?php echo esc_attr($twitter); ?>" class="regular-text" placeholder="https://twitter.com/yourhandle"></label></p>
    <p><label>LinkedIn: <input type="url" name="artitechcore_business_social_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany"></label></p>
    <p class="description"><?php esc_html_e('Your social media profile links (used in Organization schema).', 'artitechcore'); ?></p>
    <?php
}

// Content Enhancer Callbacks
function artitechcore_ce_settings_section_callback() {
    echo '<p>' . esc_html__('Configure the AI Content Enhancer to automatically inject SEO Key Takeaways, Conclusions, and smart CTAs into your posts.', 'artitechcore') . '</p>';
}

function artitechcore_ce_enabled_callback() {
    $enabled = get_option('artitechcore_ce_enabled', 0);
    ?>
    <input type="hidden" name="artitechcore_ce_enabled" value="0">
    <label>
        <input type="checkbox" name="artitechcore_ce_enabled" value="1" <?php checked($enabled, 1); ?>>
        <?php esc_html_e('Enable AI Content Enhancer features and Meta Box', 'artitechcore'); ?>
    </label>
    <?php
}

function artitechcore_ce_post_types_callback() {
    $selected = get_option('artitechcore_ce_post_types', ['post']);
    $post_types = get_post_types(['public' => true], 'objects');
    
    foreach ($post_types as $pt) {
        if ($pt->name === 'attachment') continue;
        $checked = in_array($pt->name, $selected) ? 'checked' : '';
        echo '<label style="margin-right:15px;"><input type="checkbox" name="artitechcore_ce_post_types[]" value="' . esc_attr($pt->name) . '" ' . $checked . '> ' . esc_html($pt->label) . '</label>';
    }
}

function artitechcore_ce_persist_features_callback() {
    $selected = get_option('artitechcore_ce_persist_features', []);
    if (!is_array($selected)) $selected = [];
    
    $features = [
        'key_takeaways' => 'Key Takeaways',
        'conclusion' => 'Conclusion',
        'cta' => 'Call to Action (CTA)',
        'faq' => 'AI FAQ Generator'
    ];
    
    foreach ($features as $key => $label) {
        $checked = in_array($key, $selected) ? 'checked' : '';
        echo '<label style="margin-right:15px;"><input type="checkbox" name="artitechcore_ce_persist_features[]" value="' . esc_attr($key) . '" ' . $checked . '> ' . esc_html($label) . '</label>';
    }
    echo '<p class="description">' . esc_html__('Select which AI enhancements should remain visible on your posts even after the plugin is deleted (uninstalled). Note: Deactivating the plugin will temporarily hide all enhancements until reactivated.', 'artitechcore') . '</p>';
}

function artitechcore_ce_kt_heading_callback() {
    $val = get_option('artitechcore_ce_kt_heading', 'Key Takeaways');
    echo '<input type="text" name="artitechcore_ce_kt_heading" value="' . esc_attr($val) . '" class="regular-text">';
}

function artitechcore_ce_conclusion_heading_callback() {
    $val = get_option('artitechcore_ce_conclusion_heading', 'Conclusion');
    echo '<input type="text" name="artitechcore_ce_conclusion_heading" value="' . esc_attr($val) . '" class="regular-text">';
}


function artitechcore_ce_cta_shortcode_callback() {
    $val = get_option('artitechcore_ce_cta_shortcode', '');
    ?>
    <textarea name="artitechcore_ce_cta_shortcode" id="artitechcore_ce_cta_shortcode" rows="3" class="large-text" placeholder="[contact-form-7 id=&quot;1234&quot;]"><?php echo esc_textarea($val); ?></textarea>
    <p class="description"><?php esc_html_e('Enter the shortcode for your lead grabber form. The AI will generate a contextual heading and description above it.', 'artitechcore'); ?></p>
    <?php
}

function artitechcore_ce_cta_native_callback() {
    $fields = get_option('artitechcore_ce_cta_native_fields', ['name', 'email', 'message']);
    $email  = get_option('artitechcore_ce_cta_native_email', get_option('admin_email'));
    $btn    = get_option('artitechcore_ce_cta_native_button', 'Send Message');
    
    $available_fields = [
        'name'    => 'Name',
        'email'   => 'Email',
        'phone'   => 'Phone Number',
        'message' => 'Message'
    ];
    ?>
    <div class="artitechcore-native-cta-config">
        <label><strong><?php _e('Form Fields:', 'artitechcore'); ?></strong></label><br>
        <?php foreach ($available_fields as $key => $label) : ?>
            <label style="margin-right:15px;">
                <input type="checkbox" name="artitechcore_ce_cta_native_fields[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $fields)); ?>> 
                <?php echo esc_html($label); ?>
            </label>
        <?php endforeach; ?>
        
        <div style="margin-top:15px;">
            <label><strong><?php _e('Recipient Email:', 'artitechcore'); ?></strong></label><br>
            <input type="email" name="artitechcore_ce_cta_native_email" value="<?php echo esc_attr($email); ?>" class="regular-text">
        </div>
        
        <div style="margin-top:15px;">
            <label><strong><?php _e('Submit Button Text:', 'artitechcore'); ?></strong></label><br>
            <input type="text" name="artitechcore_ce_cta_native_button" value="<?php echo esc_attr($btn); ?>" class="regular-text">
        </div>
    </div>
    <?php
}

// ============================================
// BRAND KIT CALLBACKS
// ============================================

/**
 * Brand Kit section description
 */
function artitechcore_brand_kit_section_callback() {
    echo '<p>' . esc_html__('Configure your brand identity for the AI Website Builder. These settings will be used to generate pages that match your brand\'s style, voice, and colors. You can also auto-detect this information from your existing site.', 'artitechcore') . '</p>';
}

/**
 * CTA Mode field callback
 */
function artitechcore_ce_cta_mode_callback() {
    $mode = get_option('artitechcore_ce_cta_mode', 'shortcode');
    ?>
    <select name="artitechcore_ce_cta_mode" id="artitechcore_ce_cta_mode">
        <option value="shortcode" <?php selected($mode, 'shortcode'); ?>><?php esc_html_e('Shortcode Mode (Use Elementor, CF7, etc.)', 'artitechcore'); ?></option>
        <option value="native" <?php selected($mode, 'native'); ?>><?php esc_html_e('Native Mode (Built-in AJAX Form)', 'artitechcore'); ?></option>
    </select>
    <p class="description"><?php esc_html_e('Choose how the CTA form is rendered. Native mode is recommended if Elementor forms are not loading correctly.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Auto-detect brand kit button
 */
function artitechcore_brand_kit_auto_detect_callback() {
    ?>
    <div class="artitechcore-brand-auto-detect" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
        <p><strong><?php esc_html_e('Auto-Detect from Your Site', 'artitechcore'); ?></strong></p>
        <p style="margin-bottom: 10px;"><?php esc_html_e('Scan your WordPress site to automatically fill in brand information (business name, description, colors, etc.).', 'artitechcore'); ?></p>
        <button type="button" id="artitechcore-auto-detect-brand" class="button button-secondary">
            <?php esc_html_e('Rescan Brand Identity', 'artitechcore'); ?>
        </button>
        <span id="artitechcore-brand-detect-status" style="margin-left: 10px; display: none;"></span>
    </div>
    <?php
}

/**
 * Brand name field
 */
function artitechcore_brand_name_callback() {
    $brand = artitechcore_get_brand_kit();
    $value = $brand['brand_name'];
    ?>
    <input type="text" name="artitechcore_brand_kit[brand_name]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., Austin Dental Center', 'artitechcore'); ?>">
    <p class="description"><?php esc_html_e('Your business or organization name.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Brand tagline field
 */
function artitechcore_brand_tagline_callback() {
    $brand = artitechcore_get_brand_kit();
    $value = $brand['tagline'];
    ?>
    <input type="text" name="artitechcore_brand_kit[tagline]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., Your Family\'s Smile is Our Priority', 'artitechcore'); ?>">
    <p class="description"><?php esc_html_e('A short tagline or headline that captures your brand essence.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Brand description field
 */
function artitechcore_brand_description_callback() {
    $brand = artitechcore_get_brand_kit();
    $value = $brand['description'];
    ?>
    <textarea name="artitechcore_brand_kit[description]" rows="4" class="large-text" placeholder="<?php esc_attr_e('Briefly describe what your business does and what makes you unique...', 'artitechcore'); ?>"><?php echo esc_textarea($value); ?></textarea>
    <p class="description"><?php esc_html_e('A 1-2 sentence description of your business. Used in about pages and AI content generation.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Brand colors field
 */
function artitechcore_brand_colors_callback() {
    $brand = artitechcore_get_brand_kit();
    ?>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <label><strong><?php esc_html_e('Primary Color', 'artitechcore'); ?></strong></label><br>
            <input type="text" name="artitechcore_brand_kit[primary_color]" value="<?php echo esc_attr($brand['primary_color']); ?>" class="artitechcore-color-picker" data-default-color="#4A90E2">
            <p class="description"><?php esc_html_e('Main brand color (used for headings, buttons, links).', 'artitechcore'); ?></p>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label><strong><?php esc_html_e('Secondary Color', 'artitechcore'); ?></strong></label><br>
            <input type="text" name="artitechcore_brand_kit[secondary_color]" value="<?php echo esc_attr($brand['secondary_color']); ?>" class="artitechcore-color-picker" data-default-color="#6C63FF">
            <p class="description"><?php esc_html_e('Secondary brand color (accents, highlights).', 'artitechcore'); ?></p>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label><strong><?php esc_html_e('Accent Color', 'artitechcore'); ?></strong></label><br>
            <input type="text" name="artitechcore_brand_kit[accent_color]" value="<?php echo esc_attr($brand['accent_color']); ?>" class="artitechcore-color-picker" data-default-color="#FF6B6B">
            <p class="description"><?php esc_html_e('Accent color for CTAs and highlights.', 'artitechcore'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Typography fields
 */
function artitechcore_brand_typography_callback() {
    $brand = artitechcore_get_brand_kit();
    $google_fonts = [
        'Inter' => 'Inter',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Montserrat' => 'Montserrat',
        'Poppins' => 'Poppins',
        'Lato' => 'Lato',
        'Source Sans Pro' => 'Source Sans Pro',
        'Merriweather' => 'Merriweather',
        'Playfair Display' => 'Playfair Display',
        'Oswald' => 'Oswald',
        'Raleway' => 'Raleway',
        'Ubuntu' => 'Ubuntu',
        'PT Sans' => 'PT Sans',
        'Nunito' => 'Nunito',
        ' system-ui' => 'System UI (default)',
    ];
    ?>
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <label><strong><?php esc_html_e('Heading Font', 'artitechcore'); ?></strong></label><br>
            <select name="artitechcore_brand_kit[heading_font]" style="width: 100%; max-width: 300px;">
                <?php foreach ($google_fonts as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($brand['heading_font'], $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Font for headings (H1, H2, H3).', 'artitechcore'); ?></p>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label><strong><?php esc_html_e('Body Font', 'artitechcore'); ?></strong></label><br>
            <select name="artitechcore_brand_kit[body_font]" style="width: 100%; max-width: 300px;">
                <?php foreach ($google_fonts as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($brand['body_font'], $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Font for body text and paragraphs.', 'artitechcore'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Brand voice field
 */
function artitechcore_brand_voice_callback() {
    $brand = artitechcore_get_brand_kit();
    $voices = [
        'professional' => __('Professional (authoritative, polished)', 'artitechcore'),
        'casual' => __('Casual (friendly, conversational)', 'artitechcore'),
        'innovative' => __('Innovative (cutting-edge, modern)', 'artitechcore'),
        'trustworthy' => __('Trustworthy (reliable, reassuring)', 'artitechcore'),
        'friendly' => __('Friendly (warm, approachable)', 'artitechcore'),
        'luxury' => __('Luxury (exclusive, premium)', 'artitechcore'),
    ];
    ?>
    <select name="artitechcore_brand_kit[brand_voice]" style="width: 100%; max-width: 300px;">
        <?php foreach ($voices as $key => $label): ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($brand['brand_voice'], $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e('The tone and personality of your brand communication. This influences how AI writes your content.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Design aesthetic field
 */
function artitechcore_design_aesthetic_callback() {
    $brand = artitechcore_get_brand_kit();
    $aesthetics = [
        'minimal' => __('Minimal (lots of white space, clean)', 'artitechcore'),
        'bold' => __('Bold (high contrast, impactful)', 'artitechcore'),
        'corporate' => __('Corporate (traditional, professional)', 'artitechcore'),
        'playful' => __('Playful (colorful, fun, energetic)', 'artitechcore'),
        'luxury' => __('Luxury (elegant, sophisticated)', 'artitechcore'),
        'modern' => __('Modern (contemporary, clean)', 'artitechcore'),
    ];
    ?>
    <select name="artitechcore_brand_kit[design_aesthetic]" style="width: 100%; max-width: 300px;">
        <?php foreach ($aesthetics as $key => $label): ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($brand['design_aesthetic'], $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e('Overall visual style. This guides layout, spacing, and design patterns in generated pages.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Image style field
 */
function artitechcore_image_style_callback() {
    $brand = artitechcore_get_brand_kit();
    $styles = [
        'photorealistic' => __('Photorealistic (real photos, lifelike)', 'artitechcore'),
        'illustrated' => __('Illustrated (artistic, drawn imagery)', 'artitechcore'),
        'mixed' => __('Mixed (combination of both)', 'artitechcore'),
    ];
    ?>
    <select name="artitechcore_brand_kit[image_style]" style="width: 100%; max-width: 300px;">
        <?php foreach ($styles as $key => $label): ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($brand['image_style'], $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e('Preferred visual style for AI-generated images. Note: DALL-E 3 works best with photorealistic, Gemini may vary.', 'artitechcore'); ?></p>
    <?php
}

/**
 * Maintenance Section Callback
 */
function artitechcore_maintenance_section_callback() {
    echo '<p>' . esc_html__('Manage database health and production-level settings to keep ArtitechCore running efficiently.', 'artitechcore') . '</p>';
}

/**
 * DB Maintenance Callback
 */
function artitechcore_db_maintenance_callback() {
    ?>
    <div class="artitechcore-maintenance-tools">
        <button type="button" id="artitechcore-cleanup-db" class="button button-secondary">
            🧹 <?php esc_html_e('Prune AI Transients & Logs', 'artitechcore'); ?>
        </button>
        <p class="description"><?php esc_html_e('Removes expired rate-limit data and stale AI generation transients. Recommended monthly or if the database grows large.', 'artitechcore'); ?></p>
        <div id="artitechcore-cleanup-status" style="margin-top: 10px;"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // DB Cleanup
        $('#artitechcore-cleanup-db').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('<?php esc_html_e('Cleaning...', 'artitechcore'); ?>');
            
            $.post(ajaxurl, {
                action: 'artitechcore_manual_db_cleanup',
                nonce: '<?php echo wp_create_nonce("artitechcore_maintenance_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    $('#artitechcore-cleanup-status').html('<span style="color: green;">✅ ' + response.data.message + '</span>');
                } else {
                    $('#artitechcore-cleanup-status').html('<span style="color: red;">❌ ' + response.data.message + '</span>');
                }
                btn.prop('disabled', false).text('🧹 <?php esc_html_e("Prune AI Transients & Logs", "artitechcore"); ?>');
            });
        });

        // Test Connection
        $('.artitechcore-test-conn').on('click', function() {
            var btn = $(this);
            var provider = btn.data('provider');
            var statusDiv = $('.artitechcore-test-status');

            btn.prop('disabled', true).addClass('updating-message');
            statusDiv.html('<i>' + provider.charAt(0).toUpperCase() + provider.slice(1) + ': Checking...</i>');

            $.post(ajaxurl, {
                action: 'artitechcore_test_ai_connection',
                provider: provider,
                nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    statusDiv.html('<span style="color: green;">✅ ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ': ' + response.data.message + '</span>');
                } else {
                    statusDiv.html('<span style="color: red;">❌ ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ': ' + response.data.message + '</span>');
                }
                btn.prop('disabled', false).removeClass('updating-message');
            });
        });
    });
    </script>
    <?php
}

