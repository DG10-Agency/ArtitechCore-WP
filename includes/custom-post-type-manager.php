<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Custom Post Type Manager for ArtitechCore - Complete Overhaul
 * Handles dynamic custom post type registration, management, and integration
 * 
 * @package ArtitechCore
 * @version 1.0
 * @author DG10 Agency
 * @since 1.0
 */

// Initialize custom post type manager with enhanced security and performance
function artitechcore_init_custom_post_type_manager() {
    // Register existing dynamic CPTs on init with caching
    add_action('init', 'artitechcore_register_existing_dynamic_cpts', 20);
    
    // Add admin menu for CPT management - REMOVED to consolidate into main ArtitechCore menu
    // add_action('admin_menu', 'artitechcore_add_cpt_management_menu');
    
    // Add REST API endpoints for CPT data
    add_action('rest_api_init', 'artitechcore_register_cpt_rest_endpoints');
    
    // Add CPT data to hierarchy export
    add_filter('artitechcore_hierarchy_export_data', 'artitechcore_add_cpt_to_hierarchy_export');
    
    // Add CPT archives to menu generation
    add_filter('artitechcore_menu_generation_pages', 'artitechcore_add_cpt_archives_to_menus');
    
    // Add schema generation for CPTs
    add_action('artitechcore_generate_schema_for_post', 'artitechcore_generate_cpt_schema', 10, 2);
    
    // Add AJAX handlers for CPT management
    add_action('wp_ajax_artitechcore_create_cpt_ajax', 'artitechcore_handle_cpt_creation_ajax');
    add_action('wp_ajax_artitechcore_delete_cpt_ajax', 'artitechcore_handle_cpt_deletion_ajax');
    add_action('wp_ajax_artitechcore_get_cpt_data', 'artitechcore_get_cpt_data_ajax');
    add_action('wp_ajax_artitechcore_bulk_cpt_operations', 'artitechcore_handle_bulk_cpt_operations_ajax');
    add_action('wp_ajax_artitechcore_update_cpt_ajax', 'artitechcore_handle_cpt_update_ajax');
    add_action('wp_ajax_artitechcore_duplicate_cpt', 'artitechcore_handle_duplicate_cpt_ajax');
    add_action('wp_ajax_artitechcore_get_cpt_item_count', 'artitechcore_handle_get_cpt_item_count_ajax');
    
    // Add custom field meta boxes and save handlers
    add_action('add_meta_boxes', 'artitechcore_add_custom_field_meta_boxes');
    add_action('save_post', 'artitechcore_save_custom_field_data', 10, 2);
    
    // Performance optimization: Clear cache when CPTs are updated
    add_action('updated_option', 'artitechcore_clear_cpt_cache', 10, 3);
    
    // Security: Add capability checks
    add_action('admin_init', 'artitechcore_check_cpt_management_capabilities');
    
    // --- Custom Taxonomy Manager Hooks ---
    // Register existing dynamic Taxonomies on init
    add_action('init', 'artitechcore_register_existing_dynamic_taxonomies', 21);
    
    // AJAX handlers for Taxonomy management
    add_action('wp_ajax_artitechcore_create_taxonomy_ajax', 'artitechcore_handle_taxonomy_creation_ajax');
    add_action('wp_ajax_artitechcore_delete_taxonomy_ajax', 'artitechcore_handle_taxonomy_deletion_ajax');
}
add_action('plugins_loaded', 'artitechcore_init_custom_post_type_manager');

/**
 * Register existing dynamic CPTs with caching and performance optimization
 * 
 * @since 1.0
 */
function artitechcore_register_existing_dynamic_cpts() {
    // Check cache first for performance
    $cached_cpts = wp_cache_get('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    if (false === $cached_cpts) {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
        wp_cache_set('artitechcore_dynamic_cpts', $dynamic_cpts, 'artitechcore_cpt_cache', HOUR_IN_SECONDS);
        $cached_cpts = $dynamic_cpts;
    }
    
    if (!empty($cached_cpts) && is_array($cached_cpts)) {
        foreach ($cached_cpts as $post_type => $cpt_data) {
            if (artitechcore_validate_cpt_data($cpt_data)) {
        artitechcore_register_dynamic_custom_post_type($cpt_data);
            }
        }
    }
}

/**
 * Complete CPT registration function with security and validation
 * Moved from ai-generator.php and enhanced
 * 
 * @param array $cpt_data CPT configuration data
 * @return bool|WP_Error Success status or error object
 * @since 1.0
 */
function artitechcore_register_dynamic_custom_post_type($cpt_data) {
    // Validate input data
    if (!artitechcore_validate_cpt_data($cpt_data)) {
        return new WP_Error('invalid_cpt_data', __('Invalid CPT data provided', 'artitechcore'));
    }
    
    // Sanitize all input data
    $post_type = sanitize_key($cpt_data['name']);
    $label = sanitize_text_field($cpt_data['label']);
    $description = sanitize_textarea_field($cpt_data['description'] ?? '');
    
    // Validate post type name
    if (empty($post_type) || strlen($post_type) > 20) {
        return new WP_Error('invalid_post_type', __('Invalid post type name', 'artitechcore'));
    }
    
    // Build comprehensive labels array
    $labels = array(
        'name'                  => $label,
        'singular_name'         => $label,
        'menu_name'             => $label,
        'name_admin_bar'        => $label,
        'archives'              => $label . ' Archives',
        'attributes'            => $label . ' Attributes',
        'parent_item_colon'     => 'Parent ' . $label . ':',
        'all_items'             => 'All ' . $label,
        'add_new_item'          => 'Add New ' . $label,
        'add_new'               => 'Add New',
        'new_item'              => 'New ' . $label,
        'edit_item'             => 'Edit ' . $label,
        'update_item'           => 'Update ' . $label,
        'view_item'             => 'View ' . $label,
        'view_items'            => 'View ' . $label,
        'search_items'          => 'Search ' . $label,
        'not_found'             => 'No ' . strtolower($label) . ' found',
        'not_found_in_trash'    => 'No ' . strtolower($label) . ' found in Trash',
        'featured_image'        => 'Featured Image',
        'set_featured_image'    => 'Set featured image',
        'remove_featured_image' => 'Remove featured image',
        'use_featured_image'    => 'Use as featured image',
        'insert_into_item'      => 'Insert into ' . strtolower($label),
        'uploaded_to_this_item' => 'Uploaded to this ' . strtolower($label),
        'items_list'            => $label . ' list',
        'items_list_navigation' => $label . ' list navigation',
        'filter_items_list'     => 'Filter ' . strtolower($label) . ' list',
    );
    
    // Configure CPT arguments with security and performance in mind
    $args = array(
        'label'                 => $label,
        'labels'                => $labels,
        'description'           => $description,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_in_nav_menus'     => true,
        'show_in_admin_bar'     => true,
        'show_in_rest'          => true,
        'rest_base'             => $post_type,
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'rest_namespace'        => 'wp/v2',
        'has_archive'           => true,
        'hierarchical'          => isset($cpt_data['hierarchical']) ? (bool) $cpt_data['hierarchical'] : false,
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions', 'author', 'page-attributes'),
        'taxonomies'            => isset($cpt_data['taxonomies']) && is_array($cpt_data['taxonomies']) && !empty($cpt_data['taxonomies']) ? $cpt_data['taxonomies'] : array('category', 'post_tag'),
        'menu_icon'             => sanitize_text_field($cpt_data['menu_icon'] ?? 'dashicons-admin-post'),
        'menu_position'         => (int) ($cpt_data['menu_position'] ?? 25),
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'query_var'             => true,
        'can_export'            => true,
        'delete_with_user'      => false,
    );
    
    // Apply filters for extensibility
    $args = apply_filters('artitechcore_cpt_registration_args', $args, $post_type, $cpt_data);
    
    // Register the post type
    $result = register_post_type($post_type, $args);
    
    if (is_wp_error($result)) {
        error_log('ArtitechCore CPT Registration Error: ' . $result->get_error_message());
        return $result;
    }
    
    // Register custom fields if provided
    if (!empty($cpt_data['custom_fields']) && is_array($cpt_data['custom_fields'])) {
        $field_result = artitechcore_register_custom_fields($post_type, $cpt_data['custom_fields']);
        if (is_wp_error($field_result)) {
            return $field_result;
        }
    }
    
    // Store CPT data for persistence with proper sanitization
    $existing_cpts = get_option('artitechcore_dynamic_cpts', array());
    $existing_cpts[$post_type] = artitechcore_sanitize_cpt_data($cpt_data);
    update_option('artitechcore_dynamic_cpts', $existing_cpts);
    
    // Clear cache
    wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    // Generate sample content if enabled
    $settings = get_option('artitechcore_cpt_settings', array());
    if (!empty($settings['auto_generate_sample_content']) && !empty($cpt_data['sample_entries'])) {
        artitechcore_create_sample_cpt_entries($cpt_data);
    }
    
    // Trigger action for other plugins/themes
    do_action('artitechcore_cpt_registered', $post_type, $cpt_data);
    
    // Log successful registration
    artitechcore_log_cpt_activity('register', $post_type, true);
    
    return true;
}

/**
 * Add CPT management menu with proper capabilities
 * 
 * @since 1.0
 */
function artitechcore_add_cpt_management_menu() {
    add_submenu_page(
        'artitechcore-main',
        __('Custom Post Types', 'artitechcore'),
        __('Custom Post Types', 'artitechcore'),
        'manage_options',
        'artitechcore-cpt-management',
        'artitechcore_cpt_management_page'
    );
}

function artitechcore_cpt_management_page() {
    artitechcore_render_cpt_management_content(false);
}

/**
 * Render CPT management content with conditional layout
 * 
 * @param bool $is_tab Whether it's being rendered as a tab in the main menu
 * @since 1.0
 */
function artitechcore_render_cpt_management_content($is_tab = false) {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        if ($is_tab) {
            echo '<div class="notice notice-error"><p>' . esc_html__('You do not have sufficient permissions to access this page.', 'artitechcore') . '</p></div>';
            return;
        } else {
            wp_die(__('You do not have sufficient permissions to access this page.', 'artitechcore'));
        }
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
    
    // Adjust active tab if viewed as a sub-tab
    if ($is_tab) {
        $active_tab = isset($_GET['cpt_subtab']) ? sanitize_key($_GET['cpt_subtab']) : 'list';
    }
    
    // Define menu items with their details
    $menu_items = array(
        'list' => array(
            'title' => __('Manage CPTs', 'artitechcore'),
            'icon' => '📋',
            'description' => __('View and manage existing custom post types', 'artitechcore')
        ),
        'create' => array(
            'title' => __('Create New CPT', 'artitechcore'),
            'icon' => '➕',
            'description' => __('Create new custom post types manually', 'artitechcore')
        ),
        'templates' => array(
            'title' => __('Templates & Presets', 'artitechcore'),
            'icon' => '📋',
            'description' => __('Use predefined CPT templates for common use cases', 'artitechcore')
        ),
        'bulk' => array(
            'title' => __('Bulk Operations', 'artitechcore'),
            'icon' => '⚡',
            'description' => __('Perform bulk operations on multiple CPTs', 'artitechcore')
        ),
        'import-export' => array(
            'title' => __('Import/Export', 'artitechcore'),
            'icon' => '📤',
            'description' => __('Import and export CPT configurations', 'artitechcore')
        ),
        'taxonomies' => array(
            'title' => __('Manage Taxonomies', 'artitechcore'),
            'icon' => '🏷️',
            'description' => __('Create and manage custom taxonomies', 'artitechcore')
        ),
        'settings' => array(
            'title' => __('Settings', 'artitechcore'),
            'icon' => '⚙️',
            'description' => __('Configure custom post type settings', 'artitechcore')
        )
    );
    if (!$is_tab):
    ?>
    <div class="wrap dg10-brand" id="artitechcore-cpt-management">
        <!-- Skip Link for Accessibility -->
        <a href="#main-content" class="skip-link"><?php esc_html_e('Skip to main content', 'artitechcore'); ?></a>
        
        <div class="dg10-main-layout">
            <!-- Admin Sidebar -->
            <aside class="dg10-admin-sidebar" role="complementary" aria-label="<?php esc_attr_e('CPT Management Navigation', 'artitechcore'); ?>">
                <div class="dg10-sidebar-header">
                    <div class="dg10-sidebar-title">
                        <img src="<?php echo esc_url(ARTITECHCORE_PLUGIN_URL . 'assets/images/logo.svg'); ?>" 
                             alt="<?php esc_attr_e('ArtitechCore Plugin Logo', 'artitechcore'); ?>" 
                             style="width: 24px; height: 24px;">
                        <?php esc_html_e('ArtitechCore', 'artitechcore'); ?>
                    </div>
                    <p class="dg10-sidebar-subtitle"><?php esc_html_e('Custom Post Type Management', 'artitechcore'); ?></p>
                </div>
                
                <nav class="dg10-sidebar-nav" role="navigation" aria-label="<?php esc_attr_e('CPT Management Navigation', 'artitechcore'); ?>">
                    <ul role="list">
                    <?php foreach ($menu_items as $tab_key => $item): ?>
                            <li role="listitem">
                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'artitechcore-cpt-management', 'tab' => $tab_key), admin_url('admin.php'))); ?>" 
                                   class="dg10-sidebar-nav-item <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
                                   role="menuitem"
                                   aria-label="<?php echo esc_attr($item['title'] . ' - ' . $item['description']); ?>"
                                   aria-current="<?php echo $active_tab === $tab_key ? 'page' : 'false'; ?>"
                           title="<?php echo esc_attr($item['description']); ?>">
                                     <span class="nav-icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                                     <span class="nav-text"><?php echo esc_html($item['title']); ?></span>
                        </a>
                            </li>
                    <?php endforeach; ?>
                    </ul>
                </nav>
            </aside>
            
            <!-- Main Content Area -->
            <main class="dg10-main-content" role="main" aria-label="<?php esc_attr_e('Main Content Area', 'artitechcore'); ?>" id="main-content">
                <article class="dg10-card">
    <?php else: ?>
        <div class="artitechcore-cpt-tab-navigator" style="margin-bottom: 24px;">
            <div class="artitechcore-view-controls">
                <?php foreach ($menu_items as $tab_key => $item): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('tab' => 'cpt', 'cpt_subtab' => $tab_key))); ?>" 
                       class="button <?php echo $active_tab === $tab_key ? 'button-primary' : ''; ?>">
                        <?php echo esc_html($item['icon'] . ' ' . $item['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
                    <header class="dg10-card-header">
                        <div class="dg10-hero-content">
                            <div class="dg10-hero-text">
                                <h1 id="page-title"><?php echo esc_html($menu_items[$active_tab]['title']); ?></h1>
                                <p class="dg10-hero-description">
                                    <?php echo esc_html($menu_items[$active_tab]['description']); ?>
                                </p>
                            </div>
                        </div>
                    </header>
                    
                    <div class="dg10-card-body">
                        <!-- Loading Overlay -->
                        <div id="artitechcore-loading-overlay" class="artitechcore-loading-overlay" style="display: none;" aria-hidden="true">
                            <div class="artitechcore-loading-spinner">
                                <div class="spinner"></div>
                                <p><?php esc_html_e('Processing...', 'artitechcore'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Error/Success Messages -->
                        <div id="artitechcore-messages" class="artitechcore-messages" role="status" aria-live="polite"></div>
                        
                        <?php
                        // Route to appropriate tab content
                        switch ($active_tab) {
                            case 'list':
                            artitechcore_cpt_list_tab();
                                break;
                            case 'create':
                            artitechcore_cpt_create_tab();
                                break;
                            case 'templates':
                                artitechcore_cpt_templates_tab();
                                break;
                            case 'bulk':
                                artitechcore_cpt_bulk_operations_tab();
                                break;
                            case 'import-export':
                                artitechcore_cpt_import_export_tab();
                                break;
                            case 'taxonomies':
                                artitechcore_cpt_taxonomies_tab();
                                break;
                            case 'settings':
                            artitechcore_cpt_settings_tab();
                                break;
                            default:
                                artitechcore_cpt_list_tab();
                                break;
                        }
                        ?>
                    </div>
                    
                    <?php if (!$is_tab): ?>
                    </footer>
                </article>
            </main>
        </div>
    </div>
    <?php endif; ?>
    <?php
}

/**
 * Enhanced CPT list tab with AJAX and better UX
 * 
 * @since 1.0
 */
function artitechcore_cpt_list_tab() {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    ?>
    <div class="artitechcore-cpt-list" id="cpt-list-container">
        <div class="artitechcore-cpt-list-header">
            <div class="artitechcore-search-filter">
                <label for="cpt-search" class="screen-reader-text"><?php esc_html_e('Search CPTs', 'artitechcore'); ?></label>
                <input type="search" id="cpt-search" placeholder="<?php esc_attr_e('Search custom post types...', 'artitechcore'); ?>" 
                       class="artitechcore-search-input" aria-label="<?php esc_attr_e('Search custom post types', 'artitechcore'); ?>">
                
                <select id="cpt-filter-status" aria-label="<?php esc_attr_e('Filter by status', 'artitechcore'); ?>">
                    <option value=""><?php esc_html_e('All Statuses', 'artitechcore'); ?></option>
                    <option value="active"><?php esc_html_e('Active', 'artitechcore'); ?></option>
                    <option value="inactive"><?php esc_html_e('Inactive', 'artitechcore'); ?></option>
                </select>
                
                <button type="button" class="dg10-btn dg10-btn-outline" id="refresh-cpt-list" 
                        aria-label="<?php esc_attr_e('Refresh CPT list', 'artitechcore'); ?>">
                    <span class="nav-icon">🔄</span>
                    <?php esc_html_e('Refresh', 'artitechcore'); ?>
                </button>
            </div>
            
            <div class="artitechcore-bulk-actions">
                <select id="bulk-action-select" class="dg10-form-select" aria-label="<?php esc_attr_e('Bulk actions', 'artitechcore'); ?>">
                    <option value=""><?php esc_html_e('Bulk Actions', 'artitechcore'); ?></option>
                    <option value="activate"><?php esc_html_e('Activate', 'artitechcore'); ?></option>
                    <option value="deactivate"><?php esc_html_e('Deactivate', 'artitechcore'); ?></option>
                    <option value="export"><?php esc_html_e('Export', 'artitechcore'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'artitechcore'); ?></option>
                </select>
                <button type="button" class="dg10-btn dg10-btn-primary" id="apply-bulk-action" disabled>
                    <?php esc_html_e('Apply', 'artitechcore'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($dynamic_cpts)): ?>
            <div class="artitechcore-empty-state">
                <div class="artitechcore-empty-state-icon">
                    <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
                </div>
                <h3><?php esc_html_e('No Custom Post Types', 'artitechcore'); ?></h3>
                <p><?php esc_html_e('You haven\'t created any custom post types yet. Get started by creating your first CPT.', 'artitechcore'); ?></p>
                <div class="artitechcore-empty-state-actions">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                        <?php esc_html_e('Create New CPT', 'artitechcore'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'templates')); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                        <?php esc_html_e('Use Template', 'artitechcore'); ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="artitechcore-cpt-grid" id="cpt-grid">
                    <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                    <?php artitechcore_render_cpt_card($post_type, $cpt_data); ?>
                    <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div class="artitechcore-pagination" id="cpt-pagination">
                <!-- Pagination will be populated via AJAX if needed -->
            </div>
        <?php endif; ?>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Edit CPT
        $(document).on('click', '.artitechcore-action-btn[data-action="edit"]', function() {
            var cpt = $(this).data('cpt');
            window.location.href = '<?php echo admin_url('admin.php?page=artitechcore-cpt-management&tab=cpt&cpt_subtab=create&action=edit&cpt='); ?>' + cpt;
        });

        // Duplicate CPT
        $(document).on('click', '.artitechcore-action-btn[data-action="duplicate"]', function() {
            var cpt = $(this).data('cpt');
            if(!confirm('<?php echo esc_js(__('Duplicate this Custom Post Type?', 'artitechcore')); ?>')) return;
            
            var $btn = $(this);
            var $icon = $btn.find('.dashicons');
            var originalClass = $icon.attr('class');
            $icon.attr('class', 'dashicons dashicons-update-alt spin');
            
            $.post(ajaxurl, {
                action: 'artitechcore_handle_duplicate_cpt_ajax',
                cpt_slug: cpt,
                nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
            }, function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error duplicating CPT');
                    $icon.attr('class', originalClass);
                }
            });
        });

        // Delete CPT
        $(document).on('click', '.artitechcore-action-btn[data-action="delete"]', function() {
            var cpt = $(this).data('cpt');
            var $btn = $(this);
            var $icon = $btn.find('.dashicons');
            var originalClass = $icon.attr('class');
            
            $.post(ajaxurl, {
                action: 'artitechcore_get_cpt_item_count',
                post_types: [cpt],
                nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
            }, function(countResponse) {
                var count = countResponse.success ? countResponse.data.count : 0;
                if(!confirm('<?php echo esc_js(__('Are you sure you want to delete this CPT? This will permanently delete ALL ', 'artitechcore')); ?>' + count + '<?php echo esc_js(__(' posts of this type. This cannot be undone.', 'artitechcore')); ?>')) return;

                $icon.attr('class', 'dashicons dashicons-update-alt spin');

                $.post(ajaxurl, {
                    action: 'artitechcore_delete_cpt_ajax',
                    post_type: cpt,
                    nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error deleting CPT');
                        $icon.attr('class', originalClass);
                    }
                });
            });
        });

        // Bulk Actions
        function updateBulkButtonState() {
            var hasAction = $('#bulk-action-select').val() !== '';
            var hasChecked = $('.cpt-checkbox:checked').length > 0;
            $('#apply-bulk-action').prop('disabled', !(hasAction && hasChecked));
        }

        $('#bulk-action-select, .cpt-checkbox').on('change', updateBulkButtonState);

        // Handle Apply Click
        $('#apply-bulk-action').on('click', function() {
            var action = $('#bulk-action-select').val();
            var checked = $('.cpt-checkbox:checked').map(function() { return $(this).val(); }).get();
            
            if (!action || checked.length === 0) return;
            
            if (action === 'delete') {
                $.post(ajaxurl, {
                    action: 'artitechcore_get_cpt_item_count',
                    post_types: checked,
                    nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
                }, function(countResponse) {
                    var count = countResponse.success ? countResponse.data.count : 0;
                    if (!confirm('<?php echo esc_js(__('Permanently delete selected items? This will delete ALL ', 'artitechcore')); ?>' + count + '<?php echo esc_js(__(' posts. This cannot be undone.', 'artitechcore')); ?>')) {
                        return;
                    }
                    performBulkAction();
                });
                return;
            }
            
            function performBulkAction() {
                var $btn = $('#apply-bulk-action');
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'artitechcore')); ?>');
                
                $.post(ajaxurl, {
                    action: 'artitechcore_handle_bulk_cpt_operations_ajax',
                    bulk_action: action,
                    cpt_ids: checked,
                    nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
                }, function(response) {
                   location.reload();
                });
            }
            
            performBulkAction();
        });
    });
    </script>
    <?php
}

/**
 * Enhanced CPT create tab with AJAX support and better UX
 * 
 * @since 1.0
 */
function artitechcore_cpt_create_tab() {
    ?>
    <div class="artitechcore-cpt-create">
        <form id="artitechcore-cpt-form" class="dg10-form" method="post" action="">
            <?php wp_nonce_field('artitechcore_create_manual_cpt'); ?>
            
            <div class="dg10-form-section">
                <div class="dg10-form-group">
                    <label for="cpt_name" class="dg10-form-label"><?php esc_html_e('Post Type Slug', 'artitechcore'); ?></label>
                    <input type="text" name="cpt_name" id="cpt_name" class="dg10-form-input" required 
                           placeholder="<?php esc_attr_e('e.g., portfolio', 'artitechcore'); ?>"
                           pattern="[a-z_][a-z0-9_]*" maxlength="20">
                    <p class="dg10-form-help">
                        <?php esc_html_e('Lowercase, no spaces, use underscores. Max 20 characters.', 'artitechcore'); ?>
                    </p>
                    <div class="artitechcore-field-validation"></div>
                </div>

                <div class="dg10-form-group">
                    <label for="cpt_label" class="dg10-form-label"><?php esc_html_e('Display Label', 'artitechcore'); ?></label>
                    <input type="text" name="cpt_label" id="cpt_label" class="dg10-form-input" required 
                           placeholder="<?php esc_attr_e('e.g., Portfolio', 'artitechcore'); ?>">
                    <p class="dg10-form-help"><?php esc_html_e('Human-readable name for the post type.', 'artitechcore'); ?></p>
                    <div class="artitechcore-field-validation"></div>
                </div>

                <div class="dg10-form-group">
                    <label for="cpt_description" class="dg10-form-label"><?php esc_html_e('Description', 'artitechcore'); ?></label>
                    <textarea name="cpt_description" id="cpt_description" rows="3" class="dg10-form-textarea" 
                              placeholder="<?php esc_attr_e('Brief description of what this post type is for', 'artitechcore'); ?>"></textarea>
                </div>

                <div class="dg10-form-group">
                    <label for="cpt_menu_icon" class="dg10-form-label"><?php esc_html_e('Menu Icon', 'artitechcore'); ?></label>
                    <div class="artitechcore-icon-selector">
                        <input type="text" name="cpt_menu_icon" id="cpt_menu_icon" class="dg10-form-input" 
                               value="dashicons-admin-post">
                        <p class="dg10-form-help">
                            <?php 
                            printf(
                                esc_html__('Enter a %s (e.g., dashicons-portfolio).', 'artitechcore'),
                                '<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">Dashicon class</a>'
                            ); 
                            ?>
                        </p>
                    </div>
                </div>

                <div class="dg10-form-group">
                    <label class="dg10-checkbox-label">
                        <input type="checkbox" name="cpt_hierarchical" id="cpt_hierarchical">
                        <span><?php esc_html_e('Hierarchical', 'artitechcore'); ?></span>
                        <small><?php esc_html_e('(Like Pages, allowing parents)', 'artitechcore'); ?></small>
                    </label>
                </div>
            </div>
            
            <div class="artitechcore-field-builder dg10-card" style="margin-top: 40px;">
                <div class="artitechcore-field-builder-header dg10-card-header" style="padding: 20px 32px;">
                    <h3 class="artitechcore-field-builder-title" style="margin: 0;"><?php esc_html_e('Custom Meta Fields', 'artitechcore'); ?></h3>
                    <button type="button" class="dg10-btn dg10-btn-outline artitechcore-add-field-btn" style="box-shadow: none !important; background: #fff !important; color: #8B5CF6 !important;">
                        <span class="nav-icon">➕</span>
                        <?php esc_html_e('Add New Field', 'artitechcore'); ?>
                    </button>
                </div>
                
                <div id="custom-fields-container" class="artitechcore-field-rows">
                    <!-- Dynamic fields will be added here via JS -->
                    <div class="artitechcore-empty-builder-state" style="padding: 40px; text-align: center; color: var(--dg10-text-secondary);">
                        <p><?php esc_html_e('No custom fields added yet. Click "Add New Field" to begin.', 'artitechcore'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dg10-form-actions">
                <button type="submit" class="dg10-btn dg10-btn-primary">
                    <span class="nav-icon">🚀</span>
                    <?php esc_html_e('Create Custom Post Type', 'artitechcore'); ?>
                </button>
            </div>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var urlParams = new URLSearchParams(window.location.search);
        var action = urlParams.get('action');
        var cpt = urlParams.get('cpt');
        
        if (action === 'edit' && cpt) {
            $('button[type="submit"]').html('<span class="nav-icon">💾</span> <?php esc_html_e("Update Custom Post Type", "artitechcore"); ?>');
            $('#cpt_name').val(cpt).prop('readonly', true).addClass('disabled');
            
            // Add hidden field to signal update
            if ($('input[name="is_update"]').length === 0) {
                 $('<input>').attr({type: 'hidden', name: 'is_update', value: '1'}).appendTo('#artitechcore-cpt-form');
            }

            $.post(ajaxurl, {
                action: 'artitechcore_get_cpt_data_ajax',
                post_type: cpt,
                nonce: '<?php echo wp_create_nonce("artitechcore_ajax_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#cpt_label').val(data.label);
                    $('#cpt_description').val(data.description);
                    if (data.menu_icon) $('#cpt_menu_icon').val(data.menu_icon).trigger('change');
                    if (data.hierarchical) $('#cpt_hierarchical').prop('checked', true);

                    // Populate Custom Fields
                    if (data.custom_fields && data.custom_fields.length > 0) {
                        $('#custom-fields-container').empty();
                        
                        data.custom_fields.forEach(function(field) {
                            $('.artitechcore-add-field-btn').trigger('click');
                            var $row = $('.custom-field-row').last();
                            $row.find('.field-name-input').val(field.name);
                            $row.find('input[name$="[label]"]').val(field.label);
                            $row.find('select[name$="[type]"]').val(field.type).trigger('change');
                            $row.find('input[name$="[description]"]').val(field.description);
                            if (field.required == 1 || field.required == '1' || field.required === true) {
                                $row.find('input[name$="[required]"]').prop('checked', true);
                            }
                            if (field.options) $row.find('input[name$="[options]"]').val(field.options);
                        });
                    }
                } else {
                    console.error('Error loading CPT data: ' + (response.data || 'Unknown'));
                }
            });
        }
    });
    </script>
    <?php
}

// CPT settings tab
function artitechcore_cpt_settings_tab() {
    // Handle settings save
    if (isset($_POST['save_cpt_settings']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_save_cpt_settings')) {
        $settings = array(
            'auto_schema_generation' => isset($_POST['auto_schema_generation']),
            'include_in_menus' => isset($_POST['include_in_menus']),
            'include_in_hierarchy' => isset($_POST['include_in_hierarchy']),
            'auto_generate_sample_content' => isset($_POST['auto_generate_sample_content'])
        );
        
        update_option('artitechcore_cpt_settings', $settings);
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $settings = get_option('artitechcore_cpt_settings', array(
        'auto_schema_generation' => true,
        'include_in_menus' => true,
        'include_in_hierarchy' => true,
        'auto_generate_sample_content' => true
    ));
    
    ?>
    <div class="artitechcore-cpt-settings">
        <p>Configure how custom post types integrate with other ArtitechCore features.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('artitechcore_save_cpt_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Auto Schema Generation</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_schema_generation" value="1" <?php checked($settings['auto_schema_generation']); ?>>
                            Automatically generate schema markup for custom post types
                        </label>
                        <p class="description">When enabled, schema markup will be generated for all custom post type entries</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Include in Menu Generation</th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_in_menus" value="1" <?php checked($settings['include_in_menus']); ?>>
                            Include custom post type archives in menu generation
                        </label>
                        <p class="description">When enabled, custom post type archive pages will be included in generated menus</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Include in Hierarchy</th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_in_hierarchy" value="1" <?php checked($settings['include_in_hierarchy']); ?>>
                            Include custom post types in hierarchy view and export
                        </label>
                        <p class="description">When enabled, custom post types will appear in the page hierarchy</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Generate Sample Content</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_generate_sample_content" value="1" <?php checked($settings['auto_generate_sample_content']); ?>>
                            Automatically create sample entries when creating custom post types
                        </label>
                        <p class="description">When enabled, sample entries will be created automatically for new custom post types</p>
                    </td>
                </tr>
            </table>
            
            <div class="dg10-form-actions">
                <button type="submit" name="save_cpt_settings" class="dg10-btn dg10-btn-primary">
                    <span class="nav-icon">💾</span>
                    <?php esc_html_e('Save Settings', 'artitechcore'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php
}

// Register REST API endpoints for CPT data
function artitechcore_register_cpt_rest_endpoints() {
    register_rest_route('artitechcore/v1', '/cpts', array(
        'methods' => 'GET',
        'callback' => 'artitechcore_get_cpts_rest_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}

// Get CPTs data for REST API
function artitechcore_get_cpts_rest_data($request) {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    $cpt_data = array();
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        $cpt_data[] = array(
            'post_type' => $post_type,
            'label' => $cpt_info['label'],
            'description' => $cpt_info['description'],
            'posts_count' => count($posts),
            'custom_fields' => $cpt_info['custom_fields'] ?? array(),
            'posts' => array_map(function($post) {
                return array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'status' => $post->post_status,
                    'url' => get_permalink($post->ID)
                );
            }, $posts)
        );
    }
    
    return rest_ensure_response($cpt_data);
}

// Add CPT data to hierarchy export
function artitechcore_add_cpt_to_hierarchy_export($data) {
    $settings = get_option('artitechcore_cpt_settings', array());
    if (!isset($settings['include_in_hierarchy']) || !$settings['include_in_hierarchy']) {
        return $data;
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($posts as $post) {
            $data[] = array(
                'id' => 'cpt_' . $post->ID,
                'text' => $cpt_info['label'] . ': ' . $post->post_title,
                'parent' => '#',
                'type' => 'cpt',
                'post_type' => $post_type,
                'url' => get_permalink($post->ID)
            );
        }
    }
    
    return $data;
}

// Add CPT archives to menu generation
function artitechcore_add_cpt_archives_to_menus($pages) {
    $settings = get_option('artitechcore_cpt_settings', array());
    if (!isset($settings['include_in_menus']) || !$settings['include_in_menus']) {
        return $pages;
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    
    foreach ($dynamic_cpts as $post_type => $cpt_info) {
        $archive_url = get_post_type_archive_link($post_type);
        if ($archive_url) {
            $pages[] = array(
                'title' => $cpt_info['label'],
                'url' => $archive_url,
                'type' => 'cpt_archive',
                'post_type' => $post_type
            );
        }
    }
    
    return $pages;
}

// Generate schema markup for custom post types
function artitechcore_generate_cpt_schema($post_id, $post) {
    $settings = get_option('artitechcore_cpt_settings', array());
    if (!isset($settings['auto_schema_generation']) || !$settings['auto_schema_generation']) {
        return;
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    $post_type = $post->post_type;
    
    if (!isset($dynamic_cpts[$post_type])) {
        return;
    }
    
    $cpt_info = $dynamic_cpts[$post_type];
    
    // Generate appropriate schema based on post type
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => $post->post_title,
        'description' => wp_trim_words($post->post_content, 20),
        'url' => get_permalink($post_id),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id)
    );
    
    // Add custom fields to schema
    if (!empty($cpt_info['custom_fields'])) {
        foreach ($cpt_info['custom_fields'] as $field) {
            $value = get_post_meta($post_id, $field['name'], true);
            if (!empty($value)) {
                $schema[$field['name']] = $value;
            }
        }
    }
    
    // Add schema to post meta
    update_post_meta($post_id, '_artitechcore_schema_markup', $schema);
}

/**
 * Security and validation functions
 */

// Validate CPT data structure and content
function artitechcore_validate_cpt_data($cpt_data) {
    if (!is_array($cpt_data)) return false;
    
    // Required fields
    if (empty($cpt_data['name']) || empty($cpt_data['label'])) return false;
    
    // Validate post type name format
    $post_type = sanitize_key($cpt_data['name']);
    if ($post_type !== $cpt_data['name'] || strlen($post_type) > 20) return false;
    
    // Check for reserved post type names
    // Check for reserved post type names and existing system types
    $reserved_names = array(
        'post', 'page', 'attachment', 'revision', 'nav_menu_item', 
        'custom_css', 'customize_changeset', 'oembed_cache',
        'user_request', 'wp_block', 'wp_template', 'wp_template_part', 
        'wp_navigation', 'wp_font_family', 'wp_font_face', 'action', 'order'
    );
    
    if (in_array($post_type, $reserved_names)) {
        return false;
    }

    // Check conflict with existing non-dynamic post types (only for new registrations)
    // We can't easily distinguish new vs edit here without context, but we can check if it exists and is NOT in our dynamic list
    if (post_type_exists($post_type) && !artitechcore_is_dynamic_cpt($post_type)) {
        return false;
    }
    
    return true;
}

// Validate field data structure
function artitechcore_validate_field_data($field) {
    if (!is_array($field)) return false;
    if (empty($field['name']) || empty($field['label']) || empty($field['type'])) return false;
    
    $allowed_types = array('text', 'textarea', 'number', 'date', 'datetime', 'url', 'email', 'image', 'select', 'radio', 'checkbox', 'color', 'wysiwyg');
    if (!in_array($field['type'], $allowed_types)) return false;
    
    return true;
}

// Sanitize CPT data for storage
function artitechcore_sanitize_cpt_data($cpt_data) {
    $sanitized = array(
        'name' => sanitize_key($cpt_data['name']),
        'label' => sanitize_text_field($cpt_data['label']),
        'description' => sanitize_textarea_field($cpt_data['description'] ?? ''),
        'menu_icon' => sanitize_text_field($cpt_data['menu_icon'] ?? 'dashicons-admin-post'),
        'menu_position' => (int) ($cpt_data['menu_position'] ?? 25),
        'hierarchical' => (bool) ($cpt_data['hierarchical'] ?? false),
        'taxonomies' => isset($cpt_data['taxonomies']) ? array_map('sanitize_key', (array) $cpt_data['taxonomies']) : array(),
        'custom_fields' => array()
    );
    
    if (!empty($cpt_data['custom_fields']) && is_array($cpt_data['custom_fields'])) {
        foreach ($cpt_data['custom_fields'] as $field) {
            if (artitechcore_validate_field_data($field)) {
                $sanitized['custom_fields'][] = array(
                    'name' => sanitize_key($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_key($field['type']),
                    'description' => sanitize_textarea_field($field['description'] ?? ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => isset($field['options']) ? array_map('sanitize_text_field', (array) $field['options']) : array()
                );
            }
        }
    }
    
    return $sanitized;
}

// Sanitize field values based on field type
function artitechcore_sanitize_field_value($value, $field_type) {
    switch ($field_type) {
        case 'textarea':
        case 'wysiwyg':
            return sanitize_textarea_field($value);
        case 'url':
            return esc_url_raw($value);
        case 'email':
            return sanitize_email($value);
        case 'number':
            return (int) $value;
        case 'checkbox':
            return (bool) $value;
        case 'color':
            return sanitize_hex_color($value);
        default:
            return sanitize_text_field($value);
    }
}

// Validate field values
function artitechcore_validate_field_value($value, $field_config) {
    switch ($field_config['type']) {
        case 'url':
            return empty($value) || filter_var($value, FILTER_VALIDATE_URL);
        case 'email':
            return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
        case 'number':
            return is_numeric($value);
        case 'date':
        case 'datetime':
            return empty($value) || strtotime($value) !== false;
        default:
            return true;
    }
}

// Check user capabilities for CPT management
function artitechcore_check_cpt_management_capabilities() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'artitechcore-cpt-management') {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'artitechcore'));
        }
    }
}

// Clear CPT cache when options are updated
function artitechcore_clear_cpt_cache($option_name, $old_value, $value) {
    if ($option_name === 'artitechcore_dynamic_cpts') {
        wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    }
}

// Log CPT activities for debugging and monitoring
function artitechcore_log_cpt_activity($action, $post_type, $success, $error_message = '') {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'action' => $action,
        'post_type' => $post_type,
        'success' => $success,
        'user_id' => get_current_user_id(),
        'error_message' => $error_message
    );
    
    $logs = get_option('artitechcore_cpt_logs', array());
    $logs[] = $log_entry;
    
    // Keep only last 100 log entries
    if (count($logs) > 100) {
        $logs = array_slice($logs, -100);
    }
    
    update_option('artitechcore_cpt_logs', $logs);
}

/**
 * Enhanced custom field registration with security
 */
function artitechcore_register_custom_fields($post_type, $fields) {
    if (empty($fields) || !is_array($fields)) {
        return new WP_Error('invalid_fields', __('Invalid fields data provided', 'artitechcore'));
    }
    
    foreach ($fields as $field) {
        if (!artitechcore_validate_field_data($field)) {
            continue;
        }
        
        // Validate field name (meta key)
        $field_name = sanitize_key($field['name']);
        
        // Prevent meta keys starting with underscore (protected meta)
        if (substr($field_name, 0, 1) === '_') {
            continue;
        }
        
        $field_config = array(
            'name' => $field_name,
            'label' => sanitize_text_field($field['label']),
            'type' => sanitize_key($field['type']),
            'description' => sanitize_textarea_field($field['description'] ?? ''),
            'required' => (bool) ($field['required'] ?? false),
            'options' => isset($field['options']) ? array_map('sanitize_text_field', (array) $field['options']) : array(),
            'post_type' => $post_type
        );
        
        // Register field in REST API for Gutenberg support
        register_rest_field($post_type, $field_name, array(
            'get_callback' => function($post) use ($field_name) {
                return get_post_meta($post['id'], $field_name, true);
            },
            'update_callback' => function($value, $post) use ($field_name, $field_config) {
                return update_post_meta($post->ID, $field_name, artitechcore_sanitize_field_value($value, $field_config['type']));
            },
            'schema' => artitechcore_get_field_schema($field_config['type']),
        ));
        
        // Store field configuration
        $existing_fields = get_option('artitechcore_custom_fields', array());
        $existing_fields[$post_type][$field_name] = $field_config;
        update_option('artitechcore_custom_fields', $existing_fields);
    }
    
    return true;
}

// Get field schema for REST API
function artitechcore_get_field_schema($field_type) {
    $schemas = array(
        'text' => array('type' => 'string'),
        'textarea' => array('type' => 'string'),
        'number' => array('type' => 'integer'),
        'url' => array('type' => 'string', 'format' => 'uri'),
        'email' => array('type' => 'string', 'format' => 'email'),
        'date' => array('type' => 'string', 'format' => 'date'),
        'datetime' => array('type' => 'string', 'format' => 'date-time'),
        'checkbox' => array('type' => 'boolean'),
        'color' => array('type' => 'string', 'pattern' => '^#[0-9a-fA-F]{6}$'),
    );
    
    return $schemas[$field_type] ?? array('type' => 'string');
}

// Helper function to get all dynamic CPTs
function artitechcore_get_dynamic_cpts() {
    return get_option('artitechcore_dynamic_cpts', []);
}

// Helper function to check if a post type is dynamic
function artitechcore_is_dynamic_cpt($post_type) {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    return isset($dynamic_cpts[$post_type]);
}

// Helper function to get CPT info
function artitechcore_get_cpt_info($post_type) {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
    return isset($dynamic_cpts[$post_type]) ? $dynamic_cpts[$post_type] : null;
}

/**
 * Process CPT creation from form data
 * 
 * @param array $form_data Form submission data
 * @return bool|WP_Error Success status or error object
 * @since 1.0
 */
function artitechcore_process_cpt_creation($form_data) {
    // Security check
    if (!current_user_can('manage_options')) {
        return new WP_Error('insufficient_permissions', __('You do not have permission to create custom post types.', 'artitechcore'));
    }
    
    $cpt_data = array(
        'name' => sanitize_key($form_data['cpt_name'] ?? ''),
        'label' => sanitize_text_field($form_data['cpt_label'] ?? ''),
        'description' => sanitize_textarea_field($form_data['cpt_description'] ?? ''),
        'menu_icon' => sanitize_text_field($form_data['cpt_menu_icon'] ?? 'dashicons-admin-post'),
        'hierarchical' => isset($form_data['cpt_hierarchical']),
        'custom_fields' => array()
    );
    
    // Process custom fields
    if (isset($form_data['custom_fields']) && is_array($form_data['custom_fields'])) {
        foreach ($form_data['custom_fields'] as $field) {
            if (!empty($field['name']) && !empty($field['label'])) {
                $cpt_data['custom_fields'][] = array(
                    'name' => sanitize_key($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_key($field['type']),
                    'description' => sanitize_textarea_field($field['description'] ?? ''),
                    'required' => isset($field['required']),
                    'options' => isset($field['options']) ? array_map('sanitize_text_field', explode(',', $field['options'])) : array()
                );
            }
        }
    }
    
    return artitechcore_register_dynamic_custom_post_type($cpt_data);
}

/**
 * AJAX handler for CPT creation
 * 
 * @since 1.0
 */
function artitechcore_handle_cpt_creation_ajax() {
    // Security checks
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to create custom post types.', 'artitechcore'));
    }
    
    $result = artitechcore_process_cpt_creation($_POST);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(array(
            'message' => __('Custom post type created successfully!', 'artitechcore'),
            'cpt_data' => $_POST
        ));
    }
}

/**
 * AJAX handler to get post count for one or more CPTs
 * 
 * @since 1.0
 */
function artitechcore_handle_get_cpt_item_count_ajax() {
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized access', 'artitechcore'));
    }
    
    $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array();
    
    if (empty($post_types)) {
        wp_send_json_error(__('No post types specified', 'artitechcore'));
    }
    
    $total_count = 0;
    foreach ($post_types as $post_type) {
        $counts = wp_count_posts(sanitize_key($post_type));
        $total_count += array_sum((array) $counts);
    }
    
    wp_send_json_success(array('count' => $total_count));
}

/**
 * AJAX handler for CPT deletion
 * 
 * @since 1.0
 */
function artitechcore_handle_cpt_deletion_ajax() {
    // Security checks
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to delete custom post types.', 'artitechcore'));
    }
    
    $post_type = sanitize_key($_POST['post_type'] ?? '');
    
    if (empty($post_type)) {
        wp_send_json_error(__('Invalid post type specified.', 'artitechcore'));
    }
    
    $result = artitechcore_delete_custom_post_type($post_type);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(array(
            'message' => __('Custom post type deleted successfully!', 'artitechcore')
        ));
    }
}

/**
 * AJAX handler for duplicating a CPT
 * 
 * @since 1.0
 */
function artitechcore_handle_duplicate_cpt_ajax() {
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'artitechcore'));
    }
    
    $cpt_slug = sanitize_key($_POST['cpt_slug'] ?? '');
    
    if (empty($cpt_slug)) {
        wp_send_json_error(__('Invalid CPT slug.', 'artitechcore'));
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$cpt_slug])) {
        wp_send_json_error(__('Custom post type not found.', 'artitechcore'));
    }
    
    $original_cpt = $dynamic_cpts[$cpt_slug];
    $new_cpt = $original_cpt;
    
    // Generate unique slug
    $count = 1;
    $new_slug = $cpt_slug . '_copy';
    while (isset($dynamic_cpts[$new_slug]) || post_type_exists($new_slug)) {
        $new_slug = $cpt_slug . '_copy_' . $count;
        $count++;
    }
    
    $new_cpt['name'] = $new_slug;
    $new_cpt['label'] = $original_cpt['label'] . ' ' . __('(Copy)', 'artitechcore');
    
    $result = artitechcore_register_dynamic_custom_post_type($new_cpt);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    artitechcore_log_cpt_activity('duplicate', $new_slug, true);
    
    wp_send_json_success(array(
        'message' => __('Custom post type duplicated successfully!', 'artitechcore'),
        'new_slug' => $new_slug
    ));
}

/**
 * Delete a custom post type
 * 
 * @param string $post_type Post type slug to delete
 * @return bool|WP_Error Success status or error object
 * @since 1.0
 */
function artitechcore_delete_custom_post_type($post_type) {
    $post_type = sanitize_key($post_type);
    
    if (empty($post_type)) {
        return new WP_Error('invalid_post_type', __('Invalid post type specified.', 'artitechcore'));
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        return new WP_Error('cpt_not_found', __('Custom post type not found.', 'artitechcore'));
    }
    
    // Check if there are any posts of this type
    $posts_count = wp_count_posts($post_type);
    $total_posts = (int) $posts_count->publish + (int) $posts_count->draft + 
                   (int) $posts_count->pending + (int) $posts_count->private + 
                   (int) $posts_count->future + (int) $posts_count->trash;
                   
    if ($total_posts > 0) {
        // Force delete all posts of this type
        $all_posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
        ));
        
        foreach ($all_posts as $p) {
            wp_delete_post($p->ID, true); // true = force delete
        }
    }
    
    // Remove from stored CPTs
    unset($dynamic_cpts[$post_type]);
    update_option('artitechcore_dynamic_cpts', $dynamic_cpts);
    
    // Remove custom fields configuration
    $custom_fields = get_option('artitechcore_custom_fields', array());
    if (isset($custom_fields[$post_type])) {
        unset($custom_fields[$post_type]);
        update_option('artitechcore_custom_fields', $custom_fields);
    }
    
    // Clear cache
    wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    // Log activity
    artitechcore_log_cpt_activity('delete', $post_type, true);
    
    return true;
}

/**
 * Add custom field meta boxes with proper security and validation
 * 
 * @since 1.0
 */
function artitechcore_add_custom_field_meta_boxes() {
    $custom_fields = get_option('artitechcore_custom_fields', array());
    
    foreach ($custom_fields as $post_type => $fields) {
        if (post_type_exists($post_type)) {
            foreach ($fields as $field_name => $field_config) {
                add_meta_box(
                    'artitechcore_' . $field_name,
                    $field_config['label'],
                    'artitechcore_render_custom_field_meta_box',
                    $post_type,
                    'normal',
                    'high',
                    array('field_config' => $field_config)
                );
            }
        }
    }
}

/**
 * Render custom field meta box with comprehensive security
 * 
 * @param WP_Post $post Current post object
 * @param array $metabox Metabox configuration
 * @since 1.0
 */
function artitechcore_render_custom_field_meta_box($post, $metabox) {
    $field_config = $metabox['args']['field_config'];
    $field_name = $field_config['name'];
    $field_type = $field_config['type'];
    $field_label = $field_config['label'];
    $field_description = $field_config['description'];
    $field_required = $field_config['required'];
    $field_options = $field_config['options'] ?? array();
    
    $value = get_post_meta($post->ID, $field_name, true);
    $required_attr = $field_required ? 'required aria-required="true"' : '';
    $field_id = 'artitechcore_' . $field_name;
    
    // Add nonce for security
    wp_nonce_field('artitechcore_save_custom_fields_' . $post->ID, 'artitechcore_custom_fields_nonce');
    
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($field_label); ?>
                    <?php if ($field_required): ?>
                        <span class="required" aria-label="<?php esc_attr_e('Required field', 'artitechcore'); ?>">*</span>
                    <?php endif; ?>
                </label>
            </th>
            <td>
                <?php artitechcore_render_field_input($field_id, $field_name, $field_type, $value, $field_options, $required_attr, $field_description); ?>
                
                <?php if (!empty($field_description)): ?>
                    <p class="description" id="<?php echo esc_attr($field_id . '_desc'); ?>">
                        <?php echo esc_html($field_description); ?>
                    </p>
                <?php endif; ?>
                
                <div class="artitechcore-field-validation" id="<?php echo esc_attr($field_id . '_validation'); ?>" 
                     style="display: none;" role="alert" aria-live="polite"></div>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render field input based on field type with security
 * 
 * @param string $field_id Field HTML ID
 * @param string $field_name Field name
 * @param string $field_type Field type
 * @param mixed $value Current field value
 * @param array $options Field options for select/radio fields
 * @param string $required_attr Required attribute string
 * @param string $field_description Field description for aria-describedby
 * @since 1.0
 */
function artitechcore_render_field_input($field_id, $field_name, $field_type, $value, $options = array(), $required_attr = '', $field_description = '') {
    $aria_describedby = !empty($field_description) ? 'aria-describedby="' . esc_attr($field_id . '_desc') . '"' : '';
    
    switch ($field_type) {
        case 'textarea':
            ?>
            <textarea name="<?php echo esc_attr($field_name); ?>" 
                      id="<?php echo esc_attr($field_id); ?>" 
                      rows="4" 
                      cols="50" 
                      class="large-text"
                      <?php echo $required_attr; ?>
                      <?php echo $aria_describedby; ?>><?php echo esc_textarea($value); ?></textarea>
            <?php
            break;
            
        case 'select':
            ?>
            <select name="<?php echo esc_attr($field_name); ?>" 
                    id="<?php echo esc_attr($field_id); ?>" 
                    class="regular-text"
                    <?php echo $required_attr; ?>
                    <?php echo $aria_describedby; ?>>
                <option value=""><?php esc_html_e('Select an option', 'artitechcore'); ?></option>
                <?php foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>" 
                            <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            break;
            
        case 'url':
            ?>
            <input type="url" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text" 
                   placeholder="https://"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'email':
            ?>
            <input type="email" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'number':
            ?>
            <input type="number" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="small-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'date':
            ?>
            <input type="date" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
            
        case 'checkbox':
            ?>
            <label for="<?php echo esc_attr($field_id); ?>">
                <input type="checkbox" 
                       name="<?php echo esc_attr($field_name); ?>" 
                       id="<?php echo esc_attr($field_id); ?>"
                       value="1" 
                       <?php checked($value, 1); ?>
                       <?php echo $aria_describedby; ?>>
                <?php esc_html_e('Check this option', 'artitechcore'); ?>
            </label>
            <?php
            break;
            
        default: // text
            ?>
            <input type="text" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text"
                   <?php echo $required_attr; ?>
                   <?php echo $aria_describedby; ?>>
            <?php
            break;
    }
}

/**
 * Save custom field data with comprehensive security and validation
 * 
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @since 1.0
 */
function artitechcore_save_custom_field_data($post_id, $post) {
    // Security and state checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Verify nonce
    if (!isset($_POST['artitechcore_custom_fields_nonce']) || 
        !wp_verify_nonce(sanitize_key($_POST['artitechcore_custom_fields_nonce']), 'artitechcore_save_custom_fields_' . $post_id)) {
        return;
    }
    
    $custom_fields = get_option('artitechcore_custom_fields', array());
    $post_type = get_post_type($post_id);
    
    if (!isset($custom_fields[$post_type])) {
        return;
    }
    
    foreach ($custom_fields[$post_type] as $field_name => $field_config) {
        if (isset($_POST[$field_name])) {
            $value = artitechcore_sanitize_field_value(wp_unslash($_POST[$field_name]), $field_config['type']);
            
            // Validate required fields
            if ($field_config['required'] && empty($value)) {
                add_filter('redirect_post_location', function($location) use ($field_config) {
                    return add_query_arg('artitechcore_error', 'required_field_' . $field_config['name'], $location);
                });
                continue;
            }
            
            // Validate field-specific rules
            if (!artitechcore_validate_field_value($value, $field_config)) {
                add_filter('redirect_post_location', function($location) use ($field_config) {
                    return add_query_arg('artitechcore_error', 'invalid_field_' . $field_config['name'], $location);
                });
                continue;
            }
            
            update_post_meta($post_id, $field_name, $value);
        } else if ($field_config['type'] === 'checkbox') {
            // Handle unchecked checkboxes
            update_post_meta($post_id, $field_name, 0);
        }
    }
}

/**
 * Render individual CPT card with enhanced functionality
 * 
 * @param string $post_type Post type slug
 * @param array $cpt_data CPT configuration data
 * @since 1.0
 */
function artitechcore_render_cpt_card($post_type, $cpt_data) {
    $posts_count = wp_count_posts($post_type);
    $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
    $is_active = post_type_exists($post_type);
    $last_modified = get_option('artitechcore_cpt_modified_' . $post_type, '');
    ?>
    <div class="artitechcore-cpt-card" data-cpt="<?php echo esc_attr($post_type); ?>">
        <div class="artitechcore-cpt-card-header">
            <div class="artitechcore-cpt-checkbox">
                <input type="checkbox" id="cpt-<?php echo esc_attr($post_type); ?>" 
                       value="<?php echo esc_attr($post_type); ?>" class="cpt-checkbox"
                       aria-label="<?php echo esc_attr(sprintf(__('Select %s', 'artitechcore'), $cpt_data['label'])); ?>">
            </div>
            <div class="artitechcore-cpt-status">
                <span class="artitechcore-status-indicator <?php echo $is_active ? 'active' : 'inactive'; ?>" 
                      title="<?php echo $is_active ? esc_attr__('Active', 'artitechcore') : esc_attr__('Inactive', 'artitechcore'); ?>">
                </span>
            </div>
            <div class="artitechcore-cpt-actions">
                <button type="button" class="artitechcore-action-btn" data-action="edit" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Edit %s', 'artitechcore'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                </button>
                <button type="button" class="artitechcore-action-btn" data-action="duplicate" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Duplicate %s', 'artitechcore'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                </button>
                <button type="button" class="artitechcore-action-btn artitechcore-danger" data-action="delete" 
                        data-cpt="<?php echo esc_attr($post_type); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Delete %s', 'artitechcore'), $cpt_data['label'])); ?>">
                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        
        <div class="artitechcore-cpt-card-body">
            <div class="artitechcore-cpt-icon">
                <span class="dashicons <?php echo esc_attr($cpt_data['menu_icon'] ?? 'dashicons-admin-post'); ?>" aria-hidden="true"></span>
            </div>
            <div class="artitechcore-cpt-info">
                <h3 class="artitechcore-cpt-title">
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $post_type)); ?>" 
                       title="<?php echo esc_attr(sprintf(__('View all %s', 'artitechcore'), $cpt_data['label'])); ?>">
                        <?php echo esc_html($cpt_data['label']); ?>
                    </a>
                </h3>
                <code class="artitechcore-cpt-slug"><?php echo esc_html($post_type); ?></code>
                
                <?php 
                $archive_link = get_post_type_archive_link($post_type);
                if ($archive_link): 
                ?>
                    <a href="<?php echo esc_url($archive_link); ?>" class="artitechcore-archive-link" target="_blank" title="<?php esc_attr_e('View Public Archive', 'artitechcore'); ?>">
                        <span class="dashicons dashicons-external"></span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($cpt_data['description'])): ?>
                    <p class="artitechcore-cpt-description"><?php echo esc_html($cpt_data['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="artitechcore-cpt-card-footer">
            <div class="artitechcore-cpt-stats">
                <div class="artitechcore-stat">
                    <span class="artitechcore-stat-number"><?php echo esc_html($total_posts); ?></span>
                    <span class="artitechcore-stat-label"><?php esc_html_e('Posts', 'artitechcore'); ?></span>
                </div>
                <div class="artitechcore-stat">
                    <span class="artitechcore-stat-number"><?php echo esc_html(count($cpt_data['custom_fields'] ?? array())); ?></span>
                    <span class="artitechcore-stat-label"><?php esc_html_e('Fields', 'artitechcore'); ?></span>
                </div>
            </div>
            
            <div class="artitechcore-cpt-quick-actions">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . $post_type)); ?>" 
                   class="dg10-btn dg10-btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                    <?php esc_html_e('View Posts', 'artitechcore'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $post_type)); ?>" 
                   class="dg10-btn dg10-btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                    <?php esc_html_e('Add New', 'artitechcore'); ?>
                </a>
            </div>
            
            <?php if (!empty($last_modified)): ?>
                <div class="artitechcore-cpt-meta">
                    <small><?php echo esc_html(sprintf(__('Modified: %s', 'artitechcore'), $last_modified)); ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Add placeholder functions for missing tabs
function artitechcore_cpt_templates_tab() {
    // Handle template creation
    if (isset($_POST['create_from_template']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_create_from_template')) {
        artitechcore_create_cpt_from_template();
    }
    
    $templates = artitechcore_get_cpt_templates();
    ?>
    <div class="artitechcore-cpt-templates">
        <div class="artitechcore-templates-header">
            <h3><?php esc_html_e('CPT Templates & Presets', 'artitechcore'); ?></h3>
            <p><?php esc_html_e('Choose from pre-built custom post type templates for common use cases. Templates include custom fields and sample content.', 'artitechcore'); ?></p>
        </div>
        
        <div class="artitechcore-templates-grid">
            <?php foreach ($templates as $template_id => $template): ?>
                <div class="artitechcore-template-card" data-template="<?php echo esc_attr($template_id); ?>">
                    <div class="artitechcore-template-header">
                        <div class="artitechcore-template-icon">
                            <span class="dashicons <?php echo esc_attr($template['icon']); ?>" aria-hidden="true"></span>
                        </div>
                        <div class="artitechcore-template-info">
                            <h4><?php echo esc_html($template['name']); ?></h4>
                            <p class="artitechcore-template-description"><?php echo esc_html($template['description']); ?></p>
                        </div>
                    </div>
                    
                    <div class="artitechcore-template-details">
                        <div class="artitechcore-template-stats">
                            <div class="artitechcore-stat">
                                <span class="artitechcore-stat-number"><?php echo esc_html(count($template['custom_fields'])); ?></span>
                                <span class="artitechcore-stat-label"><?php esc_html_e('Fields', 'artitechcore'); ?></span>
                            </div>
                            <div class="artitechcore-stat">
                                <span class="artitechcore-stat-number"><?php echo esc_html(count($template['sample_entries'] ?? [])); ?></span>
                                <span class="artitechcore-stat-label"><?php esc_html_e('Samples', 'artitechcore'); ?></span>
                            </div>
                        </div>
                        
                        <div class="artitechcore-template-features">
                            <h5><?php esc_html_e('Features', 'artitechcore'); ?></h5>
                            <ul>
                                <?php foreach ($template['features'] as $feature): ?>
                                    <li><?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <?php if (!empty($template['custom_fields'])): ?>
                            <div class="artitechcore-template-fields">
                                <h5><?php esc_html_e('Custom Fields', 'artitechcore'); ?></h5>
                                <div class="artitechcore-fields-preview">
                                    <?php foreach (array_slice($template['custom_fields'], 0, 3) as $field): ?>
                                        <span class="artitechcore-field-tag"><?php echo esc_html($field['label']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($template['custom_fields']) > 3): ?>
                                        <span class="artitechcore-field-tag artitechcore-more-fields">
                                            +<?php echo esc_html(count($template['custom_fields']) - 3); ?> <?php esc_html_e('more', 'artitechcore'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artitechcore-template-actions">
                        <button type="button" class="dg10-btn dg10-btn-outline artitechcore-preview-template" 
                                data-template="<?php echo esc_attr($template_id); ?>" style="flex: 1;">
                            <span class="nav-icon">👁️</span>
                            <?php esc_html_e('Preview', 'artitechcore'); ?>
                        </button>
                        
                        <form method="post" action="" class="artitechcore-template-form" style="display: contents;">
                            <?php wp_nonce_field('artitechcore_create_from_template'); ?>
                            <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
                            <button type="submit" name="create_from_template" class="dg10-btn dg10-btn-primary" style="flex: 1;">
                                <span class="nav-icon">➕</span>
                                <?php esc_html_e('Create', 'artitechcore'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Template Preview Modal -->
        <div id="artitechcore-template-preview-modal" class="artitechcore-modal-overlay" style="display: none;">
            <div class="artitechcore-modal">
                <div class="artitechcore-modal-header">
                    <h3 id="preview-template-name"><?php esc_html_e('Template Preview', 'artitechcore'); ?></h3>
                    <button class="artitechcore-modal-close">&times;</button>
                </div>
                <div class="artitechcore-modal-body" id="preview-template-content">
                    <!-- Template preview content will be loaded here -->
                </div>
                <div class="artitechcore-modal-footer">
                    <button type="button" class="dg10-btn dg10-btn-outline artitechcore-modal-close">
                        <?php esc_html_e('Close', 'artitechcore'); ?>
                    </button>
                    <button type="button" class="dg10-btn dg10-btn-primary" id="create-from-preview">
                        <span class="nav-icon">➕</span>
                        <?php esc_html_e('Create CPT', 'artitechcore'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function artitechcore_cpt_bulk_operations_tab() {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    ?>
    <div class="artitechcore-cpt-bulk">
        <div class="artitechcore-bulk-header">
            <h3><?php esc_html_e('Bulk Operations', 'artitechcore'); ?></h3>
            <p><?php esc_html_e('Perform bulk operations on multiple custom post types. Select CPTs and choose an action to apply.', 'artitechcore'); ?></p>
        </div>
        
        <?php if (empty($dynamic_cpts)): ?>
            <div class="artitechcore-empty-state">
                <div class="artitechcore-empty-state-icon">
                    <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
                </div>
                <h4><?php esc_html_e('No Custom Post Types', 'artitechcore'); ?></h4>
                <p><?php esc_html_e('You need to create some custom post types before you can perform bulk operations.', 'artitechcore'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="button button-primary">
                    <?php esc_html_e('Create Your First CPT', 'artitechcore'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="artitechcore-bulk-operations">
                <div class="artitechcore-bulk-selection">
                    <div class="artitechcore-bulk-controls">
                        <label>
                            <input type="checkbox" id="select-all-bulk" class="artitechcore-select-all">
                            <?php esc_html_e('Select All CPTs', 'artitechcore'); ?>
                        </label>
                        <span class="artitechcore-selection-count">
                            <span id="selected-count">0</span> <?php esc_html_e('CPTs selected', 'artitechcore'); ?>
                        </span>
                    </div>
                    
                    <div class="artitechcore-bulk-actions-panel">
                        <select id="bulk-action-selector" class="artitechcore-bulk-selector">
                            <option value=""><?php esc_html_e('Choose Bulk Action...', 'artitechcore'); ?></option>
                            <option value="activate"><?php esc_html_e('Activate Selected CPTs', 'artitechcore'); ?></option>
                            <option value="deactivate"><?php esc_html_e('Deactivate Selected CPTs', 'artitechcore'); ?></option>
                            <option value="export"><?php esc_html_e('Export Selected CPTs', 'artitechcore'); ?></option>
                            <option value="duplicate"><?php esc_html_e('Duplicate Selected CPTs', 'artitechcore'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete Selected CPTs', 'artitechcore'); ?></option>
                        </select>
                        
                        <button type="button" id="apply-bulk-action-btn" class="dg10-btn dg10-btn-primary" disabled>
                            <span class="nav-icon">⚡</span>
                            <?php esc_html_e('Apply Action', 'artitechcore'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="artitechcore-bulk-cpt-list">
                    <table class="dg10-table">
                        <thead>
                            <tr>
                                <th width="50px"><?php esc_html_e('Select', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('CPT Name', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('Label', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('Status', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('Posts', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('Fields', 'artitechcore'); ?></th>
                                <th><?php esc_html_e('Last Modified', 'artitechcore'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                                <?php
                                $posts_count = wp_count_posts($post_type);
                                $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
                                $is_active = post_type_exists($post_type);
                                $last_modified = get_option('artitechcore_cpt_modified_' . $post_type, '');
                                ?>
                                <tr data-cpt="<?php echo esc_attr($post_type); ?>">
                                    <td>
                                        <input type="checkbox" class="artitechcore-cpt-checkbox" value="<?php echo esc_attr($post_type); ?>">
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($post_type); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="artitechcore-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $is_active ? esc_html__('Active', 'artitechcore') : esc_html__('Inactive', 'artitechcore'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html($total_posts); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(count($cpt_data['custom_fields'] ?? array())); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($last_modified ? date('M j, Y', strtotime($last_modified)) : 'Never'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="artitechcore-bulk-results" id="bulk-results" style="display: none;">
                    <h4><?php esc_html_e('Operation Results', 'artitechcore'); ?></h4>
                    <div id="bulk-results-content"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function artitechcore_cpt_import_export_tab() {
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    // Handle export request
    if (isset($_POST['export_cpts']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_export_cpts')) {
        artitechcore_handle_cpt_export();
        return;
    }
    
    // Handle import request
    if (isset($_POST['import_cpts']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_import_cpts')) {
        artitechcore_handle_cpt_import();
    }
    ?>
    <div class="artitechcore-cpt-import-export">
        <div class="artitechcore-import-export-header">
            <h3><?php esc_html_e('Import/Export CPTs', 'artitechcore'); ?></h3>
            <p><?php esc_html_e('Import and export custom post type configurations to backup, migrate, or share your CPTs.', 'artitechcore'); ?></p>
        </div>
        
        <div class="artitechcore-import-export-grid">
            <!-- Export Section -->
            <div class="artitechcore-export-section">
                <div class="artitechcore-section-header">
                    <h4><?php esc_html_e('Export CPTs', 'artitechcore'); ?></h4>
                    <p><?php esc_html_e('Export your custom post types to a JSON file for backup or migration.', 'artitechcore'); ?></p>
                </div>
                
                <?php if (empty($dynamic_cpts)): ?>
                    <div class="artitechcore-empty-state">
                        <span class="dashicons dashicons-download" aria-hidden="true"></span>
                        <p><?php esc_html_e('No custom post types to export.', 'artitechcore'); ?></p>
                    </div>
                <?php else: ?>
                    <form method="post" action="" class="artitechcore-export-form">
                        <?php wp_nonce_field('artitechcore_export_cpts'); ?>
                        
                        <div class="artitechcore-export-options">
                            <h5><?php esc_html_e('Export Options', 'artitechcore'); ?></h5>
                            
                            <label class="artitechcore-export-option">
                                <input type="radio" name="export_type" value="all" checked>
                                <span class="option-label">
                                    <strong><?php esc_html_e('Export All CPTs', 'artitechcore'); ?></strong>
                                    <small><?php esc_html_e('Export all custom post types', 'artitechcore'); ?></small>
                                </span>
                            </label>
                            
                            <label class="artitechcore-export-option">
                                <input type="radio" name="export_type" value="selected">
                                <span class="option-label">
                                    <strong><?php esc_html_e('Export Selected CPTs', 'artitechcore'); ?></strong>
                                    <small><?php esc_html_e('Choose specific CPTs to export', 'artitechcore'); ?></small>
                                </span>
                            </label>
                        </div>
                        
                        <div class="artitechcore-cpt-selection" id="cpt-selection" style="display: none;">
                            <h5><?php esc_html_e('Select CPTs to Export', 'artitechcore'); ?></h5>
                            <?php foreach ($dynamic_cpts as $post_type => $cpt_data): ?>
                                <label class="artitechcore-cpt-option">
                                    <input type="checkbox" name="export_cpts[]" value="<?php echo esc_attr($post_type); ?>">
                                    <span class="cpt-info">
                                        <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                        <code><?php echo esc_html($post_type); ?></code>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="artitechcore-export-actions">
                            <button type="submit" name="export_cpts" class="dg10-btn dg10-btn-primary">
                                <span class="nav-icon">📥</span>
                                <?php esc_html_e('Export CPTs', 'artitechcore'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Import Section -->
            <div class="artitechcore-import-section">
                <div class="artitechcore-section-header">
                    <h4><?php esc_html_e('Import CPTs', 'artitechcore'); ?></h4>
                    <p><?php esc_html_e('Import custom post types from a JSON file.', 'artitechcore'); ?></p>
                </div>
                
                <form method="post" action="" enctype="multipart/form-data" class="artitechcore-import-form">
                    <?php wp_nonce_field('artitechcore_import_cpts'); ?>
                    
                    <div class="artitechcore-import-options">
                        <div class="artitechcore-file-upload">
                            <label for="cpt-import-file" class="artitechcore-upload-label">
                                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                                <span class="upload-text"><?php esc_html_e('Choose JSON file to import', 'artitechcore'); ?></span>
                                <input type="file" id="cpt-import-file" name="cpt_import_file" accept=".json" required>
                            </label>
                            <p class="description"><?php esc_html_e('Select a JSON file exported from ArtitechCore CPT Manager.', 'artitechcore'); ?></p>
                        </div>
                        
                        <div class="artitechcore-import-settings">
                            <h5><?php esc_html_e('Import Settings', 'artitechcore'); ?></h5>
                            
                            <label class="artitechcore-import-option">
                                <input type="checkbox" name="import_overwrite" value="1">
                                <span class="option-label">
                                    <strong><?php esc_html_e('Overwrite Existing CPTs', 'artitechcore'); ?></strong>
                                    <small><?php esc_html_e('Replace existing CPTs with the same name', 'artitechcore'); ?></small>
                                </span>
                            </label>
                            
                            <label class="artitechcore-import-option">
                                <input type="checkbox" name="import_activate" value="1" checked>
                                <span class="option-label">
                                    <strong><?php esc_html_e('Activate Imported CPTs', 'artitechcore'); ?></strong>
                                    <small><?php esc_html_e('Automatically activate imported CPTs', 'artitechcore'); ?></small>
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="artitechcore-import-actions">
                        <button type="submit" name="import_cpts" class="dg10-btn dg10-btn-primary">
                            <span class="nav-icon">📤</span>
                            <?php esc_html_e('Import CPTs', 'artitechcore'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Import/Export History -->
        <div class="artitechcore-import-export-history">
            <h4><?php esc_html_e('Recent Operations', 'artitechcore'); ?></h4>
            <div class="artitechcore-history-list">
                <?php artitechcore_display_import_export_history(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler to retrieve CPT data for editing/display
 * 
 * @since 1.0
 */
function artitechcore_get_cpt_data_ajax() {
    // Security checks
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'artitechcore'));
    }
    
    $post_type = sanitize_key($_POST['post_type'] ?? '');
    
    if (empty($post_type)) {
        wp_send_json_error(__('Invalid post type specified.', 'artitechcore'));
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        wp_send_json_error(__('Custom post type not found.', 'artitechcore'));
    }
    
    $cpt_data = $dynamic_cpts[$post_type];
    
    // Get additional data
    $posts_count = wp_count_posts($post_type);
    $total_posts = isset($posts_count->publish) ? ($posts_count->publish + $posts_count->draft + $posts_count->private) : 0;
    $is_active = post_type_exists($post_type);
    $last_modified = get_option('artitechcore_cpt_modified_' . $post_type, '');
    
    wp_send_json_success(array(
        'cpt_data' => $cpt_data,
        'stats' => array(
            'total_posts' => $total_posts,
            'is_active' => $is_active,
            'last_modified' => $last_modified,
            'custom_fields_count' => count($cpt_data['custom_fields'] ?? array())
        )
    ));
}

/**
 * AJAX handler for bulk CPT operations
 * 
 * @since 1.0
 */
function artitechcore_handle_bulk_cpt_operations_ajax() {
    // Security checks
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'artitechcore'));
    }
    
    $action = sanitize_key($_POST['bulk_action'] ?? '');
    $cpt_ids = array_map('sanitize_key', (array) ($_POST['cpt_ids'] ?? array()));
    
    if (empty($action) || empty($cpt_ids)) {
        wp_send_json_error(__('Invalid bulk action or no CPTs selected.', 'artitechcore'));
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    $results = array();
    $success_count = 0;
    $error_count = 0;
    
    foreach ($cpt_ids as $post_type) {
        if (!isset($dynamic_cpts[$post_type])) {
            $results[] = array(
                'post_type' => $post_type,
                'status' => 'error',
                'message' => __('CPT not found.', 'artitechcore')
            );
            $error_count++;
            continue;
        }
        
        switch ($action) {
            case 'activate':
                $result = artitechcore_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
                if (is_wp_error($result)) {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'error',
                        'message' => $result->get_error_message()
                    );
                    $error_count++;
                } else {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'success',
                        'message' => __('CPT activated successfully.', 'artitechcore')
                    );
                    $success_count++;
                }
                break;
                
            case 'deactivate':
                // Remove from WordPress registration (but keep in database)
                unregister_post_type($post_type);
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'success',
                    'message' => __('CPT deactivated successfully.', 'artitechcore')
                );
                $success_count++;
                break;
                
            case 'delete':
                $delete_result = artitechcore_delete_custom_post_type($post_type);
                if (is_wp_error($delete_result)) {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'error',
                        'message' => $delete_result->get_error_message()
                    );
                    $error_count++;
                } else {
                    $results[] = array(
                        'post_type' => $post_type,
                        'status' => 'success',
                        'message' => __('CPT deleted successfully.', 'artitechcore')
                    );
                    $success_count++;
                }
                break;
                
            case 'export':
                $export_data = array(
                    'post_type' => $post_type,
                    'cpt_data' => $dynamic_cpts[$post_type],
                    'export_date' => current_time('mysql'),
                    'version' => '1.0'
                );
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'success',
                    'message' => __('CPT exported successfully.', 'artitechcore'),
                    'export_data' => $export_data
                );
                $success_count++;
                break;
                
            default:
                $results[] = array(
                    'post_type' => $post_type,
                    'status' => 'error',
                    'message' => __('Invalid bulk action.', 'artitechcore')
                );
                $error_count++;
                break;
        }
    }
    
    // Clear cache after bulk operations
    wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    wp_send_json_success(array(
        'action' => $action,
        'total_processed' => count($cpt_ids),
        'success_count' => $success_count,
        'error_count' => $error_count,
        'results' => $results,
        'message' => sprintf(
            __('Bulk operation completed. %d successful, %d failed.', 'artitechcore'),
            $success_count,
            $error_count
        )
    ));
}

/**
 * AJAX handler for updating CPT data
 * 
 * @since 1.0
 */
function artitechcore_handle_cpt_update_ajax() {
    // Security checks
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'artitechcore'));
    }
    
    $post_type = sanitize_key($_POST['cpt_name'] ?? '');
    $label = sanitize_text_field($_POST['cpt_label'] ?? '');
    $description = sanitize_textarea_field($_POST['cpt_description'] ?? '');
    $menu_icon = sanitize_text_field($_POST['cpt_menu_icon'] ?? 'dashicons-admin-post');
    
    if (empty($post_type) || empty($label)) {
        wp_send_json_error(__('Invalid CPT data provided.', 'artitechcore'));
    }
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    if (!isset($dynamic_cpts[$post_type])) {
        wp_send_json_error(__('Custom post type not found.', 'artitechcore'));
    }
    
    // Update CPT data
    $dynamic_cpts[$post_type]['label'] = $label;
    $dynamic_cpts[$post_type]['description'] = $description;
    $dynamic_cpts[$post_type]['menu_icon'] = $menu_icon;
    
    // Save updated data
    update_option('artitechcore_dynamic_cpts', $dynamic_cpts);
    
    // Update last modified timestamp
    update_option('artitechcore_cpt_modified_' . $post_type, current_time('mysql'));
    
    // Clear cache
    wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    // Re-register the CPT with updated data
    $result = artitechcore_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Log activity
    artitechcore_log_cpt_activity('update', $post_type, true);
    
    wp_send_json_success(array(
        'message' => __('CPT updated successfully.', 'artitechcore'),
        'cpt_data' => $dynamic_cpts[$post_type]
    ));
}

/**
 * Get available CPT templates
 * 
 * @return array Array of CPT templates
 * @since 1.0
 */
function artitechcore_get_cpt_templates() {
    return array(
        'portfolio' => array(
            'name' => 'Portfolio',
            'description' => 'Perfect for showcasing creative work, projects, and case studies.',
            'icon' => 'dashicons-portfolio',
            'features' => array(
                'Project showcase',
                'Client information',
                'Project categories',
                'Featured images',
                'Project links'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'client_name',
                    'label' => 'Client Name',
                    'type' => 'text',
                    'description' => 'Name of the client or company',
                    'required' => false
                ),
                array(
                    'name' => 'project_url',
                    'label' => 'Project URL',
                    'type' => 'url',
                    'description' => 'Link to the live project',
                    'required' => false
                ),
                array(
                    'name' => 'project_date',
                    'label' => 'Project Date',
                    'type' => 'date',
                    'description' => 'When the project was completed',
                    'required' => false
                ),
                array(
                    'name' => 'technologies_used',
                    'label' => 'Technologies Used',
                    'type' => 'textarea',
                    'description' => 'Technologies, tools, and frameworks used',
                    'required' => false
                ),
                array(
                    'name' => 'project_category',
                    'label' => 'Project Category',
                    'type' => 'select',
                    'description' => 'Type of project',
                    'required' => true,
                    'options' => array('Web Design', 'Development', 'Branding', 'Marketing', 'Other')
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'E-commerce Website Redesign',
                    'content' => 'Complete redesign of an e-commerce platform with improved user experience and mobile responsiveness.'
                ),
                array(
                    'title' => 'Brand Identity Package',
                    'content' => 'Comprehensive brand identity design including logo, business cards, and marketing materials.'
                )
            )
        ),
        'testimonials' => array(
            'name' => 'Testimonials',
            'description' => 'Collect and display customer testimonials and reviews.',
            'icon' => 'dashicons-format-quote',
            'features' => array(
                'Customer reviews',
                'Star ratings',
                'Customer photos',
                'Company information',
                'Featured testimonials'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'customer_name',
                    'label' => 'Customer Name',
                    'type' => 'text',
                    'description' => 'Full name of the customer',
                    'required' => true
                ),
                array(
                    'name' => 'customer_company',
                    'label' => 'Company',
                    'type' => 'text',
                    'description' => 'Customer\'s company name',
                    'required' => false
                ),
                array(
                    'name' => 'customer_position',
                    'label' => 'Position',
                    'type' => 'text',
                    'description' => 'Customer\'s job title or position',
                    'required' => false
                ),
                array(
                    'name' => 'rating',
                    'label' => 'Rating',
                    'type' => 'select',
                    'description' => 'Star rating (1-5)',
                    'required' => true,
                    'options' => array('1', '2', '3', '4', '5')
                ),
                array(
                    'name' => 'customer_photo',
                    'label' => 'Customer Photo',
                    'type' => 'image',
                    'description' => 'Photo of the customer',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'Excellent Service',
                    'content' => 'The team delivered exactly what we needed on time and within budget. Highly recommended!'
                ),
                array(
                    'title' => 'Outstanding Results',
                    'content' => 'Our website traffic increased by 300% after implementing their SEO recommendations.'
                )
            )
        ),
        'team' => array(
            'name' => 'Team Members',
            'description' => 'Showcase your team members with detailed profiles and contact information.',
            'icon' => 'dashicons-groups',
            'features' => array(
                'Team profiles',
                'Social media links',
                'Skills and expertise',
                'Contact information',
                'Department organization'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'position',
                    'label' => 'Position',
                    'type' => 'text',
                    'description' => 'Job title or position',
                    'required' => true
                ),
                array(
                    'name' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'description' => 'Department or team',
                    'required' => true,
                    'options' => array('Management', 'Development', 'Design', 'Marketing', 'Sales', 'Support')
                ),
                array(
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'description' => 'Contact email address',
                    'required' => false
                ),
                array(
                    'name' => 'phone',
                    'label' => 'Phone',
                    'type' => 'text',
                    'description' => 'Contact phone number',
                    'required' => false
                ),
                array(
                    'name' => 'linkedin_url',
                    'label' => 'LinkedIn URL',
                    'type' => 'url',
                    'description' => 'LinkedIn profile URL',
                    'required' => false
                ),
                array(
                    'name' => 'skills',
                    'label' => 'Skills',
                    'type' => 'textarea',
                    'description' => 'Key skills and expertise',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'John Smith',
                    'content' => 'Experienced project manager with 10+ years in the industry.'
                ),
                array(
                    'title' => 'Sarah Johnson',
                    'content' => 'Creative director specializing in brand identity and user experience design.'
                )
            )
        ),
        'services' => array(
            'name' => 'Services',
            'description' => 'Display your services with pricing, features, and detailed descriptions.',
            'icon' => 'dashicons-admin-tools',
            'features' => array(
                'Service descriptions',
                'Pricing information',
                'Feature lists',
                'Service categories',
                'Call-to-action buttons'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'service_price',
                    'label' => 'Price',
                    'type' => 'text',
                    'description' => 'Service price or pricing range',
                    'required' => false
                ),
                array(
                    'name' => 'service_category',
                    'label' => 'Category',
                    'type' => 'select',
                    'description' => 'Service category',
                    'required' => true,
                    'options' => array('Web Development', 'Design', 'Marketing', 'Consulting', 'Support')
                ),
                array(
                    'name' => 'features_list',
                    'label' => 'Features',
                    'type' => 'textarea',
                    'description' => 'List of service features (one per line)',
                    'required' => false
                ),
                array(
                    'name' => 'delivery_time',
                    'label' => 'Delivery Time',
                    'type' => 'text',
                    'description' => 'Expected delivery time',
                    'required' => false
                ),
                array(
                    'name' => 'cta_text',
                    'label' => 'Call-to-Action Text',
                    'type' => 'text',
                    'description' => 'Button text for the service',
                    'required' => false
                ),
                array(
                    'name' => 'cta_url',
                    'label' => 'Call-to-Action URL',
                    'type' => 'url',
                    'description' => 'Link for the CTA button',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'Website Development',
                    'content' => 'Custom website development with modern technologies and responsive design.'
                ),
                array(
                    'title' => 'SEO Optimization',
                    'content' => 'Complete SEO audit and optimization to improve your search engine rankings.'
                )
            )
        ),
        'faq' => array(
            'name' => 'FAQ',
            'description' => 'Create a comprehensive FAQ section with categorized questions and answers.',
            'icon' => 'dashicons-editor-help',
            'features' => array(
                'Question categories',
                'Search functionality',
                'Expandable answers',
                'FAQ ordering',
                'Related questions'
            ),
            'custom_fields' => array(
                array(
                    'name' => 'faq_category',
                    'label' => 'Category',
                    'type' => 'select',
                    'description' => 'FAQ category',
                    'required' => true,
                    'options' => array('General', 'Billing', 'Technical', 'Support', 'Features')
                ),
                array(
                    'name' => 'faq_order',
                    'label' => 'Display Order',
                    'type' => 'number',
                    'description' => 'Order for displaying FAQs (lower numbers first)',
                    'required' => false
                ),
                array(
                    'name' => 'related_faqs',
                    'label' => 'Related FAQs',
                    'type' => 'textarea',
                    'description' => 'IDs of related FAQ posts (comma-separated)',
                    'required' => false
                )
            ),
            'sample_entries' => array(
                array(
                    'title' => 'How do I get started?',
                    'content' => 'Getting started is easy! Simply sign up for an account and follow our onboarding process.'
                ),
                array(
                    'title' => 'What payment methods do you accept?',
                    'content' => 'We accept all major credit cards, PayPal, and bank transfers.'
                )
            )
        ),
        'real_estate' => array(
            'name' => 'Real Estate / Properties',
            'description' => 'Perfect for real estate agencies, property listings, and rental businesses.',
            'icon' => 'dashicons-building',
            'features' => array(
                'Property listings',
                'Price & status tracking',
                'Location mapping',
                'Photo galleries',
                'Agent information',
                'Inquiry forms'
            ),
            'custom_fields' => array(
                array('name' => 'property_price', 'label' => 'Price', 'type' => 'text', 'required' => true),
                array('name' => 'property_status', 'label' => 'Status', 'type' => 'select', 'options' => array('For Sale', 'For Rent', 'Sold', 'Pending'), 'required' => true),
                array('name' => 'property_type', 'label' => 'Property Type', 'type' => 'select', 'options' => array('House', 'Apartment', 'Condo', 'Land', 'Commercial'), 'required' => true),
                array('name' => 'bedrooms', 'label' => 'Bedrooms', 'type' => 'number', 'required' => false),
                array('name' => 'bathrooms', 'label' => 'Bathrooms', 'type' => 'number', 'required' => false),
                array('name' => 'square_feet', 'label' => 'Square Feet', 'type' => 'number', 'required' => false),
                array('name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => true),
                array('name' => 'agent_name', 'label' => 'Agent Name', 'type' => 'text', 'required' => false),
                array('name' => 'agent_phone', 'label' => 'Agent Phone', 'type' => 'text', 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Modern Downtown Apartment', 'content' => '2-bedroom luxury apartment with stunning city views.'),
                array('title' => 'Family Home with Garden', 'content' => 'Spacious 4-bedroom family home in quiet suburban neighborhood.')
            )
        ),
        'events' => array(
            'name' => 'Events & Conferences',
            'description' => 'Ideal for event planners, venues, conferences, and workshops.',
            'icon' => 'dashicons-calendar-alt',
            'features' => array(
                'Event scheduling',
                'Venue information',
                'Ticket pricing',
                'Speaker profiles',
                'Registration links',
                'Recurring events'
            ),
            'custom_fields' => array(
                array('name' => 'event_date', 'label' => 'Event Date', 'type' => 'date', 'required' => true),
                array('name' => 'event_time', 'label' => 'Event Time', 'type' => 'text', 'required' => false),
                array('name' => 'event_venue', 'label' => 'Venue', 'type' => 'text', 'required' => true),
                array('name' => 'event_address', 'label' => 'Address', 'type' => 'textarea', 'required' => false),
                array('name' => 'ticket_price', 'label' => 'Ticket Price', 'type' => 'text', 'required' => false),
                array('name' => 'registration_url', 'label' => 'Registration URL', 'type' => 'url', 'required' => false),
                array('name' => 'event_type', 'label' => 'Event Type', 'type' => 'select', 'options' => array('Conference', 'Workshop', 'Seminar', 'Webinar', 'Social', 'Concert'), 'required' => true),
                array('name' => 'max_attendees', 'label' => 'Max Attendees', 'type' => 'number', 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Annual Tech Conference 2024', 'content' => 'Join industry leaders for our flagship technology conference.'),
                array('title' => 'Digital Marketing Workshop', 'content' => 'Hands-on workshop covering the latest marketing strategies.')
            )
        ),
        'courses' => array(
            'name' => 'Courses / Learning',
            'description' => 'For educational institutions, online courses, and training programs.',
            'icon' => 'dashicons-welcome-learn-more',
            'features' => array(
                'Course curriculum',
                'Instructor details',
                'Duration & pricing',
                'Skill levels',
                'Enrollment tracking',
                'Certification info'
            ),
            'custom_fields' => array(
                array('name' => 'instructor', 'label' => 'Instructor', 'type' => 'text', 'required' => true),
                array('name' => 'duration', 'label' => 'Duration', 'type' => 'text', 'required' => false),
                array('name' => 'course_price', 'label' => 'Price', 'type' => 'text', 'required' => false),
                array('name' => 'skill_level', 'label' => 'Skill Level', 'type' => 'select', 'options' => array('Beginner', 'Intermediate', 'Advanced', 'All Levels'), 'required' => true),
                array('name' => 'course_category', 'label' => 'Category', 'type' => 'select', 'options' => array('Technology', 'Business', 'Design', 'Marketing', 'Personal Development', 'Other'), 'required' => true),
                array('name' => 'enrollment_url', 'label' => 'Enrollment URL', 'type' => 'url', 'required' => false),
                array('name' => 'certification', 'label' => 'Certification Offered', 'type' => 'select', 'options' => array('Yes', 'No'), 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Complete Web Development Bootcamp', 'content' => 'Learn full-stack web development from scratch.'),
                array('title' => 'Business Analytics Fundamentals', 'content' => 'Master data-driven decision making for business.')
            )
        ),
        'products' => array(
            'name' => 'Products / Catalog',
            'description' => 'For product showcases, catalogs, and non-WooCommerce stores.',
            'icon' => 'dashicons-products',
            'features' => array(
                'Product details',
                'Pricing & SKU',
                'Stock status',
                'Product gallery',
                'Categories',
                'Specifications'
            ),
            'custom_fields' => array(
                array('name' => 'product_price', 'label' => 'Price', 'type' => 'text', 'required' => true),
                array('name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => false),
                array('name' => 'stock_status', 'label' => 'Stock Status', 'type' => 'select', 'options' => array('In Stock', 'Out of Stock', 'Pre-Order', 'Discontinued'), 'required' => true),
                array('name' => 'product_category', 'label' => 'Category', 'type' => 'text', 'required' => false),
                array('name' => 'brand', 'label' => 'Brand', 'type' => 'text', 'required' => false),
                array('name' => 'specifications', 'label' => 'Specifications', 'type' => 'textarea', 'required' => false),
                array('name' => 'buy_url', 'label' => 'Purchase URL', 'type' => 'url', 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Premium Wireless Headphones', 'content' => 'High-fidelity audio with active noise cancellation.'),
                array('title' => 'Ergonomic Office Chair', 'content' => 'Designed for all-day comfort and productivity.')
            )
        ),
        'locations' => array(
            'name' => 'Locations / Branches',
            'description' => 'For businesses with multiple locations, stores, or branches.',
            'icon' => 'dashicons-location-alt',
            'features' => array(
                'Store locator',
                'Business hours',
                'Contact details',
                'Map integration',
                'Services offered',
                'Staff info'
            ),
            'custom_fields' => array(
                array('name' => 'address', 'label' => 'Full Address', 'type' => 'textarea', 'required' => true),
                array('name' => 'phone', 'label' => 'Phone Number', 'type' => 'text', 'required' => true),
                array('name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => false),
                array('name' => 'business_hours', 'label' => 'Business Hours', 'type' => 'textarea', 'required' => false),
                array('name' => 'google_maps_url', 'label' => 'Google Maps URL', 'type' => 'url', 'required' => false),
                array('name' => 'region', 'label' => 'Region', 'type' => 'select', 'options' => array('North', 'South', 'East', 'West', 'Central', 'International'), 'required' => false),
                array('name' => 'manager_name', 'label' => 'Manager Name', 'type' => 'text', 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Downtown Flagship Store', 'content' => 'Our main location in the heart of the city.'),
                array('title' => 'Westside Mall Branch', 'content' => 'Convenient location at Westside Shopping Center.')
            )
        ),
        'jobs' => array(
            'name' => 'Jobs / Careers',
            'description' => 'For company career pages and job boards.',
            'icon' => 'dashicons-businessman',
            'features' => array(
                'Job listings',
                'Requirements',
                'Salary ranges',
                'Application forms',
                'Department filters',
                'Employment type'
            ),
            'custom_fields' => array(
                array('name' => 'department', 'label' => 'Department', 'type' => 'select', 'options' => array('Engineering', 'Design', 'Marketing', 'Sales', 'Operations', 'HR', 'Finance'), 'required' => true),
                array('name' => 'employment_type', 'label' => 'Employment Type', 'type' => 'select', 'options' => array('Full-Time', 'Part-Time', 'Contract', 'Freelance', 'Internship'), 'required' => true),
                array('name' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => true),
                array('name' => 'salary_range', 'label' => 'Salary Range', 'type' => 'text', 'required' => false),
                array('name' => 'experience_required', 'label' => 'Experience Required', 'type' => 'text', 'required' => false),
                array('name' => 'application_deadline', 'label' => 'Application Deadline', 'type' => 'date', 'required' => false),
                array('name' => 'apply_url', 'label' => 'Application URL', 'type' => 'url', 'required' => false)
            ),
            'sample_entries' => array(
                array('title' => 'Senior Software Engineer', 'content' => 'Join our engineering team to build innovative products.'),
                array('title' => 'Marketing Coordinator', 'content' => 'Support our marketing initiatives and campaigns.')
            )
        )
    );
}

/**
 * Create CPT from template
 * 
 * @since 1.0
 */
function artitechcore_create_cpt_from_template() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'artitechcore'));
    }
    
    $template_id = sanitize_key($_POST['template_id'] ?? '');
    
    if (empty($template_id)) {
        echo '<div class="notice notice-error"><p>' . __('Invalid template selected.', 'artitechcore') . '</p></div>';
        return;
    }
    
    $templates = artitechcore_get_cpt_templates();
    
    if (!isset($templates[$template_id])) {
        echo '<div class="notice notice-error"><p>' . __('Template not found.', 'artitechcore') . '</p></div>';
        return;
    }
    
    $template = $templates[$template_id];
    
    // Create CPT data from template
    $cpt_data = array(
        'name' => $template_id,
        'label' => $template['name'],
        'description' => $template['description'],
        'menu_icon' => $template['icon'],
        'hierarchical' => false,
        'custom_fields' => $template['custom_fields'],
        'sample_entries' => $template['sample_entries'] ?? array()
    );
    
    // Register the CPT
    $result = artitechcore_register_dynamic_custom_post_type($cpt_data);
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>' . sprintf(__('Custom post type "%s" created successfully from template!', 'artitechcore'), $template['name']) . '</p></div>';
        
        // Create sample entries if available
        if (!empty($template['sample_entries'])) {
            artitechcore_create_sample_cpt_entries($cpt_data);
        }
    }
}

/**
 * Handle CPT export
 * 
 * @since 1.0
 */
function artitechcore_handle_cpt_export() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'artitechcore'));
    }
    
    $export_type = sanitize_key($_POST['export_type'] ?? 'all');
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    $export_data = array(
        'version' => '1.0',
        'export_date' => current_time('mysql'),
        'site_url' => get_site_url(),
        'cpts' => array()
    );
    
    if ($export_type === 'selected' && isset($_POST['export_cpts'])) {
        $selected_cpts = array_map('sanitize_key', (array) $_POST['export_cpts']);
        foreach ($selected_cpts as $post_type) {
            if (isset($dynamic_cpts[$post_type])) {
                $export_data['cpts'][$post_type] = $dynamic_cpts[$post_type];
            }
        }
    } else {
        $export_data['cpts'] = $dynamic_cpts;
    }
    
    if (empty($export_data['cpts'])) {
        echo '<div class="notice notice-error"><p>' . __('No CPTs selected for export.', 'artitechcore') . '</p></div>';
        return;
    }
    
    // Generate filename
    $filename = 'artitechcore-cpts-export-' . date('Y-m-d-H-i-s') . '.json';
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(json_encode($export_data, JSON_PRETTY_PRINT)));
    
    // Output JSON
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Handle CPT import
 * 
 * @since 1.0
 */
function artitechcore_handle_cpt_import() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'artitechcore'));
    }
    
    if (!isset($_FILES['cpt_import_file']) || $_FILES['cpt_import_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>' . __('Please select a valid JSON file to import.', 'artitechcore') . '</p></div>';
        return;
    }
    
    $file_content = file_get_contents($_FILES['cpt_import_file']['tmp_name']);
    $import_data = json_decode($file_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<div class="notice notice-error"><p>' . __('Invalid JSON file format.', 'artitechcore') . '</p></div>';
        return;
    }
    
    if (!isset($import_data['cpts']) || !is_array($import_data['cpts'])) {
        echo '<div class="notice notice-error"><p>' . __('Invalid import file format.', 'artitechcore') . '</p></div>';
        return;
    }
    
    $overwrite = isset($_POST['import_overwrite']);
    $activate = isset($_POST['import_activate']);
    
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    $imported_count = 0;
    $skipped_count = 0;
    $errors = array();
    
    foreach ($import_data['cpts'] as $post_type => $cpt_data) {
        if (isset($dynamic_cpts[$post_type]) && !$overwrite) {
            $skipped_count++;
            continue;
        }
        
        // Validate CPT data
        if (!artitechcore_validate_cpt_data($cpt_data)) {
            $errors[] = sprintf(__('Invalid data for CPT "%s"', 'artitechcore'), $post_type);
            continue;
        }
        
        // Sanitize and save CPT data
        $dynamic_cpts[$post_type] = artitechcore_sanitize_cpt_data($cpt_data);
        $imported_count++;
        
        // Activate if requested
        if ($activate) {
            artitechcore_register_dynamic_custom_post_type($dynamic_cpts[$post_type]);
        }
    }
    
    // Save updated CPTs
    update_option('artitechcore_dynamic_cpts', $dynamic_cpts);
    
    // Clear cache
    wp_cache_delete('artitechcore_dynamic_cpts', 'artitechcore_cpt_cache');
    
    // Display results
    $message_parts = array();
    if ($imported_count > 0) {
        $message_parts[] = sprintf(__('%d CPTs imported successfully.', 'artitechcore'), $imported_count);
    }
    if ($skipped_count > 0) {
        $message_parts[] = sprintf(__('%d CPTs skipped (already exist).', 'artitechcore'), $skipped_count);
    }
    if (!empty($errors)) {
        $message_parts[] = sprintf(__('%d errors occurred.', 'artitechcore'), count($errors));
    }
    
    if (!empty($message_parts)) {
        $class = !empty($errors) ? 'notice-warning' : 'notice-success';
        echo '<div class="notice ' . $class . '"><p>' . implode(' ', $message_parts) . '</p></div>';
        
        if (!empty($errors)) {
            echo '<div class="notice notice-error"><p><strong>Errors:</strong><br>' . implode('<br>', $errors) . '</p></div>';
        }
    }
}

/**
 * Display import/export history
 * 
 * @since 3.0
 */
function artitechcore_display_import_export_history() {
    $history = get_option('artitechcore_import_export_history', array());
    
    if (empty($history)) {
        echo '<p>' . __('No recent import/export operations.', 'artitechcore') . '</p>';
        return;
    }
    
    // Sort by date (newest first)
    usort($history, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    echo '<div class="artitechcore-history-items">';
    foreach (array_slice($history, 0, 10) as $item) {
        $icon = $item['type'] === 'export' ? 'dashicons-download' : 'dashicons-upload';
        $class = $item['type'] === 'export' ? 'export' : 'import';
        
        echo '<div class="artitechcore-history-item ' . $class . '">';
        echo '<span class="dashicons ' . $icon . '" aria-hidden="true"></span>';
        echo '<div class="history-details">';
        echo '<strong>' . ucfirst($item['type']) . '</strong> - ';
        echo esc_html($item['description']);
        echo '<br><small>' . esc_html($item['date']) . '</small>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

// ============================================================================
// CUSTOM TAXONOMY MANAGER
// ============================================================================

/**
 * Register existing dynamic Taxonomies from database
 * 
 * @since 3.1
 */
function artitechcore_register_existing_dynamic_taxonomies() {
    $dynamic_taxonomies = get_option('artitechcore_dynamic_taxonomies', []);
    
    if (!empty($dynamic_taxonomies) && is_array($dynamic_taxonomies)) {
        foreach ($dynamic_taxonomies as $taxonomy_slug => $taxonomy_data) {
            if (artitechcore_validate_taxonomy_data($taxonomy_data)) {
                artitechcore_register_dynamic_taxonomy($taxonomy_data);
            }
        }
    }
}

/**
 * Register a single dynamic taxonomy
 * 
 * @param array $taxonomy_data Taxonomy configuration
 * @return bool|WP_Error
 * @since 3.1
 */
function artitechcore_register_dynamic_taxonomy($taxonomy_data) {
    $taxonomy_slug = sanitize_key($taxonomy_data['name']);
    $singular_label = sanitize_text_field($taxonomy_data['singular_label']);
    $plural_label = sanitize_text_field($taxonomy_data['plural_label']);
    $post_types = isset($taxonomy_data['post_types']) && is_array($taxonomy_data['post_types']) 
        ? array_map('sanitize_key', $taxonomy_data['post_types']) 
        : array('post');
    $hierarchical = isset($taxonomy_data['hierarchical']) ? (bool) $taxonomy_data['hierarchical'] : true;
    
    $labels = array(
        'name'              => $plural_label,
        'singular_name'     => $singular_label,
        'search_items'      => sprintf(__('Search %s', 'artitechcore'), $plural_label),
        'all_items'         => sprintf(__('All %s', 'artitechcore'), $plural_label),
        'parent_item'       => $hierarchical ? sprintf(__('Parent %s', 'artitechcore'), $singular_label) : null,
        'parent_item_colon' => $hierarchical ? sprintf(__('Parent %s:', 'artitechcore'), $singular_label) : null,
        'edit_item'         => sprintf(__('Edit %s', 'artitechcore'), $singular_label),
        'update_item'       => sprintf(__('Update %s', 'artitechcore'), $singular_label),
        'add_new_item'      => sprintf(__('Add New %s', 'artitechcore'), $singular_label),
        'new_item_name'     => sprintf(__('New %s Name', 'artitechcore'), $singular_label),
        'menu_name'         => $plural_label,
    );
    
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => $hierarchical,
        'public'            => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => $taxonomy_slug),
    );
    
    $result = register_taxonomy($taxonomy_slug, $post_types, $args);
    
    if (is_wp_error($result)) {
        error_log('ArtitechCore Taxonomy Registration Error: ' . $result->get_error_message());
        return $result;
    }
    
    return true;
}

/**
 * Validate Taxonomy data structure
 * 
 * @param array $taxonomy_data
 * @return bool
 * @since 3.1
 */
function artitechcore_validate_taxonomy_data($taxonomy_data) {
    if (!is_array($taxonomy_data)) return false;
    if (empty($taxonomy_data['name']) || empty($taxonomy_data['singular_label']) || empty($taxonomy_data['plural_label'])) return false;
    
    $slug = sanitize_key($taxonomy_data['name']);
    if ($slug !== $taxonomy_data['name'] || strlen($slug) > 32) return false;
    
    // Check for reserved taxonomy names
    $reserved = array('category', 'post_tag', 'nav_menu', 'link_category', 'post_format');
    if (in_array($slug, $reserved, true)) return false;
    
    return true;
}

/**
 * Render the Taxonomies Tab UI
 * 
 * @since 3.1
 */
function artitechcore_cpt_taxonomies_tab() {
    $dynamic_taxonomies = get_option('artitechcore_dynamic_taxonomies', array());
    $dynamic_cpts = get_option('artitechcore_dynamic_cpts', array());
    
    // Get all public post types for association
    $all_post_types = get_post_types(array('public' => true), 'objects');
    ?>
    <div class="artitechcore-taxonomies-manager">
        <div class="dg10-form-grid" style="grid-template-columns: 1fr 1fr; gap: 32px;">
            <!-- Create New Taxonomy Form -->
            <div class="dg10-card">
                <div class="dg10-card-header" style="padding: 20px 24px;">
                    <h3 style="margin: 0;">➕ <?php esc_html_e('Create New Taxonomy', 'artitechcore'); ?></h3>
                </div>
                <div class="dg10-card-body" style="padding: 24px;">
                    <form id="artitechcore-taxonomy-form" method="post">
                        <?php wp_nonce_field('artitechcore_create_taxonomy'); ?>
                        
                        <div class="dg10-form-group" style="margin-bottom: 20px;">
                            <label for="taxonomy_name" class="dg10-form-label"><?php esc_html_e('Taxonomy Slug', 'artitechcore'); ?></label>
                            <input type="text" name="taxonomy_name" id="taxonomy_name" class="dg10-form-input" required
                                   placeholder="<?php esc_attr_e('e.g., genre', 'artitechcore'); ?>"
                                   pattern="[a-z_][a-z0-9_]*" maxlength="32">
                            <p class="dg10-form-help"><?php esc_html_e('Lowercase, no spaces, use underscores. Max 32 chars.', 'artitechcore'); ?></p>
                        </div>
                        
                        <div class="dg10-form-group" style="margin-bottom: 20px;">
                            <label for="taxonomy_singular_label" class="dg10-form-label"><?php esc_html_e('Singular Label', 'artitechcore'); ?></label>
                            <input type="text" name="taxonomy_singular_label" id="taxonomy_singular_label" class="dg10-form-input" required
                                   placeholder="<?php esc_attr_e('e.g., Genre', 'artitechcore'); ?>">
                        </div>
                        
                        <div class="dg10-form-group" style="margin-bottom: 20px;">
                            <label for="taxonomy_plural_label" class="dg10-form-label"><?php esc_html_e('Plural Label', 'artitechcore'); ?></label>
                            <input type="text" name="taxonomy_plural_label" id="taxonomy_plural_label" class="dg10-form-input" required
                                   placeholder="<?php esc_attr_e('e.g., Genres', 'artitechcore'); ?>">
                        </div>
                        
                        <div class="dg10-form-group" style="margin-bottom: 20px;">
                            <label class="dg10-form-label"><?php esc_html_e('Assign to Post Types', 'artitechcore'); ?></label>
                            <div class="artitechcore-checkbox-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                                <?php foreach ($all_post_types as $pt_slug => $pt_obj): ?>
                                    <label class="dg10-checkbox-label" style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="taxonomy_post_types[]" value="<?php echo esc_attr($pt_slug); ?>">
                                        <span><?php echo esc_html($pt_obj->labels->singular_name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="dg10-form-group" style="margin-bottom: 20px;">
                            <label class="dg10-checkbox-label" style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="taxonomy_hierarchical" id="taxonomy_hierarchical" checked>
                                <span><?php esc_html_e('Hierarchical (like Categories)', 'artitechcore'); ?></span>
                            </label>
                            <p class="dg10-form-help"><?php esc_html_e('Unchecked = Flat (like Tags)', 'artitechcore'); ?></p>
                        </div>
                        
                        <div class="dg10-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Create Taxonomy', 'artitechcore'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Taxonomies List -->
            <div class="dg10-card">
                <div class="dg10-card-header" style="padding: 20px 24px;">
                    <h3 style="margin: 0;">📋 <?php esc_html_e('Existing Taxonomies', 'artitechcore'); ?></h3>
                </div>
                <div class="dg10-card-body" style="padding: 24px;">
                    <?php if (empty($dynamic_taxonomies)): ?>
                        <div class="artitechcore-empty-state" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-tag" style="font-size: 48px; color: var(--dg10-text-dim);"></span>
                            <p><?php esc_html_e('No custom taxonomies created yet.', 'artitechcore'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="artitechcore-taxonomy-list">
                            <?php foreach ($dynamic_taxonomies as $slug => $data): ?>
                                <div class="artitechcore-taxonomy-item dg10-glass-card" style="padding: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo esc_html($data['plural_label']); ?></strong>
                                        <code style="margin-left: 8px; font-size: 0.8em; background: var(--dg10-bg-muted); padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($slug); ?></code>
                                        <br>
                                        <small style="color: var(--dg10-text-dim);">
                                            <?php 
                                            $pts = isset($data['post_types']) ? implode(', ', $data['post_types']) : 'post';
                                            echo esc_html__('Attached to:', 'artitechcore') . ' ' . esc_html($pts);
                                            ?>
                                        </small>
                                    </div>
                                    <button type="button" class="button button-link-delete artitechcore-delete-taxonomy" data-taxonomy="<?php echo esc_attr($slug); ?>">
                                        <?php esc_html_e('Delete', 'artitechcore'); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Create Taxonomy Form Submission
        $('#artitechcore-taxonomy-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.html();
            
            $btn.prop('disabled', true).html('Creating...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'artitechcore_create_taxonomy_ajax',
                    nonce: $form.find('input[name="_wpnonce"]').val(),
                    taxonomy_name: $('#taxonomy_name').val(),
                    taxonomy_singular_label: $('#taxonomy_singular_label').val(),
                    taxonomy_plural_label: $('#taxonomy_plural_label').val(),
                    taxonomy_post_types: $('input[name="taxonomy_post_types[]"]:checked').map(function() { return this.value; }).get(),
                    taxonomy_hierarchical: $('#taxonomy_hierarchical').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error creating taxonomy.');
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert('Server error.');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        // Delete Taxonomy
        $('.artitechcore-delete-taxonomy').on('click', function() {
            var taxonomy = $(this).data('taxonomy');
            if (!confirm('Are you sure you want to delete the "' + taxonomy + '" taxonomy?')) return;
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'artitechcore_delete_taxonomy_ajax',
                    nonce: '<?php echo wp_create_nonce('artitechcore_delete_taxonomy'); ?>',
                    taxonomy: taxonomy
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Error deleting taxonomy.');
                    }
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX: Create a new Taxonomy
 * 
 * @since 3.1
 */
function artitechcore_handle_taxonomy_creation_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'artitechcore_create_taxonomy')) {
        wp_send_json_error(__('Security check failed.', 'artitechcore'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied.', 'artitechcore'));
    }
    
    $taxonomy_data = array(
        'name'            => sanitize_key($_POST['taxonomy_name'] ?? ''),
        'singular_label'  => sanitize_text_field($_POST['taxonomy_singular_label'] ?? ''),
        'plural_label'    => sanitize_text_field($_POST['taxonomy_plural_label'] ?? ''),
        'post_types'      => isset($_POST['taxonomy_post_types']) && is_array($_POST['taxonomy_post_types']) 
                             ? array_map('sanitize_key', $_POST['taxonomy_post_types']) 
                             : array('post'),
        'hierarchical'    => isset($_POST['taxonomy_hierarchical']) && $_POST['taxonomy_hierarchical'] == '1',
    );
    
    // Validate
    if (!artitechcore_validate_taxonomy_data($taxonomy_data)) {
        wp_send_json_error(__('Invalid taxonomy data. Check slug and labels.', 'artitechcore'));
    }
    
    // Check if already exists
    $existing = get_option('artitechcore_dynamic_taxonomies', array());
    if (isset($existing[$taxonomy_data['name']])) {
        wp_send_json_error(__('A taxonomy with this slug already exists.', 'artitechcore'));
    }
    
    // Check if WordPress already has this taxonomy registered
    if (taxonomy_exists($taxonomy_data['name'])) {
        wp_send_json_error(__('This taxonomy slug is already in use by WordPress or another plugin.', 'artitechcore'));
    }
    
    // Save
    $existing[$taxonomy_data['name']] = $taxonomy_data;
    update_option('artitechcore_dynamic_taxonomies', $existing);
    
    // Register immediately
    artitechcore_register_dynamic_taxonomy($taxonomy_data);
    
    // Flush rewrite rules on next load
    update_option('artitechcore_flush_rewrite_rules', true);
    
    wp_send_json_success(array('message' => __('Taxonomy created successfully!', 'artitechcore')));
}

/**
 * AJAX: Delete a Taxonomy
 * 
 * @since 3.1
 */
function artitechcore_handle_taxonomy_deletion_ajax() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'artitechcore_delete_taxonomy')) {
        wp_send_json_error(__('Security check failed.', 'artitechcore'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied.', 'artitechcore'));
    }
    
    $taxonomy_slug = sanitize_key($_POST['taxonomy'] ?? '');
    if (empty($taxonomy_slug)) {
        wp_send_json_error(__('No taxonomy specified.', 'artitechcore'));
    }
    
    $existing = get_option('artitechcore_dynamic_taxonomies', array());
    if (!isset($existing[$taxonomy_slug])) {
        wp_send_json_error(__('Taxonomy not found.', 'artitechcore'));
    }
    
    unset($existing[$taxonomy_slug]);
    update_option('artitechcore_dynamic_taxonomies', $existing);
    
    // Flush rewrite rules on next load
    update_option('artitechcore_flush_rewrite_rules', true);
    
    wp_send_json_success(array('message' => __('Taxonomy deleted.', 'artitechcore')));
}

/**
 * Flush rewrite rules if flagged
 * 
 * @since 3.1
 */
function artitechcore_maybe_flush_rewrite_rules() {
    if (get_option('artitechcore_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('artitechcore_flush_rewrite_rules');
    }
}
add_action('init', 'artitechcore_maybe_flush_rewrite_rules', 99);

