<?php
$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");

$bulk = new MongoDB\Driver\BulkWrite;

$logs = file('/var/log/nginx/access.log', FILE_IGNORE_NEW_LINES);

$logs = array_reverse($logs); // Reverse the order of the lines in the file

foreach($logs as $line) {
    $line_arr = explode(' ', $line);
    if(count($line_arr) < 12) {
        // Skip lines that don't have enough information
        continue;
    }

    $remote_ip = isset($line_arr[0]) ? $line_arr[0] : '';
    $user_name = isset($line_arr[2]) ? $line_arr[2] : '';
    $access_time = isset($line_arr[3]) ? date("Y-m-d\TH:i:s", strtotime(substr($line_arr[3], 1))) : '';
    $http_method = isset($line_arr[5]) ? substr($line_arr[5], 1) : '';
    $url = isset($line_arr[6]) ? $line_arr[6] : '';
    $http_version = isset($line_arr[7]) ? substr($line_arr[7], 0, -1) : '';
    $response_code = isset($line_arr[8]) ? $line_arr[8] : '';
    $body_sent_bytes = isset($line_arr[9]) ? $line_arr[9] : '';
    $referrer = isset($line_arr[10]) ? substr($line_arr[10], 1, -1) : '';
    $agent = isset($line_arr[11]) ? substr($line_arr[11], 1, -1) : '';
    $forwarded_for = isset($line_arr[12]) ? $line_arr[12] : '';
    $request_id = isset($line_arr[13]) ? $line_arr[13] : '';
    $country = isset($line_arr[14]) ? $line_arr[14] : '';
    $country_code = isset($line_arr[15]) ? $line_arr[15] : '';
    $region = isset($line_arr[16]) ? $line_arr[16] : '';
    $city = isset($line_arr[17]) ? $line_arr[17] : '';

    $bulk->insert([
        'remote_ip' => $remote_ip,
        'user_name' => $user_name,
        'access_time' => $access_time,
        'http_method' => $http_method,
        'url' => $url,
        'http_version' => $http_version,
        'response_code' => $response_code,
        'body_sent_bytes' => $body_sent_bytes,
        'referrer' => $referrer,
        'agent' => $agent,
        'forwarded_for' => $forwarded_for,
        'request_id' => $request_id,
        'country' => $country,
        'country_code' => $country_code,
        'region' => $region,
        'city' => $city,
    ]);
}

$result = $mongo->executeBulkWrite('nginx.access_logs', $bulk);
