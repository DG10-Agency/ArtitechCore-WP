<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Website Builder Queue System
 *
 * Handles asynchronous generation of websites using WordPress transients.
 * Prevents PHP timeouts and provides progress tracking.
 */

// Queue status constants
const ARTITECHCORE_QUEUE_PENDING = 'pending';
const ARTITECHCORE_QUEUE_PROCESSING = 'processing';
const ARTITECHCORE_QUEUE_COMPLETE = 'complete';
const ARTITECHCORE_QUEUE_FAILED = 'failed';

/**
 * Class ArtitechCore_Website_Builder_Queue
 *
 * Manages job queuing, processing, and status tracking for website generation.
 */
class ArtitechCore_Website_Builder_Queue {

    private $job_id;
    private $jobs = [];
    private $transient_prefix = 'artitechcore_builder_job_';

    /**
     * Constructor
     */
    public function __construct() {
        $this->job_id = wp_generate_uuid4();
    }

    /**
     * Add a generation job to the queue
     *
     * @param string $blueprint
     * @param array $page_configs
     * @param array $brand_kit
     * @param bool $generate_images
     * @param string $publish_status
     * @param array $metadata Optional metadata (user ID, cost estimate, etc.)
     */
    public function add_job($blueprint, $page_configs, $brand_kit, $generate_images = false, $publish_status = 'draft', $metadata = []) {
        $this->jobs[] = [
            'blueprint' => $blueprint,
            'page_configs' => $page_configs,
            'brand_kit' => $brand_kit,
            'generate_images' => $generate_images,
            'publish_status' => $publish_status,
            'created_at' => current_time('mysql'),
            'metadata' => $metadata
        ];
    }

    /**
     * Get total number of pages to be generated across all jobs
     */
    private function count_total_pages() {
        $total = 0;
        foreach ($this->jobs as $job) {
            foreach ($job['page_configs'] as $config) {
                $total += isset($config['count']) ? intval($config['count']) : 1;
            }
        }
        return $total;
    }

    /**
     * Dispatch the queued jobs for background processing
     *
     * @return string Job ID for status tracking
     */
    public function dispatch() {
        if (empty($this->jobs)) {
            throw new Exception('No jobs to dispatch');
        }

        $job_data = [
            'jobs' => $this->jobs,
            'status' => ARTITECHCORE_QUEUE_PENDING,
            'total' => $this->count_total_pages(),
            'completed' => 0,
            'errors' => [],
            'created_at' => current_time('mysql'),
            'started_at' => null,
            'completed_at' => null,
            'results' => []
        ];

        // Store job in transient with 48-hour expiry
        $transient_key = $this->transient_prefix . $this->job_id;
        set_transient($transient_key, $job_data, 48 * HOUR_IN_SECONDS);

        // Schedule background processing via WP-Cron
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(time() + 5, 'artitechcore_process_builder_job', [$this->job_id]);
        } else {
            // Fallback: mark as pending, will be processed by manual trigger or AJAX polling
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ArtitechCore: WP-Cron not available. Job ' . $this->job_id . ' will need manual processing.');
            }
        }

        // Also store in options for admin visibility (last 20 jobs)
        $this->log_job_for_admin($this->job_id, $job_data);

        return $this->job_id;
    }

    /**
     * Log job details for admin status page (keep last 20)
     */
    private function log_job_for_admin($job_id, $job_data) {
        $recent_jobs = get_option('artitechcore_recent_jobs', []);
        if (!is_array($recent_jobs)) $recent_jobs = [];

        $recent_jobs[$job_id] = [
            'id' => $job_id,
            'status' => $job_data['status'],
            'total_pages' => $job_data['total'],
            'created_at' => $job_data['created_at'],
            'blueprint' => $this->jobs[0]['blueprint'] ?? 'unknown' // First job's blueprint
        ];

        // Keep only last 20
        if (count($recent_jobs) > 20) {
            $recent_jobs = array_slice($recent_jobs, -20, true);
        }

        update_option('artitechcore_recent_jobs', $recent_jobs);
    }

    /**
     * Get job status by ID
     *
     * @param string $job_id
     * @return array|false Job data or false if not found/expired
     */
    public static function get_job_status($job_id) {
        $transient_key = 'artitechcore_builder_job_' . $job_id;
        $job_data = get_transient($transient_key);

        if (!$job_data || !is_array($job_data)) {
            return false;
        }

        return $job_data;
    }

    /**
     * Update job status (used by background processor)
     *
     * @param string $job_id
     * @param array $updates
     */
    public static function update_job($job_id, array $updates) {
        $transient_key = 'artitechcore_builder_job_' . $job_id;
        $job_data = get_transient($transient_key);

        if (!$job_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ArtitechCore: Cannot update job $job_id - not found");
            }
            return false;
        }

        $job_data = array_merge($job_data, $updates);
        $job_data['updated_at'] = current_time('mysql');

        set_transient($transient_key, $job_data, 48 * HOUR_IN_SECONDS);

        // Update admin log too
        $recent_jobs = get_option('artitechcore_recent_jobs', []);
        if (isset($recent_jobs[$job_id])) {
            $recent_jobs[$job_id]['status'] = $job_data['status'];
            $recent_jobs[$job_id]['completed'] = $job_data['completed'] ?? 0;
            $recent_jobs[$job_id]['updated_at'] = $job_data['updated_at'];
            update_option('artitechcore_recent_jobs', $recent_jobs);
        }

        return true;
    }

    /**
     * Cancel/delete a job
     *
     * @param string $job_id
     */
    public static function cancel_job($job_id) {
        $transient_key = 'artitechcore_builder_job_' . $job_id;
        delete_transient($transient_key);

        // Update admin log
        $recent_jobs = get_option('artitechcore_recent_jobs', []);
        if (isset($recent_jobs[$job_id])) {
            $recent_jobs[$job_id]['status'] = 'cancelled';
            update_option('artitechcore_recent_jobs', $recent_jobs);
        }

        return true;
    }

    /**
     * Get all pending/completed jobs for admin view
     */
    public static function get_all_jobs($limit = 50) {
        $recent_jobs = get_option('artitechcore_recent_jobs', []);
        if (!is_array($recent_jobs)) $recent_jobs = [];

        // Sort by created_at descending
        usort($recent_jobs, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($recent_jobs, 0, $limit);
    }

    /**
     * Clean up old completed jobs (can be called via WP-Cron)
     *
     * @param int $days_old Delete jobs older than this many days
     */
    public static function cleanup_old_jobs($days_old = 7) {
        $recent_jobs = get_option('artitechcore_recent_jobs', []);
        if (!is_array($recent_jobs)) return 0;

        $cutoff = strtotime("-{$days_old} days");
        $deleted = 0;

        foreach ($recent_jobs as $job_id => $job) {
            if (strtotime($job['created_at']) < $cutoff) {
                // Delete the transient too
                delete_transient('artitechcore_builder_job_' . $job_id);
                unset($recent_jobs[$job_id]);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            update_option('artitechcore_recent_jobs', $recent_jobs);
        }

        return $deleted;
    }
}

/**
 * Background job processor: Process a single queued website generation job
 *
 * @param string $job_id
 */
function artitechcore_process_builder_job($job_id) {
    $queue = new ArtitechCore_Website_Builder_Queue();
    $job_data = $queue::get_job_status($job_id);

    if (false === $job_data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ArtitechCore: Job $job_id not found or expired");
        }
        return;
    }

    // Mark as processing
    $job_data['status'] = ARTITECHCORE_QUEUE_PROCESSING;
    $job_data['started_at'] = current_time('mysql');
    $queue::update_job($job_id, [
        'status' => ARTITECHCORE_QUEUE_PROCESSING,
        'started_at' => $job_data['started_at']
    ]);

    $total_pages = $job_data['total'];
    $completed_pages = 0;
    $all_errors = [];
    $job_results = [];

    $start_time = microtime(true);
    $time_limit = ini_get('max_execution_time');
    if ($time_limit > 0) {
        $time_limit -= 5; // 5 second buffer
    } else {
        $time_limit = 25; // Default fallback if limit is 0 or unknown
    }

    try {
        foreach ($job_data['jobs'] as $job_index => $job) {
            // Check if we are approaching timeout (P2-3)
            if ((microtime(true) - $start_time) > $time_limit) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("ArtitechCore: Job $job_id reaching time limit. Rescheduling remaining batches.");
                }
                $queue::update_job($job_id, [
                    'status' => ARTITECHCORE_QUEUE_PENDING, // Requeue for next cron run
                    'jobs' => array_slice($job_data['jobs'], $job_index) // Only remaining jobs
                ]);
                wp_schedule_single_event(time() + 10, 'artitechcore_process_builder_job', [$this->job_id]);
                return;
            }

            try {
                $result = artitechcore_build_website(
                    $job['blueprint'],
                    $job['page_configs'],
                    $job['brand_kit'],
                    $job['generate_images'],
                    $job['publish_status']
                );

                $completed_pages += $result['pages_created'];
                $all_errors = array_merge($all_errors, $result['errors']);
                $job_results[] = [
                    'job_index' => $job_index,
                    'success' => true,
                    'pages_created' => $result['pages_created'],
                    'images_generated' => $result['images_generated']
                ];

                // Update progress after each job
                $queue::update_job($job_id, [
                    'completed' => $completed_pages,
                    'errors' => $all_errors,
                    'results' => $job_results
                ]);

            } catch (Exception $e) {
                $error_msg = "Job " . ($job_index + 1) . ": " . $e->getMessage();
                $all_errors[] = $error_msg;
                $job_results[] = [
                    'job_index' => $job_index,
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                $queue::update_job($job_id, [
                    'completed' => $completed_pages,
                    'errors' => $all_errors,
                    'results' => $job_results
                ]);
            }
        }

        // Job complete
        $final_status = ARTITECHCORE_QUEUE_COMPLETE; 
        $queue::update_job($job_id, [
            'status' => $final_status,
            'completed_at' => current_time('mysql'),
            'completed' => $completed_pages,
            'errors' => $all_errors,
            'results' => $job_results
        ]);

        // Update monthly usage stats (for cost tracking) using actuals (F-11)
        $first_job = $job_data['jobs'][0] ?? null;
        if ($first_job) {
            $total_actual_pages = 0;
            $total_actual_images = 0;
            foreach ($job_results as $res) {
                if ($res['success']) {
                    $total_actual_pages += $res['pages_created'];
                    $total_actual_images += $res['images_generated'];
                }
            }

            // Optional: fallback to estimated if somehow 0 but was successfully marked
            if ($total_actual_pages === 0 && $final_status === ARTITECHCORE_QUEUE_COMPLETE) {
                $total_actual_pages = $completed_pages;
                $total_actual_images = ($first_job['generate_images'] ?? false) ? $completed_pages : 0;
            }

            $provider = get_option('artitechcore_ai_provider', 'openai');
            $cost_config = ARTITECHCORE_COST[$provider] ?? ARTITECHCORE_COST['openai'];
            
            $actual_spent = ($total_actual_pages * $cost_config['content_per_page']) + ($total_actual_images * $cost_config['image']);
            
            artitechcore_update_monthly_stats($total_actual_pages, $total_actual_images, $actual_spent);
        }

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ArtitechCore: Job $job_id failed with exception: " . $e->getMessage());
        }
        $queue::update_job($job_id, [
            'status' => ARTITECHCORE_QUEUE_FAILED,
            'completed_at' => current_time('mysql'),
            'errors' => array_merge($all_errors, [$e->getMessage()])
        ]);
    }
}

/**
 * Update monthly usage statistics (for cost tracking)
 */
function artitechcore_update_monthly_stats($pages_generated, $images_generated, $spent = 0.0) {
    $current_month = date('Y-m');
    $usage_key = 'artitechcore_monthly_usage_' . $current_month;

    $usage = get_option($usage_key, [
        'pages' => 0,
        'images' => 0,
        'spent' => 0.0,
        'month' => $current_month
    ]);

    $usage['pages'] += (int)$pages_generated;
    $usage['images'] += (int)$images_generated;
    $usage['spent'] += (float)$spent;

    update_option($usage_key, $usage);

    // Also keep a running total
    $total_pages = get_option('artitechcore_total_pages_generated', 0) + $pages_generated;
    $total_images = get_option('artitechcore_total_images_generated', 0) + $images_generated;
    update_option('artitechcore_total_pages_generated', $total_pages);
    update_option('artitechcore_total_images_generated', $total_images);
}

// Register the background processing hook
add_action('artitechcore_process_builder_job', 'artitechcore_process_builder_job');

// Register cleanup cron (daily)
add_action('artitechcore_cleanup_old_builder_jobs', function() {
    ArtitechCore_Website_Builder_Queue::cleanup_old_jobs(7);
});

// Schedule cleanup on plugin activation (if not already scheduled)
// Note: Activation hooks moved to main plugin file artitechcore-for-wordpress.php
// as they only fire when called in the primary plugin file.
