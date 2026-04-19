<?php
/**
 * ArtitechCore Uninstall
 *
 * This file is run when the plugin is uninstalled (deleted).
 * It cleans up plugin-specific options and settings from the database.
 *
 * @package ArtitechCore
 * @since 1.1.2
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Consolidate logic from main plugin file
global $wpdb;

// Check what we should persist
$persist_schema = get_option('artitechcore_persist_on_uninstall', 0);
$persist_ce = get_option('artitechcore_ce_persist_features', []);
if (!is_array($persist_ce)) {
    $persist_ce = [];
}

$mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (ABSPATH . 'wp-content/mu-plugins');

if ($persist_schema || !empty($persist_ce)) {
    // We assume the main plugin file's bridge creation was already called 
    // or we can call it here if we include the main file (not recommended).
    // Instead, we just ensure we don't delete the data we want to persist.
    error_log('ArtitechCore Uninstall: Persisting data via bridge.');
} else {
    // Clean up any old bridge files
    @unlink($mu_dir . '/artitechcore-schema-bridge.php');
    @unlink($mu_dir . '/artitechcore-persistence-bridge.php');
}

// Common options to delete regardless of persistence (AI keys, etc. should usually be removed for security)
$options_to_remove = array(
    'artitechcore_ai_provider',
    'artitechcore_openai_api_key',
    'artitechcore_gemini_api_key',
    'artitechcore_deepseek_api_key',
    'artitechcore_api_timeout',
    'artitechcore_batch_size',
    'artitechcore_first_activation',
    'artitechcore_plugin_activated',
    'artitechcore_plugin_deactivated',
    'artitechcore_deactivation_date',
    'artitechcore_activation_date'
);

foreach ($options_to_remove as $option) {
    delete_option($option);
    delete_site_option($option);
}

// Logic for clean uninstall (deleting user content and tables)
// Handle options removal selectively
$options_to_remove = array(
    'artitechcore_version',
    'artitechcore_db_version',
    'artitechcore_brand_color',
    'artitechcore_default_status',
    'artitechcore_auto_schema_generation',
    'artitechcore_enable_image_generation',
    'artitechcore_image_quality',
    'artitechcore_image_size',
    'artitechcore_max_tokens',
    'artitechcore_temperature',
    'artitechcore_seo_intensity',
    'artitechcore_persist_on_uninstall',
    'artitechcore_dynamic_cpts',
    'artitechcore_dynamic_taxonomies',
    'artitechcore_flush_rewrite_rules',
    'artitechcore_custom_fields',
    'artitechcore_brand_kit',
    'artitechcore_business_name',
    'artitechcore_business_description',
    'artitechcore_business_address',
    'artitechcore_business_phone',
    'artitechcore_business_email',
    'artitechcore_business_social_facebook',
    'artitechcore_business_social_twitter',
    'artitechcore_business_social_linkedin',
    'artitechcore_ce_enabled',
    'artitechcore_ce_post_types',
    'artitechcore_ce_persist_features',
    'artitechcore_ce_kt_heading',
    'artitechcore_ce_conclusion_heading',
    'artitechcore_ce_cta_mode',
    'artitechcore_ce_cta_shortcode',
    'artitechcore_ce_cta_native_fields',
    'artitechcore_ce_cta_native_email',
    'artitechcore_ce_cta_native_button',
    'artitechcore_sitemap_url'
);

foreach ($options_to_remove as $option) {
    delete_option($option);
    delete_site_option($option);
}

// Delete options with dynamic suffixes
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'artitechcore_cpt_modified_%'");

// Custom Table Deletion based on persistence
if (!$persist_schema) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}artitechcore_schema_data");
}

// Always drop queue table as it's for internal operations
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}artitechcore_queue");

// Selective Post Meta Deletion
// Build exclusion list based on $persist_ce
$ce_meta_to_exclude = [];
if (in_array('key_takeaways', $persist_ce)) $ce_meta_to_exclude[] = '_artitechcore_ce_key_takeaways';
if (in_array('conclusion', $persist_ce))    $ce_meta_to_exclude[] = '_artitechcore_ce_conclusion';
if (in_array('faq', $persist_ce))           $ce_meta_to_exclude[] = '_artitechcore_ce_faq';
if (in_array('cta', $persist_ce)) {
    $ce_meta_to_exclude[] = '_artitechcore_ce_cta_heading';
    $ce_meta_to_exclude[] = '_artitechcore_ce_cta_description';
}

if (empty($ce_meta_to_exclude)) {
    // If nothing to persist, delete all artitechcore meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'artitechcore_%'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_artitechcore_%'");
} else {
    // Delete non-excluded meta
    $placeholders = implode(',', array_fill(0, count($ce_meta_to_exclude), '%s'));
    $query = $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE (meta_key LIKE 'artitechcore_%%' OR meta_key LIKE '_artitechcore_%%') AND meta_key NOT IN ($placeholders)",
        $ce_meta_to_exclude
    );
    $wpdb->query($query);
}

// Clear scheduled jobs
wp_clear_scheduled_hook('artitechcore_cleanup_old_builder_jobs');

// Flush rewrite rules
flush_rewrite_rules();
