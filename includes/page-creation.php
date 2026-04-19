<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Create pages from manual input
function artitechcore_create_pages_manually($titles_str) {
    if (!current_user_can('publish_pages')) {
        wp_die(esc_html__('You do not have sufficient permissions to create pages.', 'artitechcore'));
    }

    $titles = explode("\n", sanitize_textarea_field($titles_str));
    $titles = array_map('trim', $titles);

    set_time_limit(0); // Prevent timeouts for large bulk operations

    $parent_id_stack = [];
    $created_pages = 0;

    foreach ($titles as $title) {
        if (!empty($title)) {
            $depth = 0;
            $meta_description = '';
            $featured_image_url = '';
            $page_template = '';
            $post_status = 'publish';

            // Extract meta description
            if (strpos($title, ':+') !== false) {
                list($title, $meta_description) = explode(':+', $title, 2);
                $meta_description = trim($meta_description);
            }

            // Extract featured image URL
            if (strpos($title, ':*') !== false) {
                list($title, $featured_image_url) = explode(':*', $title, 2);
                $featured_image_url = trim($featured_image_url);
            }

            // Extract page template
            if (strpos($title, '::template=') !== false) {
                list($title, $template_part) = explode('::template=', $title, 2);
                $page_template = sanitize_text_field(trim($template_part));
            }

            // Extract post status
            if (strpos($title, '::status=') !== false) {
                list($title, $status_part) = explode('::status=', $title, 2);
                $post_status = sanitize_key(trim($status_part));
            }

            // Calculate depth
            while (substr($title, 0, 1) === '-') {
                $title = substr($title, 1);
                $depth++;
            }
            $title = trim($title);

            // Determine parent ID
            $parent_id = ($depth > 0 && isset($parent_id_stack[$depth - 1])) ? $parent_id_stack[$depth - 1] : 0;

            // Generate SEO-optimized slug
            $post_name = artitechcore_generate_seo_slug($title);
            
            // Create page
            $new_page = array(
                'post_title'   => wp_strip_all_tags($title),
                'post_name'    => $post_name,
                'post_content' => '',
                'post_status'  => $post_status,
                'post_type'    => 'page',
                'post_parent'  => $parent_id,
                'page_template' => $page_template,
                'post_excerpt'  => $meta_description
            );
            $page_id = wp_insert_post($new_page);

            if ($page_id) {
                $created_pages++;
                // Set featured image with SEO metadata
                if (!empty($featured_image_url)) {
                    $image_title = "Featured Image for " . sanitize_text_field($title);
                    $keywords = artitechcore_extract_primary_keywords($title);
                    $image_alt = "Visual representation of " . $keywords . " concept";
                    $image_description = "Featured image for " . sanitize_text_field($title) . " page";
                    
                    artitechcore_set_featured_image($page_id, $featured_image_url, $image_title, $image_alt, $image_description);
                }

                // Generate schema markup for the new page
                $auto_generate = get_option('artitechcore_auto_schema_generation', true);
                if ($auto_generate) {
                    artitechcore_generate_schema_markup($page_id);
                }

                // Update parent stack
                $parent_id_stack[$depth] = $page_id;
                $parent_id_stack = array_slice($parent_id_stack, 0, $depth + 1);
            }
        }
    }

    if ($created_pages > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d pages created successfully!', 'artitechcore'), absint($created_pages)) . '</p></div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('No pages were created. Please check your input.', 'artitechcore') . '</p></div>';
    }
}

// Generate SEO-optimized slug
function artitechcore_generate_seo_slug($title, $max_length = 72) {
    // Convert to lowercase
    $slug = strtolower($title);
    
    // Replace spaces with hyphens
    $slug = str_replace(' ', '-', $slug);
    
    // Remove special characters, keep only alphanumeric and hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from beginning and end
    $slug = trim($slug, '-');
    
    // Limit to max length while preserving word boundaries
    if (strlen($slug) > $max_length) {
        $slug = substr($slug, 0, $max_length);
        // Don't end with a hyphen
        $slug = rtrim($slug, '-');
    }
    
    return $slug;
}

// Set featured image with SEO metadata
if (!function_exists('artitechcore_set_featured_image')) {
    function artitechcore_set_featured_image($post_id, $image_url, $image_title = '', $image_alt = '', $image_description = '') {
    // Check if the image URL is valid
    if (filter_var($image_url, FILTER_VALIDATE_URL) === FALSE) {
        return;
    }

    // 1. Check if we already downloaded this image (Deduplication)
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => '_artitechcore_source_url',
                'value'   => $image_url,
                'compare' => '='
            )
        )
    );
    $existing = new WP_Query($args);
    if ($existing->have_posts()) {
        $attach_id = $existing->posts[0]->ID;
        set_post_thumbnail($post_id, $attach_id);
        // Update alt text/title if provided on this reuse instance (optional, but good for SEO variation? No, keeps orig)
        return $attach_id;
    }

    // Use WordPress HTTP API to fetch the image
    $timeout = defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 30; // Use global timeout constants
    $response = wp_remote_get($image_url, ['timeout' => $timeout]);
    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return; // Failed to download image
    }

    $image_data = wp_remote_retrieve_body($response);
    $filename = sanitize_file_name(basename($image_url));
    
    // Use WordPress wp_upload_bits function which handles filesystem operations properly
    $upload = wp_upload_bits($filename, null, $image_data);
    
    if ($upload['error']) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore: Failed to upload image - ' . $upload['error']);
        }
        return;
    }
    
    $file = $upload['file'];
    $wp_filetype = wp_check_filetype($filename, null);
    
    // Use provided title or fallback to sanitized filename
    $attachment_title = !empty($image_title) ? sanitize_text_field($image_title) : sanitize_file_name($filename);
    
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $attachment_title,
        'post_content'   => !empty($image_description) ? sanitize_textarea_field($image_description) : '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Store source URL for deduplication
    update_post_meta($attach_id, '_artitechcore_source_url', $image_url);
    
    // Set alt text if provided
    if (!empty($image_alt)) {
        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($image_alt));
    }
    
    set_post_thumbnail($post_id, $attach_id);
    
    return $attach_id;
}
}
