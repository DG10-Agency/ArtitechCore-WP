<?php
/**
 * ArtitechCore Link Consent Handler
 *
 * Manages opt-in consent for external links in the admin area.
 * WP.org Guideline #10 requires explicit user permission for external links.
 *
 * @package ArtitechCore
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ArtitechCore_Link_Consent
 *
 * Handles the opt-in consent mechanism for external DG10 website and
 * Calendly links displayed in the plugin admin menu.
 */
class ArtitechCore_Link_Consent {

    /**
     * Option name stored in wp_options.
     */
    const OPTION_NAME = 'artitechcore_link_consent';

    /**
     * Initialize the consent system: register hooks.
     */
    public static function init() {
        // Check for consent decision on admin_init
        add_action('admin_init', array(__CLASS__, 'handle_consent_decision'));

        // Show admin notice if consent is undecided
        add_action('admin_notices', array(__CLASS__, 'admin_consent_notice'));

        // On plugin activation, set the consent option to undecided
        register_activation_hook(ARTITECHCORE_PLUGIN_PATH . 'artitechcore-for-wordpress.php', array(__CLASS__, 'on_activation'));
    }

    /**
     * Plugin activation: set consent option to empty (undecided).
     */
    public static function on_activation() {
        if (get_option(self::OPTION_NAME) === false) {
            add_option(self::OPTION_NAME, '');
        }
    }

    /**
     * Handle consent decision from the admin notice buttons.
     * Listens for $_GET['link_consent'] and saves the preference.
     */
    public static function handle_consent_decision() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['link_consent'])) {
            return;
        }

        $consent = sanitize_key(wp_unslash($_GET['link_consent']));

        if (!in_array($consent, array('yes', 'no'), true)) {
            return;
        }

        update_option(self::OPTION_NAME, $consent);

        // Prevent resubmission: redirect back to the same page without the query arg
        $redirect_url = remove_query_arg('link_consent', wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
        wp_safe_redirect(esc_url_raw($redirect_url));
        exit;
    }

    /**
     * Display admin notice asking for consent if undecided.
     * Only shows on first admin page load after activation.
     */
    public static function admin_consent_notice() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'artitechcore') === false) {
            return;
        }

        // If already decided, don't show notice
        $consent = get_option(self::OPTION_NAME, '');
        if ($consent === 'yes' || $consent === 'no') {
            return;
        }

        $yes_url = add_query_arg('link_consent', 'yes', admin_url('admin.php?page=artitechcore'));
        $no_url  = add_query_arg('link_consent', 'no', admin_url('admin.php?page=artitechcore'));
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php esc_html_e('ArtitechCore is a free plugin by DG10 Agency. Would you like to show a small support link in the admin area to help us continue development? This is completely optional and can be changed anytime in Settings → ArtitechCore.', 'artitechcore'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url($yes_url); ?>" class="button button-primary" style="margin-right: 10px;">
                    <?php esc_html_e('Yes, show support link', 'artitechcore'); ?>
                </a>
                <a href="<?php echo esc_url($no_url); ?>" class="button button-secondary">
                    <?php esc_html_e('No thanks', 'artitechcore'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Check if external links should be displayed.
     *
     * @return bool True if consent has been given.
     */
    public static function is_allowed() {
        return get_option(self::OPTION_NAME, '') === 'yes';
    }
}
