<?php
session_start();

// 1. สร้างรหัสสุ่ม 6 ตัว
$random_alpha = md5(rand());
$captcha_code = substr($random_alpha, 0, 6);
$_SESSION['captcha_code'] = $captcha_code;

// 2. ตั้งค่าขนาดภาพ
$width = 160;
$height = 60;
$scale = 2;

$small_width = ceil($width / $scale);
$small_height = ceil($height / $scale);

// 3. สร้างภาพเล็กก่อน
$small_im = imagecreatetruecolor($small_width, $small_height);

$bg_color = imagecolorallocate($small_im, 210, 190, 160); // พื้นหลัง
$text_color = imagecolorallocate($small_im, 60, 40, 20);   // สีตัวอักษร
$line_color = imagecolorallocate($small_im, 180, 160, 130); // สีเส้น

imagefilledrectangle($small_im, 0, 0, $small_width, $small_height, $bg_color);

for($i=0; $i<3; $i++) {
    imageline($small_im, rand(0, $small_width), rand(0, $small_height), rand(0, $small_width), rand(0, $small_height), $line_color);
}

// 4. วาดตัวหนังสือลงในภาพเล็ก
$font_size = 5;
$font_width = imagefontwidth($font_size);
$font_height = imagefontheight($font_size);
$text_width = strlen($captcha_code) * $font_width;
$x = ($small_width - $text_width) / 2;
$y = ($small_height - $font_height) / 2;

imagestring($small_im, $font_size, $x, $y, $captcha_code, $text_color);

// 5. สร้างภาพจริง (ภาพใหญ่)
$im = imagecreatetruecolor($width, $height);

// 6. ขยายภาพจากเล็กไปใหญ่ (Zoom)
imagecopyresized(
    $im, $small_im, 
    0, 0, 0, 0, 
    $width, $height, 
    $small_width, $small_height
);

imagedestroy($small_im);

// 7. ส่งออกรูปภาพ
header("Content-Type: image/png");
imagepng($im);
imagedestroy($im);
?>