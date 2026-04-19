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

// AI Settings & Constants
if (!defined('ARTITECHCORE_API_TIMEOUT')) {
    define('ARTITECHCORE_API_TIMEOUT', 120);
}
if (!defined('ARTITECHCORE_AI_TEMPERATURE')) {
    define('ARTITECHCORE_AI_TEMPERATURE', 0.7);
}
if (!defined('ARTITECHCORE_AI_MAX_TOKENS')) {
    define('ARTITECHCORE_AI_MAX_TOKENS', 4000);
}

/**
 * Plugin activation hook
 * Sets up default options and initializes plugin data
 */
function artitechcore_activate() {
    // Set default plugin options
    $default_options = array(
        'artitechcore_version' => '1.1.2',
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
        'artitechcore_max_tokens' => 1000,
        'artitechcore_temperature' => 0.5,
        'artitechcore_seo_intensity' => 'high',
        'artitechcore_api_timeout' => 120,
        'artitechcore_ai_rate_limit' => 20,
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
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('ArtitechCore Plugin Activated - DB Version ' . ARTITECHCORE_DB_VERSION);
    }

    // Schedule background queue cleanup if builder files are present
    if (!wp_next_scheduled('artitechcore_cleanup_old_builder_jobs')) {
        wp_schedule_event(time(), 'daily', 'artitechcore_cleanup_old_builder_jobs');
    }
}

/**
 * Initialize plugin components
 */
function artitechcore_init() {
    // Initialize brand kit if empty or not yet scanned (Init Shield protected)
    $brand_kit = get_option('artitechcore_brand_kit');
    $scan_completed = get_option('artitechcore_initial_scan_completed');
    
    if (empty($brand_kit) && !$scan_completed) {
        update_option('artitechcore_brand_kit', artitechcore_auto_detect_brand_kit());
    }
}
add_action('init', 'artitechcore_init');

/**
 * Plugin deactivation hook
 * Performs cleanup tasks when plugin is deactivated
 */
function artitechcore_deactivate() {
    // Check if user wants to persist data via bridge
    $persist_schema = get_option('artitechcore_persist_on_uninstall', 0);
    $persist_ce = get_option('artitechcore_ce_persist_features', []);
    
    // Create the bridge if anything is to be persisted
    if ($persist_schema || !empty($persist_ce)) {
        artitechcore_create_persistence_bridge($persist_schema, $persist_ce);
    }

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
 * Creates a small MU-Plugin to keep schema/CE functional after uninstallation
 */
function artitechcore_create_persistence_bridge($persist_schema, $persist_ce) {
    if (!$persist_schema && empty($persist_ce)) {
        return false;
    }

    $mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (ABSPATH . 'wp-content/mu-plugins');
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
    $bridge_code .= " * Description: Keep SEO schemas and Content Enhancements functional after uninstallation.\n";
    $bridge_code .= " * Version: 1.6\n";
    $bridge_code .= " * Author: ArtitechCore\n";
    $bridge_code .= " */\n\n";
    $bridge_code .= "if (!defined('ABSPATH')) exit;\n\n";
    $bridge_code .= "// Do not run if the main plugin is active\n";
    $bridge_code .= "if (defined('ARTITECHCORE_VERSION')) return;\n\n";

    if ($persist_schema) {
        $bridge_code .= "/** Schema Injection */\n";
        $bridge_code .= "add_action('wp_head', function() {\n";
        $bridge_code .= "    global \$wpdb;\n";
        $bridge_code .= "    \$obj_id = is_singular() ? get_the_ID() : (is_front_page() ? get_option('page_on_front') : null);\n";
        $bridge_code .= "    \$obj_type = 'post';\n";
        $bridge_code .= "    if (!\$obj_id && (is_category() || is_tag() || is_tax())) {\n";
        $bridge_code .= "        \$term = get_queried_object();\n";
        $bridge_code .= "        if (\$term && isset(\$term->term_id)) {\n";
        $bridge_code .= "            \$obj_id = \$term->term_id;\n";
        $bridge_code .= "            \$obj_type = 'term';\n";
        $bridge_code .= "        }\n";
        $bridge_code .= "    }\n";
        $bridge_code .= "    if (!\$obj_id) return;\n";
        $bridge_code .= "    \$table = \$wpdb->prefix . 'artitechcore_schema_data';\n";
        $bridge_code .= "    if (\$wpdb->get_var(\$wpdb->prepare(\"SHOW TABLES LIKE %s\", \$table)) !== \$table) return;\n";
        $bridge_code .= "    \$data = \$wpdb->get_var(\$wpdb->prepare(\"SELECT schema_data FROM \$table WHERE object_id = %d AND object_type = %s\", \$obj_id, \$obj_type));\n";
        $bridge_code .= "    if (!empty(\$data)) {\n";
        $bridge_code .= "        echo \"\\n<!-- ArtitechCore Bridge Schema -->\\n<script type='application/ld+json'>\" . \$data . \"</script>\\n\";\n";
        $bridge_code .= "    }\n";
        $bridge_code .= "}, 30);\n\n";
    }

    if (!empty($persist_ce)) {
        $color = get_option('artitechcore_brand_color', '#b47cfd');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#b47cfd';
        $r = hexdec(substr($color, 1, 2)); $g = hexdec(substr($color, 3, 2)); $b = hexdec(substr($color, 5, 2));
        
        $kt_h = get_option('artitechcore_ce_kt_heading', 'Key Takeaways');
        $conc_h = get_option('artitechcore_ce_conclusion_heading', 'Conclusion');
        
        $css = "<style>
            .artitech-bridge-ce { font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif; }
            .artitech-bridge-kt { background:#fff; border-left:4px solid $color; padding:20px; margin:30px 0; border-radius:0 12px 12px 0; box-shadow:0 4px 20px rgba($r,$g,$b,0.08); }
            .artitech-bridge-kt h3 { margin:0 0 15px 0; font-weight:800; font-size:1.25em; color:#121322; }
            .artitech-bridge-conc { margin:40px 0 20px 0; padding:25px; background:rgba($r,$g,$b,0.03); border-radius:12px; border-left:4px solid $color; }
            .artitech-bridge-conc h3 { margin:0 0 15px 0; font-weight:800; font-size:1.4em; color:#121322; }
            .artitech-bridge-faq { margin:40px 0; padding:25px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; }
            .artitech-bridge-faq h3 { margin:0 0 20px 0; font-weight:800; }
            .artitech-bridge-faq-item { margin-bottom:20px; }
            .artitech-bridge-faq-q { font-weight:700; color:#1e293b; margin-bottom:5px; }
            .artitech-bridge-faq-a { color:#475569; line-height:1.6; }
            .artitech-bridge-cta-box { background:#fff; border:2px solid $color; padding:25px; margin:35px 0; border-radius:16px; box-shadow:0 10px 40px rgba($r,$g,$b,0.12); border-left-width:6px; }
            .artitech-bridge-cta-h { font-size:1.4em; font-weight:900; margin:0 0 8px 0; color:#121322; }
            .artitech-bridge-cta-d { color:#475569; }
        </style>";

        $bridge_code .= "/** Content Enhancements Injection */\n";
        $bridge_code .= "add_filter('the_content', function(\$content) {\n";
        $bridge_code .= "    if (!is_singular() || !in_the_loop() || !is_main_query()) return \$content;\n";
        $bridge_code .= "    \$pid = get_the_ID(); \$added = false; \$html_top = ''; \$html_bottom = '';\n";
        
        if (in_array('key_takeaways', $persist_ce)) {
            $bridge_code .= "    \$kt = get_post_meta(\$pid, '_artitechcore_ce_key_takeaways', true);\n";
            $bridge_code .= "    if (!empty(\$kt)) {\n";
            $bridge_code .= "        \$added = true; \$html_top .= '<div class=\"artitech-bridge-ce artitech-bridge-kt\"><h3>" . esc_html($kt_h) . "</h3><ul>';\n";
            $bridge_code .= "        foreach ((array)\$kt as \$p) \$html_top .= '<li>' . esc_html(\$p) . '</li>';\n";
            $bridge_code .= "        \$html_top .= '</ul></div>';\n";
            $bridge_code .= "    }\n";
        }

        if (in_array('cta', $persist_ce)) {
            $bridge_code .= "    \$ctah = get_post_meta(\$pid, '_artitechcore_ce_cta_heading', true);\n";
            $bridge_code .= "    \$ctad = get_post_meta(\$pid, '_artitechcore_ce_cta_desc', true);\n";
            $bridge_code .= "    if (!empty(\$ctah)) {\n";
            $bridge_code .= "        \$added = true;\n";
            $bridge_code .= "        \$cta_html = '<div class=\"artitech-bridge-ce artitech-bridge-cta-box\"><div class=\"artitech-bridge-cta-h\">' . esc_html(\$ctah) . '</div>';\n";
            $bridge_code .= "        if (\$ctad) \$cta_html .= '<div class=\"artitech-bridge-cta-d\">' . esc_html(\$ctad) . '</div>';\n";
            $bridge_code .= "        \$cta_html .= '</div>';\n";
            $bridge_code .= "        // Insert CTA in the middle of content if possible\n";
            $bridge_code .= "        \$paragraphs = explode('</p>', \$content);\n";
            $bridge_code .= "        if (count(\$paragraphs) > 4) {\n";
            $bridge_code .= "            \$middle = floor(count(\$paragraphs) / 2);\n";
            $bridge_code .= "            \$paragraphs[\$middle] .= \$cta_html;\n";
            $bridge_code .= "            \$content = implode('</p>', \$paragraphs);\n";
            $bridge_code .= "        } else {\n";
            $bridge_code .= "            \$html_bottom .= \$cta_html;\n";
            $bridge_code .= "        }\n";
            $bridge_code .= "    }\n";
        }
        
        if (in_array('faq', $persist_ce)) {
            $bridge_code .= "    \$faq = get_post_meta(\$pid, '_artitechcore_ce_faq', true);\n";
            $bridge_code .= "    if (!empty(\$faq) && is_array(\$faq)) {\n";
            $bridge_code .= "        \$added = true; \$faq_html = '<div class=\"artitech-bridge-ce artitech-bridge-faq\"><h3>Frequently Asked Questions</h3>';\n";
            $bridge_code .= "        \$schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];\n";
            $bridge_code .= "        foreach (\$faq as \$f) {\n";
            $bridge_code .= "            if (empty(\$f['q']) || empty(\$f['a'])) continue;\n";
            $bridge_code .= "            \$faq_html .= '<div class=\"artitech-bridge-faq-item\"><div class=\"artitech-bridge-faq-q\">' . esc_html(\$f['q']) . '</div><div class=\"artitech-bridge-faq-a\">' . nl2br(esc_html(\$f['a'])) . '</div></div>';\n";
            $bridge_code .= "            \$schema['mainEntity'][] = ['@type' => 'Question', 'name' => \$f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => \$f['a']]];\n";
            $bridge_code .= "        }\n";
            $bridge_code .= "        \$faq_html .= '</div><script type=\"application/ld+json\">' . json_encode(\$schema) . '</script>';\n";
            $bridge_code .= "        \$html_bottom .= \$faq_html;\n";
            $bridge_code .= "    }\n";
        }

        if (in_array('conclusion', $persist_ce)) {
            $bridge_code .= "    \$cn = get_post_meta(\$pid, '_artitechcore_ce_conclusion', true);\n";
            $bridge_code .= "    if (!empty(\$cn)) {\n";
            $bridge_code .= "        \$added = true; \$html_bottom .= '<div class=\"artitech-bridge-ce artitech-bridge-conc\"><h3>" . esc_html($conc_h) . "</h3><p>' . nl2br(esc_html(\$cn)) . '</p></div>';\n";
            $bridge_code .= "    }\n";
        }

        $bridge_code .= "    return \$added ? " . var_export($css, true) . " . \$html_top . \$content . \$html_bottom : \$content;\n";
        $bridge_code .= "}, 99);\n";
    }

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
function artitechcore_migrate_schema_data_v1($batch_limit = 500) {
    global $wpdb;
    
    // 1. Concurrency Lock: Prevent multiple simultaneous migrations
    if (get_transient('artitechcore_migrating_schema')) {
        return false;
    }
    set_transient('artitechcore_migrating_schema', true, 300); // 5 minute lock

    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    $legacy_keys = array('_artitechcore_schema_type', '_artitechcore_schema_data', '_artitechcore_schema_markup', '_artitechcore_schema_origin', '_artitechcore_schema_locked');
    $keys_in = "'" . implode("','", $legacy_keys) . "'";

    // --- PART A: POST META ---
    $post_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key IN ($keys_in) 
        AND meta_value != ''
        LIMIT %d", $batch_limit));

    $processed_posts = 0;
    foreach ($post_ids as $post_id) {
        $metas = $wpdb->get_results($wpdb->prepare("
            SELECT meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key IN ($keys_in)
        ", $post_id));

        if (empty($metas)) continue;

        $vals = array();
        foreach ($metas as $m) {
            $key = str_replace('_artitechcore_schema_', '', $m->meta_key);
            // Handle 'markup' as 'data' alias for consistency
            if ($key === 'markup') $key = 'data';
            $vals[$key] = $m->meta_value;
        }

        if (!empty($vals['data'])) {
            $inserted = $wpdb->replace($table_name, array(
                'object_id'    => $post_id,
                'object_type'  => 'post',
                'schema_type'  => !empty($vals['type']) ? $vals['type'] : 'webpage',
                'schema_data'  => $vals['data'],
                'origin'       => isset($vals['origin']) ? $vals['origin'] : 'generated',
                'is_locked'    => !empty($vals['locked']) ? 1 : 0,
                'created_date' => current_time('mysql'),
                'updated_date' => current_time('mysql')
            ));

            if ($inserted !== false) {
                // Verified move: safe to delete specific legacy meta for this ID
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->postmeta} 
                    WHERE post_id = %d 
                    AND meta_key IN ($keys_in)
                ", $post_id));
                $processed_posts++;
            }
        }
    }

    // --- PART B: TERM META (if post batch didn't use up the limit) ---
    $remaining_limit = $batch_limit - count($post_ids);
    $processed_terms = 0;
    if ($remaining_limit > 0) {
        $term_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT term_id FROM {$wpdb->termmeta} 
            WHERE meta_key IN ($keys_in) 
            AND meta_value != ''
            LIMIT %d", $remaining_limit));

        foreach ($term_ids as $term_id) {
            $metas = $wpdb->get_results($wpdb->prepare("
                SELECT meta_key, meta_value 
                FROM {$wpdb->termmeta} 
                WHERE term_id = %d 
                AND meta_key IN ($keys_in)
            ", $term_id));

            if (empty($metas)) continue;

            $vals = array();
            foreach ($metas as $m) {
                $key = str_replace('_artitechcore_schema_', '', $m->meta_key);
                if ($key === 'markup') $key = 'data';
                $vals[$key] = $m->meta_value;
            }

            if (!empty($vals['data'])) {
                $inserted = $wpdb->replace($table_name, array(
                    'object_id'    => $term_id,
                    'object_type'  => 'term',
                    'schema_type'  => !empty($vals['type']) ? $vals['type'] : 'webpage',
                    'schema_data'  => $vals['data'],
                    'origin'       => isset($vals['origin']) ? $vals['origin'] : 'generated',
                    'is_locked'    => !empty($vals['locked']) ? 1 : 0,
                    'created_date' => current_time('mysql'),
                    'updated_date' => current_time('mysql')
                ));

                if ($inserted !== false) {
                    $wpdb->query($wpdb->prepare("
                        DELETE FROM {$wpdb->termmeta} 
                        WHERE term_id = %d 
                        AND meta_key IN ($keys_in)
                    ", $term_id));
                    $processed_terms++;
                }
            }
        }
    }

    delete_transient('artitechcore_migrating_schema');
    
    // Return true if more data potentially remains in this category
    return (count($post_ids) >= $batch_limit || (isset($term_ids) && count($term_ids) >= $remaining_limit));
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
// No register_uninstall_hook needed; uninstall.php is used instead.

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

// Include necessary files — load order matters.
// brand-kit.php must load before settings-page.php (settings UI renders the brand kit form)
// and before website-generator.php (builder uses artitechcore_get_brand_kit()).
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/brand-kit.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/admin-menu.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/page-creation.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/csv-handler.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/settings-page.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/api-utils.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/ai-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/menu-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/schema-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/keyword-analyzer.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/content-enhancer.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/website-generator.php';
require_once ARTITECHCORE_PLUGIN_PATH . 'includes/website-generator-queue.php';

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Enqueue scripts and styles
function artitechcore_admin_scripts($hook) {
    global $post; // Required to access current post object in post/page editing context
    $current_screen = get_current_screen();
    if (!$current_screen) return;

    // Enqueue brand CSS first (Variables & Base)
    wp_enqueue_style('artitechcore-dg10-brand', ARTITECHCORE_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '1.0');
    
    // Enqueue Consolidated Admin UI CSS
    wp_enqueue_style('artitechcore-admin-ui', ARTITECHCORE_PLUGIN_URL . 'assets/css/admin-ui.css', array('artitechcore-dg10-brand'), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/css/admin-ui.css'));

    // Enqueue Schema Column Styles (Specific for Pages/Posts lists)
    if ($current_screen->base === 'edit' || $current_screen->base === 'upload') {
        wp_enqueue_style('artitechcore-schema-column', ARTITECHCORE_PLUGIN_URL . 'assets/css/schema-column.css', array(), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/css/schema-column.css'));
    }

    // Enqueue CPT Management CSS
    wp_enqueue_style('artitechcore-cpt-management', ARTITECHCORE_PLUGIN_URL . 'assets/css/cpt-management.css', array('artitechcore-admin-ui'), '1.0');

    // --- SCRIPTS ---

    // 1. External dependencies (shared)
    wp_enqueue_script('jquery');
    
    // 2. Hierarchy Dependencies (only where needed)
    if (strpos($current_screen->id, 'artitechcore-') !== false && isset($_GET['tab']) && $_GET['tab'] === 'hierarchy') {
        wp_enqueue_style('jstree', ARTITECHCORE_PLUGIN_URL . 'assets/vendor/jstree/themes/default/style.min.css');
        wp_enqueue_script('jstree', ARTITECHCORE_PLUGIN_URL . 'assets/vendor/jstree/jstree.min.js', array('jquery'), '3.3.15', true);
        wp_enqueue_script('d3', ARTITECHCORE_PLUGIN_URL . 'assets/vendor/d3/d3.v7.min.js', array(), '7.0.0', true);
        wp_enqueue_style('artitechcore-hierarchy', ARTITECHCORE_PLUGIN_URL . 'assets/css/hierarchy.css');
        wp_enqueue_script('artitechcore-hierarchy', ARTITECHCORE_PLUGIN_URL . 'assets/js/hierarchy.js', array('jquery', 'jstree', 'd3'), ARTITECHCORE_VERSION, true);
    }

    // 3. Post/Page Editing Context (Content Enhancer)
    if ($hook === 'post-new.php' || $hook === 'post.php') {
        $supported_types = get_option('artitechcore_ce_post_types', ['post']);
        if ($post && is_array($supported_types) && in_array($post->post_type, $supported_types)) {
            wp_add_inline_style('artitechcore-admin-ui', '
                .artitechcore-ce-panel { background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px; }
                .artitechcore-ce-field { margin-bottom: 20px; }
                .artitechcore-ce-field label { font-weight: bold; display: block; margin-bottom: 5px; }
                .artitechcore-ce-field textarea { width: 100%; min-height: 100px; }
                .artitechcore-ce-field input[type="text"] { width: 100%; }
                .artitechcore-ce-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; flex-wrap: wrap; gap: 10px; }
                .artitechcore-ce-badge { background: #b47cfd; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
                .artitechcore-ce-warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 15px; margin-bottom: 15px; font-size: 13px; }
            ');
        }
    }

    // 3. Plugin Page Scripts
    if (strpos($current_screen->id, 'artitechcore-') !== false) {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

        if ($tab === 'cpt') {
            wp_enqueue_script('artitechcore-cpt-management', ARTITECHCORE_PLUGIN_URL . 'assets/js/cpt-management.js', array('jquery'), '1.0', true);
        }

        if ($tab === 'ai') {
            wp_enqueue_script('artitechcore-ai-generator', ARTITECHCORE_PLUGIN_URL . 'assets/js/ai-generator.js', array('jquery'), '1.0', true);
        }

        if ($tab === 'website' || $tab === 'website-builder') {
            // JS for website generator is output inline in website-generator.php
        }

        if ($tab === 'hierarchy') {
            wp_enqueue_script('artitechcore-hierarchy', ARTITECHCORE_PLUGIN_URL . 'assets/js/hierarchy.js', array('jquery'), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/js/hierarchy.js'), true);
        }

        // Always load schema generator on plugin pages for preview
        wp_enqueue_script('artitechcore-schema-generator', ARTITECHCORE_PLUGIN_URL . 'assets/js/schema-generator.js', array('jquery'), filemtime(ARTITECHCORE_PLUGIN_PATH . 'assets/js/schema-generator.js'), true);
    }

    // 4. Global Admin Context Scripts
    wp_enqueue_script('artitechcore-scripts', ARTITECHCORE_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '1.0', true);
    wp_enqueue_script('artitechcore-keyword-analyzer', ARTITECHCORE_PLUGIN_URL . 'assets/js/keyword-analyzer.js', array('jquery'), '1.0', true);


    // 5. Settings Specific (Color Picker)
    if (strpos($current_screen->id, 'artitechcore-') !== false) {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
    }

    // --- LOCALIZATION (Unified) ---
    wp_localize_script('artitechcore-scripts', 'artitechcore_data', array(
        'ajaxurl'    => admin_url('admin-ajax.php'),
        'plugin_url' => ARTITECHCORE_PLUGIN_URL,
        'post_id'    => $post ? $post->ID : 0,
        'nonces'     => array(
            'ajax'     => wp_create_nonce('artitechcore_ajax_nonce'),
            'main'     => wp_create_nonce('artitechcore_nonce'),
        ),
        'strings'    => array(
            'processing'    => esc_html__('Processing...', 'artitechcore'),
            'success'       => esc_html__('Action completed successfully.', 'artitechcore'),
            'error'         => esc_html__('An unexpected error occurred.', 'artitechcore'),
            'network_error' => esc_html__('Network failure. Please check your connection.', 'artitechcore')
        )
    ));

    // Also localize for the keyword analyzer specifically since it uses its own variable name
    wp_localize_script('artitechcore-keyword-analyzer', 'artitechcore_keyword_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('artitechcore_ajax_nonce')
    ));

    // Localize for Hierarchy
    wp_localize_script('artitechcore-hierarchy', 'artitechcoreHierarchy', array(
        'rest_url' => esc_url_raw(rest_url('artitechcore/v1/')),
        'nonce'    => wp_create_nonce('wp_rest')
    ));

    // Localize for CPT Management
    wp_localize_script('artitechcore-cpt-management', 'artitechcore_cpt_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('artitechcore_ajax_nonce')
    ));

    // Localize for AI Generator
    wp_localize_script('artitechcore-ai-generator', 'artitechcore_ai_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('artitechcore_ajax_nonce')
    ));

    // Localize for Schema Generator
    wp_localize_script('artitechcore-schema-generator', 'artitechcore_schema_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('artitechcore_ajax_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'artitechcore_admin_scripts');

/**
 * Enqueue Frontend Scripts
 */
function artitechcore_frontend_scripts() {
    wp_enqueue_script('artitechcore-frontend', ARTITECHCORE_PLUGIN_URL . 'assets/js/frontend.js', array(), ARTITECHCORE_VERSION, true);
    
    wp_localize_script('artitechcore-frontend', 'artitechcore_frontend_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('artitechcore_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'artitechcore_frontend_scripts');
