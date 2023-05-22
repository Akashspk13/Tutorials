<?php

$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");

$bulk = new MongoDB\Driver\BulkWrite;

$logs = file('/var/log/nginx/access.log', FILE_IGNORE_NEW_LINES);

foreach($logs as $line) {
    $line_arr = explode(' ', $line);
    $bulk->insert([
        'ip' => $line_arr[0],
        'date' => date("Y-m-d\TH:i:s", strtotime(substr($line_arr[3], 1))),
        'request' => substr($line_arr[5], 1),
        'status' => $line_arr[8],
        'referrer' => substr($line_arr[10], 1),
        'user_agent' => substr($line_arr[11], 1),
    ]);
}

$result = $mongo->executeBulkWrite('nginx.logs', $bulk);
