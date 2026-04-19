<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Keyword Density Analyzer for ArtitechCore Plugin
 * Provides comprehensive keyword analysis functionality
 */

class ArtitechCore_Keyword_Analyzer {
    
    private $plugin_url;
    private $plugin_path;
    
    public function __construct() {
        $this->plugin_url = ARTITECHCORE_PLUGIN_URL;
        $this->plugin_path = ARTITECHCORE_PLUGIN_PATH;
        
        // Initialize hooks
        add_action('wp_ajax_artitechcore_analyze_keywords', array($this, 'analyze_keywords_ajax'));
        add_action('wp_ajax_artitechcore_get_pages', array($this, 'get_pages_ajax'));
        add_action('wp_ajax_artitechcore_export_keyword_analysis', array($this, 'export_analysis_ajax'));
        add_action('wp_ajax_artitechcore_ai_expand_keywords', array($this, 'ai_expand_keywords_ajax'));
    }
    
    /**
     * Get all published pages for dropdown
     */
    public function get_pages_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('artitechcore_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'artitechcore'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'artitechcore'));
        }
        
        $pages = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => 500, // Limit to 500 to prevent memory exhaustion on large sites
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $formatted_pages = array();
        foreach ($pages as $page) {
            $formatted_pages[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'type' => $page->post_type
            );
        }
        
        wp_send_json_success($formatted_pages);
    }
    
    /**
     * Analyze keywords for a specific page
     */
    public function analyze_keywords_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('artitechcore_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'artitechcore'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'artitechcore'));
        }
        
        // Validate and sanitize input data
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        $keywords_raw = isset($_POST['keywords']) ? $_POST['keywords'] : '';
        $keywords_input = '';
        
        if (!empty($keywords_raw)) {
            if (is_array($keywords_raw)) {
                // Support both flat array and { clusters: [...] } format
                $clusters = isset($keywords_raw['clusters']) ? $keywords_raw['clusters'] : $keywords_raw;
                
                $keywords_input = array();
                foreach ((array)$clusters as $cluster) {
                    if (is_array($cluster)) {
                        $keywords_input[] = [
                            'seed' => sanitize_text_field($cluster['seed'] ?? ''),
                            'variations' => isset($cluster['variations']) ? array_map('sanitize_text_field', (array)$cluster['variations']) : []
                        ];
                    }
                }
            } else {
                $keywords_input = sanitize_textarea_field($keywords_raw);
            }
        }
        
        // Additional validation
        if (empty($page_id) || $page_id <= 0) {
            wp_send_json_error(__('Invalid page ID provided.', 'artitechcore'));
        }
        
        if (empty($keywords_input)) {
            wp_send_json_error(__('Keywords are required for analysis.', 'artitechcore'));
        }
        
        // Check if page exists and user has access
        $page = get_post($page_id);
        if (!$page || $page->post_status !== 'publish') {
            wp_send_json_error(__('Page not found or not accessible.', 'artitechcore'));
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit()) {
            wp_send_json_error(__('Too many requests. Please wait a moment before trying again.', 'artitechcore'));
        }
        
        try {
            // Support both is_ai and ai_superpowers for backward compatibility during transition
            $is_ai = (isset($_POST['ai_superpowers']) && $_POST['ai_superpowers'] === 'true') || 
                     (isset($_POST['is_ai']) && $_POST['is_ai'] === 'true');
            $intent = isset($_POST['intent']) ? sanitize_text_field($_POST['intent']) : '';
            $analysis = $this->analyze_page_keywords($page_id, $keywords_input, $is_ai, $intent);
            
            if ($analysis) {
                // Log successful analysis
                $this->log_analysis_activity($page_id, $keywords_input, true, '', $is_ai);
                wp_send_json_success($analysis);
            } else {
                $this->log_analysis_activity($page_id, $keywords_input, false, 'Analysis failed', $is_ai);
                wp_send_json_error(__('Failed to analyze keywords. Please try again.', 'artitechcore'));
            }
        } catch (Exception $e) {
            // Log error
            $this->log_analysis_activity($page_id, $keywords_input, false, $e->getMessage(), $isset_ai ?? false);
            wp_send_json_error(__('An error occurred during analysis. Please try again.', 'artitechcore'));
        }
    }
    
    /**
     * AI Semantic Expansion AJAX Handler
     */
    public function ai_expand_keywords_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('artitechcore_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'artitechcore'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'artitechcore'));
        }
        
        $keywords_input = isset($_POST['keywords']) ? sanitize_textarea_field($_POST['keywords']) : '';
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        if (empty($keywords_input)) {
            wp_send_json_error(__('Please enter at least one seed keyword.', 'artitechcore'));
        }
        
        // Extract up to 10 seeds
        $seeds = $this->extract_keywords($keywords_input);
        if (count($seeds) > 10) {
            $seeds = array_slice($seeds, 0, 10);
        }
        
        $page = get_post($page_id);
        $page_title = $page ? $page->post_title : 'Expert Article';
        
        // Call AI for expansion (1:4 ratio)
        $expansion = $this->get_ai_expansion($seeds, $page_title);
        
        if (!$expansion) {
            wp_send_json_error(__('AI expansion failed. Please check your API settings.', 'artitechcore'));
        }
        
        wp_send_json_success($expansion);
    }
    
    /**
     * Get AI variations for seeds
     */
    private function get_ai_expansion($seeds, $page_title) {
        $provider = get_option('artitechcore_ai_provider', 'openai');
        $api_key = get_option('artitechcore_' . $provider . '_api_key');
        
        if (empty($api_key)) {
            return false;
        }
        
        $seeds_str = implode(', ', $seeds);
        $prompt = "You are an SEO expert. For each of the following seed keywords, provided 4 highly relevant semantic variations (synonyms, LSI terms, or related entities) that a professional writer would use naturally in a high-quality article about \"{$page_title}\". 
        
        Seed Keywords: {$seeds_str}
        
        Return EXACTLY valid JSON in this format:
        {
          \"clusters\": [
            { \"seed\": \"seed keyword\", \"variations\": [\"var1\", \"var2\", \"var3\", \"var4\"] },
            ...
          ],
          \"intent\": \"Informational|Transactional|Navigational\"
        }
        
        Important Guidelines:
        1. Maximum 10 seeds. Provide exactly 4 variations per seed.
        2. Semantic Inclusion: Include common singular/plural variations and near-synonyms (e.g., if seed is 'tooth cleaning', variations SHOULD include 'teeth cleaning').
        3. Intent Consistency: Ensure variations match the topical intent of the seed.
        4. Return ONLY JSON.";
        
        $result = null;
        if ($provider === 'gemini') {
            $result = $this->call_gemini_json($prompt, $api_key);
        } elseif ($provider === 'openai') {
            $result = $this->call_openai_json($prompt, $api_key);
        } elseif ($provider === 'deepseek') {
            $result = $this->call_deepseek_json($prompt, $api_key);
        }
        
        return $result;
    }
    
    /**
     * Export keyword analysis results
     */
    public function export_analysis_ajax() {
        // Verify nonce for security
        if (!check_ajax_referer('artitechcore_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'artitechcore'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to access this feature.', 'artitechcore'));
        }
        
        // Validate and sanitize input data
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
        $analysis_data_raw = isset($_POST['analysis_data']) ? $_POST['analysis_data'] : '';
        
        // Validate format
        if (!in_array($format, array('csv', 'json'))) {
            wp_send_json_error(__('Invalid export format specified.', 'artitechcore'));
        }
        
        // Validate and sanitize analysis data
        if (empty($analysis_data_raw)) {
            wp_send_json_error(__('No analysis data provided for export.', 'artitechcore'));
        }
        
        // Decode and validate JSON data
        $analysis_data = json_decode(stripslashes($analysis_data_raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid analysis data format.', 'artitechcore'));
        }
        
        // Validate data structure
        if (!$this->validate_analysis_data($analysis_data)) {
            wp_send_json_error(__('Invalid analysis data structure.', 'artitechcore'));
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit('export')) {
            wp_send_json_error(__('Too many export requests. Please wait a moment before trying again.', 'artitechcore'));
        }
        
        try {
            $this->export_analysis($format, $analysis_data);
        } catch (Exception $e) {
            // Log error
            error_log('ArtitechCore Export Error: ' . $e->getMessage());
            wp_send_json_error(__('Export failed. Please try again.', 'artitechcore'));
        }
    }
    
    /**
     * Check rate limiting for AJAX requests
     */
    private function check_rate_limit($action = 'analysis') {
        $user_id = get_current_user_id();
        $transient_key = 'artitechcore_rate_limit_' . $action . '_' . $user_id;
        
        $requests = get_transient($transient_key);
        if ($requests === false) {
            $requests = 0;
        }
        
        // Allow 10 requests per minute for analysis, 5 for export
        $limit = ($action === 'export') ? 5 : 10;
        
        if ($requests >= $limit) {
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $requests + 1, 60); // 60 seconds
        
        return true;
    }
    
    /**
     * Log analysis activity for security monitoring
     */
    private function log_analysis_activity($page_id, $keywords, $success, $error_message = '', $is_ai = false) {
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        
        $log_data = array(
            'user_id' => $user_id,
            'page_id' => $page_id,
            'keywords_count' => is_array($keywords) ? count($keywords) : count($this->extract_keywords($keywords)),
            'success' => $success,
            'error_message' => $error_message,
            'ip_address' => $user_ip,
            'timestamp' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'is_ai' => $is_ai ? 1 : 0
        );
        
        // Log to WordPress error log for security monitoring
        error_log('ArtitechCore Analysis Activity: ' . json_encode($log_data));
        
        // Store in database for detailed tracking (optional)
        $this->store_analysis_log($log_data);
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Store analysis log in database
     */
    private function store_analysis_log($log_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'artitechcore_generation_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'page_id' => $log_data['page_id'],
                'generation_type' => 'keyword_analysis',
                'ai_provider' => 'none',
                'tokens_used' => 0,
                'generation_time' => $log_data['timestamp'],
                'success' => $log_data['success'] ? 1 : 0,
                'error_message' => $log_data['error_message']
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d', '%s')
        );
    }
    
    /**
     * Validate analysis data structure
     */
    private function validate_analysis_data($data) {
        // Check required structure
        if (!is_array($data)) {
            return false;
        }
        
        $required_keys = array('page_info', 'keywords', 'summary');
        foreach ($required_keys as $key) {
            if (!isset($data[$key])) {
                return false;
            }
        }
        
        // Validate page_info structure
        $page_info_required = array('id', 'title', 'url', 'type', 'word_count', 'analysis_date');
        foreach ($page_info_required as $key) {
            if (!isset($data['page_info'][$key])) {
                return false;
            }
        }
        
        // Validate keywords is array
        if (!is_array($data['keywords'])) {
            return false;
        }
        
        // Validate summary structure
        $summary_required = array('total_keywords', 'keywords_found', 'total_words', 'average_density');
        foreach ($summary_required as $key) {
            if (!isset($data['summary'][$key])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Main keyword analysis function with performance optimizations
     */
    public function analyze_page_keywords($page_id, $keywords_input, $is_ai = false, $intent = '') {
        // Start memory monitoring
        $start_memory = memory_get_usage();
        
        // Get page content
        $page = get_post($page_id);
        if (!$page) {
            return false;
        }
        
        // Extract and clean keywords
        if ($is_ai && is_array($keywords_input)) {
            // keywords_input is already our expanded cluster [{seed, variations}, ...]
            $clusters = $keywords_input;
            $all_keywords = array();
            foreach ($clusters as $c) {
                $all_keywords[] = $c['seed'];
                $all_keywords = array_merge($all_keywords, $c['variations']);
            }
            $keywords = array_unique($all_keywords);
        } else {
            $clusters = array();
            $keywords = $this->extract_keywords($keywords_input);
        }

        if (empty($keywords)) {
            return false;
        }
        
        // Limit keywords for performance (max 100 in AI mode, 50 in normal)
        $limit = $is_ai ? 100 : 50;
        if (count($keywords) > $limit) {
            $keywords = array_slice($keywords, 0, $limit);
        }
        
        // Get page content for analysis with caching
        $content_data = $this->extract_page_content($page);
        $content_data_normalized = $this->normalize_content_data($content_data);
        
        // Truncate content if extremely large to prevent server hang (30+ years experience safety)
        $content_size = strlen($content_data['full_content']);
        if ($content_size > 500000) { // 500KB hard limit
            $content_data['full_content'] = substr($content_data['full_content'], 0, 500000);
            $content_data['content'] = substr($content_data['content'], 0, 400000);
            error_log('ArtitechCore: Content truncated for page ID: ' . $page_id);
        }
        
        // Analyze each keyword with progress tracking
        $results = array();
        $total_words = $this->count_total_words($content_data['full_content']);
        
        // Pre-process content for better performance
        $content_lower = strtolower($content_data['full_content']);
        $content_words = $this->extract_words($content_lower);
        
        foreach ($keywords as $index => $keyword) {
            // Memory check every 10 keywords
            if ($index % 10 === 0) {
                $current_memory = memory_get_usage();
                if (($current_memory - $start_memory) > 50 * 1024 * 1024) { // 50MB limit
                    error_log('ArtitechCore: Memory limit reached during keyword analysis');
                    break;
                }
            }
            
            // Normalize keyword for smarter matching
            $keyword_analysis = $this->analyze_single_keyword_optimized($keyword, $content_data, $total_words, $content_lower, $content_words, $content_data_normalized);
            if ($keyword_analysis) {
                $results[] = $keyword_analysis;
            }
        }
        
        // Sort by density (highest first)
        usort($results, function($a, $b) {
            return $b['density'] <=> $a['density'];
        });
        
        // Log memory usage
        $end_memory = memory_get_usage();
        $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
        error_log('ArtitechCore: Keyword analysis memory usage: ' . round($memory_used, 2) . 'MB');
        
        return array(
            'page_info' => array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'type' => $page->post_type,
                'word_count' => $total_words,
                'analysis_date' => current_time('Y-m-d H:i:s'),
                'content_size' => $content_size,
                'memory_used' => round($memory_used, 2)
            ),
            'keywords' => $results,
            'summary' => $this->generate_summary($results, $total_words, $is_ai, $clusters, $intent)
        );
    }

    /**
     * Normalize content data for semantic matching
     */
    private function normalize_content_data($content_data) {
        return array(
            'title' => $this->normalize_term($content_data['title']),
            'content' => $this->normalize_term($content_data['content']),
            'meta_description' => $this->normalize_term($content_data['meta_description']),
            'excerpt' => $this->normalize_term($content_data['excerpt']),
            'headings' => array_map(array($this, 'normalize_term'), $content_data['headings']),
            'full_content' => $this->normalize_term($content_data['full_content'])
        );
    }

    /**
     * Basic term normalization for better matching (singularization + suffix strip)
     */
    private function normalize_term($term) {
        $term = strtolower(trim($term));
        if (empty($term)) return '';

        // Basic lemmatization heuristics (30+ years experience)
        // 1. Remove plural 's' at end of words (simple version)
        $term = preg_replace('/(\w+)s\b/i', '$1', $term);
        // 2. Remove common suffixes for broader matching
        $term = preg_replace('/(\w+)ing\b/i', '$1', $term);
        $term = preg_replace('/(\w+)ion\b/i', '$1', $term);
        $term = preg_replace('/(\w+)ed\b/i', '$1', $term);
        
        // 3. Special case for tooth/teeth
        $term = str_replace('teeth', 'tooth', $term);
        
        return $term;
    }
    
    /**
     * Extract keywords from input text
     */
    private function extract_keywords($keywords_input) {
        // Split by newlines and commas
        $lines = preg_split('/[\r\n]+/', $keywords_input);
        $keywords = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Split by commas if present
            $comma_separated = explode(',', $line);
            foreach ($comma_separated as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword) && strlen($keyword) > 1) {
                    $keywords[] = $keyword;
                }
            }
        }
        
        // Remove duplicates and return
        return array_unique($keywords);
    }
    
    /**
     * Extract all relevant content from a page
     */
    private function extract_page_content($page) {
        // Get meta description
        $meta_description = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true);
        if (empty($meta_description)) {
            $meta_description = get_post_meta($page->ID, '_aioseo_description', true);
        }
        
        // Get excerpt
        $excerpt = $page->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words($page->post_content, 55);
        }
        
        // Extract headings
        $headings = $this->extract_headings($page->post_content);
        
        // Clean main content
        $clean_content = $this->clean_content($page->post_content);
        
        // Combine all content for analysis
        $full_content = implode(' ', array(
            $page->post_title,
            $meta_description,
            $excerpt,
            $clean_content,
            implode(' ', $headings)
        ));
        
        return array(
            'title' => $page->post_title,
            'content' => $clean_content,
            'meta_description' => $meta_description,
            'excerpt' => $excerpt,
            'headings' => $headings,
            'full_content' => $full_content
        );
    }
    
    /**
     * Extract headings from content
     */
    private function extract_headings($content) {
        $headings = array();
        
        // Match h1-h6 tags
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $heading) {
                $clean_heading = wp_strip_all_tags($heading);
                if (!empty($clean_heading)) {
                    $headings[] = $clean_heading;
                }
            }
        }
        
        return $headings;
    }
    
    /**
     * Clean content by removing HTML and normalizing text
     */
    private function clean_content($content) {
        // Remove HTML tags
        $content = wp_strip_all_tags($content);
        
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
    
    /**
     * Count total words in content
     */
    private function count_total_words($content) {
        $words = str_word_count($content, 0, '0123456789');
        return $words;
    }
    
    /**
     * Extract words from content for better analysis
     */
    private function extract_words($content) {
        // Remove HTML tags and normalize
        $content = preg_replace('/<[^>]+>/', ' ', $content);
        $content = preg_replace('/[^\w\s]/', ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        return explode(' ', trim($content));
    }
    
    /**
     * Optimized single keyword analysis
     */
    private function analyze_single_keyword_optimized($keyword, $content_data, $total_words, $content_lower, $content_words, $content_data_normalized = null) {
        $keyword_lower = strtolower(trim($keyword));
        if (empty($keyword_lower)) {
            return false;
        }
        
        $keyword_count = 0;
        $positions = array();
        
        // Count occurrences in different content areas with improved accuracy
        $areas = array(
            'title' => $content_data['title'],
            'content' => $content_data['content'],
            'meta_description' => $content_data['meta_description'],
            'excerpt' => $content_data['excerpt'],
            'headings' => implode(' ', $content_data['headings'])
        );
        
        $area_counts = array();
        $is_smart_match = false;
        
        foreach ($areas as $area_name => $area_content) {
            if (empty($area_content)) continue;
            
            $area_content_lower = strtolower($area_content);
            
            // Check original matching
            $area_count = $this->count_keyword_occurrences($keyword_lower, $area_content_lower);
            
            // Check normalized matching if no exact matches found and we have normalized content
            if ($area_count === 0 && !empty($content_data_normalized)) {
                $norm_keyword = $this->normalize_term($keyword_lower);
                $norm_content = $area_name === 'headings' ? implode(' ', $content_data_normalized['headings']) : $content_data_normalized[$area_name];
                
                if (!empty($norm_keyword) && !empty($norm_content)) {
                    $norm_count = $this->count_keyword_occurrences($norm_keyword, $norm_content);
                    if ($norm_count > 0) {
                        $area_count = $norm_count;
                        $is_smart_match = true;
                    }
                }
            }

            $area_counts[$area_name] = $area_count;
            $keyword_count += $area_count;
        }
        
        // Find positions in full content (limit to first 20 for performance)
        $offset = 0;
        $position_count = 0;
        while (($pos = strpos($content_lower, $keyword_lower, $offset)) !== false && $position_count < 20) {
            $positions[] = $pos;
            $offset = $pos + 1;
            $position_count++;
        }
        
        // Calculate density with improved accuracy
        $density = $total_words > 0 ? ($keyword_count / $total_words) * 100 : 0;
        
        // Determine status with more nuanced thresholds
        $status = $this->get_keyword_status_improved($density, $keyword_count, $total_words);
        
        return array(
            'keyword' => $keyword,
            'count' => $keyword_count,
            'density' => round($density, 2),
            'status' => $status,
            'positions' => $positions,
            'area_counts' => $area_counts,
            'is_smart_match' => $is_smart_match,
            'context' => $this->get_keyword_context_optimized($keyword_lower, $content_data['content'], 3),
            'relevance_score' => $this->calculate_relevance_score($keyword_lower, $content_data, $keyword_count)
        );
    }
    
    /**
     * Count keyword occurrences with word boundary matching
     */
    private function count_keyword_occurrences($keyword, $content) {
        // Handle multi-word keywords
        if (strpos($keyword, ' ') !== false) {
            $keyword_parts = explode(' ', $keyword);
            $count = 0;
            $offset = 0;
            
            while (($pos = strpos($content, $keyword, $offset)) !== false) {
                // Check word boundaries
                $before = $pos > 0 ? $content[$pos - 1] : ' ';
                $after = $pos + strlen($keyword) < strlen($content) ? $content[$pos + strlen($keyword)] : ' ';
                
                if (!ctype_alnum($before) && !ctype_alnum($after)) {
                    $count++;
                }
                $offset = $pos + 1;
            }
            return $count;
        } else {
            // Single word keyword with word boundary matching
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            return preg_match_all($pattern, $content);
        }
    }
    
    /**
     * Calculate relevance score for keyword
     */
    private function calculate_relevance_score($keyword, $content_data, $count) {
        $score = 0;
        
        // Title presence (high weight)
        if (stripos($content_data['title'], $keyword) !== false) {
            $score += 30;
        }
        
        // Meta description presence (medium weight)
        if (!empty($content_data['meta_description']) && stripos($content_data['meta_description'], $keyword) !== false) {
            $score += 20;
        }
        
        // Headings presence (medium weight)
        if (!empty($content_data['headings'])) {
            $headings_text = implode(' ', $content_data['headings']);
            if (stripos($headings_text, $keyword) !== false) {
                $score += 15;
            }
        }
        
        // Content frequency (weighted by count)
        $score += min($count * 2, 25);
        
        return min($score, 100); // Cap at 100
    }
    
    
    /**
     * Improved keyword status with more nuanced analysis
     */
    private function get_keyword_status_improved($density, $count, $total_words) {
        // Consider both density and absolute count
        if ($density >= 3.0 || ($density >= 2.0 && $count >= 10)) {
            return 'high';
        } elseif ($density >= 1.5 || ($density >= 1.0 && $count >= 5)) {
            return 'good';
        } elseif ($density >= 0.8 || ($density >= 0.5 && $count >= 3)) {
            return 'moderate';
        } elseif ($density >= 0.2 || $count >= 1) {
            return 'low';
        } else {
            return 'none';
        }
    }
    
    
    /**
     * Optimized context extraction with better performance
     */
    private function get_keyword_context_optimized($keyword, $content, $context_words = 3) {
        $contexts = array();
        $content_lower = strtolower($content);
        
        // Clean content for better word extraction
        $clean_content = preg_replace('/[^\w\s]/', ' ', $content);
        $words = preg_split('/\s+/', trim($clean_content));
        $words_lower = array_map('strtolower', $words);
        
        $found_count = 0;
        foreach ($words_lower as $index => $word) {
            if ($found_count >= 5) break; // Limit contexts for performance
            
            if (strpos($word, $keyword) !== false) {
                $start = max(0, $index - $context_words);
                $end = min(count($words) - 1, $index + $context_words);
                
                $context = array_slice($words, $start, $end - $start + 1);
                $context_text = implode(' ', $context);
                
                // Highlight the keyword in context
                $context_text = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<strong>' . $keyword . '</strong>', $context_text);
                $contexts[] = $context_text;
                $found_count++;
            }
        }
        
        return $contexts;
    }
    
    /**
     * Generate analysis summary with enhanced metrics
     */
    private function generate_summary($results, $total_words, $is_ai = false, $clusters = array(), $intent = '') {
        $total_keywords = count($results);
        $keywords_found = count(array_filter($results, function($r) { return $r['count'] > 0; }));
        $avg_density = $total_keywords > 0 ? array_sum(array_column($results, 'density')) / $total_keywords : 0;
        
        $status_counts = array(
            'high' => 0,
            'good' => 0,
            'moderate' => 0,
            'low' => 0,
            'none' => 0
        );
        
        $total_relevance_score = 0;
        $keywords_with_relevance = 0;
        
        $result_map = array();
        foreach ($results as $result) {
            $status_counts[$result['status']]++;
            $result_map[strtolower($result['keyword'])] = $result;
            
            if (isset($result['relevance_score'])) {
                $total_relevance_score += $result['relevance_score'];
                $keywords_with_relevance++;
            }
        }
        
        $cluster_results = array();
        if ($is_ai && !empty($clusters)) {
            foreach ($clusters as $cluster) {
                $seed = strtolower($cluster['seed']);
                $variations = array_map('strtolower', $cluster['variations']);
                
                $seed_res = $result_map[$seed] ?? ['count' => 0, 'density' => 0];
                $group_count = $seed_res['count'];
                $found_count = ($seed_res['count'] > 0) ? 1 : 0;
                
                $variation_data = array();
                foreach ($variations as $var) {
                    $var_res = $result_map[$var] ?? ['count' => 0, 'density' => 0];
                    $group_count += $var_res['count'];
                    if ($var_res['count'] > 0) $found_count++;
                    $variation_data[$var] = $var_res['count'];
                }
                
                $coverage = ($found_count / 5) * 100;
                $cluster_results[] = array(
                    'seed' => $cluster['seed'],
                    'total_count' => $group_count,
                    'coverage_percent' => round($coverage, 1),
                    'variations_found' => $found_count,
                    'is_healthy' => $found_count >= 3,
                    'is_stuffed' => $group_count > 0 && ($seed_res['count'] > ($group_count * 0.7) && $group_count > 5),
                    'variation_data' => $variation_data
                );
            }
        }
        
        $avg_relevance = $keywords_with_relevance > 0 ? $total_relevance_score / $keywords_with_relevance : 0;
        
        // Calculate SEO score
        $seo_score = $this->calculate_seo_score_topical($results, $total_words, $is_ai, $clusters);
        
        return array(
            'total_keywords' => $total_keywords,
            'keywords_found' => $keywords_found,
            'total_words' => $total_words,
            'average_density' => round($avg_density, 2),
            'average_relevance' => round($avg_relevance, 1),
            'seo_score' => $seo_score,
            'status_distribution' => $status_counts,
            'recommendations' => $this->generate_recommendations($results, $status_counts),
            'performance_metrics' => $this->get_performance_metrics($results),
            'cluster_results' => $cluster_results,
            'intent' => $intent
        );
    }
    
    /**
     * Calculate overall SEO score with Topical Awareness
     */
    private function calculate_seo_score_topical($results, $total_words, $is_ai = false, $clusters = array()) {
        $score = 0;
        $max_score = 100;
        
        if ($is_ai && !empty($clusters)) {
            // TOPICAL SCORING (For AI Superpowers)
            $total_clusters = count($clusters);
            if ($total_clusters === 0) return 0;

            $result_map = array();
            foreach ($results as $r) {
                $result_map[strtolower($r['keyword'])] = $r;
            }

            $clusters_covered = 0;
            $total_cluster_relevance = 0;
            $cluster_health_count = 0;

            foreach ($clusters as $cluster) {
                $seed = strtolower($cluster['seed']);
                $variations = array_map('strtolower', $cluster['variations']);
                
                $seed_res = $result_map[$seed] ?? ['count' => 0, 'relevance_score' => 0, 'density' => 0];
                $max_relevance = $seed_res['relevance_score'];
                $is_covered = ($seed_res['count'] > 0);
                $cluster_density = $seed_res['density'];

                foreach ($variations as $var) {
                    $var_res = $result_map[$var] ?? ['count' => 0, 'relevance_score' => 0, 'density' => 0];
                    if ($var_res['count'] > 0) $is_covered = true;
                    $max_relevance = max($max_relevance, $var_res['relevance_score']);
                    $cluster_density += $var_res['density'];
                }

                if ($is_covered) $clusters_covered++;
                $total_cluster_relevance += $max_relevance;
                
                // Cluster health: Good if coverage is found and density is balanced
                if ($is_covered && $cluster_density >= 0.5 && $cluster_density <= 4.0) {
                    $cluster_health_count++;
                }
            }

            // 1. Cluster Coverage (40 points) - Most important for topical SEO
            $coverage_score = ($clusters_covered / $total_clusters) * 40;
            $score += $coverage_score;

            // 2. Topical Relevance (30 points)
            $avg_cluster_relevance = $total_cluster_relevance / $total_clusters;
            $relevance_score = ($avg_cluster_relevance / 100) * 30;
            $score += $relevance_score;

            // 3. Density/Health Balance (20 points)
            $health_score = ($cluster_health_count / $total_clusters) * 20;
            $score += $health_score;

            // 4. Word Count bonus (10 points)
            if ($total_words >= 600) $score += 10;
            elseif ($total_words >= 300) $score += 7;

        } else {
            // LEGACY SCORING (For Exact Keyword matching)
            $score = $this->calculate_seo_score($results, $total_words);
        }

        return min(round($score), $max_score);
    }
    
    /**
     * Calculate overall SEO score
     */
    private function calculate_seo_score($results, $total_words) {
        $score = 0;
        $max_score = 100;
        
        // Keyword coverage (30 points)
        $found_keywords = count(array_filter($results, function($r) { return $r['count'] > 0; }));
        $coverage_score = min(($found_keywords / count($results)) * 30, 30);
        $score += $coverage_score;
        
        // Density optimization (25 points)
        $good_density_count = count(array_filter($results, function($r) { 
            return $r['density'] >= 0.5 && $r['density'] <= 2.5; 
        }));
        $density_score = min(($good_density_count / count($results)) * 25, 25);
        $score += $density_score;
        
        // Relevance score (25 points)
        $total_relevance = 0;
        $relevance_count = 0;
        foreach ($results as $result) {
            if (isset($result['relevance_score'])) {
                $total_relevance += $result['relevance_score'];
                $relevance_count++;
            }
        }
        if ($relevance_count > 0) {
            $avg_relevance = $total_relevance / $relevance_count;
            $relevance_score = ($avg_relevance / 100) * 25;
            $score += $relevance_score;
        }
        
        // Content length optimization (20 points)
        if ($total_words >= 300 && $total_words <= 2000) {
            $score += 20; // Optimal length
        } elseif ($total_words >= 200 && $total_words < 300) {
            $score += 15; // Good length
        } elseif ($total_words > 2000 && $total_words <= 3000) {
            $score += 15; // Acceptable but long
        } else {
            $score += 5; // Too short or too long
        }
        
        return min(round($score), $max_score);
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics($results) {
        $metrics = array(
            'over_optimized' => 0,
            'under_optimized' => 0,
            'well_optimized' => 0,
            'not_found' => 0
        );
        
        foreach ($results as $result) {
            switch ($result['status']) {
                case 'high':
                    $metrics['over_optimized']++;
                    break;
                case 'good':
                case 'moderate':
                    $metrics['well_optimized']++;
                    break;
                case 'low':
                    $metrics['under_optimized']++;
                    break;
                case 'none':
                    $metrics['not_found']++;
                    break;
            }
        }
        
        return $metrics;
    }
    
    /**
     * Generate comprehensive SEO recommendations
     */
    private function generate_recommendations($results, $status_counts) {
        $recommendations = array();
        
        // High density keywords (over-optimization)
        if ($status_counts['high'] > 0) {
            $high_keywords = array_filter($results, function($r) { return $r['status'] === 'high'; });
            $keyword_list = array_slice(array_column($high_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'warning',
                'priority' => 'high',
                'title' => __('Over-Optimization Detected', 'artitechcore'),
                'message' => sprintf(__('%d keyword(s) have high density (≥3%%). Consider reducing usage to avoid keyword stuffing penalties.', 'artitechcore'), $status_counts['high']),
                'keywords' => $keyword_list,
                'action' => __('Reduce keyword frequency and use synonyms or related terms instead.', 'artitechcore')
            );
        }
        
        // Low density keywords (under-optimization)
        if ($status_counts['low'] > 0) {
            $low_keywords = array_filter($results, function($r) { return $r['status'] === 'low'; });
            $keyword_list = array_slice(array_column($low_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'info',
                'priority' => 'medium',
                'title' => __('Under-Optimization Detected', 'artitechcore'),
                'message' => sprintf(__('%d keyword(s) have low density (0.2-0.8%%). Consider increasing usage naturally.', 'artitechcore'), $status_counts['low']),
                'keywords' => $keyword_list,
                'action' => __('Add keywords naturally in headings, meta descriptions, and content.', 'artitechcore')
            );
        }
        
        // No keywords found
        if ($status_counts['none'] > 0) {
            $none_keywords = array_filter($results, function($r) { return $r['status'] === 'none'; });
            $keyword_list = array_slice(array_column($none_keywords, 'keyword'), 0, 3);
            
            $recommendations[] = array(
                'type' => 'error',
                'priority' => 'high',
                'title' => __('Keywords Not Found', 'artitechcore'),
                'message' => sprintf(__('%d keyword(s) were not found in the content.', 'artitechcore'), $status_counts['none']),
                'keywords' => $keyword_list,
                'action' => __('Add these keywords to your content, title, or meta description.', 'artitechcore')
            );
        }
        
        // Good optimization
        if ($status_counts['good'] > 0 || $status_counts['moderate'] > 0) {
            $good_count = $status_counts['good'] + $status_counts['moderate'];
            $recommendations[] = array(
                'type' => 'success',
                'priority' => 'low',
                'title' => __('Good Optimization', 'artitechcore'),
                'message' => sprintf(__('%d keyword(s) are well-optimized with good density levels.', 'artitechcore'), $good_count),
                'action' => __('Keep maintaining these keyword levels.', 'artitechcore')
            );
        }
        
        // Content length recommendations
        $total_words = 0;
        if (!empty($results)) {
            // Get word count from first result (all should have same total)
            $total_words = $results[0]['total_words'] ?? 0;
        }
        
        if ($total_words > 0) {
            if ($total_words < 300) {
                $recommendations[] = array(
                    'type' => 'warning',
                    'priority' => 'medium',
                    'title' => __('Content Too Short', 'artitechcore'),
                    'message' => sprintf(__('Content has only %d words. Google prefers content with 300+ words.', 'artitechcore'), $total_words),
                    'action' => __('Add more valuable content to improve SEO performance.', 'artitechcore')
                );
            } elseif ($total_words > 3000) {
                $recommendations[] = array(
                    'type' => 'info',
                    'priority' => 'low',
                    'title' => __('Content Very Long', 'artitechcore'),
                    'message' => sprintf(__('Content has %d words. Consider breaking into multiple pages if appropriate.', 'artitechcore'), $total_words),
                    'action' => __('Ensure content remains engaging and valuable throughout.', 'artitechcore')
                );
            }
        }
        
        // Sort by priority
        $priority_order = array('high' => 3, 'medium' => 2, 'low' => 1);
        usort($recommendations, function($a, $b) use ($priority_order) {
            return $priority_order[$b['priority']] <=> $priority_order[$a['priority']];
        });
        
        return $recommendations;
    }
    
    /**
     * Export analysis results
     */
    private function export_analysis($format, $analysis_data) {
        $page_info = $analysis_data['page_info'];
        $keywords = $analysis_data['keywords'];
        $summary = $analysis_data['summary'];
        
        $filename = sanitize_file_name($page_info['title']) . '_keyword_analysis_' . date('Y-m-d');
        
        switch ($format) {
            case 'csv':
                $this->export_csv($filename, $page_info, $keywords, $summary);
                break;
            case 'json':
                $this->export_json($filename, $analysis_data);
                break;
            default:
                wp_die('Invalid export format');
        }
    }
    
    /**
     * Export as CSV
     */
    private function export_csv($filename, $page_info, $keywords, $summary) {
        // Sanitize filename to prevent directory traversal
        $filename = sanitize_file_name($filename);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        // Set security headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // Page info header
        fputcsv($output, array('Page Analysis Report'));
        fputcsv($output, array('Page Title', $page_info['title']));
        fputcsv($output, array('URL', $page_info['url']));
        fputcsv($output, array('Word Count', $page_info['word_count']));
        fputcsv($output, array('Analysis Date', $page_info['analysis_date']));
        fputcsv($output, array(''));
        
        // Summary
        fputcsv($output, array('Summary'));
        fputcsv($output, array('Total Keywords', $summary['total_keywords']));
        fputcsv($output, array('Keywords Found', $summary['keywords_found']));
        fputcsv($output, array('Average Density', $summary['average_density'] . '%'));
        fputcsv($output, array(''));
        
        // Keywords data
        fputcsv($output, array('Keyword', 'Count', 'Density (%)', 'Status', 'Title', 'Content', 'Meta Description', 'Excerpt', 'Headings'));
        
        foreach ($keywords as $keyword) {
            fputcsv($output, array(
                $keyword['keyword'],
                $keyword['count'],
                $keyword['density'],
                ucfirst($keyword['status']),
                $keyword['area_counts']['title'] ?? 0,
                $keyword['area_counts']['content'] ?? 0,
                $keyword['area_counts']['meta_description'] ?? 0,
                $keyword['area_counts']['excerpt'] ?? 0,
                $keyword['area_counts']['headings'] ?? 0
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export as JSON
     */
    private function export_json($filename, $analysis_data) {
        // Sanitize filename to prevent directory traversal
        $filename = sanitize_file_name($filename);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        // Set security headers
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($analysis_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * AI Provider Callers (Reused from schema generator pattern)
     */
    private function call_gemini_json($prompt, $api_key) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;
        $response = artitechcore_safe_ai_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 800]
            ]),
            'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
        ], 'gemini');
        if (is_wp_error($response)) {
            error_log('ArtitechCore Gemini Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            $json_str = trim($body['candidates'][0]['content']['parts'][0]['text']);
            $json_str = preg_replace('/^```json\s*|\s*```$/i', '', $json_str);
            return json_decode($json_str, true);
        }
        return false;
    }

    private function call_openai_json($prompt, $api_key) {
        $response = artitechcore_safe_ai_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
            'body' => json_encode([
                'model' => 'gpt-4o',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2
            ]),
            'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
        ], 'openai');
        if (is_wp_error($response)) {
            error_log('ArtitechCore OpenAI Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            return json_decode($body['choices'][0]['message']['content'], true);
        }
        return false;
    }

    private function call_deepseek_json($prompt, $api_key) {
        $response = artitechcore_safe_ai_remote_post('https://api.deepseek.com/v1/chat/completions', [
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.2
            ]),
            'timeout' => defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120,
        ], 'deepseek');
        if (is_wp_error($response)) {
            error_log('ArtitechCore DeepSeek Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            return json_decode($body['choices'][0]['message']['content'], true);
        }
        return false;
    }
}

// Initialize the keyword analyzer
new ArtitechCore_Keyword_Analyzer();
