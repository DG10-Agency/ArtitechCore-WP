<?php
/**
 * ArtitechCore API Utilities
 * 
 * Provides robust rate limiting, backoff/retry, and connection testing.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check and track AI rate limits & concurrency using transients
 * 
 * @return bool|WP_Error True if allowed, WP_Error if rate limited or concurrency reached.
 */
function artitechcore_check_ai_rate_limit() {
    // 1. Enhanced Identification (Handle Guest IPs)
    $user_id = get_current_user_id();
    $limit = (int) get_option('artitechcore_ai_rate_limit', 20);
    
    if ($user_id > 0) {
        $id_part = (string) $user_id;
    } else {
        // Use hashed IP for guests to avoid shared ID 0 bottleneck/security flaw
        $id_part = 'ip_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
    
    $rate_transient = 'artitechcore_ai_rate_limit_' . $id_part;
    
    // 2. Concurrency Control (Global Guard)
    // Prevents PHP worker exhaustion during retry-sleep loops
    $active_calls = (int) get_transient('artitechcore_active_ai_calls');
    $concurrency_limit = apply_filters('artitechcore_ai_concurrency_limit', 3);
    
    if ($active_calls >= $concurrency_limit) {
        return new WP_Error(
            'concurrency_reached', 
            __('Server is busy processing other AI tasks. Please try again in a few seconds.', 'artitechcore')
        );
    }

    // 3. RPM Rate Limit
    $current = (int) get_transient($rate_transient);
    if ($current >= $limit) {
        return new WP_Error(
            'rate_limit_exceeded', 
            sprintf(__('Rate limit exceeded. Please wait a minute. (Max: %d rpm)', 'artitechcore'), $limit)
        );
    }
    
    set_transient($rate_transient, $current + 1, MINUTE_IN_SECONDS);
    return true;
}

/**
 * Log AI interaction history and errors for diagnostics
 */
function artitechcore_log_ai_event($code, $message, $provider, $is_error = true) {
    $history = get_option('artitechcore_ai_history', []);
    if (!is_array($history)) {
        $history = [];
    }

    $entry = [
        'timestamp' => current_time('mysql'),
        'code'      => $code,
        'message'   => wp_trim_words($message, 20),
        'provider'  => $provider,
        'status'    => $is_error ? 'error' : 'success'
    ];

    array_unshift($history, $entry);
    $history = array_slice($history, 0, 10); // Keep last 10 entries
    update_option('artitechcore_ai_history', $history);
}

/**
 * Centralize API Calls with Robust Retry, Backoff, and Concurrency Monitoring
 * 
 * @param string $url The API URL
 * @param array $args The wp_remote_request args
 * @param string $provider The provider name for specialized error handling
 * @param string $method The HTTP method (default 'POST')
 * @return array|WP_Error
 */
function artitechcore_safe_ai_remote_request($url, $args, $provider = 'openai', $method = 'POST') {
    // 1. Pre-flight Checks
    $rate_check = artitechcore_check_ai_rate_limit();
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }

    // 2. Increment Concurrency Lock
    $active_calls = (int) get_transient('artitechcore_active_ai_calls');
    set_transient('artitechcore_active_ai_calls', $active_calls + 1, 5 * MINUTE_IN_SECONDS);

    $max_retries = 3;
    $retry_count = 0;
    $wait_time = 2; // Initial wait in seconds
    $args['method'] = $method;

    if (!isset($args['timeout'])) {
        $args['timeout'] = defined('ARTITECHCORE_API_TIMEOUT') ? ARTITECHCORE_API_TIMEOUT : 120;
    }

    $final_result = null;

    try {
        while ($retry_count <= $max_retries) {
            if ($retry_count > 0) {
                error_log(sprintf('ArtitechCore: Retrying %s AI %s call (Attempt %d/%d)...', $provider, $method, $retry_count, $max_retries));
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                $retry_count++;
                if ($retry_count > $max_retries) {
                    $final_result = $response;
                    break;
                }
                sleep($wait_time);
                $wait_time *= 2;
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            
            if ($code === 200) {
                $final_result = $response;
                break;
            }

            // Retry on 429 or 5xx
            if ($code === 429 || ($code >= 500 && $code <= 599)) {
                $retry_count++;
                if ($retry_count > $max_retries) {
                    $final_result = new WP_Error('api_server_error', sprintf(__('Provider %s returned %d after %d retries.', 'artitechcore'), $provider, $code, $max_retries));
                    break;
                }
                sleep($wait_time);
                $wait_time *= 2;
                continue;
            }

            $final_result = new WP_Error('api_request_error', sprintf(__('Provider %s error %d: %s', 'artitechcore'), $provider, $code, wp_remote_retrieve_response_message($response)));
            break;
        }
    } finally {
        // 3. Decrement Concurrency Lock (Ensures clean release even on fatal errors)
        $active_calls = (int) get_transient('artitechcore_active_ai_calls');
        set_transient('artitechcore_active_ai_calls', max(0, $active_calls - 1), 5 * MINUTE_IN_SECONDS);
    }

    if (!$final_result) {
        $final_result = new WP_Error('api_unknown_failure', __('Unknown failure in AI pipeline.', 'artitechcore'));
    }

    // 4. Log Failure for Diagnostics
    if (is_wp_error($final_result)) {
        artitechcore_log_ai_event($final_result->get_error_code(), $final_result->get_error_message(), $provider, true);
    }

    return $final_result;
}

/**
 * Wrapper for POST AI requests
 */
function artitechcore_safe_ai_remote_post($url, $args, $provider = 'openai') {
    return artitechcore_safe_ai_remote_request($url, $args, $provider, 'POST');
}

/**
 * Wrapper for GET AI requests
 */
function artitechcore_safe_ai_remote_get($url, $args, $provider = 'openai') {
    return artitechcore_safe_ai_remote_request($url, $args, $provider, 'GET');
}
