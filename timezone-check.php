<?php
// Quick timezone check
require_once '../../../wp-config.php';
require_once '../../../wp-load.php';

echo "WordPress Timezone: " . wp_timezone_string() . "\n";
echo "WordPress GMT Offset: " . get_option('gmt_offset') . " hours\n";
echo "Current WordPress Time: " . current_time('Y-m-d H:i:s T') . "\n";
echo "Current UTC Time: " . gmdate('Y-m-d H:i:s T') . "\n";
echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current PHP Time: " . date('Y-m-d H:i:s T') . "\n";
?>