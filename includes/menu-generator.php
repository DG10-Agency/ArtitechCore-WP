<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Menu generator functions
class ArtitechCore_Menu_Generator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get custom post type archives for menu generation
     */
    public function get_custom_post_type_archives() {
        $cpt_archives = array();
        $dynamic_cpts = get_option('artitechcore_dynamic_cpts', []);
        
        foreach ($dynamic_cpts as $post_type => $cpt_info) {
            $archive_url = get_post_type_archive_link($post_type);
            if ($archive_url) {
                $cpt_archives[] = array(
                    'ID' => 'cpt_archive_' . $post_type,
                    'title' => $cpt_info['label'],
                    'url' => $archive_url,
                    'type' => 'cpt_archive',
                    'post_type' => $post_type
                );
            }
        }
        
        return $cpt_archives;
    }

    /**
     * Detect legal pages by title patterns
     */
    public function detect_legal_pages() {
        $legal_patterns = array(
            '/privacy|policy|gdpr/i',
            '/terms|conditions|service/i', 
            '/disclaimer/i',
            '/cookie|policy/i',
            '/refund|policy/i',
            '/shipping|policy/i',
            '/return|policy/i',
            '/legal/i'
        );
        
        $legal_pages = array();
        $all_pages = get_pages(array('post_status' => 'publish'));
        
        foreach ($all_pages as $page) {
            foreach ($legal_patterns as $pattern) {
                if (preg_match($pattern, $page->post_title)) {
                    $legal_pages[] = array(
                        'ID' => $page->ID,
                        'title' => $page->post_title,
                        'url' => esc_url(get_permalink($page->ID))
                    );
                    break; // Stop checking other patterns for this page
                }
            }
        }
        
        return $legal_pages;
    }
    
    /**
     * Create universal bottom menu
     */
    public function create_universal_bottom_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }
        $menu_name = 'Universal Bottom Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);
        
        // Safety check: Prevent accidental deletion
        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        // Delete existing menu if it exists and overwrite is true
        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }
        
        // Create new menu
        $menu_id = wp_create_nav_menu($menu_name);
        
        if (is_wp_error($menu_id)) {
            return false;
        }
        
        // Add standard items
        $this->add_menu_item($menu_id, 'Home', home_url());
        
        // Add legal pages
        $legal_pages = $this->detect_legal_pages();
        foreach ($legal_pages as $page) {
            $this->add_menu_item($menu_id, esc_html($page['title']), $page['url']);
        }
        
        // Add sitemap if configured
        $sitemap_url = get_option('artitechcore_sitemap_url', '');
        if (!empty($sitemap_url)) {
            $this->add_menu_item($menu_id, 'Sitemap', esc_url($sitemap_url));
        }
        
        // Add contact page if exists
        $contact_pages = get_posts(array(
            'post_type' => 'page',
            'title' => 'Contact',
            'post_status' => 'publish',
            'numberposts' => 1
        ));

        if (!empty($contact_pages)) {
            $contact_page = $contact_pages[0];
            $this->add_menu_item($menu_id, 'Contact', esc_url(get_permalink($contact_page->ID)));
        }
        
        // Assign to menu location if specified
        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        } else {
             // Fallback to auto-detection only if no location specified
            $locations = get_theme_mod('nav_menu_locations');
            $footer_location = '';
            
            // Check for common footer menu locations
            $possible_locations = array('footer', 'footer-menu', 'footer_navigation', 'menu-footer');
            foreach ($possible_locations as $loc) {
                if (isset($locations[$loc]) || array_key_exists($loc, get_registered_nav_menus())) {
                    $footer_location = $loc;
                    break;
                }
            }
            
            if (!empty($footer_location)) {
               $locations[$footer_location] = $menu_id;
               set_theme_mod('nav_menu_locations', $locations);
            }
        }
        
        return $menu_id;
    }
    
    /**
     * Add item to menu and return the menu item ID
     */
    private function add_menu_item($menu_id, $title, $url, $parent_id = 0) {
        $menu_item_id = wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => esc_html($title),
            'menu-item-url' => esc_url($url),
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => $parent_id
        ));
        
        return is_wp_error($menu_item_id) ? false : $menu_item_id;
    }
    
    /**
     * Generate services menu with proper hierarchy
     */
    public function create_services_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }
        
        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        // Find service-related pages with comprehensive detection
        $service_pages = array();
        $service_categories = array();
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $content_lower = strtolower($page->post_content);
            
            // Detect main service categories
            if (preg_match('/^services?$/i', $page->post_title)) {
                $service_categories['main_services'] = $page;
                continue;
            }
            
            // Comprehensive service detection - check title and content
            $is_service_page = false;
            
            // Direct service keywords in title
            if (preg_match('/(service|solution|offer|package|plan|consulting|development|design|marketing|seo|web|digital|app|mobile|software|programming|coding|branding|creative|strategy|analytics|optimization|maintenance|support|hosting|security|ecommerce|cms|wordpress|shopify|magento|ppc|advertising|social media|email marketing|content marketing|graphic design|logo design|ui design|ux design|website design|web development|app development|software development|technical|audit|local seo|technical seo)/i', $title_lower)) {
                $is_service_page = true;
            }
            
            // Service-related terms that might not have "service" in title
            elseif (preg_match('/(web design|website design|app development|mobile development|software development|digital marketing|social media marketing|search engine optimization|seo optimization|content creation|brand strategy|graphic design|logo design|ui\/ux design|e-commerce|ecommerce|online store|wordpress|shopify|magento|drupal|joomla|html|css|javascript|php|python|react|angular|vue|node|laravel|codeigniter|bootstrap|responsive design|mobile responsive|custom development|web application|mobile application|api development|database design|server management|cloud hosting|digital transformation|automation|crm|erp|project management|quality assurance|testing|bug fixes|website maintenance|security audit|performance optimization|speed optimization|conversion optimization|landing page|sales funnel|lead generation|pay per click|google ads|facebook ads|instagram ads|linkedin ads|youtube ads|twitter ads|email campaigns|newsletter|blog writing|copywriting|technical writing|video production|photography|illustration|infographic|presentation design|print design|business cards|brochures|flyers|banners|signage)/i', $title_lower)) {
                $is_service_page = true;
            }
            
            // Check for industry-specific services
            elseif (preg_match('/(healthcare|medical|dental|legal|law|attorney|lawyer|real estate|property|finance|financial|banking|insurance|education|school|university|restaurant|food|retail|ecommerce|manufacturing|construction|automotive|technology|saas|startup|nonprofit|charity|government|agency|consulting|freelance|creative|studio|portfolio)/i', $title_lower)) {
                // If it's industry-specific AND contains service indicators
                if (preg_match('/(solution|service|consulting|development|design|marketing|strategy|management|system|platform|tool)/i', $title_lower)) {
                    $is_service_page = true;
                }
            }
            
            // Additional check for pages that might be services based on common patterns
            elseif (preg_match('/(custom|professional|expert|specialist|premium|enterprise|business|corporate|advanced|basic|standard|starter|pro|ultimate|complete|full|comprehensive|managed|dedicated|freelance|agency|studio|consultancy)/i', $title_lower)) {
                // If it has service indicators combined with service-level terms
                if (preg_match('/(development|design|marketing|consulting|support|management|optimization|creation|strategy|analysis|audit|maintenance|hosting|security|integration|automation|training|coaching)/i', $title_lower)) {
                    $is_service_page = true;
                }
            }
            
            if ($is_service_page) {
                $service_pages[] = $page;
            }
        }
        
        // If still no service pages found, do one more broad sweep
        if (empty($service_pages)) {
            foreach ($all_pages as $page) {
                $title_lower = strtolower($page->post_title);
                // Very broad final check
                if (preg_match('/(what we do|our work|portfolio|pricing|plans|packages|offerings|capabilities|expertise|specialties|focus areas)/i', $title_lower)) {
                    $service_pages[] = $page;
                }
            }
        }
        
        if (empty($service_pages)) {
            return false;
        }
        
        $menu_name = 'Services Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);
        
        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }
        
        $menu_id = wp_create_nav_menu($menu_name);
        
        if (is_wp_error($menu_id)) {
            return false;
        }
        
        // Smart organization of services
        $organized_services = $this->organize_services_intelligently($service_pages);
        
        // Create main Services parent item (use main services page if exists)
        $services_parent_url = isset($service_categories['main_services']) 
            ? esc_url(get_permalink($service_categories['main_services']->ID)) 
            : '#';
        $services_parent_id = $this->add_menu_item($menu_id, 'Services', $services_parent_url, 0);
        
        // Add organized service categories
        foreach ($organized_services as $category => $services) {
            if (count($services) == 1) {
                // Single service - add directly under main Services
                $this->add_menu_item($menu_id, $services[0]->post_title, esc_url(get_permalink($services[0]->ID)), $services_parent_id);
            } else {
                // Multiple services in category - create subcategory
                $category_parent_id = $this->add_menu_item($menu_id, $category, '#', $services_parent_id);
                
                foreach ($services as $service) {
                    // Add actual service pages
                    $service_item_id = $this->add_menu_item($menu_id, $service->post_title, esc_url(get_permalink($service->ID)), $category_parent_id);
                    
                    // Add any child pages of this service
                    $child_services = get_pages(array(
                        'post_status' => 'publish',
                        'parent' => $service->ID
                    ));
                    
                    foreach ($child_services as $child_service) {
                        $this->add_menu_item($menu_id, $child_service->post_title, esc_url(get_permalink($child_service->ID)), $service_item_id);
                    }
                }
            }
        }
        
        // Add any uncategorized services
        $all_organized_ids = array();
        foreach ($organized_services as $services) {
            foreach ($services as $service) {
                $all_organized_ids[] = $service->ID;
            }
        }
        
        foreach ($service_pages as $service) {
            if (!in_array($service->ID, $all_organized_ids)) {
                $this->add_menu_item($menu_id, $service->post_title, esc_url(get_permalink($service->ID)), $services_parent_id);
            }
        }
        
        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }
    
    /**
     * Debug function to show which service pages are being detected
     */
    public function debug_service_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        echo "<h3>Debug: Service Page Detection</h3>";
        echo '<p>' . esc_html(sprintf(__('Total pages found: %d', 'artitechcore'), count($all_pages))) . '</p>';
        
        $service_pages = array();
        $service_categories = array();
        $debug_info = array();
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $content_lower = strtolower($page->post_content);
            
            $debug_info[$page->ID] = array(
                'title' => $page->post_title,
                'url' => esc_url(get_permalink($page->ID)),
                'detected' => false,
                'reason' => 'Not detected as service page'
            );
            
            // Detect main service categories
            if (preg_match('/^services?$/i', $page->post_title)) {
                $service_categories['main_services'] = $page;
                $debug_info[$page->ID]['detected'] = 'main_services';
                $debug_info[$page->ID]['reason'] = 'Main services page';
                continue;
            }
            
            // Comprehensive service detection - check title and content
            $is_service_page = false;
            $detection_reason = '';
            
            // Direct service keywords in title
            if (preg_match('/(service|solution|offer|package|plan|consulting|development|design|marketing|seo|web|digital|app|mobile|software|programming|coding|branding|creative|strategy|analytics|optimization|maintenance|support|hosting|security|ecommerce|cms|wordpress|shopify|magento|ppc|advertising|social media|email marketing|content marketing|graphic design|logo design|ui design|ux design|website design|web development|app development|software development|technical|audit|local seo|technical seo)/i', $title_lower)) {
                $is_service_page = true;
                $detection_reason = 'Direct service keywords in title';
            }
            
            // Service-related terms that might not have "service" in title
            elseif (preg_match('/(web design|website design|app development|mobile development|software development|digital marketing|social media marketing|search engine optimization|seo optimization|content creation|brand strategy|graphic design|logo design|ui\/ux design|e-commerce|ecommerce|online store|wordpress|shopify|magento|drupal|joomla|html|css|javascript|php|python|react|angular|vue|node|laravel|codeigniter|bootstrap|responsive design|mobile responsive|custom development|web application|mobile application|api development|database design|server management|cloud hosting|digital transformation|automation|crm|erp|project management|quality assurance|testing|bug fixes|website maintenance|security audit|performance optimization|speed optimization|conversion optimization|landing page|sales funnel|lead generation|pay per click|google ads|facebook ads|instagram ads|linkedin ads|youtube ads|twitter ads|email campaigns|newsletter|blog writing|copywriting|technical writing|video production|photography|illustration|infographic|presentation design|print design|business cards|brochures|flyers|banners|signage)/i', $title_lower)) {
                $is_service_page = true;
                $detection_reason = 'Service-related terms without "service" keyword';
            }
            
            // Check for industry-specific services
            elseif (preg_match('/(healthcare|medical|dental|legal|law|attorney|lawyer|real estate|property|finance|financial|banking|insurance|education|school|university|restaurant|food|retail|ecommerce|manufacturing|construction|automotive|technology|saas|startup|nonprofit|charity|government|agency|consulting|freelance|creative|studio|portfolio)/i', $title_lower)) {
                // If it's industry-specific AND contains service indicators
                if (preg_match('/(solution|service|consulting|development|design|marketing|strategy|management|system|platform|tool)/i', $title_lower)) {
                    $is_service_page = true;
                    $detection_reason = 'Industry-specific service page';
                }
            }
            
            // Additional check for pages that might be services based on common patterns
            elseif (preg_match('/(custom|professional|expert|specialist|premium|enterprise|business|corporate|advanced|basic|standard|starter|pro|ultimate|complete|full|comprehensive|managed|dedicated|freelance|agency|studio|consultancy)/i', $title_lower)) {
                // If it has service indicators combined with service-level terms
                if (preg_match('/(development|design|marketing|consulting|support|management|optimization|creation|strategy|analysis|audit|maintenance|hosting|security|integration|automation|training|coaching)/i', $title_lower)) {
                    $is_service_page = true;
                    $detection_reason = 'Service-level terms with service indicators';
                }
            }
            
            if ($is_service_page) {
                $service_pages[] = $page;
                $debug_info[$page->ID]['detected'] = true;
                $debug_info[$page->ID]['reason'] = $detection_reason;
            }
        }
        
        // If still no service pages found, do one more broad sweep
        if (empty($service_pages)) {
            foreach ($all_pages as $page) {
                $title_lower = strtolower($page->post_title);
                // Very broad final check
                if (preg_match('/(what we do|our work|portfolio|pricing|plans|packages|offerings|capabilities|expertise|specialties|focus areas)/i', $title_lower)) {
                    $service_pages[] = $page;
                    $debug_info[$page->ID]['detected'] = true;
                    $debug_info[$page->ID]['reason'] = 'Broad sweep detection';
                }
            }
        }
        
        echo '<h4>' . esc_html(sprintf(__('Service Pages Detected (%d):', 'artitechcore'), count($service_pages))) . '</h4>';
        
        if (!empty($service_pages)) {
            $organized_services = $this->organize_services_intelligently($service_pages);
            
            foreach ($organized_services as $category => $services) {
                echo '<h5>' . esc_html(sprintf(__('Category: %s (%d services)', 'artitechcore'), $category, count($services))) . '</h5>';
                echo "<ul>";
                foreach ($services as $service) {
                    echo '<li><strong>' . esc_html($service->post_title) . '</strong> - <a href="' . esc_url(get_permalink($service->ID)) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></li>';
                }
                echo "</ul>";
            }
        } else {
            echo "<p>No service pages detected.</p>";
        }
        
        echo "<h4>All Pages Analysis:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Page Title</th><th>Status</th><th>Reason</th><th>Link</th></tr>";
        
        foreach ($debug_info as $info) {
            $status_color = $info['detected'] ? 'green' : 'red';
            $status_text = $info['detected'] ? ($info['detected'] === true ? 'DETECTED' : strtoupper($info['detected'])) : 'NOT DETECTED';
            
            echo "<tr>";
            echo '<td>' . esc_html($info['title']) . '</td>';
            echo '<td style="color: ' . esc_attr($status_color) . ';"><strong>' . esc_html($status_text) . '</strong></td>';
            echo '<td>' . esc_html($info['reason']) . '</td>';
            echo '<td><a href="' . esc_url($info['url']) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></td>';
            echo "</tr>";
        }
        
        echo "</table>";
        
        return true;
    }
    
    /**
     * Intelligently organize service pages by category
     */
    private function organize_services_intelligently($service_pages) {
        $organized = array(
            'web_development' => array(),
            'digital_marketing' => array(),
            'design_creative' => array(),
            'consulting_strategy' => array(),
            'support_maintenance' => array(),
            'ecommerce' => array(),
            'industry_specific' => array(),
            'other_services' => array()
        );
        
        foreach ($service_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $categorized = false;
            
            // Web Development & Programming
            if (preg_match('/(web development|website development|app development|mobile development|software development|custom development|programming|coding|html|css|javascript|php|python|react|angular|vue|node|laravel|wordpress development|drupal|joomla|cms|web application|mobile application|api development|database|server|backend|frontend|full stack)/i', $title_lower)) {
                $organized['web_development'][] = $page;
                $categorized = true;
            }
            
            // E-commerce (separate from web dev due to importance)
            elseif (preg_match('/(ecommerce|e-commerce|online store|online shop|shopify|magento|woocommerce|opencart|prestashop|shopping cart|payment integration|inventory management|product catalog)/i', $title_lower)) {
                $organized['ecommerce'][] = $page;
                $categorized = true;
            }
            
            // Digital Marketing & SEO
            elseif (preg_match('/(seo|search engine optimization|digital marketing|social media marketing|content marketing|email marketing|marketing automation|ppc|pay per click|google ads|facebook ads|instagram ads|linkedin ads|advertising|lead generation|conversion optimization|analytics|tracking|campaign management|influencer marketing|affiliate marketing|video marketing)/i', $title_lower)) {
                $organized['digital_marketing'][] = $page;
                $categorized = true;
            }
            
            // Design & Creative
            elseif (preg_match('/(web design|website design|ui design|ux design|user interface|user experience|graphic design|logo design|brand design|branding|visual identity|creative design|print design|illustration|photography|video production|animation|infographic|presentation design|business cards|brochures|flyers|banners|signage)/i', $title_lower)) {
                $organized['design_creative'][] = $page;
                $categorized = true;
            }
            
            // Consulting & Strategy
            elseif (preg_match('/(consulting|strategy|strategic planning|business consulting|technical consulting|digital transformation|automation|process optimization|project management|training|coaching|advisory|analysis|audit|assessment|research|planning)/i', $title_lower)) {
                $organized['consulting_strategy'][] = $page;
                $categorized = true;
            }
            
            // Support & Maintenance
            elseif (preg_match('/(support|maintenance|technical support|website maintenance|hosting|server management|cloud hosting|backup|security|security audit|performance optimization|speed optimization|monitoring|updates|bug fixes|troubleshooting|help desk)/i', $title_lower)) {
                $organized['support_maintenance'][] = $page;
                $categorized = true;
            }
            
            // Industry-Specific Services
            elseif (preg_match('/(healthcare|medical|dental|legal|law|attorney|real estate|property|finance|financial|banking|insurance|education|school|university|restaurant|food service|retail|manufacturing|construction|automotive|saas|startup|nonprofit|charity|government)/i', $title_lower)) {
                $organized['industry_specific'][] = $page;
                $categorized = true;
            }
            
            // If not categorized yet, put in other services
            if (!$categorized) {
                $organized['other_services'][] = $page;
            }
        }
        
        // Remove empty categories and rename keys for better display
        $filtered = array_filter($organized, function($services) {
            return !empty($services);
        });
        
        // Rename categories for better menu display
        $renamed = array();
        $category_labels = array(
            'web_development' => 'Web Development',
            'digital_marketing' => 'Digital Marketing',
            'design_creative' => 'Design & Creative',
            'consulting_strategy' => 'Consulting & Strategy',
            'support_maintenance' => 'Support & Maintenance',
            'ecommerce' => 'E-commerce',
            'industry_specific' => 'Industry Solutions',
            'other_services' => 'Other Services'
        );
        
        foreach ($filtered as $key => $services) {
            $renamed[$category_labels[$key]] = $services;
        }
        
        return $renamed;
    }
    
    /**
     * Generate company menu with proper hierarchy
     */
    public function create_company_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }
        
        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        // Intelligently categorize company pages
        $company_structure = $this->organize_company_pages($all_pages);
        
        if (empty($company_structure)) {
            return false;
        }

        $menu_name = 'Company Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }
        
        // Build menu structure
        $this->build_company_menu_structure($menu_id, $company_structure);
        
        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }
    
    /**
     * Organize company pages into logical categories with comprehensive detection
     */
    private function organize_company_pages($all_pages) {
        $structure = array(
            'about' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'team' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'careers' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'contact' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'company_info' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'other' => array()
        );
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $categorized = false;
            
            // About Us section - comprehensive detection
            if (preg_match('/^about(\s+us)?$/i', $page->post_title)) {
                $structure['about']['main_page'] = $page;
                $categorized = true;
            } elseif (preg_match('/(about us|about our company|about the company|our story|our mission|our vision|our values|company history|company background|who we are|what we do|why choose us|our approach|our philosophy|our commitment|company overview|business overview|corporate profile|company profile|how we started|founded|establishment|genesis|background|heritage|legacy|company culture|our culture|core values|guiding principles|mission statement|vision statement|company ethos|about our business|meet the company)/i', $title_lower)) {
                $structure['about']['sub_pages'][] = $page;
                $categorized = true;
            }
            
            // Team section - comprehensive detection
            if (!$categorized && (preg_match('/^(team|our team|the team|meet the team|staff)$/i', $page->post_title))) {
                $structure['team']['main_page'] = $page;
                $categorized = true;
            } elseif (!$categorized && preg_match('/(our team|the team|meet the team|team members|staff members|our staff|our people|team page|leadership|leadership team|management|management team|executives|executive team|board of directors|board members|founders|co-founders|key personnel|core team|project team|department heads|senior staff|principal|partners|associates|consultants|advisors|advisory board|meet our|meet the|staff directory|team directory|employee profiles|bios|biographies|who\'s who|personnel|human resources team|organizational chart|company roster)/i', $title_lower)) {
                $structure['team']['sub_pages'][] = $page;
                $categorized = true;
            }
            
            // Careers section - comprehensive detection
            if (!$categorized && preg_match('/^(careers?|jobs?|employment|opportunities)$/i', $page->post_title)) {
                $structure['careers']['main_page'] = $page;
                $categorized = true;
            } elseif (!$categorized && preg_match('/(careers?|jobs?|employment|work with us|join us|join our team|hiring|recruitment|job opportunities|career opportunities|open positions|current openings|job openings|vacancies|positions available|job listings|employment opportunities|internships?|graduate programs?|trainee programs?|job search|apply now|we\'re hiring|now hiring|job board|career portal|talent acquisition|human resources|hr|recruiting|job application|career development|professional development|career growth|job postings)/i', $title_lower)) {
                $structure['careers']['sub_pages'][] = $page;
                $categorized = true;
            }
            
            // Contact section - comprehensive detection
            if (!$categorized && preg_match('/^contact(\s+us)?$/i', $page->post_title)) {
                $structure['contact']['main_page'] = $page;
                $categorized = true;
            } elseif (!$categorized && preg_match('/(contact us|get in touch|reach us|reach out|contact information|contact details|how to reach us|get in contact|office|offices|location|locations|address|addresses|phone|telephone|email|contact form|contact page|touch base|connect with us|find us|visit us|headquarters|head office|branch office|regional office|local office|customer service|support|help|assistance|inquiries|questions|feedback|consultation|schedule|appointment|meeting|demo|quote|estimate|proposal|sales|business development|partnership|collaboration)/i', $title_lower)) {
                $structure['contact']['sub_pages'][] = $page;
                $categorized = true;
            }
            
            // Company Information - comprehensive detection
            if (!$categorized && preg_match('/(company|corporate|business|organization|enterprise|firm|agency|group|corporation|inc|ltd|llc)/i', $title_lower)) {
                if (preg_match('/(company information|corporate information|business information|company details|corporate profile|business profile|company overview|corporate overview|business overview|legal information|terms|privacy|policy|policies|compliance|certifications|accreditations|awards|recognition|achievements|milestones|timeline|history|years of experience|established|incorporated|founded|registration|license|permits|insurance|bonding|credentials|qualifications|industry experience|expertise|specialization|focus|sectors|markets|clientele|customer base|testimonials|references|case studies|success stories|portfolio|gallery|showcase|examples|samples|work|projects|clients|partners|affiliations|associations|memberships|networks|alliances|relationships|vendors|suppliers|contractors|subcontractors)/i', $title_lower)) {
                    if (preg_match('/^(company|corporate|business)\s/i', $page->post_title)) {
                        $structure['company_info']['main_page'] = $page;
                    } else {
                        $structure['company_info']['sub_pages'][] = $page;
                    }
                    $categorized = true;
                }
            }
            
            // News, Press, Media - comprehensive detection
            if (!$categorized && preg_match('/(news|press|media|blog|articles|updates|announcements|releases|press releases|media kit|media center|newsroom|in the news|media coverage|publications|newsletter|insights|thought leadership|industry news|company news|latest news|recent news|breaking news|updates|announcements|events|webinars|conferences|seminars|workshops|speaking|presentations|interviews|podcasts|videos|resources|downloads|whitepapers|case studies|reports|research|studies|analysis|commentary|opinions|perspectives|trends|forecasts|predictions|market insights)/i', $title_lower)) {
                $structure['other'][] = $page;
                $categorized = true;
            }
            
            // Investor Relations - comprehensive detection
            if (!$categorized && preg_match('/(investor|investment|shareholders?|stockholders?|equity|financial|finance|funding|capital|ipo|public|private|venture|angel|seed|series|round|valuation|stock|shares|dividends|earnings|revenue|profit|loss|reports|quarterly|annual|sec|filings|regulatory|compliance|governance|board|proxy|voting|meetings|calendar|events|presentations|conference calls|financial statements|balance sheet|income statement|cash flow|ratios|metrics|kpis|performance|growth|strategy|outlook|guidance|forecasts|projections)/i', $title_lower)) {
                $structure['other'][] = $page;
                $categorized = true;
            }
            
            // Legal, Terms, Privacy - comprehensive detection
            if (!$categorized && preg_match('/(legal|terms|privacy|policy|policies|conditions|agreement|contract|license|copyright|trademark|intellectual property|data protection|gdpr|ccpa|cookies|disclaimer|liability|warranty|indemnification|jurisdiction|governing law|dispute resolution|arbitration|mediation|compliance|regulations|regulatory|statutory|mandatory|required|obligatory|necessary|essential|important|critical|vital|key|main|primary|principal|major|significant|substantial|material|relevant|applicable|pertinent|related|associated|connected|linked|tied|bound|subject|governed|controlled|managed|administered|overseen|supervised|monitored|tracked|measured|evaluated|assessed|reviewed|audited|inspected|examined|investigated|studied|analyzed|researched)/i', $title_lower)) {
                $structure['other'][] = $page;
                $categorized = true;
            }
            
            // If still not categorized, check for any company-related terms
            if (!$categorized && preg_match('/(company|corporate|business|organization|enterprise|firm|agency|group|corporation|inc|ltd|llc|professional|commercial|industrial|service|solutions|consulting|advisory|management|development|operations|administration|executive|strategic|tactical|operational|functional|technical|specialized|expert|experienced|qualified|certified|licensed|accredited|approved|authorized|registered|established|reputable|reliable|trustworthy|professional|quality|excellence|superior|premium|top|leading|best|premier|elite|exclusive|unique|innovative|creative|cutting-edge|state-of-the-art|advanced|sophisticated|comprehensive|complete|full-service|end-to-end|turnkey|one-stop|integrated|holistic|customized|tailored|personalized|specialized|focused|dedicated|committed|passionate|driven|results-oriented|client-focused|customer-centric)/i', $title_lower)) {
                $structure['other'][] = $page;
            }
        }
        
        // Clean up empty sections
        $cleaned_structure = array();
        foreach ($structure as $section => $data) {
            if ($section === 'other') {
                if (!empty($data)) {
                    $cleaned_structure[$section] = $data;
                }
            } else {
                if ($data['main_page'] || !empty($data['sub_pages'])) {
                    $cleaned_structure[$section] = $data;
                }
            }
        }
        
        return $cleaned_structure;
    }
    
    /**
     * Build the actual company menu structure
     */
    private function build_company_menu_structure($menu_id, $structure) {
        foreach ($structure as $section => $data) {
            if ($section === 'other') {
                // Add other pages directly to menu
                foreach ($data as $page) {
                    $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), 0);
                }
            } else {
                // Handle structured sections
                if (isset($data['main_page']) && $data['main_page']) {
                    // Use main page as parent
                    $parent_id = $this->add_menu_item($menu_id, $data['main_page']->post_title, esc_url(get_permalink($data['main_page']->ID)), 0);
                    
                    // Add sub-pages under main page
                    foreach ($data['sub_pages'] as $sub_page) {
                        $this->add_menu_item($menu_id, $sub_page->post_title, esc_url(get_permalink($sub_page->ID)), $parent_id);
                    }
                    
                    // Add any WordPress child pages
                    $wp_children = get_pages(array(
                        'post_status' => 'publish',
                        'parent' => $data['main_page']->ID
                    ));
                    
                    foreach ($wp_children as $child) {
                        $this->add_menu_item($menu_id, $child->post_title, esc_url(get_permalink($child->ID)), $parent_id);
                    }
                    
                } elseif (!empty($data['sub_pages'])) {
                    // No main page, create category parent
                    $category_title = ucfirst($section);
                    if ($section === 'about') $category_title = 'About Us';
                    elseif ($section === 'careers') $category_title = 'Careers';
                    
                    $parent_id = $this->add_menu_item($menu_id, $category_title, '#', 0);
                    
                    foreach ($data['sub_pages'] as $sub_page) {
                        $this->add_menu_item($menu_id, $sub_page->post_title, esc_url(get_permalink($sub_page->ID)), $parent_id);
                    }
                }
            }
        }
    }

    /**
     * Generate main navigation menu (primary header menu)
     */
    public function create_main_navigation_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Main Navigation Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
             return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }
        
        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        // Organize pages into main navigation structure
        $nav_structure = $this->organize_main_navigation($all_pages);
        
        // Build the menu
        $this->build_main_navigation_menu($menu_id, $nav_structure);
        
        // === DYNAMIC CPT INTEGRATION ===
        // Get all public custom post types NOT built-in
        $args = array(
           'public'   => true,
           '_builtin' => false
        );
        $output = 'objects';
        $operator = 'and';
        $post_types = get_post_types($args, $output, $operator);

        if (!empty($post_types)) {
            foreach ($post_types  as $post_type ) {
                if ($post_type->has_archive) {
                     $this->add_menu_item($menu_id, esc_html($post_type->labels->name), esc_url(get_post_type_archive_link($post_type->name)));
                }
            }
        }
        // ===============================

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }
    
    /**
     * Organize pages for main navigation with comprehensive detection
     */
    private function organize_main_navigation($all_pages) {
        $structure = array(
            'home' => home_url(),
            'about' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'services' => array(
                'main_page' => null,
                'categories' => array()
            ),
            'industries' => array(
                'main_page' => null,
                'sub_pages' => array()
            ),
            'resources' => array(
                'blog' => get_option('page_for_posts'),
                'resource_pages' => array(),
                'categories' => array()
            ),
            'contact' => null
        );
        
        // First, separate service pages using comprehensive detection
        $service_pages = array();
        $other_pages = array();
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $is_service_page = false;
            
            // Detect main service categories
            if (preg_match('/^services?$/i', $page->post_title)) {
                $structure['services']['main_page'] = $page;
                continue;
            }
            
            // Comprehensive service detection - same logic as service menu
            if (preg_match('/(service|solution|offer|package|plan|consulting|development|design|marketing|seo|web|digital|app|mobile|software|programming|coding|branding|creative|strategy|analytics|optimization|maintenance|support|hosting|security|ecommerce|cms|wordpress|shopify|magento|ppc|advertising|social media|email marketing|content marketing|graphic design|logo design|ui design|ux design|website design|web development|app development|software development|technical|audit|local seo|technical seo)/i', $title_lower)) {
                $is_service_page = true;
            }
            
            // Service-related terms that might not have "service" in title
            elseif (preg_match('/(web design|website design|app development|mobile development|software development|digital marketing|social media marketing|search engine optimization|seo optimization|content creation|brand strategy|graphic design|logo design|ui\/ux design|e-commerce|ecommerce|online store|wordpress|shopify|magento|drupal|joomla|html|css|javascript|php|python|react|angular|vue|node|laravel|codeigniter|bootstrap|responsive design|mobile responsive|custom development|web application|mobile application|api development|database design|server management|cloud hosting|digital transformation|automation|crm|erp|project management|quality assurance|testing|bug fixes|website maintenance|security audit|performance optimization|speed optimization|conversion optimization|landing page|sales funnel|lead generation|pay per click|google ads|facebook ads|instagram ads|linkedin ads|youtube ads|twitter ads|email campaigns|newsletter|blog writing|copywriting|technical writing|video production|photography|illustration|infographic|presentation design|print design|business cards|brochures|flyers|banners|signage)/i', $title_lower)) {
                $is_service_page = true;
            }
            
            // Check for industry-specific services
            elseif (preg_match('/(healthcare|medical|dental|legal|law|attorney|lawyer|real estate|property|finance|financial|banking|insurance|education|school|university|restaurant|food|retail|ecommerce|manufacturing|construction|automotive|technology|saas|startup|nonprofit|charity|government|agency|consulting|freelance|creative|studio|portfolio)/i', $title_lower)) {
                if (preg_match('/(solution|service|consulting|development|design|marketing|strategy|management|system|platform|tool)/i', $title_lower)) {
                    $is_service_page = true;
                }
            }
            
            // Additional check for pages that might be services based on common patterns
            elseif (preg_match('/(custom|professional|expert|specialist|premium|enterprise|business|corporate|advanced|basic|standard|starter|pro|ultimate|complete|full|comprehensive|managed|dedicated|freelance|agency|studio|consultancy)/i', $title_lower)) {
                if (preg_match('/(development|design|marketing|consulting|support|management|optimization|creation|strategy|analysis|audit|maintenance|hosting|security|integration|automation|training|coaching)/i', $title_lower)) {
                    $is_service_page = true;
                }
            }
            
            if ($is_service_page) {
                $service_pages[] = $page;
            } else {
                $other_pages[] = $page;
            }
        }
        
        // Organize service pages using the same logic as service menu
        if (!empty($service_pages)) {
            $organized_services = $this->organize_services_intelligently($service_pages);
            $structure['services']['categories'] = $organized_services;
        }
        
        // Process other pages
        foreach ($other_pages as $page) {
            $title_lower = strtolower($page->post_title);
            
            // About section - comprehensive detection
            if (preg_match('/^about(\s+us)?$/i', $page->post_title)) {
                $structure['about']['main_page'] = $page;
            } elseif (preg_match('/(about us|about our company|about the company|our story|our mission|our vision|our values|company history|company background|who we are|what we do|why choose us|our approach|our philosophy|our commitment|company overview|business overview|corporate profile|company profile|how we started|founded|establishment|genesis|background|heritage|legacy|company culture|our culture|core values|guiding principles|mission statement|vision statement|company ethos|about our business|meet the company|team|leadership|management)/i', $title_lower)) {
                $structure['about']['sub_pages'][] = $page;
            }
            
            // Industries section - comprehensive detection
            elseif (preg_match('/^industries?$/i', $page->post_title)) {
                $structure['industries']['main_page'] = $page;
            } elseif (preg_match('/(healthcare|medical|dental|veterinary|pharmaceutical|biotech|finance|financial|banking|insurance|investment|accounting|tax|education|school|university|college|training|elearning|retail|ecommerce|wholesale|distribution|manufacturing|industrial|production|factory|construction|engineering|architecture|real estate|property|legal|law|attorney|lawyer|nonprofit|charity|government|municipal|public sector|technology|tech|software|saas|startup|automotive|transportation|logistics|hospitality|hotel|restaurant|food|beverage|entertainment|media|publishing|healthcare|fitness|wellness|beauty|fashion|agriculture|energy|utilities|telecommunications|consulting|professional services)/i', $title_lower)) {
                $structure['industries']['sub_pages'][] = $page;
            }
            
            // Resources section - comprehensive detection
            elseif (preg_match('/(resource|resources|guide|guides|tutorial|tutorials|documentation|docs|knowledge|knowledge base|help|help center|faq|faqs|frequently asked questions|support|support center|manual|manuals|handbook|handbooks|whitepaper|whitepapers|case study|case studies|report|reports|research|study|studies|analysis|insights|tips|best practices|how to|how-to|library|archive|downloads|tools|templates|checklists|worksheets|ebooks|webinar|webinars|video|videos|podcast|podcasts|blog|articles|news|updates|newsletter|learning|training|education|academy|university)/i', $title_lower)) {
                $structure['resources']['resource_pages'][] = $page;
            }
            
            // Contact - comprehensive detection
            elseif (preg_match('/^contact(\s+us)?$/i', $page->post_title)) {
                $structure['contact'] = $page;
            }
        }
        
        // Get blog categories for resources
        if ($structure['resources']['blog']) {
            $structure['resources']['categories'] = get_categories(array(
                'hide_empty' => true,
                'number' => 6
            ));
        }
        
        return $structure;
    }
    
    /**
     * Build the main navigation menu structure
     */
    private function build_main_navigation_menu($menu_id, $structure) {
        // Home
        $this->add_menu_item($menu_id, 'Home', $structure['home']);
        
        // About section
        if ($structure['about']['main_page']) {
            $about_parent_id = $this->add_menu_item($menu_id, $structure['about']['main_page']->post_title, esc_url(get_permalink($structure['about']['main_page']->ID)), 0);
            
            foreach ($structure['about']['sub_pages'] as $sub_page) {
                $this->add_menu_item($menu_id, $sub_page->post_title, esc_url(get_permalink($sub_page->ID)), $about_parent_id);
            }
        } elseif (!empty($structure['about']['sub_pages'])) {
            $about_parent_id = $this->add_menu_item($menu_id, 'About', '#', 0);
            
            foreach ($structure['about']['sub_pages'] as $sub_page) {
                $this->add_menu_item($menu_id, $sub_page->post_title, esc_url(get_permalink($sub_page->ID)), $about_parent_id);
            }
        } else {
            $this->add_menu_item($menu_id, 'About', home_url('/about/'));
        }
        
        // Services section
        $services_url = $structure['services']['main_page'] 
            ? esc_url(get_permalink($structure['services']['main_page']->ID)) 
            : home_url('/services/');
        $services_parent_id = $this->add_menu_item($menu_id, 'Services', $services_url, 0);
        
        foreach ($structure['services']['categories'] as $category_name => $services) {
            if (count($services) == 1) {
                // Single service - add directly under Services
                $this->add_menu_item($menu_id, $services[0]->post_title, esc_url(get_permalink($services[0]->ID)), $services_parent_id);
            } else {
                // Multiple services - create category
                $category_parent_id = $this->add_menu_item($menu_id, $category_name, '#', $services_parent_id);
                
                foreach ($services as $service) {
                    $this->add_menu_item($menu_id, $service->post_title, esc_url(get_permalink($service->ID)), $category_parent_id);
                }
            }
        }
        
        // Industries section (only if there are industry pages)
        if ($structure['industries']['main_page'] || !empty($structure['industries']['sub_pages'])) {
            $industries_url = $structure['industries']['main_page'] 
                ? esc_url(get_permalink($structure['industries']['main_page']->ID)) 
                : '#';
            $industries_parent_id = $this->add_menu_item($menu_id, 'Industries', $industries_url, 0);
            
            foreach ($structure['industries']['sub_pages'] as $industry_page) {
                $this->add_menu_item($menu_id, $industry_page->post_title, esc_url(get_permalink($industry_page->ID)), $industries_parent_id);
            }
        }
        
        // Resources section
        if ($structure['resources']['blog'] || !empty($structure['resources']['resource_pages'])) {
            $resources_parent_id = $this->add_menu_item($menu_id, 'Resources', '#', 0);
            
            // Add blog if exists
            if ($structure['resources']['blog']) {
                $this->add_menu_item($menu_id, 'Blog', esc_url(get_permalink($structure['resources']['blog'])), $resources_parent_id);
                
                // Add categories
                foreach ($structure['resources']['categories'] as $category) {
                    $this->add_menu_item($menu_id, esc_html($category->name), esc_url(get_category_link($category->term_id)), $resources_parent_id);
                }
            }
            
            // Add resource pages
            foreach ($structure['resources']['resource_pages'] as $resource_page) {
                $this->add_menu_item($menu_id, $resource_page->post_title, esc_url(get_permalink($resource_page->ID)), $resources_parent_id);
            }
        }
        
        // Contact
        if ($structure['contact']) {
            $this->add_menu_item($menu_id, $structure['contact']->post_title, esc_url(get_permalink($structure['contact']->ID)));
        } else {
            $this->add_menu_item($menu_id, 'Contact', home_url('/contact/'));
        }
    }

    /**
     * Generate resources/knowledge base menu with comprehensive detection and smart categorization
     */
    public function create_resources_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Resources Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }

        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));

        // Find main resources page
        $main_resources_page = null;
        foreach ($all_pages as $page) {
            if (preg_match('/^resources?$/i', $page->post_title)) {
                $main_resources_page = $page;
                break;
            }
        }

        // Add main resources link
        $resources_url = $main_resources_page ? esc_url(get_permalink($main_resources_page->ID)) : home_url('/resources/');
        $resources_parent_id = $this->add_menu_item($menu_id, 'Resources', $resources_url);

        // Organize resource pages into categories
        $organized_resources = $this->organize_resource_pages_intelligently($all_pages);

        // Add blog/news first if exists
        if (get_option('page_for_posts')) {
            $this->add_menu_item($menu_id, 'Blog', esc_url(get_permalink(get_option('page_for_posts'))), $resources_parent_id);
        }

        // Build organized resource structure
        foreach ($organized_resources as $category_name => $pages) {
            if (count($pages) == 1) {
                // Single page - add directly under Resources
                $this->add_menu_item($menu_id, $pages[0]->post_title, esc_url(get_permalink($pages[0]->ID)), $resources_parent_id);
            } else {
                // Multiple pages - create category
                $category_parent_id = $this->add_menu_item($menu_id, $category_name, '#', $resources_parent_id);
                
                foreach ($pages as $page) {
                    $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id);
                    
                    // Add any child pages
                    $child_pages = get_pages(array(
                        'post_status' => 'publish',
                        'parent' => $page->ID
                    ));
                    
                    foreach ($child_pages as $child_page) {
                        $this->add_menu_item($menu_id, $child_page->post_title, esc_url(get_permalink($child_page->ID)), $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id));
                    }
                }
            }
        }

        // Add blog categories if blog exists
        if (get_option('page_for_posts')) {
            $categories = get_categories(array('hide_empty' => true, 'number' => 6));
            if (!empty($categories)) {
                $categories_parent_id = $this->add_menu_item($menu_id, 'Blog Categories', '#', $resources_parent_id);
                foreach ($categories as $category) {
                    $this->add_menu_item($menu_id, esc_html($category->name), esc_url(get_category_link($category->term_id)), $categories_parent_id);
                }
            }
        }

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }

    /**
     * Intelligently organize resource pages by category with comprehensive detection
     */
    private function organize_resource_pages_intelligently($all_pages) {
        $organized = array(
            'knowledge_base' => array(),
            'documentation' => array(),
            'tutorials_guides' => array(),
            'case_studies' => array(),
            'tools_templates' => array(),
            'learning_training' => array(),
            'downloads' => array(),
            'support_help' => array(),
            'other_resources' => array()
        );
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $content_lower = strtolower($page->post_content);
            $categorized = false;
            
            // Skip if it's the main resources page
            if (preg_match('/^resources?$/i', $page->post_title)) {
                continue;
            }
            
            // Knowledge Base & Information Resources
            if (preg_match('/(knowledge|knowledge base|information|info|database|repository|library|archive|reference|encyclopedia|glossary|directory|index|catalog|registry|collection|compilation|compendium|resource center|information center|data|facts|statistics|research|studies|analysis|insights|intelligence|findings|discoveries|observations)/i', $title_lower)) {
                $organized['knowledge_base'][] = $page;
                $categorized = true;
            }
            
            // Documentation & Technical Resources
            elseif (preg_match('/(documentation|docs|manual|manuals|handbook|handbooks|specification|specifications|api|api documentation|technical documentation|user manual|admin manual|installation|setup|configuration|readme|getting started|quickstart|quick start|reference|guide book|instruction|instructions)/i', $title_lower)) {
                $organized['documentation'][] = $page;
                $categorized = true;
            }
            
            // Tutorials, Guides & How-To Resources
            elseif (preg_match('/(tutorial|tutorials|guide|guides|how to|how-to|walkthrough|step by step|step-by-step|instructions|lesson|lessons|course|courses|training|workshop|workshops|masterclass|bootcamp|crash course|learn|learning|teach|teaching|educational|education|academy|university|school|class|classes|module|modules|chapter|chapters|unit|units|session|sessions)/i', $title_lower)) {
                $organized['tutorials_guides'][] = $page;
                $categorized = true;
            }
            
            // Case Studies, Examples & Success Stories
            elseif (preg_match('/(case study|case studies|example|examples|sample|samples|demo|demos|demonstration|demonstrations|showcase|portfolio|gallery|success story|success stories|testimonial|testimonials|review|reviews|client story|customer story|project|projects|work|results|outcome|outcomes|achievement|achievements|accomplishment|accomplishments)/i', $title_lower)) {
                $organized['case_studies'][] = $page;
                $categorized = true;
            }
            
            // Tools, Templates & Resources
            elseif (preg_match('/(tool|tools|template|templates|resource|resources|kit|kits|toolkit|toolbox|utility|utilities|calculator|calculators|generator|generators|builder|builders|creator|creators|maker|makers|planner|planners|tracker|trackers|checklist|checklists|worksheet|worksheets|form|forms|spreadsheet|spreadsheets)/i', $title_lower)) {
                $organized['tools_templates'][] = $page;
                $categorized = true;
            }
            
            // Learning & Training Materials
            elseif (preg_match('/(webinar|webinars|seminar|seminars|conference|conferences|event|events|video|videos|podcast|podcasts|audio|recording|recordings|presentation|presentations|slide|slides|deck|decks|ebook|ebooks|book|books|publication|publications|report|reports|whitepaper|whitepapers|paper|papers|study|studies|research|article|articles|post|posts|content|material|materials)/i', $title_lower)) {
                $organized['learning_training'][] = $page;
                $categorized = true;
            }
            
            // Downloads & Digital Assets
            elseif (preg_match('/(download|downloads|file|files|asset|assets|media|digital|pdf|document|documents|attachment|attachments|software|app|application|program|plugin|extension|theme|script|code|source|library|framework|package|bundle|archive|zip|installer|setup|update|patch|upgrade)/i', $title_lower)) {
                $organized['downloads'][] = $page;
                $categorized = true;
            }
            
            // Support & Help Resources
            elseif (preg_match('/(faq|faqs|frequently asked questions|help|help center|support|support center|assistance|customer support|technical support|troubleshoot|troubleshooting|problem|problems|issue|issues|solution|solutions|fix|fixes|repair|maintenance|bug|bugs|error|errors|ticket|tickets|contact|contact us|get help|need help)/i', $title_lower)) {
                $organized['support_help'][] = $page;
                $categorized = true;
            }
            
            // Check for more general resource indicators
            elseif (preg_match('/(resource|library|center|hub|portal|platform|database|repository|collection|archive|directory|index|catalog|registry|compilation|reference|information|data|content|material|guide|manual|handbook|documentation|tutorial|lesson|course|training|education|learning|knowledge|insight|tip|tips|advice|best practice|best practices|strategy|strategies)/i', $title_lower)) {
                $organized['other_resources'][] = $page;
                $categorized = true;
            }
            
            // Content-based detection for pages that might not have obvious titles
            elseif (!$categorized && strlen($content_lower) > 500) {
                // Check content for resource indicators
                if (preg_match_all('/(tutorial|guide|how to|step by step|instruction|learn|teach|example|case study|download|template|tool|resource|help|support|faq|documentation|manual)/i', $content_lower, $matches)) {
                    $match_count = count($matches[0]);
                    if ($match_count >= 3) {
                        // Determine category based on most frequent terms
                        $content_matches = array_count_values(array_map('strtolower', $matches[0]));
                        arsort($content_matches);
                        $primary_term = key($content_matches);
                        
                        if (in_array($primary_term, ['tutorial', 'guide', 'how to', 'step by step', 'instruction', 'learn', 'teach'])) {
                            $organized['tutorials_guides'][] = $page;
                        } elseif (in_array($primary_term, ['example', 'case study'])) {
                            $organized['case_studies'][] = $page;
                        } elseif (in_array($primary_term, ['download', 'template', 'tool'])) {
                            $organized['tools_templates'][] = $page;
                        } elseif (in_array($primary_term, ['help', 'support', 'faq'])) {
                            $organized['support_help'][] = $page;
                        } elseif (in_array($primary_term, ['documentation', 'manual'])) {
                            $organized['documentation'][] = $page;
                        } else {
                            $organized['other_resources'][] = $page;
                        }
                        $categorized = true;
                    }
                }
            }
        }
        
        // Remove empty categories and rename keys for better display
        $filtered = array_filter($organized, function($pages) {
            return !empty($pages);
        });
        
        // Rename categories for better menu display
        $renamed = array();
        $category_labels = array(
            'knowledge_base' => 'Knowledge Base',
            'documentation' => 'Documentation',
            'tutorials_guides' => 'Tutorials & Guides',
            'case_studies' => 'Case Studies',
            'tools_templates' => 'Tools & Templates',
            'learning_training' => 'Learning Materials',
            'downloads' => 'Downloads',
            'support_help' => 'Help & Support',
            'other_resources' => 'Other Resources'
        );
        
        foreach ($filtered as $key => $pages) {
            $renamed[$category_labels[$key]] = $pages;
        }
        
        return $renamed;
    }
    
    /**
     * Generate footer quick links menu with comprehensive detection and smart prioritization
     */
    public function create_footer_quick_links_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Footer Quick Links';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }

        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));

        // Organize footer links with priority-based detection
        $footer_structure = $this->organize_footer_links_by_priority($all_pages);

        // Add Home first (highest priority)
        $this->add_menu_item($menu_id, 'Home', home_url());

        // Add high priority pages (About, Services, Contact)
        foreach ($footer_structure['high_priority'] as $page) {
            $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)));
        }

        // Add medium priority pages (Resources, Support, etc.)
        foreach ($footer_structure['medium_priority'] as $page) {
            $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)));
        }

        // Add legal pages (Privacy, Terms, etc.)
        foreach ($footer_structure['legal_pages'] as $page) {
            $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)));
        }

        // Add company pages if space allows (limited to 3)
        $added_company = 0;
        foreach ($footer_structure['company_pages'] as $page) {
            if ($added_company < 3) {
                $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)));
                $added_company++;
            }
        }

        // Add sitemap and other utility pages
        foreach ($footer_structure['utility_pages'] as $page) {
            $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)));
        }

        // Add default sitemap if no sitemap page found
        if (empty($footer_structure['utility_pages'])) {
            $this->add_menu_item($menu_id, 'Sitemap', home_url('/sitemap/'));
        }

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }

    /**
     * Organize footer links by priority with comprehensive detection
     */
    private function organize_footer_links_by_priority($all_pages) {
        $structure = array(
            'high_priority' => array(),    // About, Services, Contact
            'medium_priority' => array(),  // Blog, Resources, Support, Pricing
            'legal_pages' => array(),      // Privacy, Terms, etc.
            'company_pages' => array(),    // Careers, Team, etc.
            'utility_pages' => array()     // Sitemap, etc.
        );
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $categorized = false;
            
            // HIGH PRIORITY - Essential pages that should appear in most footers
            if (preg_match('/^(about|about us)$/i', $page->post_title)) {
                $structure['high_priority'][] = $page;
                $categorized = true;
            } elseif (preg_match('/^(services?|service)$/i', $page->post_title)) {
                $structure['high_priority'][] = $page;
                $categorized = true;
            } elseif (preg_match('/^(contact|contact us)$/i', $page->post_title)) {
                $structure['high_priority'][] = $page;
                $categorized = true;
            }
            
            // MEDIUM PRIORITY - Important but secondary pages
            elseif (preg_match('/(blog|news|articles|resources?|pricing|prices?|plans?|packages?|support|help|faq|faqs|frequently asked questions|get started|getting started|how it works|features|portfolio|work|projects|case studies|testimonials|reviews)/i', $title_lower)) {
                $structure['medium_priority'][] = $page;
                $categorized = true;
            }
            
            // LEGAL PAGES - Privacy, Terms, etc.
            elseif (preg_match('/(privacy|policy|policies|terms|conditions|terms of service|terms of use|cookie policy|gdpr|data protection|disclaimer|legal|refund policy|return policy|shipping policy|cancellation policy|acceptable use|user agreement|license|copyright|trademark)/i', $title_lower)) {
                $structure['legal_pages'][] = $page;
                $categorized = true;
            }
            
            // COMPANY PAGES - Team, Careers, Company Info
            elseif (preg_match('/(careers?|jobs?|employment|team|our team|staff|leadership|management|founders|company|company information|corporate|business|organization|mission|vision|values|culture|history|story|why choose us|why us|our approach|our philosophy|locations?|offices?|awards|recognition|certifications|partnerships?|investors?|press|media|news)/i', $title_lower)) {
                $structure['company_pages'][] = $page;
                $categorized = true;
            }
            
            // UTILITY PAGES - Sitemap, etc.
            elseif (preg_match('/(sitemap|site map|search|search results|404|error|accessibility|accessibility statement|site credits|credits|acknowledgments|thanks)/i', $title_lower)) {
                $structure['utility_pages'][] = $page;
                $categorized = true;
            }
            
            // Additional detection for pages that might be important for footers
            elseif (!$categorized) {
                // Check for pages that are commonly linked in footers
                if (preg_match('/(home|homepage|main|index|welcome|start|begin|introduction|overview|summary|what we do|our work|solutions?|offerings?|capabilities|expertise|specialties|advantages|benefits|why choose|choose us|client|clients|customer|customers|partner|partners|affiliate|affiliates|vendor|vendors|supplier|suppliers)/i', $title_lower)) {
                    $structure['medium_priority'][] = $page;
                } elseif (preg_match('/(download|downloads|tools?|resources?|library|archive|documentation|docs|manual|guide|tutorial|help center|knowledge base|learning|training|education|academy)/i', $title_lower)) {
                    $structure['medium_priority'][] = $page;
                }
            }
        }
        
        // Sort each category by importance and title
        foreach ($structure as $category => &$pages) {
            if (!empty($pages)) {
                // Sort by custom priority, then alphabetically
                usort($pages, function($a, $b) use ($category) {
                    $priority_order = array();
                    
                    if ($category === 'high_priority') {
                        $priority_order = ['about', 'services', 'contact'];
                    } elseif ($category === 'medium_priority') {
                        $priority_order = ['blog', 'resources', 'pricing', 'support', 'portfolio'];
                    } elseif ($category === 'legal_pages') {
                        $priority_order = ['privacy', 'terms', 'cookie', 'disclaimer'];
                    } elseif ($category === 'company_pages') {
                        $priority_order = ['careers', 'team', 'about us', 'company', 'locations'];
                    }
                    
                    $a_priority = 999;
                    $b_priority = 999;
                    $a_title_lower = strtolower($a->post_title);
                    $b_title_lower = strtolower($b->post_title);
                    
                    // Find priority positions
                    foreach ($priority_order as $index => $term) {
                        if (strpos($a_title_lower, $term) !== false) {
                            $a_priority = $index;
                            break;
                        }
                    }
                    
                    foreach ($priority_order as $index => $term) {
                        if (strpos($b_title_lower, $term) !== false) {
                            $b_priority = $index;
                            break;
                        }
                    }
                    
                    // First sort by priority, then alphabetically
                    if ($a_priority !== $b_priority) {
                        return $a_priority - $b_priority;
                    }
                    
                    return strcmp($a->post_title, $b->post_title);
                });
            }
        }
        
        return $structure;
    }
    
    /**
     * Generate social media menu
     */
    public function create_social_media_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Social Media Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }

        // Common social media platforms
        $social_links = array(
            'Facebook' => 'https://facebook.com',
            'Twitter' => 'https://twitter.com',
            'LinkedIn' => 'https://linkedin.com',
            'Instagram' => 'https://instagram.com',
            'YouTube' => 'https://youtube.com',
            'Pinterest' => 'https://pinterest.com'
        );

        foreach ($social_links as $platform => $url) {
            $this->add_menu_item($menu_id, $platform, $url);
        }

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }

    /**
     * Generate support/help menu with comprehensive detection and smart categorization
     */
    public function create_support_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Support Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }

        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));

        // Find main support page
        $main_support_page = null;
        foreach ($all_pages as $page) {
            if (preg_match('/^support$/i', $page->post_title) || preg_match('/^help$/i', $page->post_title)) {
                $main_support_page = $page;
                break;
            }
        }

        // Add main support link
        $support_url = $main_support_page ? esc_url(get_permalink($main_support_page->ID)) : home_url('/support/');
        $support_parent_id = $this->add_menu_item($menu_id, 'Support', $support_url);

        // Organize support pages into categories
        $organized_support = $this->organize_support_pages_intelligently($all_pages);

        // Build organized support structure
        foreach ($organized_support as $category_name => $pages) {
            if (count($pages) == 1) {
                // Single page - add directly under Support
                $this->add_menu_item($menu_id, $pages[0]->post_title, esc_url(get_permalink($pages[0]->ID)), $support_parent_id);
            } else {
                // Multiple pages - create category
                $category_parent_id = $this->add_menu_item($menu_id, $category_name, '#', $support_parent_id);
                
                foreach ($pages as $page) {
                    $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id);
                    
                    // Add any child pages
                    $child_pages = get_pages(array(
                        'post_status' => 'publish',
                        'parent' => $page->ID
                    ));
                    
                    foreach ($child_pages as $child_page) {
                        $this->add_menu_item($menu_id, $child_page->post_title, esc_url(get_permalink($child_page->ID)), $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id));
                    }
                }
            }
        }

        // Add default contact support link if no contact pages found
        $has_contact = false;
        foreach ($organized_support as $category => $pages) {
            foreach ($pages as $page) {
                if (preg_match('/(contact|get help|reach us)/i', $page->post_title)) {
                    $has_contact = true;
                    break 2;
                }
            }
        }
        
        if (!$has_contact) {
            $this->add_menu_item($menu_id, 'Contact Support', home_url('/contact/'), $support_parent_id);
        }

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }

    /**
     * Intelligently organize support pages by category with comprehensive detection
     */
    private function organize_support_pages_intelligently($all_pages) {
        $organized = array(
            'help_center' => array(),
            'faqs' => array(),
            'documentation' => array(),
            'troubleshooting' => array(),
            'contact_support' => array(),
            'community' => array(),
            'system_status' => array(),
            'other_support' => array()
        );
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $content_lower = strtolower($page->post_content);
            $categorized = false;
            
            // Skip if it's the main support/help page
            if (preg_match('/^(support|help)$/i', $page->post_title)) {
                continue;
            }
            
            // Help Center & General Help Resources
            if (preg_match('/(help center|help centre|assistance|customer service|customer care|user support|general help|getting help|need help|help section|help area|help hub|support hub|help portal|support portal|help desk|service desk|help resources|support resources)/i', $title_lower)) {
                $organized['help_center'][] = $page;
                $categorized = true;
            }
            
            // FAQs & Frequently Asked Questions
            elseif (preg_match('/(faq|faqs|frequently asked questions|frequent questions|common questions|popular questions|q&a|questions and answers|questions|answers|ask|query|queries|inquiries|inquiry)/i', $title_lower)) {
                $organized['faqs'][] = $page;
                $categorized = true;
            }
            
            // Documentation & Manuals
            elseif (preg_match('/(documentation|docs|manual|manuals|handbook|handbooks|user guide|user manual|admin guide|admin manual|technical documentation|api documentation|developer docs|reference|specifications|instructions|how to use|usage guide|operating instructions|setup guide|installation guide|configuration guide|getting started|quick start)/i', $title_lower)) {
                $organized['documentation'][] = $page;
                $categorized = true;
            }
            
            // Troubleshooting & Problem Solving
            elseif (preg_match('/(troubleshoot|troubleshooting|problem|problems|issue|issues|bug|bugs|error|errors|fix|fixes|repair|solution|solutions|resolve|resolution|diagnostic|diagnosis|debugging|bug report|error report|known issues|common problems|technical issues|performance issues|compatibility issues)/i', $title_lower)) {
                $organized['troubleshooting'][] = $page;
                $categorized = true;
            }
            
            // Contact & Direct Support
            elseif (preg_match('/(contact|contact us|contact support|get in touch|reach us|reach out|submit ticket|create ticket|support ticket|help ticket|report issue|report problem|request help|request support|customer support|technical support|live chat|phone support|email support|support form|contact form|help form|feedback|suggestions|complaints)/i', $title_lower)) {
                $organized['contact_support'][] = $page;
                $categorized = true;
            }
            
            // Community & Forums
            elseif (preg_match('/(community|forum|forums|discussion|discussions|user community|support community|help community|community support|user forum|support forum|help forum|message board|bulletin board|group|groups|network|social|peer support|user group|community help)/i', $title_lower)) {
                $organized['community'][] = $page;
                $categorized = true;
            }
            
            // System Status & Service Updates
            elseif (preg_match('/(status|system status|service status|uptime|downtime|outage|outages|maintenance|scheduled maintenance|service updates|system updates|announcements|alerts|notifications|incidents|incident report|service disruption|availability|monitoring)/i', $title_lower)) {
                $organized['system_status'][] = $page;
                $categorized = true;
            }
            
            // Additional support-related detection
            elseif (preg_match('/(support|help|assistance|service|customer|user|technical|tutorial|guide|training|education|learning|knowledge|information|resource|tool|utility|tips|advice|best practices|walkthrough|step by step)/i', $title_lower)) {
                // Check content for more specific categorization
                if (strlen($content_lower) > 300) {
                    if (preg_match_all('/(faq|question|answer)/i', $content_lower, $matches) && count($matches[0]) >= 3) {
                        $organized['faqs'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(troubleshoot|problem|issue|error|fix)/i', $content_lower, $matches) && count($matches[0]) >= 3) {
                        $organized['troubleshooting'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(contact|phone|email|form)/i', $content_lower, $matches) && count($matches[0]) >= 2) {
                        $organized['contact_support'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(manual|guide|instruction|documentation)/i', $content_lower, $matches) && count($matches[0]) >= 2) {
                        $organized['documentation'][] = $page;
                        $categorized = true;
                    }
                }
                
                if (!$categorized) {
                    $organized['other_support'][] = $page;
                    $categorized = true;
                }
            }
        }
        
        // Remove empty categories and rename keys for better display
        $filtered = array_filter($organized, function($pages) {
            return !empty($pages);
        });
        
        // Rename categories for better menu display
        $renamed = array();
        $category_labels = array(
            'help_center' => 'Help Center',
            'faqs' => 'FAQs',
            'documentation' => 'Documentation',
            'troubleshooting' => 'Troubleshooting',
            'contact_support' => 'Contact Support',
            'community' => 'Community',
            'system_status' => 'System Status',
            'other_support' => 'Other Support'
        );
        
        foreach ($filtered as $key => $pages) {
            // Sort pages within each category
            usort($pages, function($a, $b) {
                return strcmp($a->post_title, $b->post_title);
            });
            
            $renamed[$category_labels[$key]] = $pages;
        }
        
        return $renamed;
    }
    
    /**
     * Generate products catalog menu with comprehensive detection and smart categorization
     */
    public function create_products_menu($overwrite = false, $location = '') {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $menu_name = 'Products Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);

        if ($menu_exists && !$overwrite) {
            return new WP_Error('menu_exists', __('Menu already exists. Enable overwrite to update.', 'artitechcore'));
        }

        if ($menu_exists) {
            wp_delete_nav_menu($menu_exists->term_id);
        }

        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return false;
        }

        // Get all published pages
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));

        // Find main products page
        $main_products_page = null;
        foreach ($all_pages as $page) {
            if (preg_match('/^products?$/i', $page->post_title) || preg_match('/^catalog$/i', $page->post_title) || preg_match('/^shop$/i', $page->post_title)) {
                $main_products_page = $page;
                break;
            }
        }

        // Add main products link
        $products_url = $main_products_page ? esc_url(get_permalink($main_products_page->ID)) : home_url('/products/');
        $products_parent_id = $this->add_menu_item($menu_id, 'Products', $products_url);

        // Organize product pages into categories
        $organized_products = $this->organize_product_pages_intelligently($all_pages);

        // Build organized product structure
        foreach ($organized_products as $category_name => $pages) {
            if (count($pages) == 1) {
                // Single page - add directly under Products
                $this->add_menu_item($menu_id, $pages[0]->post_title, esc_url(get_permalink($pages[0]->ID)), $products_parent_id);
            } else {
                // Multiple pages - create category
                $category_parent_id = $this->add_menu_item($menu_id, $category_name, '#', $products_parent_id);
                
                foreach ($pages as $page) {
                    $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id);
                    
                    // Add any child pages
                    $child_pages = get_pages(array(
                        'post_status' => 'publish',
                        'parent' => $page->ID
                    ));
                    
                    foreach ($child_pages as $child_page) {
                        $this->add_menu_item($menu_id, $child_page->post_title, esc_url(get_permalink($child_page->ID)), $this->add_menu_item($menu_id, $page->post_title, esc_url(get_permalink($page->ID)), $category_parent_id));
                    }
                }
            }
        }

        if (!empty($location)) {
            $locations = get_theme_mod('nav_menu_locations');
            $locations[$location] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return $menu_id;
    }
    
    /**
     * Intelligently organize product pages by category with comprehensive detection
     */
    private function organize_product_pages_intelligently($all_pages) {
        $organized = array(
            'product_catalog' => array(),
            'pricing_plans' => array(),
            'product_features' => array(),
            'comparisons' => array(),
            'demos_trials' => array(),
            'specifications' => array(),
            'product_support' => array(),
            'other_products' => array()
        );
        
        foreach ($all_pages as $page) {
            $title_lower = strtolower($page->post_title);
            $content_lower = strtolower($page->post_content);
            $categorized = false;
            
            // Skip if it's the main products/catalog/shop page
            if (preg_match('/^(products?|catalog|shop)$/i', $page->post_title)) {
                continue;
            }
            
            // Product Catalog & Main Product Pages
            if (preg_match('/(product|products|catalog|catalogue|inventory|collection|range|line|series|portfolio|gallery|showcase|offerings|solutions|items|goods|merchandise|models|versions|variants|options|selection|variety)/i', $title_lower)) {
                // Check if it's not pricing/features/etc.
                if (!preg_match('/(pricing|price|plan|feature|comparison|demo|trial|spec|support)/i', $title_lower)) {
                    $organized['product_catalog'][] = $page;
                    $categorized = true;
                }
            }
            
            // Pricing & Plans
            elseif (preg_match('/(pricing|prices?|price list|cost|costs|costing|fee|fees|rate|rates|charge|charges|tariff|tariffs|plan|plans|package|packages|bundle|bundles|tier|tiers|level|levels|subscription|subscriptions|membership|memberships|quote|quotes|quotation|quotations|estimate|estimates|budget|budgets|investment|value|affordable|discount|discounts|sale|sales|offer|offers|deal|deals|promotion|promotions|special|specials|coupon|coupons)/i', $title_lower)) {
                $organized['pricing_plans'][] = $page;
                $categorized = true;
            }
            
            // Product Features & Benefits
            elseif (preg_match('/(feature|features|benefit|benefits|advantage|advantages|capability|capabilities|functionality|function|functions|what it does|how it works|overview|highlights|key points|selling points|unique|special|innovative|advanced|powerful|comprehensive|complete|full)/i', $title_lower)) {
                $organized['product_features'][] = $page;
                $categorized = true;
            }
            
            // Product Comparisons
            elseif (preg_match('/(comparison|compare|vs|versus|difference|differences|alternative|alternatives|competitor|competitors|competitive|benchmark|benchmarking|evaluation|review|reviews|rating|ratings|choose|choice|decision|selection)/i', $title_lower)) {
                $organized['comparisons'][] = $page;
                $categorized = true;
            }
            
            // Demos, Trials & Samples
            elseif (preg_match('/(demo|demos|demonstration|demonstrations|trial|trials|free trial|test|testing|sample|samples|preview|previews|try|try it|try now|test drive|pilot|beta|prototype|mockup|example|examples|showcase|live demo|interactive)/i', $title_lower)) {
                $organized['demos_trials'][] = $page;
                $categorized = true;
            }
            
            // Specifications & Technical Details
            elseif (preg_match('/(specification|specifications|specs|spec|technical|tech|details|requirements|system requirements|compatibility|supported|minimum|recommended|hardware|software|platform|operating system|browser|device|mobile|desktop|api|integration|technical documentation|datasheet|manual)/i', $title_lower)) {
                $organized['specifications'][] = $page;
                $categorized = true;
            }
            
            // Product Support & Help
            elseif (preg_match('/(product support|help|assistance|training|tutorial|tutorials|guide|guides|documentation|docs|manual|manuals|faq|faqs|knowledge base|learning|education|onboarding|getting started|setup|installation|configuration|troubleshooting|support center|help center)/i', $title_lower)) {
                $organized['product_support'][] = $page;
                $categorized = true;
            }
            
            // Additional product-related detection
            elseif (preg_match('/(buy|purchase|order|shop|store|cart|checkout|payment|subscribe|sign up|get started|download|install|upgrade|update|license|licensing|terms|agreement|warranty|guarantee|refund|return)/i', $title_lower)) {
                // Check content for more specific categorization
                if (strlen($content_lower) > 300) {
                    if (preg_match_all('/(price|cost|fee|plan|subscription)/i', $content_lower, $matches) && count($matches[0]) >= 3) {
                        $organized['pricing_plans'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(feature|benefit|advantage|capability)/i', $content_lower, $matches) && count($matches[0]) >= 3) {
                        $organized['product_features'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(demo|trial|test|sample)/i', $content_lower, $matches) && count($matches[0]) >= 2) {
                        $organized['demos_trials'][] = $page;
                        $categorized = true;
                    } elseif (preg_match_all('/(specification|requirement|compatibility)/i', $content_lower, $matches) && count($matches[0]) >= 2) {
                        $organized['specifications'][] = $page;
                        $categorized = true;
                    }
                }
                
                if (!$categorized) {
                    $organized['other_products'][] = $page;
                    $categorized = true;
                }
            }
        }
        
        // Remove empty categories and rename keys for better display
        $filtered = array_filter($organized, function($pages) {
            return !empty($pages);
        });
        
        // Rename categories for better menu display
        $renamed = array();
        $category_labels = array(
            'product_catalog' => 'Product Catalog',
            'pricing_plans' => 'Pricing & Plans',
            'product_features' => 'Features & Benefits',
            'comparisons' => 'Comparisons',
            'demos_trials' => 'Demos & Trials',
            'specifications' => 'Specifications',
            'product_support' => 'Product Support',
            'other_products' => 'Other'
        );
        
        foreach ($filtered as $key => $pages) {
            // Sort pages within each category by priority
            if ($key === 'pricing_plans') {
                // Sort pricing pages by common plan names
                usort($pages, function($a, $b) {
                    $priority_order = ['basic', 'standard', 'pro', 'professional', 'premium', 'enterprise', 'ultimate'];
                    $a_priority = 999;
                    $b_priority = 999;
                    $a_title_lower = strtolower($a->post_title);
                    $b_title_lower = strtolower($b->post_title);
                    
                    foreach ($priority_order as $index => $term) {
                        if (strpos($a_title_lower, $term) !== false) {
                            $a_priority = $index;
                            break;
                        }
                    }
                    
                    foreach ($priority_order as $index => $term) {
                        if (strpos($b_title_lower, $term) !== false) {
                            $b_priority = $index;
                            break;
                        }
                    }
                    
                    if ($a_priority !== $b_priority) {
                        return $a_priority - $b_priority;
                    }
                    
                    return strcmp($a->post_title, $b->post_title);
                });
            } else {
                // Sort alphabetically for other categories
                usort($pages, function($a, $b) {
                    return strcmp($a->post_title, $b->post_title);
                });
            }
            
            $renamed[$category_labels[$key]] = $pages;
        }
        
        return $renamed;
    }
    
    /**
     * Debug function to show which resource pages are being detected
     */
    public function debug_resource_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        echo "<h3>Debug: Resource Page Detection</h3>";
        echo '<p>' . esc_html(sprintf(__('Total pages found: %d', 'artitechcore'), count($all_pages))) . '</p>';
        
        $organized_resources = $this->organize_resource_pages_intelligently($all_pages);
        
        if (!empty($organized_resources)) {
            foreach ($organized_resources as $category => $pages) {
                echo '<h4>' . esc_html(sprintf(__('Category: %s (%d pages)', 'artitechcore'), $category, count($pages))) . '</h4>';
                echo "<ul>";
                foreach ($pages as $page) {
                    echo '<li><strong>' . esc_html($page->post_title) . '</strong> - <a href="' . esc_url(get_permalink($page->ID)) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></li>';
                }
                echo "</ul>";
            }
        } else {
            echo "<p>No resource pages detected.</p>";
        }
        
        return true;
    }
    
    /**
     * Debug function to show which footer link pages are being detected
     */
    public function debug_footer_links_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        echo "<h3>Debug: Footer Links Detection</h3>";
        echo '<p>' . esc_html(sprintf(__('Total pages found: %d', 'artitechcore'), count($all_pages))) . '</p>';
        
        $footer_structure = $this->organize_footer_links_by_priority($all_pages);
        
        if (!empty($footer_structure)) {
            foreach ($footer_structure as $priority => $pages) {
                if (!empty($pages)) {
                    echo '<h4>' . esc_html(sprintf(__('Priority: %s (%d pages)', 'artitechcore'), ucwords(str_replace('_', ' ', $priority)), count($pages))) . '</h4>';
                    echo "<ul>";
                    foreach ($pages as $page) {
                        echo '<li><strong>' . esc_html($page->post_title) . '</strong> - <a href="' . esc_url(get_permalink($page->ID)) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></li>';
                    }
                    echo "</ul>";
                }
            }
        } else {
            echo "<p>No footer link pages detected.</p>";
        }
        
        return true;
    }
    
    /**
     * Debug function to show which support pages are being detected
     */
    public function debug_support_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        echo "<h3>Debug: Support Page Detection</h3>";
        echo '<p>' . esc_html(sprintf(__('Total pages found: %d', 'artitechcore'), count($all_pages))) . '</p>';
        
        $organized_support = $this->organize_support_pages_intelligently($all_pages);
        
        if (!empty($organized_support)) {
            foreach ($organized_support as $category => $pages) {
                echo '<h4>' . esc_html(sprintf(__('Category: %s (%d pages)', 'artitechcore'), $category, count($pages))) . '</h4>';
                echo "<ul>";
                foreach ($pages as $page) {
                    echo '<li><strong>' . esc_html($page->post_title) . '</strong> - <a href="' . esc_url(get_permalink($page->ID)) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></li>';
                }
                echo "</ul>";
            }
        } else {
            echo "<p>No support pages detected.</p>";
        }
        
        return true;
    }
    
    /**
     * Debug function to show which product pages are being detected
     */
    public function debug_product_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $all_pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC'
        ));
        
        echo "<h3>Debug: Product Page Detection</h3>";
        echo '<p>' . esc_html(sprintf(__('Total pages found: %d', 'artitechcore'), count($all_pages))) . '</p>';
        
        $organized_products = $this->organize_product_pages_intelligently($all_pages);
        
        if (!empty($organized_products)) {
            foreach ($organized_products as $category => $pages) {
                echo '<h4>' . esc_html(sprintf(__('Category: %s (%d pages)', 'artitechcore'), $category, count($pages))) . '</h4>';
                echo "<ul>";
                foreach ($pages as $page) {
                    echo '<li><strong>' . esc_html($page->post_title) . '</strong> - <a href="' . esc_url(get_permalink($page->ID)) . '" target="_blank">' . esc_html__('View', 'artitechcore') . '</a></li>';
                }
                echo "</ul>";
            }
        } else {
            echo "<p>No product pages detected.</p>";
        }
        
        return true;
    }
    
    /**
     * Debug function to show detection for all menu types at once
     */
    public function debug_all_menu_detection() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        echo "<div style='max-width: 1200px; margin: 20px auto;'>";
        echo "<h2>ArtitechCore Menu Generator - Complete Detection Debug</h2>";
        
        // Service Detection (existing)
        $this->debug_service_detection();
        echo "<hr style='margin: 30px 0;'>";
        
        // Resource Detection
        $this->debug_resource_detection();
        echo "<hr style='margin: 30px 0;'>";
        
        // Footer Links Detection
        $this->debug_footer_links_detection();
        echo "<hr style='margin: 30px 0;'>";
        
        // Support Detection
        $this->debug_support_detection();
        echo "<hr style='margin: 30px 0;'>";
        
        // Product Detection
        $this->debug_product_detection();
        
        echo "</div>";
        
        return true;
    }
}

// Initialize menu generator
$artitechcore_menu_generator = ArtitechCore_Menu_Generator::get_instance();

// Wrapper functions for admin interface
// Wrapper functions for admin interface - Updated to support Overwrite and Location
function artitechcore_generate_universal_bottom_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_universal_bottom_menu($overwrite, $location);
}

function artitechcore_generate_services_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_services_menu($overwrite, $location);
}

function artitechcore_generate_company_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_company_menu($overwrite, $location);
}

function artitechcore_generate_main_navigation_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_main_navigation_menu($overwrite, $location);
}

function artitechcore_generate_resources_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_resources_menu($overwrite, $location);
}

function artitechcore_generate_footer_quick_links_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_footer_quick_links_menu($overwrite, $location);
}

function artitechcore_generate_social_media_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_social_media_menu($overwrite, $location);
}

function artitechcore_generate_support_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_support_menu($overwrite, $location);
}

function artitechcore_generate_products_menu($overwrite = false, $location = '') {
    global $artitechcore_menu_generator;
    return $artitechcore_menu_generator->create_products_menu($overwrite, $location);
}

