<?php
session_start();
require_once 'db_con.php';

// ตรวจสอบ admin
if (!isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
  http_response_code(403);
  exit;
}

// ตรวจสอบ file upload
if (!isset($_FILES['file'])) {
  echo json_encode(['error' => 'ไม่มีไฟล์อัพโหลด']);
  http_response_code(400);
  exit;
}

$file = $_FILES['file'];

// ตรวจสอบ file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
  echo json_encode(['error' => 'ขนาดไฟล์ใหญ่เกินไป (max 5MB)']);
  http_response_code(413);
  exit;
}

// ตรวจสอบ MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime_type, $allowed_types)) {
  echo json_encode(['error' => 'ประเภทไฟล์ไม่อนุญาต (JPG, PNG, GIF, WebP เท่านั้น)']);
  http_response_code(415);
  exit;
}

// ตรวจสอบ uploads directory
$uploads_dir = __DIR__ . '/uploads/posts/';
if (!file_exists($uploads_dir)) {
  mkdir($uploads_dir, 0755, true);
}

// สร้างชื่อไฟล์ unique
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'post_' . time() . '_' . uniqid() . '.' . $file_ext;
$target_path = $uploads_dir . $new_filename;

// อัพโหลดไฟล์
if (move_uploaded_file($file['tmp_name'], $target_path)) {
  // ส่ง response กลับ
  echo json_encode([
    'location' => 'uploads/posts/' . $new_filename
  ]);
  http_response_code(200);
} else {
  echo json_encode(['error' => 'อัพโหลดไฟล์ล้มเหลว']);
  http_response_code(500);
}
