<?php
/**
 * Web Extension Checker Endpoint
 * 
 * This file should be accessible via web server to check Apache PHP extensions
 * URL: http://localhost/invoice/public/check-web-extensions.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $data = [
        'success' => true,
        'timestamp' => time(),
        'php_version' => PHP_VERSION,
        'sapi' => php_sapi_name(),
        'loaded_extensions' => get_loaded_extensions(),
        'extension_count' => count(get_loaded_extensions()),
        'config_file' => php_ini_loaded_file(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}