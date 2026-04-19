<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Create pages from CSV file
function artitechcore_create_pages_from_csv($file) {
    try {
        // 1. Validate file upload
        if (!is_uploaded_file($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $error_message = artitechcore_get_upload_error_message($file['error']);
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            return;
        }

        // 2. Check file size (5MB limit)
        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_file_size) {
            $file_size_mb = round($file['size'] / (1024 * 1024), 2);
            $max_size_mb = round($max_file_size / (1024 * 1024), 2);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('File size too large. Your file is %s MB, but the maximum allowed size is %s MB. Please reduce the file size and try again.', 'artitechcore'),
                    $file_size_mb,
                    $max_size_mb
                ) . '</p></div>';
            return;
        }

        // 3. Additional file size validation using filesize()
        $actual_file_size = filesize($file['tmp_name']);
        if ($actual_file_size === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to determine file size. Please try again.', 'artitechcore') . '</p></div>';
            return;
        }

        if ($actual_file_size > $max_file_size) {
            $file_size_mb = round($actual_file_size / (1024 * 1024), 2);
            $max_size_mb = round($max_file_size / (1024 * 1024), 2);
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('File size too large. Your file is %s MB, but the maximum allowed size is %s MB. Please reduce the file size and try again.', 'artitechcore'),
                    $file_size_mb,
                    $max_size_mb
                ) . '</p></div>';
            return;
        }

        // 4. Check if file is empty
        if ($actual_file_size === 0) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('The uploaded file is empty. Please upload a valid CSV file with content.', 'artitechcore') . '</p></div>';
            return;
        }

        // 5. Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to verify file type. Please try again.', 'artitechcore') . '</p></div>';
            return;
        }

        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type === false) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Unable to read file type. Please try again.', 'artitechcore') . '</p></div>';
            return;
        }

        // Allow various CSV MIME types
        $allowed_mime_types = [
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'text/comma-separated-values'
        ];

        if (!in_array($mime_type, $allowed_mime_types)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('Invalid file type. Expected CSV file, but received %s. Please upload a valid CSV file.', 'artitechcore'),
                    esc_html($mime_type)
                ) . '</p></div>';
            return;
        }

        // 6. Additional file extension check
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                sprintf(
                    esc_html__('Invalid file extension. Expected .csv file, but received .%s. Please upload a valid CSV file.', 'artitechcore'),
                    esc_html($file_extension)
                ) . '</p></div>';
            return;
        }

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ArtitechCore CSV File Validation Error: ' . $e->getMessage());
        }
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('An error occurred during file validation. Please try again.', 'artitechcore') . '</p></div>';
        return;
    }

    // 7. Process CSV content
    set_time_limit(0); // Prevent timeouts for large imports
    
    $created_pages = 0;
    $page_map = []; // To map titles to page IDs for parent lookup
    $row_count = 0;
    
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $header = fgetcsv($handle);
        
        if (!$header) {
             echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('The CSV file is empty or unreadable.', 'artitechcore') . '</p></div>';
             fclose($handle);
             return;
        }

        // Remove BOM if present from first header column
        if (isset($header[0]) && substr($header[0], 0, 3) === pack('CCC', 0xef, 0xbb, 0xbf)) {
            $header[0] = substr($header[0], 3);
        }
        
        $header = array_map('trim', $header); // Trim whitespace from headers

        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_count++;
            if ($row_count > 10000) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Row limit exceeded (10,000). Processing stopped.', 'artitechcore') . '</p></div>';
                 break;
            }

            if (count($header) !== count($row)) {
                continue; // Skip malformed rows
            }

            $row_data = array_combine($header, $row);

            $post_title = isset($row_data['post_title']) ? sanitize_text_field(wp_unslash($row_data['post_title'])) : '';
            if (empty($post_title)) {
                continue; // A title is required
            }

            $post_parent_title = isset($row_data['post_parent']) ? sanitize_text_field(wp_unslash($row_data['post_parent'])) : '';
            $meta_description = isset($row_data['meta_description']) ? sanitize_textarea_field(wp_unslash($row_data['meta_description'])) : '';
            $featured_image_url = isset($row_data['featured_image']) ? esc_url_raw(wp_unslash($row_data['featured_image'])) : '';
            $page_template = isset($row_data['page_template']) ? sanitize_text_field(wp_unslash($row_data['page_template'])) : '';
            $post_status = isset($row_data['post_status']) ? sanitize_key(wp_unslash($row_data['post_status'])) : 'publish';
            
            // Use custom slug if provided, otherwise generate SEO-optimized slug
            $post_name = isset($row_data['slug']) && !empty($row_data['slug']) 
                ? sanitize_title(wp_unslash($row_data['slug']))
                : artitechcore_generate_seo_slug($post_title);

            // Determine parent ID
            $parent_id = 0;
            if (!empty($post_parent_title) && isset($page_map[$post_parent_title])) {
                $parent_id = $page_map[$post_parent_title];
            }

            // Create page
            $new_page = array(
                'post_title'   => $post_title,
                'post_name'    => $post_name,
                'post_content' => '',
                'post_status'  => $post_status,
                'post_type'    => 'page',
                'post_parent'  => $parent_id,
                'page_template' => $page_template,
                'post_excerpt'  => $meta_description
            );
            $page_id = wp_insert_post($new_page);

            if ($page_id && !is_wp_error($page_id)) {
                $created_pages++;
                $page_map[$post_title] = $page_id; // Map title to ID for future parent lookups

                // Set featured image with SEO metadata
                if (!empty($featured_image_url)) {
                    $image_title = "Featured Image for " . sanitize_text_field($post_title);
                    $keywords = artitechcore_extract_primary_keywords($post_title);
                    $image_alt = "Visual representation of " . $keywords . " concept";
                    $image_description = "Featured image for " . sanitize_text_field($post_title) . " page";
                    
                    artitechcore_set_featured_image($page_id, $featured_image_url, $image_title, $image_alt, $image_description);
                }

                // Generate Schema Markup (Optimized mode - no AI analysis)
                if (function_exists('artitechcore_generate_schema_markup')) {
                    artitechcore_generate_schema_markup($page_id, false);
                }
            }
        }
        fclose($handle);
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('An error occurred while reading the CSV file.', 'artitechcore') . '</p></div>';
        return;
    }

    if ($created_pages > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d pages created successfully from the CSV file!', 'artitechcore'), absint($created_pages)) . '</p></div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('No pages were created from the CSV file. Please check the file format and content.', 'artitechcore') . '</p></div>';
    }
}

// Get user-friendly upload error message
function artitechcore_get_upload_error_message($error_code) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => __('File exceeds the maximum upload size allowed by the server configuration.', 'artitechcore'),
        UPLOAD_ERR_FORM_SIZE => __('File exceeds the maximum upload size specified in the form.', 'artitechcore'),
        UPLOAD_ERR_PARTIAL => __('File was only partially uploaded. Please try again.', 'artitechcore'),
        UPLOAD_ERR_NO_FILE => __('No file was uploaded. Please select a file to upload.', 'artitechcore'),
        UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder on the server. Please contact the administrator.', 'artitechcore'),
        UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk. Please try again.', 'artitechcore'),
        UPLOAD_ERR_EXTENSION => __('File upload was stopped by a PHP extension. Please contact the administrator.', 'artitechcore'),
    ];

    return isset($error_messages[$error_code]) ? $error_messages[$error_code] : __('Unknown upload error occurred. Please try again.', 'artitechcore');
}

// Get maximum allowed file size for display
function artitechcore_get_max_file_size_display() {
    $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
    $max_size_mb = round($max_file_size / (1024 * 1024), 2);
    return sprintf(__('Maximum file size: %s MB', 'artitechcore'), $max_size_mb);
}

// Validate file size before upload (client-side validation helper)
function artitechcore_validate_file_size_before_upload($file_size) {
    $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
    return $file_size <= $max_file_size;
}
