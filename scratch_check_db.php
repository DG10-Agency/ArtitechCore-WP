<?php
require_once('wp-load.php');

global $wpdb;

echo "Meta counts:\n";
$meta_counts = $wpdb->get_results("SELECT meta_key, count(*) as count FROM {$wpdb->postmeta} WHERE meta_key LIKE '_artitechcore_schema_%' GROUP BY meta_key");
foreach ($meta_counts as $row) {
    echo "{$row->meta_key}: {$row->count}\n";
}

echo "\nCustom table count:\n";
$table_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}artitechcore_schema_data");
echo "Total: $table_count\n";

echo "\nChecking MU Plugin:\n";
$mu_plugin = ABSPATH . 'wp-content/mu-plugins/artitechcore-persistence-bridge.php';
if (file_exists($mu_plugin)) {
    echo "Bridge exists.\n";
} else {
    echo "Bridge MISSING.\n";
}
