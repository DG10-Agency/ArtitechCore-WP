<?php
/**
 * ArtitechCore Uninstall
 *
 * ⚠️ IMPORTANT: This file only runs when the plugin is DELETED through WP Admin.
 * For routine updates, DO NOT delete the plugin. Instead:
 *   1. Deactivate the plugin
 *   2. Upload the new zip (Add New → Upload → "Replace current")
 *   3. Reactivate
 * This preserves ALL your settings in the database.
 *
 * This uninstaller is DESIGNED TO BE SAFE by default:
 * - Business settings, brand kit, colors, and configurations are PRESERVED
 * - Only API keys are removed (security concern for abandoned sites)
 * - Custom Post Types and their content are preserved
 * - Only cleanup: scheduled hooks, transient caches, and internal markers
 *
 * @package ArtitechCore
 * @since 1.1.2
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// ─── DANGER: Set these to true ONLY if you intentionally want to destroy data ───
$remove_api_keys   = false; // Set true to delete API keys from database
$remove_tables     = false; // Set true to drop custom database tables
$remove_post_meta  = false; // Set true to delete all ArtitechCore post meta
$remove_all_opts   = false; // Set true to COMPLETELY WIPE all plugin settings
// ⚠️ Setting any of the above to true will DESTROY data that cannot be recovered.

// ─── Always clean up: transient state, hooks, and sync files ───

// Clear scheduled cron jobs
wp_clear_scheduled_hook('artitechcore_cleanup_old_builder_jobs');
wp_clear_scheduled_hook('artitechcore_cleanup_temporary_data');

// Remove persistence bridge MU-plugin (if exists)
$mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (ABSPATH . 'wp-content/mu-plugins');
@wp_delete_file($mu_dir . '/artitechcore-schema-bridge.php');
@wp_delete_file($mu_dir . '/artitechcore-persistence-bridge.php');

// Delete internal flags and transient state (safe — auto-recreated on install)
$transient_options = array(
    'artitechcore_first_activation',
    'artitechcore_plugin_activated',
    'artitechcore_plugin_deactivated',
    'artitechcore_deactivation_date',
    'artitechcore_activation_date',
    'artitechcore_db_version',
);
foreach ($transient_options as $option) {
    delete_option($option);
    delete_site_option($option);
}

// Delete dynamic CPT-modified timestamps (transient state)
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
    $wpdb->esc_like('artitechcore_cpt_modified_') . '%'
));

// ─── Optionally remove API keys (security) ───
if ($remove_api_keys) {
    $api_options = array(
        'artitechcore_ai_provider',
        'artitechcore_openai_api_key',
        'artitechcore_gemini_api_key',
        'artitechcore_deepseek_api_key',
    );
    foreach ($api_options as $option) {
        delete_option($option);
        delete_site_option($option);
    }
}

// ─── Optionally drop custom tables ───
if ($remove_tables) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}artitechcore_schema_data");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}artitechcore_queue");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}artitechcore_generation_logs");
}

// ─── Optionally remove all post meta ───
if ($remove_post_meta) {
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        $wpdb->esc_like('artitechcore_') . '%'
    ));
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        $wpdb->esc_like('_artitechcore_') . '%'
    ));
}

// ─── Optionally COMPLETELY wipe all plugin settings ───
if ($remove_all_opts) {
    $all_options = array(
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
        'artitechcore_api_timeout',
        'artitechcore_batch_size',
        'artitechcore_ai_rate_limit',
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
        'artitechcore_initial_scan_completed',
        'artitechcore_ai_schema_enrichment',
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
        'artitechcore_sitemap_url',
        'artitechcore_ai_provider',
        'artitechcore_openai_api_key',
        'artitechcore_gemini_api_key',
        'artitechcore_deepseek_api_key',
    );
    foreach ($all_options as $option) {
        delete_option($option);
        delete_site_option($option);
    }
}

// Flush rewrite rules
flush_rewrite_rules();
