<?php
require_once 'db_con.php';

echo "<h3>📡 กำลังทดสอบการเชื่อมต่อกับ EasySlip...</h3>";
echo "<hr>";

// ตรวจสอบว่ามี API Key หรือยัง
if (empty($config_easyslip_api_key)) {
    die("❌ ผิดพลาด: ไม่พบ API Key ในไฟล์ db_con.php");
}

echo "🔹 <strong>API Key ของคุณ:</strong> " . substr($config_easyslip_api_key, 0, 10) . "...... (ซ่อนไว้)<br><br>";

// เริ่มต้นยิง cURL แบบส่งข้อมูลเปล่าๆ ไป
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://developer.easyslip.com/api/v1/verify');
curl_setopt($ch, CURLOPT_POST, 1);
// เราจงใจไม่ส่งรูปภาพ เพื่อดูว่า API จะตอบกลับมาว่าอะไร
curl_setopt($ch, CURLOPT_POSTFIELDS, []); 
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $config_easyslip_api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิด SSL เพื่อทดสอบ
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // รอแค่ 10 วิพอ

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    // กรณี 1: เชื่อมต่อไม่ได้เลย (เน็ตหลุด, DNS ผิด, XAMPP บล็อก)
    echo "<div style='color:red; border:1px solid red; padding:10px;'>";
    echo "<h2>❌ เชื่อมต่อไม่สำเร็จ (Connection Failed)</h2>";
    echo "<strong>สาเหตุ:</strong> เครื่องของคุณส่งข้อมูลออกไปหา EasySlip ไม่ได้<br>";
    echo "<strong>Error Message:</strong> $curl_error";
    echo "</div>";

} elseif ($http_code == 401) {
    // กรณี 2: เชื่อมติด แต่ API Key ผิด
    echo "<div style='color:orange; border:1px solid orange; padding:10px;'>";
    echo "<h2>⚠️ เชื่อมต่อได้ แต่ API Key ผิด (Unauthorized)</h2>";
    echo "<strong>Server ตอบกลับ:</strong> $response<br>";
    echo "แนะนำให้เช็ค API Key ในไฟล์ db_con.php อีกครั้ง";
    echo "</div>";

} elseif ($http_code == 400 || $http_code == 200) {
    // กรณี 3: เชื่อมต่อสำเร็จ
    echo "<div style='color:green; border:1px solid green; padding:10px;'>";
    echo "<h2>✅ การเชื่อมต่อสมบูรณ์ (Connected Successfully!)</h2>";
    echo "เว็บของคุณคุยกับ EasySlip รู้เรื่องแล้ว!<br><br>";
    echo "<strong>สถานะ HTTP:</strong> $http_code<br>";
    echo "<strong>EasySlip ตอบกลับมาว่า:</strong> <span style='background:#eee; padding:2px;'>$response</span><br><br>";
    
    $json = json_decode($response, true);
    if(isset($json['message']) && $json['message'] == 'image file is required') {
        echo "💡 <strong>แปลผล:</strong> EasySlip บอกว่า 'ขอดูรูปหน่อย' <br>แสดงว่าท่อส่งข้อมูลทำงานปกติ ปัญหาที่เหลือคือเรื่องไฟล์รูปอย่างเดียวครับ";
    }
    echo "</div>";

} else {
    // กรณีอื่นๆ
    echo "<strong>Status Code:</strong> $http_code<br>";
    echo "<strong>Response:</strong> $response";
}
?>