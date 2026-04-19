<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin menu
function artitechcore_add_admin_menu() {
    add_menu_page(
        __('ArtitechCore AI Architect', 'artitechcore'),
        __('ArtitechCore', 'artitechcore'),
        'manage_options',
        'artitechcore-main',
        'artitechcore_admin_page',
        ARTITECHCORE_PLUGIN_URL . 'assets/images/logo.svg',
        25
    );

    // Add submenus to restore menu item visibility
    add_submenu_page(
        'artitechcore-main',
        __('Manual Creation', 'artitechcore'),
        __('Manual Creation', 'artitechcore'),
        'manage_options',
        'artitechcore-main',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('CSV Upload', 'artitechcore'),
        __('CSV Upload', 'artitechcore'),
        'manage_options',
        'artitechcore-csv-upload',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('AI Generator', 'artitechcore'),
        __('AI Generator', 'artitechcore'),
        'manage_options',
        'artitechcore-ai-generator',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Website Builder', 'artitechcore'),
        __('Website Builder', 'artitechcore'),
        'manage_options',
        'artitechcore-website-builder',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Schema Generator', 'artitechcore'),
        __('Schema Generator', 'artitechcore'),
        'manage_options',
        'artitechcore-schema-generator',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Menu Generator', 'artitechcore'),
        __('Menu Generator', 'artitechcore'),
        'manage_options',
        'artitechcore-menu-generator',
        'artitechcore_admin_page'
    );
    
    add_submenu_page(
        'artitechcore-main',
        __('Page Hierarchy', 'artitechcore'),
        __('Page Hierarchy', 'artitechcore'),
        'manage_options',
        'artitechcore-hierarchy',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Keyword Analysis', 'artitechcore'),
        __('Keyword Analysis', 'artitechcore'),
        'manage_options',
        'artitechcore-keyword-analysis',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Custom Post Types', 'artitechcore'),
        __('Custom Post Types', 'artitechcore'),
        'manage_options',
        'artitechcore-cpt-management',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Post Templates', 'artitechcore'),
        __('Post Templates', 'artitechcore'),
        'manage_options',
        'artitechcore-main&tab=templates',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Content Enhancer', 'artitechcore'),
        __('Content Enhancer', 'artitechcore'),
        'manage_options',
        'artitechcore-content-enhancer',
        'artitechcore_admin_page'
    );

    add_submenu_page(
        'artitechcore-main',
        __('Settings', 'artitechcore'),
        __('Settings', 'artitechcore'),
        'manage_options',
        'artitechcore-settings',
        'artitechcore_admin_page'
    );
}
add_action('admin_menu', 'artitechcore_add_admin_menu');

// Admin page content
function artitechcore_admin_page() {
    // Determine active tab from URL parameter or page slug
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
    $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    if (empty($active_tab)) {
        switch ($current_page) {
            case 'artitechcore-csv-upload':
                $active_tab = 'csv';
                break;
            case 'artitechcore-ai-generator':
                $active_tab = 'ai';
                break;
            case 'artitechcore-website-builder':
                $active_tab = 'website-builder';
                break;
            case 'artitechcore-schema-generator':
                $active_tab = 'schema';
                break;
            case 'artitechcore-menu-generator':
                $active_tab = 'menu';
                break;
            case 'artitechcore-hierarchy':
                $active_tab = 'hierarchy';
                break;
            case 'artitechcore-keyword-analysis':
                $active_tab = 'keyword-analysis';
                break;
            case 'artitechcore-cpt-management':
                $active_tab = 'cpt';
                break;
            case 'artitechcore-content-enhancer':
                $active_tab = 'enhancer';
                break;
            case 'artitechcore-settings':
                $active_tab = 'settings';
                break;
            default:
                $active_tab = 'manual';
                break;
        }
    }
    
    // Define menu items with their details
    $menu_items = array(
        'manual' => array(
            'title' => __('Add Pages', 'artitechcore'),
            'icon' => '✍️',
            'description' => __('Create pages manually with custom hierarchy', 'artitechcore')
        ),
        'csv' => array(
            'title' => __('CSV Upload', 'artitechcore'),
            'icon' => '📂',
            'description' => __('Bulk import pages from CSV files', 'artitechcore')
        ),
        'ai' => array(
            'title' => __('AI Generator', 'artitechcore'),
            'icon' => '🤖',
            'description' => __('Generate page ecosystems and structures with AI', 'artitechcore')
        ),
        'website-builder' => array(
            'title' => __('Website Builder', 'artitechcore'),
            'icon' => '🏗️',
            'description' => __('Build complete websites from industry blueprints', 'artitechcore')
        ),
        'schema' => array(
            'title' => __('Schema Generator', 'artitechcore'),
            'icon' => '🧩',
            'description' => __('Create structured data markup', 'artitechcore')
        ),
        'menu' => array(
            'title' => __('Menu Generator', 'artitechcore'),
            'icon' => '🗺️',
            'description' => __('Automatically generate WordPress menus', 'artitechcore')
        ),
        'hierarchy' => array(
            'title' => __('Page Hierarchy', 'artitechcore'),
            'icon' => '🪜',
            'description' => __('Visualize and manage page structure', 'artitechcore')
        ),
        'keyword-analysis' => array(
            'title' => __('Keyword Analysis', 'artitechcore'),
            'icon' => '📈',
            'description' => __('Analyze keyword density and SEO', 'artitechcore')
        ),
        'cpt' => array(
            'title' => __('Custom Post Types', 'artitechcore'),
            'icon' => '📦',
            'description' => __('Create and manage custom post types', 'artitechcore')
        ),
        'templates' => array(
            'title' => __('Post Templates', 'artitechcore'),
            'icon' => '📝',
            'description' => __('Manage dynamic templates for post types', 'artitechcore')
        ),
        'enhancer' => array(
            'title' => __('Content Enhancer', 'artitechcore'),
            'icon' => '🚀',
            'description' => __('Generate AI Key Takeaways and CTAs', 'artitechcore')
        ),
        'settings' => array(
            'title' => __('Settings', 'artitechcore'),
            'icon' => '⚙️',
            'description' => __('Configure plugin options', 'artitechcore')
        )
    );
    ?>
    <div class="wrap dg10-brand">
        <!-- Skip Link for Accessibility - Positioned at page level -->
        <a href="#page-title" class="skip-link"><?php esc_html_e('Skip to main content', 'artitechcore'); ?></a>
        
        <div class="dg10-main-layout">
            <!-- Admin Sidebar -->
            <aside class="dg10-admin-sidebar" role="complementary" aria-label="<?php esc_attr_e('ArtitechCore Navigation Menu', 'artitechcore'); ?>">
                <div class="dg10-sidebar-header">
                    <div class="dg10-sidebar-title">
                        <img src="<?php echo esc_url(ARTITECHCORE_PLUGIN_URL); ?>assets/images/logo.svg" alt="<?php esc_attr_e('ArtitechCore Plugin Logo', 'artitechcore'); ?>" class="dg10-logo-img" role="img">
                        <span class="dg10-plugin-name"><?php esc_html_e('ArtitechCore', 'artitechcore'); ?></span>
                    </div>
                    <p class="dg10-sidebar-subtitle" role="text"><?php esc_html_e('Advanced SEO Infrastructure', 'artitechcore'); ?></p>
                </div>
                
                <nav class="dg10-sidebar-nav" role="navigation" aria-label="<?php esc_attr_e('Main Navigation', 'artitechcore'); ?>">
                    <ul role="list">
                        <?php foreach ($menu_items as $tab_key => $item): ?>
                            <li role="listitem">
                                <a href="?page=artitechcore-main&tab=<?php echo esc_attr($tab_key); ?>" 
                                   class="dg10-sidebar-nav-item <?php echo $active_tab == $tab_key ? 'active' : ''; ?>"
                                   role="menuitem"
                                   aria-label="<?php echo esc_attr($item['title'] . ' - ' . $item['description']); ?>"
                                   aria-current="<?php echo $active_tab == $tab_key ? 'page' : 'false'; ?>"
                                   title="<?php echo esc_attr($item['description']); ?>">
                                    <span class="nav-icon" aria-hidden="true" role="img" aria-label="<?php echo esc_attr($item['title'] . ' icon'); ?>"><?php echo esc_html($item['icon']); ?></span>
                                    <span class="nav-text"><?php echo esc_html($item['title']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </aside>
            
            <!-- Main Content Area -->
            <main class="dg10-main-content" role="main" aria-label="<?php esc_attr_e('Main Content Area', 'artitechcore'); ?>">
                <!-- Notification Container for JS Relocation -->
                <div id="artitechcore-notices-container" class="dg10-notices-area"></div>

                <article class="dg10-card" role="article" aria-labelledby="page-title">
                    <header class="dg10-card-header" role="banner">
                        <div class="dg10-hero-content">
                            <div class="dg10-hero-text">
                                <?php 
                                // Safe access with fallback for undefined tabs
                                $tab_title = isset($menu_items[$active_tab]['title']) 
                                    ? $menu_items[$active_tab]['title'] 
                                    : __('ArtitechCore', 'artitechcore');
                                $tab_description = isset($menu_items[$active_tab]['description']) 
                                    ? $menu_items[$active_tab]['description'] 
                                    : '';
                                ?>
                                <h1 id="page-title" role="heading" aria-level="1"><?php echo esc_html($tab_title); ?></h1>
                                <p class="dg10-hero-description" role="text" aria-describedby="page-title">
                                    <?php echo esc_html($tab_description); ?>
                                </p>
                            </div>
                        </div>
                    </header>
                    <div class="dg10-card-body" role="main" aria-label="<?php esc_attr_e('Content Section', 'artitechcore'); ?>">
        <?php
        if ($active_tab == 'manual') {
            artitechcore_render_manual_tab_content();
        } elseif ($active_tab == 'csv') {
            artitechcore_render_csv_tab_content();
        } elseif ($active_tab == 'ai') {
            artitechcore_ai_generation_tab();
        } elseif ($active_tab == 'website-builder') {
            if (function_exists('artitechcore_website_builder_tab')) {
                artitechcore_website_builder_tab();
            }
        } elseif ($active_tab == 'schema') {
            artitechcore_schema_generator_tab();
        } elseif ($active_tab == 'menu') {
            artitechcore_menu_generator_tab();
        } elseif ($active_tab == 'hierarchy') {
            artitechcore_hierarchy_tab();
        } elseif ($active_tab == 'keyword-analysis') {
            artitechcore_keyword_analysis_tab();
        } elseif ($active_tab == 'cpt') {
            // Check if function exists (it should as it's included in main plugin file)
            if (function_exists('artitechcore_render_cpt_management_content')) {
                artitechcore_render_cpt_management_content(true);
            }
        } elseif ($active_tab == 'templates') {
            // Templates tab - render CPT templates directly
            if (function_exists('artitechcore_cpt_templates_tab')) {
                artitechcore_cpt_templates_tab();
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__('Templates functionality is part of the CPT Manager. Please access it via the Custom Post Types tab.', 'artitechcore') . '</p></div>';
            }
        } elseif ($active_tab == 'enhancer') {
            if (function_exists('artitechcore_content_enhancer_tab')) {
                artitechcore_content_enhancer_tab();
            }
        } elseif ($active_tab == 'settings') {
            artitechcore_settings_tab();
        }
        ?>
                    </div>
                    <footer class="dg10-card-footer" role="contentinfo" aria-label="<?php esc_attr_e('About DG10 Agency', 'artitechcore'); ?>">
                        <section class="dg10-promotion-section" role="region" aria-labelledby="about-us-heading">
                            <header class="dg10-promotion-header">
                                <img src="<?php echo esc_url(ARTITECHCORE_PLUGIN_URL); ?>assets/images/dg10-brand-logo.svg" alt="<?php esc_attr_e('DG10 Agency Logo', 'artitechcore'); ?>" class="dg10-promotion-logo" role="img">
                                <h3 id="about-us-heading" role="heading" aria-level="3"><?php esc_html_e('About us', 'artitechcore'); ?></h3>
                            </header>
                            <div class="dg10-promotion-content">
                                <p role="text"><?php esc_html_e('We are the ultimate growth ecosystem for visionary entrepreneurs. Leveraging over a decade of expertise, we empower you to Build | Market | Analyze | Automate | Scale with our services, elite software products, and high-performance AI tools. We aren’t just an agency; we are your digital infrastructure powerhouse, providing the advanced technology and strategic edge you need to dominate your market.', 'artitechcore'); ?></p>
                                <div class="dg10-promotion-buttons" role="group" aria-label="<?php esc_attr_e('Action Buttons', 'artitechcore'); ?>">
                                    <a href="https://www.dg10.agency" target="_blank" class="dg10-btn dg10-btn-primary" role="button" aria-label="<?php esc_attr_e('Visit DG10 Agency Website - Opens in new tab', 'artitechcore'); ?>">
                                        <span class="btn-text"><?php esc_html_e('Visit Website', 'artitechcore'); ?></span>
                                        <span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('External link icon'); ?>">→</span>
                                    </a>
                                    <a href="https://calendly.com/dg10-agency/30min" target="_blank" class="dg10-btn dg10-btn-outline" role="button" aria-label="<?php esc_attr_e('Book a Free Consultation - Opens in new tab', 'artitechcore'); ?>">
                                        <span class="dg10-btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Calendar icon'); ?>">📅</span>
                                        <span class="btn-text"><?php esc_html_e('Book a Free Consultation', 'artitechcore'); ?></span>
                                    </a>
                                </div>
                                <p class="dg10-promotion-footer" role="text">
                                    <?php printf(__('This is an open-source project - please %s.', 'artitechcore'), '<a href="' . esc_url(ARTITECHCORE_GITHUB_URL) . '" target="_blank" role="link" aria-label="' . esc_attr__('Star the repository on GitHub - Opens in new tab', 'artitechcore') . '">' . __('star the repo on GitHub', 'artitechcore') . '</a>'); ?>
                                </p>
                            </div>
                        </section>
                    </footer>
                </article>
            </main>
        </div>
    </div>
    
    <!-- Sidebar JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Handle sidebar navigation
        $('.dg10-sidebar-nav-item').on('click', function(e) {
            // Remove active class and aria-current from all items
            $('.dg10-sidebar-nav-item').removeClass('active').attr('aria-current', 'false');
            // Add active class and aria-current to clicked item
            $(this).addClass('active').attr('aria-current', 'page');
        });
        
        // Handle responsive sidebar behavior
        function handleSidebarResponsive() {
            if ($(window).width() <= 960) {
                // Mobile/tablet view - horizontal scroll
                $('.dg10-sidebar-nav').addClass('mobile-nav').attr('aria-orientation', 'horizontal');
            } else {
                // Desktop view - vertical sidebar
                $('.dg10-sidebar-nav').removeClass('mobile-nav').attr('aria-orientation', 'vertical');
            }
        }
        
        // Run on load and resize
        handleSidebarResponsive();
        $(window).on('resize', handleSidebarResponsive);
        
        // Smooth scroll for mobile navigation
        $('.dg10-sidebar-nav').on('scroll', function() {
            // Optional: Add scroll indicators or other mobile nav enhancements
        });
        
        // Enhanced keyboard navigation support
        $('.dg10-sidebar-nav-item').on('keydown', function(e) {
            var $items = $('.dg10-sidebar-nav-item');
            var currentIndex = $items.index(this);
            var $currentItem = $(this);
            
            switch(e.key) {
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    $currentItem.click();
                    break;
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    var nextIndex = (currentIndex + 1) % $items.length;
                    $items.eq(nextIndex).focus();
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    var prevIndex = currentIndex === 0 ? $items.length - 1 : currentIndex - 1;
                    $items.eq(prevIndex).focus();
                    break;
                case 'Home':
                    e.preventDefault();
                    $items.first().focus();
                    break;
                case 'End':
                    e.preventDefault();
                    $items.last().focus();
                    break;
            }
        });
        
        // Add focus management for better accessibility
        $('.dg10-sidebar-nav-item').on('focus', function() {
            $(this).attr('aria-selected', 'true');
        }).on('blur', function() {
            $(this).attr('aria-selected', 'false');
        });
        
        // Focus management for accessibility
        $('.dg10-sidebar-nav-item').on('focus', function() {
            $(this).addClass('focused');
        }).on('blur', function() {
            $(this).removeClass('focused');
        });
    });
    </script>
    
    <!-- Sidebar Styles moved to admin-ui.css -->
    <?php
}

// Manual creation tab content
// Manual creation tab content calling wrapper (renamed for cache bust)
function artitechcore_render_manual_tab_content() {
    ?>
    <div id="artitechcore-manual-tab-debug" style="margin-bottom: 20px;">
        <!-- Debug Marker -->
    </div>
    <div class="artitechcore-manual-container">
        <form method="post" action="" role="form" aria-label="<?php esc_attr_e('Manual Page Creation Form', 'artitechcore'); ?>">
                <?php wp_nonce_field('artitechcore_manual_create_pages'); ?>
                <fieldset class="dg10-form-group">
                    <legend class="sr-only"><?php esc_html_e('Page Creation Settings', 'artitechcore'); ?></legend>
                    <div class="dg10-form-field">
                        <label for="artitechcore_titles" class="dg10-form-label" id="titles-label"><?php esc_html_e('Page Titles', 'artitechcore'); ?></label>
                        <textarea name="artitechcore_titles" 
                                  id="artitechcore_titles" 
                                  rows="10" 
                                  class="dg10-form-textarea" 
                                  placeholder="<?php esc_attr_e('Enter one page title per line. Use hyphens for nesting...', 'artitechcore'); ?>"
                                  aria-labelledby="titles-label"
                                  aria-describedby="syntax-guide"
                                  aria-required="true"
                                  role="textbox"></textarea>
                        <div id="syntax-guide" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e('Syntax Guide', 'artitechcore'); ?>">
                            <strong><?php esc_html_e('Syntax Guide:', 'artitechcore'); ?></strong><br>
                            • <?php esc_html_e('Use', 'artitechcore'); ?> <code>-</code> <?php esc_html_e('for child pages (one hyphen per level)', 'artitechcore'); ?><br>
                            • <?php esc_html_e('Use', 'artitechcore'); ?> <code>:+</code> <?php esc_html_e('for meta description', 'artitechcore'); ?><br>
                            • <?php esc_html_e('Use', 'artitechcore'); ?> <code>:*</code> <?php esc_html_e('for featured image URL', 'artitechcore'); ?><br>
                            • <?php esc_html_e('Use', 'artitechcore'); ?> <code>::template=template-name.php</code> <?php esc_html_e('for page template', 'artitechcore'); ?><br>
                            • <?php esc_html_e('Use', 'artitechcore'); ?> <code>::status=draft</code> <?php esc_html_e('for post status', 'artitechcore'); ?><br>
                            • <strong><?php esc_html_e('SEO slugs are automatically generated', 'artitechcore'); ?></strong> (<?php esc_html_e('max 72 chars', 'artitechcore'); ?>)
                        </div>
                    </div>
                </fieldset>
                <div class="dg10-form-actions">
                    <button type="submit" 
                            name="submit" 
                            class="dg10-btn dg10-btn-primary"
                            role="button"
                            aria-label="<?php esc_attr_e('Create Pages from Titles', 'artitechcore'); ?>">
                        <span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Rocket icon'); ?>">🚀</span>
                        <span class="btn-text"><?php esc_html_e('Create Pages', 'artitechcore'); ?></span>
                </button>
            </form>
    </div>
    <?php
    if (isset($_POST['submit']) && check_admin_referer('artitechcore_manual_create_pages')) {
        artitechcore_create_pages_manually($_POST['artitechcore_titles']);
    }
}

// CSV upload tab content
// CSV upload tab content calling wrapper (renamed for cache bust)
function artitechcore_render_csv_tab_content() {
    ?>
    <div class="artitechcore-csv-container">
            <form method="post" action="" enctype="multipart/form-data" role="form" aria-label="<?php esc_attr_e('CSV File Upload Form', 'artitechcore'); ?>">
                <?php wp_nonce_field('artitechcore_csv_upload'); ?>
                <fieldset class="dg10-form-group">
                    <legend class="sr-only"><?php esc_html_e('CSV Upload Settings', 'artitechcore'); ?></legend>
                    <div class="dg10-form-field">
                        <label for="artitechcore_csv_file" class="dg10-form-label" id="csv-file-label"><?php esc_html_e('CSV File', 'artitechcore'); ?></label>
                        <input type="file" 
                               name="artitechcore_csv_file" 
                               id="artitechcore_csv_file" 
                               accept=".csv"
                               aria-labelledby="csv-file-label"
                               aria-describedby="csv-instructions"
                               aria-required="true"
                               role="button">
                        <div id="csv-instructions" class="dg10-form-help" role="region" aria-label="<?php esc_attr_e('CSV File Instructions', 'artitechcore'); ?>">
                            <p><?php printf(__('Upload a CSV file with the following columns: %s, %s (optional), %s, %s, %s, %s, %s.', 'artitechcore'), 
                                '<code>post_title</code>', 
                                '<code>slug</code>', 
                                '<code>post_parent</code>', 
                                '<code>meta_description</code>', 
                                '<code>featured_image</code>', 
                                '<code>page_template</code>', 
                                '<code>post_status</code>'); ?></p>
                            <ul>
                                <li><?php printf(__('The %s column should contain the title of the parent page.', 'artitechcore'), '<code>post_parent</code>'); ?></li>
                                <li><?php printf(__('%s is optional - if empty, SEO-optimized slugs are automatically generated.', 'artitechcore'), '<code>slug</code>'); ?></li>
                                <li><strong><?php echo esc_html(artitechcore_get_max_file_size_display()); ?></strong></li>
                                <li><?php _e('Maximum rows: 10,000', 'artitechcore'); ?></li>
                            </ul>
                        </div>
                    </div>
                </fieldset>
                <div class="dg10-form-actions">
                    <button type="submit" 
                            name="submit" 
                            class="dg10-btn dg10-btn-primary"
                            role="button"
                            aria-label="<?php esc_attr_e('Upload CSV File and Create Pages', 'artitechcore'); ?>">
                        <span class="btn-icon" aria-hidden="true" role="img" aria-label="<?php esc_attr_e('Upload icon'); ?>">📤</span>
                        <span class="btn-text"><?php esc_html_e('Upload and Create Pages', 'artitechcore'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    <?php
    if (isset($_POST['submit']) && check_admin_referer('artitechcore_csv_upload')) {
        if (isset($_FILES['artitechcore_csv_file']) && !empty($_FILES['artitechcore_csv_file']['tmp_name'])) {
            artitechcore_create_pages_from_csv($_FILES['artitechcore_csv_file']);
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Please select a CSV file to upload.', 'artitechcore') . '</p></div>';
        }
    }
}

// Menu generator tab content
function artitechcore_menu_generator_tab() {
    // Get registered nav menus for the dropdown
    $locations = get_registered_nav_menus();
    $location_options = '<option value="">' . esc_html__('Auto-detect / None', 'artitechcore') . '</option>';
    if (!empty($locations)) {
        foreach ($locations as $slug => $name) {
            $location_options .= '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
        }
    }

    // Shared controls HTML to avoid repetition
    $controls_html = '
    <div class="menu-generator-controls" style="margin: 15px 0; text-align: left; padding: 10px; background: #f0f0f1; border-radius: 4px;">
        <p style="margin-bottom: 10px;">
            <label style="font-weight: 500;">
                <input type="checkbox" name="force_overwrite" value="1"> 
                ' . esc_html__('Force Overwrite Existing Menu', 'artitechcore') . '
            </label>
        </p>
        <p style="margin: 0;">
            <label for="menu_loc" style="display: block; margin-bottom: 4px;">' . esc_html__('Assign to Location (Optional):', 'artitechcore') . '</label>
            <select name="menu_location" style="width: 100%;">
                ' . $location_options . '
            </select>
        </p>
    </div>';
    
    // Inline Notification Logic (Simplified for reliability)
    if (isset($_POST['generate_menu']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'artitechcore_generate_menu')) {
        $menu_type = isset($_POST['menu_type']) ? sanitize_key($_POST['menu_type']) : '';
        $overwrite = isset($_POST['force_overwrite']) ? true : false;
        $location  = isset($_POST['menu_location']) ? sanitize_key($_POST['menu_location']) : '';

        $result = false;
        
        switch ($menu_type) {
            case 'universal_bottom':
                $result = artitechcore_generate_universal_bottom_menu($overwrite, $location);
                break;
            case 'services':
                $result = artitechcore_generate_services_menu($overwrite, $location);
                break;
            case 'company':
                $result = artitechcore_generate_company_menu($overwrite, $location);
                break;
            case 'main_navigation':
                $result = artitechcore_generate_main_navigation_menu($overwrite, $location);
                break;
            case 'resources':
                $result = artitechcore_generate_resources_menu($overwrite, $location);
                break;
            case 'footer_quick_links':
                $result = artitechcore_generate_footer_quick_links_menu($overwrite, $location);
                break;
            case 'social_media':
                $result = artitechcore_generate_social_media_menu($overwrite, $location);
                break;
            case 'support':
                $result = artitechcore_generate_support_menu($overwrite, $location);
                break;
            case 'products':
                $result = artitechcore_generate_products_menu($overwrite, $location);
                break;
        }

        echo '<div id="artitechcore-result-notice" class="artitechcore-notice" style="background: #fff; border-left: 4px solid #46b450; padding: 20px; margin: 20px 0; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">';
        
        if (is_wp_error($result)) {
             echo '<h3 style="margin: 0 0 10px; color: #d63638;">' . __('Error', 'artitechcore') . '</h3>';
             echo '<p style="font-size: 14px; margin: 0;">' . esc_html($result->get_error_message()) . '</p>';
             echo '<script>document.getElementById("artitechcore-result-notice").style.borderLeftColor = "#d63638";</script>';
        } elseif ($result) {
             $edit_link = admin_url('nav-menus.php?action=edit&menu=' . $result);
             $locations_link = admin_url('nav-menus.php?action=locations');
             
             echo '<h3 style="margin: 0 0 10px; color: #46b450;">' . sprintf(__('%s Created Successfully!', 'artitechcore'), ucwords(str_replace('_', ' ', $menu_type))) . '</h3>';
             echo '<div style="display: flex; gap: 15px; align-items: center; margin-top: 15px;">';
             echo '<a href="' . esc_url($edit_link) . '" class="button button-primary button-large">' . __('Edit Menu Items', 'artitechcore') . '</a>';
             if (!empty($location)) {
                echo '<span style="color: #46b450; display: flex; align-items: center;"><span class="dashicons dashicons-yes" style="margin-right: 5px;"></span> ' . __('Assigned to location', 'artitechcore') . '</span>';
             } else {
                echo '<a href="' . esc_url($locations_link) . '" class="button button-secondary">' . __('Manage Locations', 'artitechcore') . '</a>';
             }
             echo '</div>';
        } else {
             echo '<h3 style="margin: 0 0 10px; color: #dba617;">' . __('Warning', 'artitechcore') . '</h3>';
             echo '<p>' . __('Failed to create menu. No pages found or empty selection.', 'artitechcore') . '</p>';
             echo '<script>document.getElementById("artitechcore-result-notice").style.borderLeftColor = "#dba617";</script>';
        }
        echo '</div>';

        // Auto-scroll to result
        echo '<script>
        jQuery(document).ready(function($) {
            $("html, body").animate({
                scrollTop: $("#artitechcore-result-notice").offset().top - 100
            }, 500);
        });
        </script>';
    }
    ?>
    <section class="dg10-card" role="region" aria-labelledby="menu-generator-heading">
        <div class="dg10-card-body">
            <h2 id="menu-generator-heading"><?php _e('Menu Generator', 'artitechcore'); ?></h2>
            <p><?php _e('Automatically generate WordPress menus based on your created pages.', 'artitechcore'); ?></p>
        
        <div class="menu-generator-options">
            <!-- Primary Navigation Menus -->
            <div class="menu-category">
                <h3>🧭 Primary Navigation</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Main Navigation Menu</h3>
                            <p>Complete primary header navigation with:</p>
                            <ul>
                                <li>Home + About/Company dropdowns</li>
                                <li>Services/Solutions with sub-items</li>
                                <li>Industry/Solution categories</li>
                                <li>Resources/Blog with categories</li>
                                <li>Contact page integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="main_navigation">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Main Navigation', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Services Menu</h3>
                            <p>Creates a menu with all service-related pages:</p>
                            <ul>
                                <li>Detects pages with "service", "solution", "offer", or "package" in title</li>
                                <li>Includes a main "Services" link</li>
                                <li>Perfect for header navigation</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="services">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Services Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Content & Product Menus -->
            <div class="menu-category">
                <h3>📄 Content & Products</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Products Menu</h3>
                            <p>Catalog menu for product-based businesses:</p>
                            <ul>
                                <li>Detects product, catalog, shop pages</li>
                                <li>Links to pricing and packages</li>
                                <li>Perfect for e-commerce sites</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="products">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Products Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Resources Menu</h3>
                            <p>Knowledge base and content navigation:</p>
                            <ul>
                                <li>Blog posts and categories</li>
                                <li>Guides, tutorials, documentation</li>
                                <li>FAQ and help resources</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="resources">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Resources Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Support Menu</h3>
                            <p>Customer support and help navigation:</p>
                            <ul>
                                <li>Help, FAQ, troubleshooting pages</li>
                                <li>Contact support integration</li>
                                <li>Documentation and guides</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="support">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Support Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer & Social Menus -->
            <div class="menu-category">
                <h3>🔗 Footer & Links</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Universal Bottom Menu</h3>
                            <p>Comprehensive footer menu with:</p>
                            <ul>
                                <li>Home link</li>
                                <li>All legal pages (Privacy, Terms, etc.)</li>
                                <li>Sitemap link (from settings)</li>
                                <li>Contact page integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="universal_bottom">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Footer Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Footer Quick Links</h3>
                            <p>Minimal footer navigation:</p>
                            <ul>
                                <li>Home and essential pages</li>
                                <li>Popular content links</li>
                                <li>Sitemap integration</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="footer_quick_links">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Quick Links', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Company & Social -->
            <div class="menu-category">
                <h3>🏢 Company & Social</h3>
                <div class="menu-cards-grid">
                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Company Menu</h3>
                            <p>Creates a menu with company information pages:</p>
                            <ul>
                                <li>Detects pages like About, Team, Mission, Contact</li>
                                <li>Ideal for footer or secondary navigation</li>
                                <li>Includes all company-related content</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="company">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Company Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>

                    <form method="post" action="">
                        <?php wp_nonce_field('artitechcore_generate_menu'); ?>

                        <div class="menu-option-card">
                            <h3>Social Media Menu</h3>
                            <p>Social media links menu:</p>
                            <ul>
                                <li>Facebook, Twitter, LinkedIn</li>
                                <li>Instagram, YouTube, Pinterest</li>
                                <li>Ready for customization</li>
                            </ul>
                            <input type="hidden" name="menu_type" value="social_media">
                            <?php echo $controls_html; ?>
                            <?php submit_button('Generate Social Menu', 'primary', 'generate_menu'); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="menu-generator-info">
            <h3>How it works:</h3>
            <ul>
                <li>Menus are created in WordPress Appearance → Menus</li>
                <li>Universal Bottom Menu tries to auto-assign to footer location</li>
                <li>You can manually assign menus to locations if needed</li>
                <li>Existing menus with the same name will be replaced</li>
            </ul>
            
            <h3>Note:</h3>
            <p>Make sure you have created pages before generating menus. The generator detects pages based on their titles and content.</p>
        </div>
        </div>
    </section>
    
    <!-- Menu Generator Styles moved to admin-ui.css -->
    <?php
}


// Keyword Analysis tab content
function artitechcore_keyword_analysis_tab() {
    ?>
    <section class="dg10-card" role="region" aria-labelledby="keyword-analysis-heading">
        <div class="dg10-card-body">
            <form id="artitechcore-keyword-analysis-form">
                <?php wp_nonce_field('artitechcore_ajax_nonce', 'artitechcore_keyword_nonce'); ?>
                
                <div class="dg10-form-group">
                    <label for="artitechcore_page_select" class="dg10-form-label">Select Page to Analyze</label>
                    <select name="page_id" id="artitechcore_page_select" class="dg10-form-select" required>
                        <option value="">Loading pages...</option>
                    </select>
                    <div class="dg10-form-help">
                        Choose a published page or post to analyze for keyword density
                    </div>
                </div>
                
                <div class="dg10-form-group">
                    <label for="artitechcore_keywords_input" class="dg10-form-label">Keywords to Analyze</label>
                    <textarea name="keywords" id="artitechcore_keywords_input" rows="8" class="dg10-form-textarea" 
                              placeholder="Enter keywords to analyze, one per line or separated by commas:&#10;&#10;web design&#10;SEO services&#10;digital marketing&#10;responsive design, mobile optimization" required></textarea>
                    <div class="dg10-form-help">
                        <strong>Format:</strong> One keyword per line, or comma-separated keywords. The analyzer will count exact matches (case-insensitive) and calculate density percentages.
                    </div>
                    <div id="keyword-count" class="dg10-form-help" style="margin-top: 8px;"></div>
                </div>
                
                <div class="dg10-form-group">
                    <div class="dg10-toggle-wrapper">
                        <label class="dg10-toggle">
                            <input type="checkbox" id="artitechcore_ai_superpowers" name="ai_superpowers" checked>
                            <span class="dg10-toggle-slider"></span>
                        </label>
                        <span class="dg10-toggle-label">
                            <strong>✨ AI Superpowers</strong> (Default)
                            <span class="dg10-badge dg10-badge-success">Premium</span>
                        </span>
                    </div>
                    <div class="dg10-form-help">
                        Uses semantic intelligence to expand your seeds (1:4 ratio), map search intent, and analyze topical coverage instead of just counting words.
                    </div>
                </div>
                
                <div id="ai-expansion-preview" class="dg10-hidden">
                    <div class="dg10-alert dg10-alert-info">
                        <div class="alert-content">
                            <strong>✨ Expansion Ready:</strong> Review and refine the AI-generated semantic variations below before starting the analysis.
                        </div>
                    </div>
                    <div id="editable-clusters-container" class="dg10-clusters-grid"></div>
                </div>
                
                <div class="dg10-form-group dg10-keyword-actions">
                    <button type="button" id="artitechcore-expand-btn" class="dg10-btn dg10-btn-secondary">
                        <span class="btn-text">✨ Expand with AI</span>
                        <span class="dg10-spinner dg10-hidden"></span>
                    </button>
                    <button type="submit" id="artitechcore-analyze-btn" class="dg10-btn dg10-btn-primary btn-disabled" disabled>
                        <span class="btn-text">📊 Analyze Content</span>
                        <span class="dg10-spinner dg10-hidden"></span>
                    </button>
                </div>
            </form>
            
            <!-- Results Section -->
            <div id="artitechcore-analysis-results" class="dg10-hidden">
                <div class="dg10-card">
                    <div class="dg10-card-header">
                        <h3>📊 Analysis Results</h3>
                        <div class="analysis-actions">
                            <button id="export-csv-btn" class="dg10-btn dg10-btn-outline dg10-btn-sm">
                                📊 Export CSV
                            </button>
                            <button id="export-json-btn" class="dg10-btn dg10-btn-outline dg10-btn-sm">
                                📋 Export JSON
                            </button>
                        </div>
                    </div>
                    <div class="dg10-card-body">
                        <!-- Page Info -->
                        <div id="page-info-section" class="analysis-section">
                            <h4>📄 Page Information</h4>
                            <div id="page-info-content"></div>
                        </div>
                        
                        <!-- Summary -->
                        <div id="summary-section" class="analysis-section">
                            <h4>📈 Analysis Summary</h4>
                            <div id="ai-intent-badge-container" class="dg10-hidden" style="margin-bottom: 15px;"></div>
                            <div id="summary-content"></div>
                        </div>
                        
                        <!-- Topic Cards (AI Mode Only) -->
                        <div id="topic-cards-section" class="analysis-section dg10-hidden">
                            <h4>🧩 Topical Coverage (Semantic Groups)</h4>
                            <div class="dg10-form-help" style="margin-bottom: 20px;">
                                We've grouped your seed keywords with their AI-suggested variations. "Healthy" groups have 3+ variations present in your content.
                            </div>
                            <div id="topic-cards-container" class="topic-cards-grid"></div>
                        </div>
                        
                        <!-- Keywords Table -->
                        <div id="keywords-section" class="analysis-section">
                            <h4>🔍 Keyword Analysis</h4>
                            <div class="table-responsive">
                                <table id="keywords-table" class="dg10-table">
                                    <thead>
                                        <tr>
                                            <th>Keyword</th>
                                            <th>Count</th>
                                            <th>Density</th>
                                            <th>Status</th>
                                            <th>Relevance</th>
                                            <th>Areas Found</th>
                                            <th>Context</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Recommendations -->
                        <div id="recommendations-section" class="analysis-section">
                            <h4>💡 SEO Recommendations</h4>
                            <div id="recommendations-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Keyword Analysis Styles moved to admin-ui.css -->
    
    <!-- Keyword Analysis JS moved to keyword-analyzer.js -->    
    <!-- Accessibility CSS -->
    <!-- Accessibility styles moved to admin-ui.css -->
    <?php
}

/**
 * End of admin-menu.php
 */
