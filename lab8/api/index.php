<?php
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($request, PHP_URL_PATH);
$base = '/lab7/api';

$path = str_replace($base, '', $path);
$segments = explode('/', trim($path, '/'));

if (empty($segments[0])) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

switch ($segments[0]) {
    case 'users':
        if (!isset($segments[1])) {
            // POST /api/users
            require_once 'users.php';
        } else {
            // PUT /api/users/{id}
            $_SERVER['PATH_INFO'] = '/' . $segments[1];
            require_once 'users/id.php';
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}
?>