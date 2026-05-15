<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Controllers/AdminController.php';

$controller = new AdminController();

$courseId = $_GET['course_id'] ?? null;

$courses = $controller->getSubject();

// กันพัง
echo json_encode([
    'status' => 'success',
    'data' => $courses ?? []
]);
exit;
