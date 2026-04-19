<?php
/**
 * Plugin Name: ArtitechCore
 * Plugin URI: https://github.com/DG10-Agency/ArtitechCore-WP
 * Description: The core engine for Artitech WP ecosystem, providing AI-powered page generation, hierarchy management, and structural organization.
 * Version: 1.1.2
 * Requires at least: 5.6
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: DG10 Agency
 * Author URI: https://www.dg10.agency
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: artitechcore
 * Domain Path: /languages
 * Network: false
 * 
 * @package ArtitechCore
 * @version 1.1.2
 * @author DG10 Agency
 * @license GPL-2.0+
 */

define('ARTITECHCORE_VERSION', '1.1.2');
define('ARTITECHCORE_DB_VERSION', 1);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('ARTITECHCORE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ARTITECHCORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARTITECHCORE_GITHUB_URL', 'https://github.com/DG10-Agency/ArtitechCore-WP');

/**
 * Plugin activation hook
 * Sets up default options and initializes plugin data
 */
function artitechcore_activate() {
    // Set default plugin options
    $default_options = array(
        'artitechcore_version' => '1.0',
        'artitechcore_ai_provider' => 'openai',
        'artitechcore_openai_api_key' => '',
        'artitechcore_gemini_api_key' => '',
        'artitechcore_deepseek_api_key' => '',
        'artitechcore_brand_color' => '#4A90E2',
        'artitechcore_default_status' => 'draft',
        'artitechcore_auto_schema_generation' => true,
        'artitechcore_enable_image_generation' => true,
        'artitechcore_image_quality' => 'standard',
        'artitechcore_image_size' => '1024x1024',
        'artitechcore_max_tokens' => 400,
        'artitechcore_temperature' => 0.5,
        'artitechcore_seo_intensity' => 'high',
        'artitechcore_api_timeout' => 30,
        'artitechcore_batch_size' => 10,
        'artitechcore_activation_date' => current_time('mysql'),
        'artitechcore_first_activation' => true
    );
    
    // Set options only if they don't exist
    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $default_value);
        }
    }
    
    // Create custom database tables if needed
    artitechcore_create_database_tables();
    
    // Handle database migrations
    $installed_db_version = get_option('artitechcore_db_version', 0);
    if ($installed_db_version < ARTITECHCORE_DB_VERSION) {
        if ($installed_db_version < 1) {
            artitechcore_migrate_schema_data_v1();
        }
        update_option('artitechcore_db_version', ARTITECHCORE_DB_VERSION);
    }
    
    // Set activation flag
    update_option('artitechcore_plugin_activated', true);
    
    // Log activation
    error_log('ArtitechCore Plugin Activated - DB Version ' . ARTITECHCORE_DB_VERSION);

    // Schedule background queue cleanup if builder files are present
    if (!wp_next_scheduled('artitechcore_cleanup_old_builder_jobs')) {
        wp_schedule_event(time(), 'daily', 'artitechcore_cleanup_old_builder_jobs');
    }
}

/**
 * Plugin deactivation hook
 * Performs cleanup tasks when plugin is deactivated
 */
function artitechcore_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('artitechcore_cleanup_temporary_data');
    wp_clear_scheduled_hook('artitechcore_cleanup_old_builder_jobs');
    
    // Clear any cached data
    wp_cache_flush();
    
    // Set deactivation flag
    update_option('artitechcore_plugin_deactivated', true);
    update_option('artitechcore_deactivation_date', current_time('mysql'));
    
    // Log deactivation
    error_log('ArtitechCore Plugin Deactivated');
}

/**
 * Plugin uninstall hook
 * Removes all plugin data when plugin is deleted
 */
function artitechcore_uninstall() {
    // Only run if user has proper permissions
    if (!current_user_can('delete_plugins')) {
        return;
    }

    // Check what we should persist
    $persist_schema = get_option('artitechcore_persist_on_uninstall', 0);
    $persist_ce = get_option('artitechcore_ce_persist_features', []);
    if (!is_array($persist_ce)) $persist_ce = [];
    
    $mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (ABSPATH . 'wp-content/mu-plugins');
    
    if ($persist_schema || !empty($persist_ce)) {
        // Create MU-Plugin bridge to keep features functional
        artitechcore_create_persistence_bridge($persist_schema, $persist_ce);
    } else {
        // FIX #6: Clean up any old bridge files from prior installs
        @unlink($mu_dir . '/artitechcore-schema-bridge.php');
        @unlink($mu_dir . '/artitechcore-persistence-bridge.php');
    }

    // Remove all plugin options (FIX #3: always include persist_on_uninstall)
    $options_to_remove = array(
        'artitechcore_version',
        'artitechcore_ai_provider',
        'artitechcore_openai_api_key',
        'artitechcore_gemini_api_key',
        'artitechcore_deepseek_api_key',
        'artitechcore_brand_color',
        'artitechcore_default_status',
        'artitechcore_auto_schema_generation',
        'artitechcore_enable_image_generation',
        'artitechcore_image_quality',
        'artitechcore_image_size',
        'artitechcore_max_tokens',
        'artitechcore_temperature',
        'artitechcore_seo_intensity',
        'artitechcore_api_timeout',
        'artitechcore_batch_size',
        'artitechcore_activation_date',
        'artitechcore_first_activation',
        'artitechcore_plugin_activated',
        'artitechcore_plugin_deactivated',
        'artitechcore_deactivation_date',
        'artitechcore_ce_enabled',
        'artitechcore_ce_post_types',
        'artitechcore_ce_kt_heading',
        'artitechcore_ce_conclusion_heading',
        'artitechcore_ce_cta_shortcode',
        'artitechcore_ce_cta_mode',
        'artitechcore_ce_cta_native_fields',
        'artitechcore_ce_cta_native_email',
        'artitechcore_ce_cta_native_button',
        'artitechcore_ce_persist_features',
        'artitechcore_persist_on_uninstall',
    );
    
    if (!$persist_schema) {
        $options_to_remove[] = 'artitechcore_business_name';
        $options_to_remove[] = 'artitechcore_business_description';
        $options_to_remove[] = 'artitechcore_business_address';
        $options_to_remove[] = 'artitechcore_business_phone';
        $options_to_remove[] = 'artitechcore_business_email';
        $options_to_remove[] = 'artitechcore_business_social_facebook';
        $options_to_remove[] = 'artitechcore_business_social_twitter';
        $options_to_remove[] = 'artitechcore_business_social_linkedin';
        
        // Remove custom database tables ONLY if not persisting
        artitechcore_drop_database_tables();
    }
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log uninstall
    error_log('ArtitechCore Plugin Uninstalled');
}

/**
 * Creates a small MU-Plugin to keep schema/CE functional after uninstallation
 */
function artitechcore_create_persistence_bridge($persist_schema, $persist_ce) {
    $mu_dir = WPMU_PLUGIN_DIR;
    if (!is_dir($mu_dir)) {
        if (!wp_is_writable(dirname($mu_dir)) || !@mkdir($mu_dir, 0755, true)) {
            error_log('ArtitechCore Error: Could not create mu-plugins directory.');
            return false;
        }
    }
    
    if (!is_writable($mu_dir)) {
        error_log('ArtitechCore Error: mu-plugins directory is not writable.');
        return false;
    }

    $bridge_code = "<?php\n";
    $bridge_code .= "/**\n";
    $bridge_code .= " * Plugin Name: ArtitechCore Persistence Bridge\n";
    $bridge_code .= " * Description: Automatically created by ArtitechCore to keep your SEO schemas and/or AI Enhancements functional after plugin removal.\n";
    $bridge_code .= " * Version: 1.2\n";
    $bridge_code .= " * Author: ArtitechCore\n";
    $bridge_code .= " */\n\n";
    $bridge_code .= "if (!defined('ABSPATH')) exit;\n\n";
    $bridge_code .= "// Do not run if the main ArtitechCore plugin is active (prevents redeclaration crashes)\n";
    $bridge_code .= "if (defined('ARTITECHCORE_VERSION')) return;\n\n";

    if ($persist_schema) {
        $bridge_code .= "/** Schema Injection */\n";
        $bridge_code .= "add_action('wp_head', function() {\n";
        $bridge_code .= "    global \$wpdb;\n";
        $bridge_code .= "    \$object_id = null;\n";
        $bridge_code .= "    \$object_type = 'post';\n";
        $bridge_code .= "    if (is_singular()) \$object_id = get_the_ID();\n";
        $bridge_code .= "    elseif (is_front_page()) \$object_id = get_option('page_on_front');\n";
        $bridge_code .= "    elseif (is_category() || is_tag() || is_tax()) {\n";
        $bridge_code .= "        \$term = get_queried_object();\n";
        $bridge_code .= "        if (\$term && isset(\$term->term_id)) {\n";
        $bridge_code .= "            \$object_id = \$term->term_id;\n";
        $bridge_code .= "            \$object_type = 'term';\n";
        $bridge_code .= "        }\n";
        $bridge_code .= "    }\n";
        $bridge_code .= "    if (!\$object_id) return;\n";
        $bridge_code .= "    \$table_name = \$wpdb->prefix . 'artitechcore_schema_data';\n";
        $bridge_code .= "    // Check if table exists before querying\n";
        $bridge_code .= "    if (\$wpdb->get_var(\"SHOW TABLES LIKE '\$table_name'\") !== \$table_name) return;\n";
        $bridge_code .= "    \$row = \$wpdb->get_row(\$wpdb->prepare(\"SELECT schema_data FROM \$table_name WHERE object_id = %d AND object_type = %s\", \$object_id, \$object_type));\n";
        $bridge_code .= "    if (!empty(\$row->schema_data)) {\n";
        $bridge_code .= "        \$schema_array = json_decode(\$row->schema_data, true);\n";
        $bridge_code .= "        if (is_array(\$schema_array) && !empty(\$schema_array)) {\n";
        $bridge_code .= "            echo \"\\n<!-- ArtitechCore Schema Bridge -->\\n\";\n";
        $bridge_code .= "            echo '<script type=\"application/ld+json\" class=\"artitech-schema-bridge\">' . wp_json_encode(\$schema_array) . \"</script>\\n\";\n";
        $bridge_code .= "        }\n";
        $bridge_code .= "    }\n";
        $bridge_code .= "}, 30);\n\n";
    }

    if (!empty($persist_ce)) {
        // Fetch global options before they are deleted to hardcode them into the bridge
        $theme_color = get_option('artitechcore_brand_color', '#b47cfd');
        $kt_head = get_option('artitechcore_ce_kt_heading', 'Key Takeaways');
        $conc_head = get_option('artitechcore_ce_conclusion_heading', 'Conclusion');
        $cta_mode = get_option('artitechcore_ce_cta_mode', 'shortcode');
        $cta_shortcode = get_option('artitechcore_ce_cta_shortcode', '');
        $cta_native_fields = get_option('artitechcore_ce_cta_native_fields', ['name', 'email', 'message']);
        $cta_native_email = get_option('artitechcore_ce_cta_native_email', get_option('admin_email'));
        $cta_native_button = get_option('artitechcore_ce_cta_native_button', 'Send Message');

        // FIX #2: Validate hex color with regex fallback
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $theme_color)) {
            $theme_color = '#b47cfd';
        }

        // Pre-convert theme color to rgba for shadows
        $r = hexdec(substr($theme_color, 1, 2));
        $g = hexdec(substr($theme_color, 3, 2));
        $b = hexdec(substr($theme_color, 5, 2));
        $rgba_shadow = "rgba($r,$g,$b,0.15)";

        // Generate hardcoded CE CSS (NEW: Compact & Horizontal)
        // Generate hardcoded CE CSS (Matched with main plugin for brand consistency)
        $css_block  = "    .artitechcore-ce-kt { background: #ffffff; border-left: 4px solid {$theme_color}; padding: 20px 25px; margin: 30px 0; border-radius: 0 12px 12px 0; box-shadow: 0 4px 20px rgba({$r},{$g},{$b},0.08); }\n";
        $css_block .= "    .artitechcore-ce-kt-title { margin-top:0; color:#121322; font-size:1.25em; font-weight:800; margin-bottom:15px; }\n";
        $css_block .= "    .artitechcore-ce-kt ul { margin:0; padding-left:20px; list-style:disc; }\n";
        $css_block .= "    .artitechcore-ce-kt li { margin-bottom:8px; line-height:1.6; color:#334155; }\n";
        $css_block .= "    .artitechcore-ce-conclusion { margin:40px 0 20px 0; padding:25px; background:rgba({$r},{$g},{$b},0.03); border-radius:12px; border-left:4px solid {$theme_color}; }\n";
        $css_block .= "    .artitechcore-ce-conclusion h3 { font-size:1.5em; font-weight:800; margin:0 0 15px 0; color:#121322; }\n";
        $css_block .= "    .artitechcore-ce-cta-wrapper { background:#ffffff; border:2px solid {$theme_color}; padding:25px; margin:35px 0; border-radius:16px; box-shadow:0 10px 40px rgba({$r},{$g},{$b},0.12); text-align:left; position:relative; overflow:hidden; }\n";
        $css_block .= "    .artitechcore-ce-cta-wrapper::before { content:''; position:absolute; top:0; left:0; bottom:0; width:6px; background:{$theme_color}; }\n";
        $css_block .= "    .artitechcore-ce-cta-head { font-size:1.4em; font-weight:900; margin:0 0 8px 0; color:#121322; line-height:1.2; }\n";
        $css_block .= "    .artitechcore-ce-cta-desc { font-size:1.05em; color:#475569; margin:0 0 20px 0; line-height:1.5; }\n";
        $css_block .= "    .artitechcore-ce-native-form { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-start; }\n";
        $css_block .= "    .artitechcore-ce-form-field { flex:1; min-width:180px; }\n";
        $css_block .= "    .artitechcore-ce-form-field.field-message { flex-basis:100%; }\n";
        $css_block .= "    .artitechcore-ce-form-field input, .artitechcore-ce-form-field textarea { width:100%; padding:12px 14px; border:1px solid rgba({$r},{$g},{$b},0.2); border-radius:8px; font-size:14px; box-sizing:border-box; background:#fff; color:#0f172a; transition:all 0.2s; }\n";
        $css_block .= "    .artitechcore-ce-form-field input:focus, .artitechcore-ce-form-field textarea:focus { border-color:{$theme_color}; box-shadow:0 0 0 3px rgba({$r},{$g},{$b},0.1); outline:none; }\n";
        $css_block .= "    .artitechcore-ce-submit-btn { background-color:{$theme_color}; color:#ffffff !important; border:none; padding:12px 28px; border-radius:8px; font-size:14px; font-weight:800; cursor:pointer; transition:all 0.2s; white-space:nowrap; box-shadow:0 4px 12px rgba({$r},{$g},{$b},0.2); display:inline-flex; align-items:center; justify-content:center; min-height:44px; }\n";
        $css_block .= "    .artitechcore-ce-submit-btn:hover { background-color:#ffffff !important; color:{$theme_color} !important; border:1px solid {$theme_color}; transform:translateY(-2px); box-shadow:0 8px 25px rgba({$r},{$g},{$b},0.25); }\n";
        $css_block .= "    .artitechcore-ce-submit-btn:disabled { opacity:0.6; cursor:not-allowed; }\n";
        $css_block .= "    .artitechcore-ce-submit-btn.loading, .artitechcore-ce-submit-btn.loading:hover { background-color:{$theme_color} !important; color:transparent !important; cursor:wait; }\n";
        $css_block .= "    .artitechcore-ce-submit-btn.loading::after { content:''; position:absolute; width:18px; height:18px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:ce-spinner 0.6s linear infinite; }\n";
        $css_block .= "    @keyframes ce-spinner { to { transform:rotate(360deg); } }\n";
        $css_block .= "    .artitechcore-ce-form-response { flex-basis:100%; padding:12px; border-radius:8px; font-size:13px; margin-top:8px; display:none; font-weight:600; }\n";
        $css_block .= "    .artitechcore-ce-form-response.success { background:rgba(34,197,94,0.1); color:#15803d; border:1px solid rgba(34,197,94,0.2); }\n";
        $css_block .= "    .artitechcore-ce-form-response.error { background:rgba(239,68,68,0.1); color:#b91c1c; border:1px solid rgba(239,68,68,0.2); }\n";
        $css_block .= "    .artitechcore-ce-faq { margin:40px 0; padding:30px; background:#ffffff; border:1px solid rgba({$r},{$g},{$b},0.15); border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.03); }\n";
        $css_block .= "    .artitechcore-ce-faq-title { margin-top:0; margin-bottom:25px; font-size:1.6em; font-weight:900; color:#0f172a; border-bottom:3px solid {$theme_color}; display:inline-block; padding-bottom:5px; }\n";
        $css_block .= "    .artitechcore-ce-faq-item { margin-bottom:25px; border-bottom:1px solid #f1f5f9; padding-bottom:20px; }\n";
        $css_block .= "    .artitechcore-ce-faq-item:last-child { margin-bottom:0; border-bottom:none; padding-bottom:0; }\n";
        $css_block .= "    .artitechcore-ce-faq-q { font-weight:800; color:{$theme_color}; margin-bottom:10px; font-size:1.15em; display:flex; gap:12px; }\n";
        $css_block .= "    .artitechcore-ce-faq-q::before { content:'Q.'; opacity:0.5; font-weight:400; }\n";
        $css_block .= "    .artitechcore-ce-faq-a { color:#334155; line-height:1.7; font-size:1em; display:flex; gap:12px; }\n";
        $css_block .= "    .artitechcore-ce-faq-a::before { content:'A.'; opacity:0.5; font-weight:400; }\n";
        $css_block .= "    @media (max-width: 600px) { .artitechcore-ce-native-form { flex-direction:column; } .artitechcore-ce-form-field { width:100%; flex:none; } .artitechcore-ce-submit-btn { width:100%; } .artitechcore-ce-faq { padding:20px; } }\n";

        // FIX #5: CSS is now a static variable echoed inside the_content filter, not on every page
        // We write the CSS as a heredoc constant so it's only echoed when content is injected
        $bridge_code .= "/** Content Enhancer CSS (loaded only when enhancements exist) */\n";
        $bridge_code .= "define('ARTITECHCORE_BRIDGE_CSS', '<style>\n" . $css_block . "</style>');\n\n";

        // Generate content filter (FIX #7: use priority 99 to match original plugin)
        $bridge_code .= "/** Content Enhancer Injection */\n";
        $bridge_code .= "add_filter('the_content', function(\$content) {\n";
        $bridge_code .= "    if (!is_singular() || !in_the_loop() || !is_main_query()) return \$content;\n";
        $bridge_code .= "    \$post_id = get_the_ID();\n";
        $bridge_code .= "    \$enhanced_content = \$content;\n";
        $bridge_code .= "    \$_has_ce = false;\n";
        
        if (in_array('key_takeaways', $persist_ce)) {
            // FIX #1: Use strpos+substr instead of preg_replace to avoid backreference corruption
            $bridge_code .= "    \$kt = get_post_meta(\$post_id, '_artitechcore_ce_key_takeaways', true);\n";
            $bridge_code .= "    if (!empty(\$kt) && is_array(\$kt)) {\n";
            $bridge_code .= "        \$_has_ce = true;\n";
            $bridge_code .= "        \$kt_html = '<div class=\"artitechcore-ce-kt\"><h3 class=\"artitechcore-ce-kt-title\">" . esc_html($kt_head) . "</h3><ul>';\n";
            $bridge_code .= "        foreach (\$kt as \$point) \$kt_html .= '<li>' . esc_html(\$point) . '</li>';\n";
            $bridge_code .= "        \$kt_html .= '</ul></div>';\n";
            $bridge_code .= "        \$enhanced_content = \$kt_html . \"\\n\" . \$enhanced_content;\n";
            $bridge_code .= "    }\n";
        }

        if (in_array('cta', $persist_ce) && ($cta_mode === 'native' || !empty($cta_shortcode))) {
            $bridge_code .= "    \$cta_head = get_post_meta(\$post_id, '_artitechcore_ce_cta_heading', true);\n";
            $bridge_code .= "    \$cta_desc = get_post_meta(\$post_id, '_artitechcore_ce_cta_desc', true);\n";
            $bridge_code .= "    if (!empty(\$cta_head)) {\n";
            $bridge_code .= "        \$_has_ce = true;\n";
            $bridge_code .= "        \$cta_html = '<div class=\"artitechcore-ce-cta-wrapper\">'; \n";
            $bridge_code .= "        \$cta_html .= '<h3 class=\"artitechcore-ce-cta-head\">' . esc_html(\$cta_head) . '</h3>';\n";
            $bridge_code .= "        if (!empty(\$cta_desc)) \$cta_html .= '<p class=\"artitechcore-ce-cta-desc\">' . esc_html(\$cta_desc) . '</p>';\n";
            $bridge_code .= "        \$cta_html .= '<div class=\"artitechcore-ce-cta-form-container\">';\n";

            if ($cta_mode === 'native') {
                $fields_export = var_export($cta_native_fields, true);
                $bridge_code .= "        \$fields = " . $fields_export . ";\n";
                $bridge_code .= "        \$cta_html .= '<form class=\"artitechcore-ce-native-form\" id=\"ce-bridge-form-' . esc_attr(\$post_id) . '\" method=\"post\" action=\"\">';\n";
                $bridge_code .= "        \$cta_html .= wp_nonce_field('artitechcore_ce_submit_cta', '_ce_nonce', true, false);\n";
                $bridge_code .= "        \$cta_html .= '<input type=\"hidden\" name=\"post_id\" value=\"' . esc_attr(\$post_id) . '\">';\n";
                $bridge_code .= "        \$cta_html .= '<input type=\"hidden\" name=\"action\" value=\"artitechcore_ce_submit_cta\">';\n";
                $bridge_code .= "        foreach (\$fields as \$field) {\n";
                $bridge_code .= "            \$placeholder = ucfirst(\$field);\n";
                $bridge_code .= "            \$type = (\$field === 'email') ? 'email' : ((\$field === 'phone') ? 'tel' : 'text');\n";
                $bridge_code .= "            \$cta_html .= '<div class=\"artitechcore-ce-form-field field-' . esc_attr(\$field) . '\">';\n";
                $bridge_code .= "            if (\$field === 'message') \$cta_html .= '<textarea name=\"' . esc_attr(\$field) . '\" placeholder=\"' . esc_attr(\$placeholder) . '\" required rows=\"1\"></textarea>';\n";
                $bridge_code .= "            else \$cta_html .= '<input type=\"' . esc_attr(\$type) . '\" name=\"' . esc_attr(\$field) . '\" placeholder=\"' . esc_attr(\$placeholder) . '\" required>';\n";
                $bridge_code .= "            \$cta_html .= '</div>';\n";
                $bridge_code .= "        }\n";
                $bridge_code .= "        \$cta_html .= '<button type=\"submit\" class=\"artitechcore-ce-submit-btn\">' . esc_html(" . var_export($cta_native_button, true) . ") . '</button><div class=\"artitechcore-ce-form-response\"></div></form>';\n";
            } else {
                $bridge_code .= "        \$cta_html .= do_shortcode('" . addslashes(wp_kses_post($cta_shortcode)) . "');\n";
            }

            $bridge_code .= "        \$cta_html .= '</div></div>';\n";
            $bridge_code .= "        \$h2_count = preg_match_all('/<h2[^>]*>.*?<\/h2>/i', \$enhanced_content, \$matches);\n";
            $bridge_code .= "        if (\$h2_count >= 3) {\n";
            $bridge_code .= "            \$parts = preg_split('/(<\/h2>)/i', \$enhanced_content, -1, PREG_SPLIT_DELIM_CAPTURE);\n";
            $bridge_code .= "            for (\$i = 5; \$i < count(\$parts); \$i += 6) {\n";
            $bridge_code .= "                if (isset(\$parts[\$i])) \$parts[\$i] .= \"\\n\" . \$cta_html;\n";
            $bridge_code .= "            }\n";
            $bridge_code .= "            \$enhanced_content = implode('', \$parts);\n";
            $bridge_code .= "        } else {\n";
            $bridge_code .= "            \$paragraphs = explode('</p>', \$enhanced_content);\n";
            $bridge_code .= "            \$para_count = count(\$paragraphs);\n";
            $bridge_code .= "            if (\$para_count > 4) {\n";
            $bridge_code .= "                \$mid = floor(\$para_count / 2);\n";
            $bridge_code .= "                if (isset(\$paragraphs[\$mid])) \$paragraphs[\$mid] .= \"\\n\" . \$cta_html;\n";
            $bridge_code .= "                \$enhanced_content = implode('</p>', \$paragraphs);\n";
            $bridge_code .= "            } else {\n";
            $bridge_code .= "                \$enhanced_content .= \"\\n\" . \$cta_html;\n";
            $bridge_code .= "            }\n";
            $bridge_code .= "        }\n";
            $bridge_code .= "    }\n";
        }

        if (in_array('conclusion', $persist_ce)) {
            $bridge_code .= "    \$conc = get_post_meta(\$post_id, '_artitechcore_ce_conclusion', true);\n";
            $bridge_code .= "    if (!empty(\$conc)) {\n";
            $bridge_code .= "        \$_has_ce = true;\n";
            $bridge_code .= "        \$enhanced_content .= '<div class=\"artitechcore-ce-conclusion\"><h3 class=\"artitechcore-ce-conclusion-title\">" . esc_html($conc_head) . "</h3>';\n";
            $bridge_code .= "        \$enhanced_content .= '<p>' . nl2br(esc_html(\$conc)) . '</p></div>';\n";
            $bridge_code .= "    }\n";
        }

        if (in_array('faq', $persist_ce)) {
            $bridge_code .= "    \$faq = get_post_meta(\$post_id, '_artitechcore_ce_faq', true);\n";
            $bridge_code .= "    if (!empty(\$faq) && is_array(\$faq)) {\n";
            $bridge_code .= "        \$_has_ce = true;\n";
            $bridge_code .= "        \$faq_html = '<div class=\"artitechcore-ce-faq\"><h3 class=\"artitechcore-ce-faq-title\">Frequently Asked Questions</h3>';\n";
            $bridge_code .= "        foreach (\$faq as \$item) {\n";
            $bridge_code .= "            if (!empty(\$item['q']) && !empty(\$item['a'])) {\n";
            $bridge_code .= "                \$faq_html .= '<div class=\"artitechcore-ce-faq-item\">';\n";
            $bridge_code .= "                \$faq_html .= '<div class=\"artitechcore-ce-faq-q\">' . esc_html(\$item['q']) . '</div>';\n";
            $bridge_code .= "                \$faq_html .= '<div class=\"artitechcore-ce-faq-a\">' . wpautop(esc_html(\$item['a'])) . '</div>';\n";
            $bridge_code .= "                \$faq_html .= '</div>';\n";
            $bridge_code .= "            }\n";
            $bridge_code .= "        }\n";
            $bridge_code .= "        \$faq_html .= '</div>';\n";
            $bridge_code .= "        \$enhanced_content .= \$faq_html;\n";
            $bridge_code .= "    }\n";
        }

        $bridge_code .= "    if (\$_has_ce) {\n";
        $bridge_code .= "        \$enhanced_content = ARTITECHCORE_BRIDGE_CSS . \"\\n\" . \$enhanced_content;\n";
        $bridge_code .= "        \$enhanced_content .= '<script>if(!window._artitech_bridge_js){window._artitech_bridge_js=true;document.addEventListener(\"submit\",function(e){if(e.target&&e.target.classList.contains(\"artitechcore-ce-native-form\")){e.preventDefault();var f=e.target,b=f.querySelector(\".artitechcore-ce-submit-btn\"),r=f.querySelector(\".artitechcore-ce-form-response\");if(b.classList.contains(\"loading\"))return;b.disabled=true;b.classList.add(\"loading\");if(r){r.style.display=\"none\";r.className=\"artitechcore-ce-form-response\";}fetch(\"'.admin_url('admin-ajax.php').'\",{method:\"POST\",body:new FormData(f)}).then(res=>res.json()).then(res=>{if(res.success){if(r){r.className=\"artitechcore-ce-form-response success\";r.textContent=res.data;r.style.display=\"block\";}f.reset();}else{if(r){r.className=\"artitechcore-ce-form-response error\";r.textContent=res.data||\"Error occurred\";r.style.display=\"block\";}}}).catch(()=>{if(r){r.className=\"artitechcore-ce-form-response error\";r.textContent=\"Connection error. Please try again.\";r.style.display=\"block\";}}).finally(()=>{b.disabled=false;b.classList.remove(\"loading\");});}});}</script>';\n";
        $bridge_code .= "    }\n";
        $bridge_code .= "    return \$enhanced_content;\n";
        $bridge_code .= "}, 99);\n\n";

        if (in_array('cta', $persist_ce) && $cta_mode === 'native') {
            $bridge_code .= "add_action('wp_ajax_artitechcore_ce_submit_cta', 'artitechcore_bridge_cta_handler');\n";
            $bridge_code .= "add_action('wp_ajax_nopriv_artitechcore_ce_submit_cta', 'artitechcore_bridge_cta_handler');\n\n";
            $bridge_code .= "if (!function_exists('artitechcore_bridge_cta_handler')) {\n";
            $bridge_code .= "    function artitechcore_bridge_cta_handler() {\n";
            $bridge_code .= "        check_ajax_referer('artitechcore_ce_submit_cta', '_ce_nonce');\n";
            $bridge_code .= "    \$ip = \$_SERVER['REMOTE_ADDR'];\n";
            $bridge_code .= "    \$transient_key = 'ce_cta_limit_' . md5(\$ip);\n";
            $bridge_code .= "    if (get_transient(\$transient_key)) wp_send_json_error('Please wait a few seconds before submitting again.');\n";
            $bridge_code .= "    set_transient(\$transient_key, 1, 5);\n\n";
            $bridge_code .= "    \$post_id = isset(\$_POST['post_id']) ? absint(\$_POST['post_id']) : 0;\n";
            $bridge_code .= "    \$to = " . var_export($cta_native_email, true) . ";\n";
            $bridge_code .= "    \$subject = '[Lead] CTA Submission: ' . get_the_title(\$post_id);\n";
            $bridge_code .= "    \$body = \"New CTA Lead\\n\\nURL: \" . get_permalink(\$post_id) . \"\\n\\n\";\n";
            $bridge_code .= "    foreach (['name','email','phone','message'] as \$f) {\n";
            $bridge_code .= "        if (isset(\$_POST[\$f])) {\n";
            $bridge_code .= "            \$val = (\$f === 'message') ? sanitize_textarea_field(\$_POST[\$f]) : sanitize_text_field(\$_POST[\$f]);\n";
            $bridge_code .= "            \$body .= strtoupper(\$f) . ': ' . \$val . \"\\n\";\n";
            $bridge_code .= "        }\n";
            $bridge_code .= "    }\n";
            $bridge_code .= "    wp_mail(\$to, \$subject, \$body);\n";
            $bridge_code .= "    wp_send_json_success('Thank you! Your submission has been received.');\n";
            $bridge_code .= "}\n";
        }
    }

    // Replace the old schema bridge with the new unified bridge
    @unlink($mu_dir . '/artitechcore-schema-bridge.php');
    return @file_put_contents($mu_dir . '/artitechcore-persistence-bridge.php', $bridge_code);
}

/**
 * Create custom database tables for plugin
 */
function artitechcore_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing AI generation logs
    $table_name = $wpdb->prefix . 'artitechcore_generation_logs';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        generation_type varchar(50) NOT NULL,
        ai_provider varchar(50) NOT NULL,
        tokens_used int(11) DEFAULT 0,
        generation_time datetime DEFAULT CURRENT_TIMESTAMP,
        success tinyint(1) DEFAULT 1,
        error_message text,
        PRIMARY KEY  (id),
        KEY page_id (page_id),
        KEY generation_type (generation_type),
        KEY generation_time (generation_time)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Table for storing schema generation data
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        object_id bigint(20) NOT NULL,
        object_type varchar(20) NOT NULL DEFAULT 'post',
        schema_type varchar(100) NOT NULL,
        schema_data longtext NOT NULL,
        origin varchar(20) DEFAULT 'generated',
        is_locked tinyint(1) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY object_identity (object_id, object_type),
        KEY schema_type (schema_type)
    ) $charset_collate;";
    
    dbDelta($sql);
}

/**
 * Migration: Move schema data from meta to custom table (v1)
 */
function artitechcore_migrate_schema_data_v1() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';

    $post_meta_type   = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_artitechcore_schema_type' AND meta_value != ''");
    $post_meta_data   = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_artitechcore_schema_data' AND meta_value != ''");
    $post_meta_origin = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_artitechcore_schema_origin' AND meta_value != ''");
    $post_meta_locked = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_artitechcore_schema_locked' AND meta_value != ''");
    
    $data_map = [];
    foreach ($post_meta_type as $row)   $data_map[$row->post_id]['type']   = $row->meta_value;
    foreach ($post_meta_data as $row)   $data_map[$row->post_id]['data']   = $row->meta_value;
    foreach ($post_meta_origin as $row) $data_map[$row->post_id]['origin'] = $row->meta_value;
    foreach ($post_meta_locked as $row) $data_map[$row->post_id]['locked'] = $row->meta_value;

    foreach ($data_map as $post_id => $vals) {
        if (!empty($vals['type']) && !empty($vals['data'])) {
            $wpdb->replace($table_name, [
                'object_id'   => $post_id,
                'object_type' => 'post',
                'schema_type' => $vals['type'],
                'schema_data' => $vals['data'],
                'origin'      => $vals['origin'] ?? 'generated',
                'is_locked'   => !empty($vals['locked']) ? 1 : 0
            ]);
        }
    }

    // 2. Migrate Term Meta
    $term_meta_type   = $wpdb->get_results("SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = '_artitechcore_schema_type' AND meta_value != ''");
    $term_meta_data   = $wpdb->get_results("SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = '_artitechcore_schema_data' AND meta_value != ''");
    $term_meta_origin = $wpdb->get_results("SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = '_artitechcore_schema_origin' AND meta_value != ''");
    $term_meta_locked = $wpdb->get_results("SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = '_artitechcore_schema_locked' AND meta_value != ''");
    
    $term_map = [];
    foreach ($term_meta_type as $row)   $term_map[$row->term_id]['type']   = $row->meta_value;
    foreach ($term_meta_data as $row)   $term_map[$row->term_id]['data']   = $row->meta_value;
    foreach ($term_meta_origin as $row) $term_map[$row->term_id]['origin'] = $row->meta_value;
    foreach ($term_meta_locked as $row) $term_map[$row->term_id]['locked'] = $row->meta_value;

    foreach ($term_map as $term_id => $vals) {
        if (!empty($vals['type']) && !empty($vals['data'])) {
            $wpdb->replace($table_name, [
                'object_id'   => $term_id,
                'object_type' => 'term',
                'schema_type' => $vals['type'],
                'schema_data' => $vals['data'],
                'origin'      => $vals['origin'] ?? 'generated',
                'is_locked'   => !empty($vals['locked']) ? 1 : 0
            ]);
        }
    }
}

/**
 * Drop custom database tables
 */
function artitechcore_drop_database_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'artitechcore_generation_logs',
        $wpdb->prefix . 'artitechcore_schema_data'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'artitechcore_activate');
register_deactivation_hook(__FILE__, 'artitechcore_deactivate');
register_uninstall_hook(__FILE__, 'artitechcore_uninstall');

/**
 * Load plugin textdomain for internationalization
 */
function artitechcore_load_textdomain() {
    load_plugin_textdomain(
        'artitechcore',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'artitechcore_load_textdomain');

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Include necessary files
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/admin-menu.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/page-creation.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/csv-handler.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/settings-page.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/ai-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/menu-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/schema-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/keyword-analyzer.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/content-enhancer.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/website-generator-queue.php';

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Enqueue scripts and styles
function artitechcore_enqueue_assets() {
    // Enqueue brand CSS first (Variables & Base)
    wp_enqueue_style('artitechcore-dg10-brand', ARTITECHCORE_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '1.0');
    
    // Enqueue Consolidated Admin UI CSS
    wp_enqueue_style('artitechcore-admin-ui', ARTITECHCORE_PLUGIN_URL . 'assets/css/admin-ui.css', array('artitechcore-dg10-brand'), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/css/admin-ui.css'));

    // Enqueue Schema Column Styles (Specific for Pages/Posts lists)
    $screen = get_current_screen();
    if ($screen && ($screen->base === 'edit' || $screen->base === 'upload')) {
        wp_enqueue_style('artitechcore-schema-column', ARTITECHCORE_PLUGIN_URL . 'assets/css/schema-column.css', array(), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/css/schema-column.css'));
    }

    // Enqueue CPT Management CSS
    wp_enqueue_style('artitechcore-cpt-management', ARTITECHCORE_PLUGIN_URL . 'assets/css/cpt-management.css', array('artitechcore-admin-ui'), '1.0');

    // Enqueue Scripts
    $current_screen = get_current_screen();

    // CPT Management Scripts (only on CPT tab)
    if ($current_screen && strpos($current_screen->id, 'artitechcore-') !== false && isset($_GET['tab']) && $_GET['tab'] === 'cpt') {
        wp_enqueue_script('artitechcore-cpt-management', ARTITECHCORE_PLUGIN_URL . 'assets/js/cpt-management.js', array('jquery'), '1.0', true);
        
        // Localize CPT management script
        wp_localize_script('artitechcore-cpt-management', 'artitechcore_cpt_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('artitechcore_cpt_ajax'),
            'plugin_url' => ARTITECHCORE_PLUGIN_URL,
            'strings' => array(
                'loading' => __('Loading...', 'artitechcore'),
                'success' => __('Success!', 'artitechcore'),
                'error' => __('An error occurred.', 'artitechcore'),
                'network_error' => __('Network error occurred. Please try again.', 'artitechcore')
            )
        ));
    }

    // AI Generator Scripts (only on AI tab)
    if ($current_screen && strpos($current_screen->id, 'artitechcore-') !== false && isset($_GET['tab']) && $_GET['tab'] === 'ai') {
        wp_enqueue_script('artitechcore-ai-generator', ARTITECHCORE_PLUGIN_URL . 'assets/js/ai-generator.js', array('jquery'), '1.0', true);
        
        // Share CPT data for ajaxurl and nonce consistency if needed, 
        // or localize specifically for AI
        wp_localize_script('artitechcore-ai-generator', 'artitechcore_ai_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('artitechcore_ai_ajax')
        ));
    }
    
    // Main Admin Scripts
    wp_enqueue_script('artitechcore-scripts', ARTITECHCORE_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '1.0', true);
    
    // Keyword Analyzer Scripts
    wp_enqueue_script('artitechcore-keyword-analyzer', ARTITECHCORE_PLUGIN_URL . 'assets/js/keyword-analyzer.js', array('jquery'), '1.0', true);
    wp_localize_script('artitechcore-keyword-analyzer', 'artitechcore_keyword_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('artitechcore_keyword_analysis')
    ));

    // Schema Generator Scripts
    wp_enqueue_script('artitechcore-schema-generator', ARTITECHCORE_PLUGIN_URL . 'assets/js/schema-generator.js', array('jquery'), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/js/schema-generator.js'), true);
    wp_localize_script('artitechcore-schema-generator', 'artitechcore_schema_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('artitechcore_schema_preview')
    ));

    // Localize script with plugin URL
    wp_localize_script('artitechcore-scripts', 'artitechcore_plugin_data', array(
        'plugin_url' => ARTITECHCORE_PLUGIN_URL
    ));
}
add_action('admin_enqueue_scripts', 'artitechcore_enqueue_assets');
