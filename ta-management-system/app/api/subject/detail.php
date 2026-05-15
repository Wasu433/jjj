<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Controllers/SubjectController.php';

$controller = new SubjectController();
$courseId = $_GET['course_id'] ?? null;

$token = $controller->getToken();
$getAcad = $controller->getAcad($token);
$courses = $controller->getCourseDetail($token, $getAcad['year'], $getAcad['semester'], $courseId);


// กันพัง
echo json_encode([
    'status' => 'success',
    'data' => $courses ?? []
]);
exit;
