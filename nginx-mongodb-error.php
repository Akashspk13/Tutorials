<?php
$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");

$bulk = new MongoDB\Driver\BulkWrite;

$logs = file('/var/log/nginx/error.log', FILE_IGNORE_NEW_LINES);

$logs = array_reverse($logs); // Reverse the order of the lines in the file

foreach($logs as $line) {
    $line_arr = explode(' ', $line);
    if(count($line_arr) < 4) {
        // Skip lines that don't have enough information
        continue;
    }

    $log_time = isset($line_arr[0]) ? date("Y-m-d\TH:i:s", strtotime($line_arr[0])) : '';
    $log_level = isset($line_arr[1]) ? $line_arr[1] : '';
    $log_message = isset($line_arr[2]) ? substr($line_arr[2], 1, -1) : '';

    $bulk->insert([
        'log_time' => $log_time,
        'log_level' => $log_level,
        'log_message' => $log_message,
    ]);
}

$result = $mongo->executeBulkWrite('nginx.error_logs', $bulk);
