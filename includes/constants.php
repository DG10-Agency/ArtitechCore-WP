<?php
/**
 * Artitech-Core Constants
 *
 * This file defines all core constants used by the plugin to ensure consistency
 * and prevent redundant definitions or naming collisions.
 *
 * @package Artitech-Core
 * @since 1.1.3
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Plugin Versioning
if (!defined('ARTITECH_AI_VERSION')) {
	define('ARTITECH_AI_VERSION', '1.1.2');
}
if (!defined('ARTITECH_AI_DB_VERSION')) {
	define('ARTITECH_AI_DB_VERSION', 1);
}

// Text Domain
if (!defined('ARTITECH_AI_TEXT_DOMAIN')) {
	define('ARTITECH_AI_TEXT_DOMAIN', 'artitech-core');
}

// Plugin Paths and URLs
if (!defined('ARTITECH_AI_PLUGIN_PATH')) {
	define('ARTITECH_AI_PLUGIN_PATH', plugin_dir_path(dirname(__FILE__)));
}
if (!defined('ARTITECH_AI_PLUGIN_URL')) {
	define('ARTITECH_AI_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
}
if (!defined('ARTITECH_AI_GITHUB_URL')) {
	define('ARTITECH_AI_GITHUB_URL', 'https://github.com/DG10-Agency/Artitech-Core-WP');
}

// AI Settings & Parameters
if (!defined('ARTITECH_AI_API_TIMEOUT')) {
	define('ARTITECH_AI_API_TIMEOUT', 120);
}
if (!defined('ARTITECH_AI_AI_TEMPERATURE')) {
	define('ARTITECH_AI_AI_TEMPERATURE', 0.7);
}
if (!defined('ARTITECH_AI_AI_MAX_TOKENS')) {
	define('ARTITECH_AI_AI_MAX_TOKENS', 4000);
}

// Generation Limits (Moved from website-generator.php to centralize)
if (!defined('ARTITECH_AI_MAX_PAGES_PER_BATCH')) {
	define('ARTITECH_AI_MAX_PAGES_PER_BATCH', 50);
}
if (!defined('ARTITECH_AI_MAX_PAGES_PER_TYPE')) {
	define('ARTITECH_AI_MAX_PAGES_PER_TYPE', 20);
}
if (!defined('ARTITECH_AI_QUEUE_EXPIRY')) {
	define('ARTITECH_AI_QUEUE_EXPIRY', 48 * HOUR_IN_SECONDS);
}

// AI Model Defaults
if (!defined('ARTITECH_AI_IMAGE_MODEL')) {
	define('ARTITECH_AI_IMAGE_MODEL', 'dall-e-3');
}
if (!defined('ARTITECH_AI_IMAGE_SIZE')) {
	define('ARTITECH_AI_IMAGE_SIZE', '1024x1024');
}
