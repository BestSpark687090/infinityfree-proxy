<?php
    // Configure the upstream hostname here
    $target_host = 'bestspark.org';

    $log = __DIR__ . '/proxy_log.txt';
function lg($msg) {
    global $log;
    file_put_contents($log, date('H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}
function shutdown_handler() {
    $error = error_get_last();
    if ($error !== null) {
        lg('FATAL: ' . print_r($error, true));
    }
}
register_shutdown_function('shutdown_handler');
lg('--- request start: ' . $_SERVER['REQUEST_URI']);
$remote_base = 'https://' . $target_host;
$request_uri = $_SERVER['REQUEST_URI'];
$url = $remote_base . $request_uri;
lg('url built: ' . $url);
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0';
$opts = array(
'http' => array(
'method' => 'GET',
'header' => "User-Agent: " . $ua . "\r\n",
'ignore_errors' => true,
'timeout' => 15
),
'ssl' => array(
'verify_peer' => false,
'verify_peer_name' => false
)
);
lg('opts built');
$context = stream_context_create($opts);
lg('context created, fetching...');
$body = @file_get_contents($url, false, $context);
lg('fetch done: ' . ($body === false ? 'FAILED' : 'OK len=' . strlen($body)));
if ($body === false) {
    http_response_code(502);
    echo 'Proxy error';
    lg('exiting due to fetch failure');
    exit;
}
$content_type = 'text/html';
if (isset($http_response_header)) {
    foreach ($http_response_header as $h) {
        if (stripos($h, 'Content-Type:') === 0) {
            $content_type = trim(substr($h, strlen('Content-Type:')));
        }
    }
}
lg('content type: ' . $content_type);
header('Content-Type: ' . $content_type);
lg('header sent');
$host = $_SERVER['HTTP_HOST'];
if (stripos($content_type, 'text/html') !== false) {
    $body = str_replace($target_host, $host, $body);
}
lg('about to echo body, len=' . strlen($body));
echo $body;
lg('done');
