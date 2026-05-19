<?php
/**
 * Plugin Name: Artitech-Core
 * Plugin URI: https://github.com/DG10-Agency/Artitech-Core-WP
 * Description: The core engine for Artitech WP ecosystem, providing AI-powered page generation, hierarchy management, and structural organization.
 * Version: 1.1.2
 * Author: DG10 Agency
 * Text Domain: artitech-core
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include constants and core files.
// Include constants and core files early to prevent "function not defined" errors during bootstrap.
require_once plugin_dir_path( __FILE__ ) . 'includes/constants.php';

// Core components logic loaded at the top of the file.
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/brand-kit.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/admin-menu.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/page-creation.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/csv-handler.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/settings-page.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/api-utils.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/ai-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/menu-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/schema-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/keyword-analyzer.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/content-enhancer.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/website-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/website-generator-queue.php';

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Intentional bootstrap logging, plugin-owned table setup, and translation loading.


/**
 * Plugin activation hook
 * Sets up default options and initializes plugin data
 */
function ARTITECH_AI_activate() {
	// Set default plugin options
	$default_options = array(
			'ARTITECH_AI_version' => '1.1.2',
			'ARTITECH_AI_ai_provider' => 'openai',
			'ARTITECH_AI_openai_api_key' => '',
			'ARTITECH_AI_gemini_api_key' => '',
			'ARTITECH_AI_deepseek_api_key' => '',
			'ARTITECH_AI_brand_color' => '#4A90E2',
			'ARTITECH_AI_default_status' => 'draft',
			'ARTITECH_AI_auto_schema_generation' => true,
			'ARTITECH_AI_enable_image_generation' => true,
			'ARTITECH_AI_image_quality' => 'standard',
			'ARTITECH_AI_image_size' => '1024x1024',
			'ARTITECH_AI_max_tokens' => 1000,
			'ARTITECH_AI_temperature' => 0.5,
			'ARTITECH_AI_seo_intensity' => 'high',
			'ARTITECH_AI_api_timeout' => 120,
			'ARTITECH_AI_ai_rate_limit' => 20,
			'ARTITECH_AI_batch_size' => 10,
			'ARTITECH_AI_activation_date' => current_time('mysql'),
			'ARTITECH_AI_first_activation' => true
	);
	
	// Set options only if they don't exist
	foreach ($default_options as $option_name => $default_value) {
			if (get_option($option_name) === false) {
				add_option($option_name, $default_value);
			}
	}
	
	// Create custom database tables if needed
	ARTITECH_AI_create_database_tables();
	
	// Handle database migrations
	$installed_db_version = get_option('ARTITECH_AI_db_version', 0);
	if ($installed_db_version < ARTITECH_AI_DB_VERSION) {
			if ($installed_db_version < 1) {
				ARTITECH_AI_migrate_schema_data_v1();
			}
			update_option('ARTITECH_AI_db_version', ARTITECH_AI_DB_VERSION);
	}
	
	// Set activation flag
	update_option('ARTITECH_AI_plugin_activated', true);
	
	// Log activation
	if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('Artitech-Core Plugin Activated - DB Version ' . ARTITECH_AI_DB_VERSION);
	}

	// Schedule background queue cleanup if builder files are present
	if (!wp_next_scheduled('ARTITECH_AI_cleanup_old_builder_jobs')) {
			wp_schedule_event(time(), 'daily', 'ARTITECH_AI_cleanup_old_builder_jobs');
	}
}

/**
 * Initialize plugin components
 */
function ARTITECH_AI_init() {
	// Other initialization occurs below

	// Initialize brand kit if empty or not yet scanned (Init Shield protected)
	$brand_kit = get_option('ARTITECH_AI_brand_kit');
	$scan_completed = get_option('ARTITECH_AI_initial_scan_completed');
	
	if (empty($brand_kit) && !$scan_completed) {
			if (function_exists('ARTITECH_AI_auto_detect_brand_kit')) {
				update_option('ARTITECH_AI_brand_kit', ARTITECH_AI_auto_detect_brand_kit());
			}
	}
}
add_action('init', 'ARTITECH_AI_init');

/**
 * Plugin deactivation hook
 * Performs cleanup tasks when plugin is deactivated
 */
function ARTITECH_AI_deactivate() {
	// Check if user wants to persist data via bridge
	$persist_schema = get_option('ARTITECH_AI_persist_on_uninstall', 0);
	$persist_ce = get_option('ARTITECH_AI_ce_persist_features', []);
	
	// Create the bridge if anything is to be persisted
	if ($persist_schema || !empty($persist_ce)) {
			ARTITECH_AI_create_persistence_bridge($persist_schema, $persist_ce);
	}

	// Clear scheduled events
	wp_clear_scheduled_hook('ARTITECH_AI_cleanup_temporary_data');
	wp_clear_scheduled_hook('ARTITECH_AI_cleanup_old_builder_jobs');
	
	// Clear any cached data
	wp_cache_flush();
	
	// Set deactivation flag
	update_option('ARTITECH_AI_plugin_deactivated', true);
	update_option('ARTITECH_AI_deactivation_date', current_time('mysql'));
	
	// Log deactivation
	if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('Artitech-Core Plugin Deactivated');
	}
}


/**
 * Centralized WP_Filesystem initialization
 *
 * @return bool True if successful, False otherwise.
 */
function ARTITECH_AI_init_filesystem() {
	global $wp_filesystem;
	if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if (!function_exists('WP_Filesystem')) {
				return false;
			}
			// Use default method (direct usually)
			if (!WP_Filesystem()) {
				return false;
			}
	}
	return true;
}

/**
 * Creates a small MU-Plugin to keep schema/CE functional after uninstallation
 */
function ARTITECH_AI_create_persistence_bridge($persist_schema, $persist_ce) {
	$persist_schema = (bool) $persist_schema;
	$persist_ce = is_array($persist_ce) ? array_map('sanitize_key', $persist_ce) : [];
	
	$mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : (ABSPATH . 'wp-content/mu-plugins');
	if (!file_exists($mu_dir)) {
			wp_mkdir_p($mu_dir);
	}

	$bridge_code = "<?php\n/**\n * Artitech-Core Persistence Bridge\n * This is an auto-generated file to ensure data persistence after uninstallation.\n */\n\n";

	if ($persist_schema) {
			$bridge_code .= "add_action('wp_head', function() {\n";
			$bridge_code .= "    global \$wpdb;\n";
	}
	global $wp_filesystem;

	$wp_filesystem->delete($mu_dir . '/Artitech-Core-schema-bridge.php');
	return $wp_filesystem->put_contents($mu_dir . '/Artitech-Core-persistence-bridge.php', $bridge_code);
}

/**
 * Create custom database tables for plugin
 */
function ARTITECH_AI_create_database_tables() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	
	// Table for storing AI generation logs
	$table_name = $wpdb->prefix . 'ARTITECH_AI_generation_logs';
	
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
	$table_name = $wpdb->prefix . 'ARTITECH_AI_schema_data';
	
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
function ARTITECH_AI_migrate_schema_data_v1($batch_limit = 500) {
	global $wpdb;
	
	// 1. Concurrency Lock: Prevent multiple simultaneous migrations
	if (get_transient('ARTITECH_AI_migrating_schema')) {
			return false;
	}
	set_transient('ARTITECH_AI_migrating_schema', true, 300); // 5 minute lock

	$table_name = $wpdb->prefix . 'ARTITECH_AI_schema_data';
	$legacy_keys = array('_ARTITECH_AI_schema_type', '_ARTITECH_AI_schema_data', '_ARTITECH_AI_schema_markup', '_ARTITECH_AI_schema_origin', '_ARTITECH_AI_schema_locked');
	$placeholders = implode(',', array_fill(0, count($legacy_keys), '%s'));

	// --- PART A: POST META ---
	$post_ids = $wpdb->get_col($wpdb->prepare("
			SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key IN ($placeholders) 
			AND meta_value != ''
			LIMIT %d", array_merge($legacy_keys, [$batch_limit])));

	$processed_posts = 0;
	foreach ($post_ids as $post_id) {
			$metas = $wpdb->get_results($wpdb->prepare("
				SELECT meta_key, meta_value 
				FROM {$wpdb->postmeta} 
				WHERE post_id = %d 
				AND meta_key IN ($placeholders)
			", array_merge([$post_id], $legacy_keys)));

			if (empty($metas)) continue;

			$vals = array();
			foreach ($metas as $m) {
				$key = str_replace('_ARTITECH_AI_schema_', '', $m->meta_key);
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
							AND meta_key IN ($placeholders)
						", array_merge([$post_id], $legacy_keys)));
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
				WHERE meta_key IN ($placeholders) 
				AND meta_value != ''
				LIMIT %d", array_merge($legacy_keys, [$remaining_limit])));

			foreach ($term_ids as $term_id) {
				$metas = $wpdb->get_results($wpdb->prepare("
						SELECT meta_key, meta_value 
						FROM {$wpdb->termmeta} 
						WHERE term_id = %d 
						AND meta_key IN ($placeholders)
				", array_merge([$term_id], $legacy_keys)));

				if (empty($metas)) continue;

				$vals = array();
				foreach ($metas as $m) {
						$key = str_replace('_ARTITECH_AI_schema_', '', $m->meta_key);
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
									AND meta_key IN ($placeholders)
							", array_merge([$term_id], $legacy_keys)));
							$processed_terms++;
						}
				}
			}
	}

	delete_transient('ARTITECH_AI_migrating_schema');
	
	// Return true if more data potentially remains in this category
	return (count($post_ids) >= $batch_limit || (isset($term_ids) && count($term_ids) >= $remaining_limit));
}

/**
 * Drop custom database tables
 */
function ARTITECH_AI_drop_database_tables() {
	global $wpdb;
	
	$tables = array(
			$wpdb->prefix . 'ARTITECH_AI_generation_logs',
			$wpdb->prefix . 'ARTITECH_AI_schema_data'
	);
	
	foreach ($tables as $table) {
			$wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table));
	}
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'ARTITECH_AI_activate');
register_deactivation_hook(__FILE__, 'ARTITECH_AI_deactivate');
// No register_uninstall_hook needed; uninstall.php is used instead.

/**
 * Load plugin textdomain for internationalization
 */
function ARTITECH_AI_load_textdomain() {
	load_plugin_textdomain(
			ARTITECH_AI_TEXT_DOMAIN,
			false,
			dirname(plugin_basename(__FILE__)) . '/languages'
	);
}
add_action('init', 'ARTITECH_AI_load_textdomain');

// Output schema markup in wp_head is now handled in includes/schema-generator.php

// Include necessary files — load order matters.
// brand-kit.php must load before settings-page.php (settings UI renders the brand kit form)
// and before website-generator.php (builder uses ARTITECH_AI_get_brand_kit()).
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/brand-kit.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/admin-menu.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/page-creation.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/csv-handler.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/settings-page.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/api-utils.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/ai-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/custom-post-type-manager.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/hierarchy-manager.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/menu-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/schema-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/keyword-analyzer.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/content-enhancer.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/website-generator.php';
require_once ARTITECH_AI_PLUGIN_PATH . 'includes/website-generator-queue.php';

/**
 * Collection of all translatable strings for JavaScript
 *
 * @return array
 */
function ARTITECH_AI_get_js_strings() {
	return array(
			'advanced_mode' => array(
				'standard_title'   => __('Standard Mode', 'artitech-core'),
				'standard_desc'    => __('Creates standard pages only', 'artitech-core'),
				'advanced_title'   => __('Advanced Mode', 'artitech-core'),
				'advanced_desc'    => __('Analyzes your business and suggests custom post types with relevant fields', 'artitech-core'),
				'advanced_enabled' => __('Advanced Mode enabled - AI will analyze your business and suggest custom post types below', 'artitech-core'),
				'advanced_preview' => __('Advanced Mode will show business analysis and custom post type suggestions below', 'artitech-core'),
			),
			'ai_generator' => array(
				'starting'              => __('Connecting to AI Neural Network...', 'artitech-core'),
				'processing'            => __('Processing...', 'artitech-core'),
				'error_prefix'          => __('AI Generation failed: ', 'artitech-core'),
				'generic_generation_error' => __('AI Architect encountered an error during generation.', 'artitech-core'),
				'deploying'             => __('Deploying Architecture...', 'artitech-core'),
				'deployment_success'    => __('Ecosystem Deployed Successfully', 'artitech-core'),
				'deployment_success_msg' => __('Your AI-architected digital ecosystem has been successfully deployed and is ready for refinement.', 'artitech-core'),
				'deployment_error'      => __('Deployment Error:', 'artitech-core'),
				'deployment_error_net'  => __('Network failure during deployment. Please verify created pages before retrying.', 'artitech-core'),
				'generic_error'         => __('An error occurred during generation.', 'artitech-core'),
				'request_timeout'       => __('The architect is taking longer than usual. Please check back in a few minutes.', 'artitech-core'),
				'explore_pages'         => __('Explore Generated Pages', 'artitech-core'),
				'architectural_oversight' => __('Architectural Oversight', 'artitech-core'),
				'fill_fields_alert'     => __('Please enter your business type and mission details.', 'artitech-core'),
				'steps'                 => array(
						__('Analyzing brand architecture...', 'artitech-core'),
						__('Synthesizing content blueprints...', 'artitech-core'),
						__('Finalizing structural integrity...', 'artitech-core'),
				),
				'ai_steps'              => array(
						__('Interpreting business signals...', 'artitech-core'),
						__('Analyzing market competition...', 'artitech-core'),
						__('Defining neural post type architecture...', 'artitech-core'),
						__('Mapping SEO semantic relationships...', 'artitech-core'),
						__('Structuring custom field schemas...', 'artitech-core'),
						__('Finalizing ecosystem blueprint...', 'artitech-core'),
				),
				'deployment_steps'      => array(
						__('Initializing content deployment...', 'artitech-core'),
						__('Building page hierarchy...', 'artitech-core'),
						__('Architecting custom post types...', 'artitech-core'),
						__('Registering meta-field schemas...', 'artitech-core'),
						__('Creating taxonomic bridges...', 'artitech-core'),
						__('Deploying AI Featured Assets...', 'artitech-core'),
						__('Finalizing structural integrity...', 'artitech-core'),
				),
			),
			'common' => array(
				'analyzing_business'    => __('🤖 Analyzing Your Business with AI', 'artitech-core'),
				'analyzing_with_ai'     => __('🤖 Analyzing with AI...', 'artitech-core'),
				'checking'              => __('Checking...', 'artitech-core'),
				'cleaning'              => __('Cleaning...', 'artitech-core'),
				'crafting_structure'    => __('Crafting the perfect page structure for your needs...', 'artitech-core'),
				'creating_pages'        => __('🚀 Creating Pages...', 'artitech-core'),
				'detected_refreshing'   => __('✓ Detected! Refreshing...', 'artitech-core'),
				'detecting'             => __('Detecting...', 'artitech-core'),
				'error'                 => __('An unexpected error occurred.', 'artitech-core'),
				'error_prefix'          => __('✗ Error: ', 'artitech-core'),
				'fill_fields_alert'     => __('Please fill in both the Business Niche and Mission fields.', 'artitech-core'),
				'generating_pages'      => __('🚀 Generating Awesome Pages with Context-Aware AI', 'artitech-core'),
				'network_error'         => __('Network failure. Please check your connection.', 'artitech-core'),
				'network_error_retry'   => __('✗ Network error. Please try again.', 'artitech-core'),
				'please_wait'           => __('This may take a few moments. Please don\'t close this window...', 'artitech-core'),
				'processing'            => __('Processing...', 'artitech-core'),
				'rescan_brand'          => __('Rescan Brand Identity', 'artitech-core'),
				'rescan_website'        => __('Re-Scan Website', 'artitech-core'),
				'scanning'              => __('Scanning...', 'artitech-core'),
				'scanning_site'         => __('Scanning your site...', 'artitech-core'),
				'scanning_wait'         => __('⏳ Scanning...', 'artitech-core'),
				'success'               => __('Action completed successfully.', 'artitech-core'),
				'unknown_error'         => __('Unknown error', 'artitech-core'),
			),
			'cpt_manager' => array(
				'bulk_failed'           => __('Bulk operation failed', 'artitech-core'),
				'bulk_select_alert'     => __('Please select an action and at least one CPT', 'artitech-core'),
				'cancel'                => __('Cancel', 'artitech-core'),
				'create_failed'         => __('Failed to create CPT', 'artitech-core'),
				'create_success'        => __('Custom post type created successfully!', 'artitech-core'),
				/* translators: %s: post type name */
				'delete_confirm'        => __('Are you sure you want to delete this custom post type? This will permanently delete ALL %s posts of this type. This action cannot be undone.', 'artitech-core'),
				'delete_failed'         => __('Failed to delete CPT', 'artitech-core'),
				/* translators: 1: number of post types, 2: total posts */
				'delete_multi_confirm'  => __('Are you sure you want to delete %1$s custom post type(s)? This will permanently delete ALL %2$s posts belonging to these types. This action cannot be undone.', 'artitech-core'),
				/* translators: %s: number of post types */
				'delete_multi_simple'   => __('Are you sure you want to delete %s custom post type(s)? This action cannot be undone.', 'artitech-core'),
				'delete_simple'         => __('Are you sure you want to delete this custom post type? This action cannot be undone.', 'artitech-core'),
				'delete_success'        => __('Custom post type deleted successfully', 'artitech-core'),
				'description_label'     => __('Description:', 'artitech-core'),
				'display_label'         => __('Display Label:', 'artitech-core'),
				'duplicate_confirm'     => __('Are you sure you want to duplicate this custom post type?', 'artitech-core'),
				'duplicate_failed'      => __('Failed to duplicate CPT', 'artitech-core'),
				'duplicate_success'     => __('Custom post type duplicated successfully', 'artitech-core'),
				'edit_title'            => __('Edit Custom Post Type: ', 'artitech-core'),
				'field_added'           => __('New custom field added', 'artitech-core'),
				'field_label'           => __('Field Label', 'artitech-core'),
				'field_name'            => __('Field Name', 'artitech-core'),
				'field_removed'         => __('Custom field removed', 'artitech-core'),
				'field_required'        => __('This field is required', 'artitech-core'),
				'field_type'            => __('Field Type', 'artitech-core'),
				'menu_icon_label'       => __('Menu Icon:', 'artitech-core'),
				'name_format_error'     => __('Post type name must contain only lowercase letters, numbers, and underscores', 'artitech-core'),
				'name_length_error'     => __('Post type name must be 20 characters or less', 'artitech-core'),
				'network_error'         => __('Network error occurred', 'artitech-core'),
				'operation_results'     => __('Operation Results:', 'artitech-core'),
				'options_help'          => __('Enter options separated by commas', 'artitech-core'),
				'options_label'         => __('Options (comma-separated)', 'artitech-core'),
				'refresh_failed'        => __('Failed to refresh CPT list', 'artitech-core'),
				'refresh_success'       => __('CPT list refreshed successfully', 'artitech-core'),
				'remove'                => __('Remove', 'artitech-core'),
				/* translators: %s: field name */
				'remove_field_confirm'  => __('Are you sure you want to remove the "%s" field?', 'artitech-core'),
				'required'              => __('Required', 'artitech-core'),
				'save_changes'          => __('Save Changes', 'artitech-core'),
				/* translators: %s: tab name */
				'switched_tab'          => __('Switched to %s tab', 'artitech-core'),
				'title'                 => __('Title', 'artitech-core'),
				'untitled_field'        => __('Untitled field', 'artitech-core'),
				'update_failed'         => __('Failed to update CPT', 'artitech-core'),
				'update_success'        => __('CPT updated successfully', 'artitech-core'),
				'types' => array(
						'checkbox' => __('Checkbox', 'artitech-core'),
						'date'     => __('Date', 'artitech-core'),
						'email'    => __('Email', 'artitech-core'),
						'number'   => __('Number', 'artitech-core'),
						'select'   => __('Select', 'artitech-core'),
						'text'     => __('Text', 'artitech-core'),
						'textarea' => __('Textarea', 'artitech-core'),
						'url'      => __('URL', 'artitech-core'),
				),
			),
			'frontend' => array(
				'generic_error'    => __('An error occurred. Please try again.', 'artitech-core'),
				'connection_error' => __('Connection error. Please try again.', 'artitech-core'),
			),
			'hierarchy' => array(
				'copied'                => __('Copied!', 'artitech-core'),
				'error'                 => __('Error:', 'artitech-core'),
				'error_loading'         => __('Error loading hierarchy: ', 'artitech-core'),
				'last_edited'           => __('Last edited:', 'artitech-core'),
				'load_failed'           => __('Failed to load hierarchy data.', 'artitech-core'),
				'no_data'               => __('No hierarchy data found.', 'artitech-core'),
				'org_chart_error'       => __('Failed to generate Org Chart: ', 'artitech-core'),
				'root_node'             => __('Site Root', 'artitech-core'),
				'sub_pages'             => __('Sub-pages', 'artitech-core'),
				'view'                  => __('View', 'artitech-core'),
				'view_pages'            => __('View Pages', 'artitech-core'),
				'view_posts'            => __('View Posts', 'artitech-core'),
				'website_root'          => __('Website Root', 'artitech-core'),
			),
			'keyword_analyzer' => array(
				'avg_density'           => __('Avg Density', 'artitech-core'),
				'avg_relevance'         => __('Avg Relevance', 'artitech-core'),
				'analysis_date'         => __('Analysis Date', 'artitech-core'),
				'analysis_error'        => __('Analysis failed. Please try again.', 'artitech-core'),
				'analysis_failed'       => __('Analysis failed: ', 'artitech-core'),
				'add_variation'         => __('Add Variation', 'artitech-core'),
				'add_variation_placeholder' => __('Add variation...', 'artitech-core'),
				'content'               => __('Content', 'artitech-core'),
				'content_size'          => __('Content Size', 'artitech-core'),
				'coverage'              => __('% Coverage', 'artitech-core'),
				'detected_intent'       => __('Detected Intent:', 'artitech-core'),
				'enter_keywords_alert'  => __('Please enter keywords for analysis.', 'artitech-core'),
				'expand_first_alert'    => __('Please click "Expand with AI" first to review the semantic variations.', 'artitech-core'),
				'expansion_error'       => __('AI Expansion connection error.', 'artitech-core'),
				'expansion_failed'      => __('AI Expansion failed: ', 'artitech-core'),
				'fill_fields_alert'     => __('Please select a page and enter seed keywords first.', 'artitech-core'),
				'headings'              => __('Headings', 'artitech-core'),
				'healthy_mix'           => __('Healthy Mix', 'artitech-core'),
				'keywords_found'        => __('Keywords Found', 'artitech-core'),
				'keywords_label'        => __('Keywords: ', 'artitech-core'),
				'low_variety'           => __('Low Variety', 'artitech-core'),
				'memory_used'           => __('Memory Used', 'artitech-core'),
				'meta'                  => __('Meta', 'artitech-core'),
				'mentions'              => __(' Mentions', 'artitech-core'),
				'no_context'            => __('No context', 'artitech-core'),
				'no_recommendations'    => __('No specific recommendations.', 'artitech-core'),
				'not_found'             => __('Not found', 'artitech-core'),
				'page_title'            => __('Page Title', 'artitech-core'),
				'recommendation_title'  => __('Recommendation', 'artitech-core'),
				'select_page_alert'     => __('Please select a page or post to analyze.', 'artitech-core'),
				'select_page_default'   => __('Select a page...', 'artitech-core'),
				'seo_score'             => __('SEO Score', 'artitech-core'),
				'stuffing_risk'         => __('Stuffing Risk', 'artitech-core'),
				'title'                 => __('Title', 'artitech-core'),
				'total_keywords'        => __('Total Keywords', 'artitech-core'),
				'total_words'           => __('Total Words', 'artitech-core'),
				'url'                   => __('URL', 'artitech-core'),
				'view_page'             => __('View Page', 'artitech-core'),
				'word_count'            => __('Word Count', 'artitech-core'),
			),
			'schema_generator' => array(
				'back_to_preview'       => __('Back to Preview', 'artitech-core'),
				'copy_error'            => __('Failed to copy.', 'artitech-core'),
				'copy_success'          => __('Copied to clipboard!', 'artitech-core'),
				'delete_confirm'        => __('Are you sure you want to delete this schema entry?', 'artitech-core'),
				'edit_schema'           => __('Edit Schema', 'artitech-core'),
				'error_network'         => __('Network error. Please try again.', 'artitech-core'),
				'fetch_error'           => __('Could not fetch schema.', 'artitech-core'),
				'generate_schema'       => __('Generate Schema', 'artitech-core'),
				'generating'            => __('Generating...', 'artitech-core'),
				'invalid_json'          => __('Invalid JSON.', 'artitech-core'),
				'loading'               => __('Loading...', 'artitech-core'),
				'loading_schema'        => __('Loading schema...', 'artitech-core'),
				'missing_id'            => __('Missing page id', 'artitech-core'),
				'network_error'         => __('Network error occurred.', 'artitech-core'),
				'no_data'               => __('No schema data found for this item.', 'artitech-core'),
				'preview'               => __('Preview', 'artitech-core'),
				'preview_title'         => __('Schema Markup Preview', 'artitech-core'),
				'saved'                 => __('Saved', 'artitech-core'),
				'save_failed'           => __('Save failed', 'artitech-core'),
				'save_schema'           => __('Save Schema', 'artitech-core'),
				'save_success'          => __('Schema saved successfully!', 'artitech-core'),
				'saving'                => __('Saving...', 'artitech-core'),
				'type_article'          => __('Article', 'artitech-core'),
				'type_generic'          => __('Website', 'artitech-core'),
				'type_local'            => __('Local Business', 'artitech-core'),
				'valid_json'            => __('Valid JSON', 'artitech-core'),
			),
			'scripts' => array(
				'auto_detect_btn'       => __('Auto-Detect Brand Info', 'artitech-core'),
			),
			'settings' => array(
				'advanced_mode_off'     => __('Standard interface restored.', 'artitech-core'),
				'advanced_mode_on'      => __('Advanced settings enabled. Exercise caution.', 'artitech-core'),
				'brand_kit_needed'      => __('Brand Kit configuration required for premium AI generation.', 'artitech-core'),
				'checking'              => __('Checking...', 'artitech-core'),
				'cleaning'              => __('Cleaning...', 'artitech-core'),
				'confirm_reset'         => __('Are you sure you want to reset all settings to defaults? This cannot be undone.', 'artitech-core'),
				'loading'               => __('Loading System Assets...', 'artitech-core'),
				'rescan_brand'          => __('Rescan Brand Identity', 'artitech-core'),
				'rescan_website'        => __('Re-Scan Website', 'artitech-core'),
				'scanning'              => __('Scanning...', 'artitech-core'),
			),
			'taxonomy' => array(
				'creating'        => __('Creating...', 'artitech-core'),
				'deleting'        => __('Deleting...', 'artitech-core'),
				'delete_confirm'  => __('Are you sure you want to delete this taxonomy?', 'artitech-core'),
				'error_name'      => __('Please enter a taxonomy name.', 'artitech-core'),
				'error_singular'  => __('Please enter a singular name.', 'artitech-core'),
				'error_generic'   => __('An error occurred. Please try again.', 'artitech-core'),
			),
	);
}

// Enqueue scripts and styles
function ARTITECH_AI_admin_scripts($hook) {
	global $post; // Required to access current post object in post/page editing context
	$current_screen = get_current_screen();
	if (!$current_screen) return;

	// Get translatable strings
	$i18n_strings = ARTITECH_AI_get_js_strings();

	// Enqueue brand CSS first (Variables & Base)
	wp_enqueue_style('Artitech-Core-dg10-brand', ARTITECH_AI_PLUGIN_URL . 'assets/css/dg10-brand.css', array(), '1.0');
	
	// Enqueue Consolidated Admin UI CSS
	wp_enqueue_style('Artitech-Core-admin-ui', ARTITECH_AI_PLUGIN_URL . 'assets/css/admin-ui.css', array('Artitech-Core-dg10-brand'), filemtime(ARTITECH_AI_PLUGIN_PATH . 'assets/css/admin-ui.css'));

	// Enqueue Schema Column Styles (Specific for Pages/Posts lists)
	if ($current_screen->base === 'edit' || $current_screen->base === 'upload') {
			wp_enqueue_style('Artitech-Core-schema-column', ARTITECH_AI_PLUGIN_URL . 'assets/css/schema-column.css', array(), filemtime(ARTITECH_AI_PLUGIN_PATH . 'assets/css/schema-column.css'));
	}

	// Enqueue CPT Management CSS
	wp_enqueue_style('Artitech-Core-cpt-management', ARTITECH_AI_PLUGIN_URL . 'assets/css/cpt-management.css', array('Artitech-Core-admin-ui'), '1.0');

	// --- SCRIPTS ---

	// 1. External dependencies (shared)
	wp_enqueue_script('jquery');
	
	// 2. Global Admin Scripts (Foundation)
	wp_enqueue_script('Artitech-Core-scripts', ARTITECH_AI_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '1.0', true);

	// Provide UNIFIED data object to all script handles
	wp_localize_script('Artitech-Core-scripts', 'ARTITECH_AI_data', array(
			'ajaxurl'             => admin_url('admin-ajax.php'),
			'rest_url'            => esc_url_raw(rest_url('Artitech-Core/v1/')),
			'plugin_url'          => ARTITECH_AI_PLUGIN_URL,
			'post_id'             => $post ? $post->ID : 0,
			'ajax_nonce'          => wp_create_nonce('ARTITECH_AI_ajax_nonce'),
			'rest_nonce'          => wp_create_nonce('wp_rest'),
			'maintenance_nonce'   => wp_create_nonce('ARTITECH_AI_maintenance_nonce'),
			'delete_taxonomy_nonce' => wp_create_nonce('ARTITECH_AI_delete_taxonomy'),
			'strings'             => $i18n_strings
	));

	// 3. Hierarchy Dependencies (only where needed)
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI state check, no state modification.
	if (strpos($current_screen->id, 'Artitech-Core-') !== false && isset($_GET['tab']) && 'hierarchy' === sanitize_key(wp_unslash($_GET['tab']))) {
			wp_enqueue_style('jstree', ARTITECH_AI_PLUGIN_URL . 'assets/vendor/jstree/themes/default/style.min.css', array(), '3.3.15');
			wp_enqueue_script('jstree', ARTITECH_AI_PLUGIN_URL . 'assets/vendor/jstree/jstree.min.js', array('jquery'), '3.3.15', true);
			wp_enqueue_script('d3', ARTITECH_AI_PLUGIN_URL . 'assets/vendor/d3/d3.v7.min.js', array(), '7.0.0', true);
			wp_enqueue_style('Artitech-Core-hierarchy', ARTITECH_AI_PLUGIN_URL . 'assets/css/hierarchy.css', array(), ARTITECH_AI_VERSION);
			wp_enqueue_script('Artitech-Core-hierarchy', ARTITECH_AI_PLUGIN_URL . 'assets/js/hierarchy.js', array('jquery', 'jstree', 'd3', 'Artitech-Core-scripts'), ARTITECH_AI_VERSION, true);
	}

	// 4. Post/Page Editing Context
	if ($hook === 'post-new.php' || $hook === 'post.php') {
			$supported_types = get_option('ARTITECH_AI_ce_post_types', ['post']);
			if ($post && is_array($supported_types) && in_array($post->post_type, $supported_types)) {
				wp_add_inline_style('Artitech-Core-admin-ui', '
						.Artitech-Core-ce-panel { background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px; }
						.Artitech-Core-ce-field { margin-bottom: 20px; }
						.Artitech-Core-ce-field label { font-weight: bold; display: block; margin-bottom: 5px; }
						.Artitech-Core-ce-field textarea { width: 100%; min-height: 100px; }
						.Artitech-Core-ce-field input[type="text"] { width: 100%; }
						.Artitech-Core-ce-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; flex-wrap: wrap; gap: 10px; }
						.Artitech-Core-ce-badge { background: #b47cfd; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
						.Artitech-Core-ce-warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 15px; margin-bottom: 15px; font-size: 13px; }
				');
			}
	}

	// 5. Plugin Page Specific Scripts
	if (strpos($current_screen->id, 'Artitech-Core-') !== false) {
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab switching for script enqueuing only. */
			$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';

			if ($tab === 'cpt') {
				wp_enqueue_script('Artitech-Core-cpt-management', ARTITECH_AI_PLUGIN_URL . 'assets/js/cpt-management.js', array('jquery', 'Artitech-Core-scripts'), '1.0', true);
				wp_enqueue_script('Artitech-Core-cpt-manager', ARTITECH_AI_PLUGIN_URL . 'assets/js/cpt-manager.js', array('jquery', 'Artitech-Core-scripts'), '1.0', true);
			}

			if ($tab === 'ai') {
				wp_enqueue_script('Artitech-Core-ai-generator', ARTITECH_AI_PLUGIN_URL . 'assets/js/ai-generator.js', array('jquery', 'Artitech-Core-scripts'), '1.0', true);
			}

			if ($tab === 'hierarchy') {
				 // Already enqueued above in section 3 if tab matches
			}

			// Always load schema generator on plugin pages for preview
			wp_enqueue_script('Artitech-Core-schema-generator', ARTITECH_AI_PLUGIN_URL . 'assets/js/schema-generator.js', array('jquery', 'Artitech-Core-scripts'), filemtime(ARTITECH_AI_PLUGIN_PATH . 'assets/js/schema-generator.js'), true);

			// Load settings-specific scripts
			wp_enqueue_script('Artitech-Core-settings', ARTITECH_AI_PLUGIN_URL . 'assets/js/settings.js', array('jquery', 'Artitech-Core-scripts'), filemtime(ARTITECH_AI_PLUGIN_PATH . 'assets/js/settings.js'), true);
	}

	// 6. Global Context Scripts (available everywhere)
	wp_enqueue_script('Artitech-Core-keyword-analyzer', ARTITECH_AI_PLUGIN_URL . 'assets/js/keyword-analyzer.js', array('jquery', 'Artitech-Core-scripts'), '1.0', true);

	// 7. Settings Specific (Color Picker)
	if (strpos($current_screen->id, 'Artitech-Core-') !== false) {
			wp_enqueue_script('wp-color-picker');
			wp_enqueue_style('wp-color-picker');
	}
}
add_action('admin_enqueue_scripts', 'ARTITECH_AI_admin_scripts');

/**
 * Enqueue Frontend Scripts
 */
function ARTITECH_AI_frontend_scripts() {
	wp_enqueue_script('Artitech-Core-frontend', ARTITECH_AI_PLUGIN_URL . 'assets/js/frontend.js', array(), ARTITECH_AI_VERSION, true);
	
	// Provide unified data to frontend as well
	wp_localize_script('Artitech-Core-frontend', 'ARTITECH_AI_data', array(
			'ajaxurl'    => admin_url('admin-ajax.php'),
			'ajax_nonce' => wp_create_nonce('ARTITECH_AI_ajax_nonce'),
			'strings'    => ARTITECH_AI_get_js_strings()
	));
}
add_action('wp_enqueue_scripts', 'ARTITECH_AI_frontend_scripts');

// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound

