<?php
session_start();
require_once 'db_con.php';

if (isset($_SESSION['account_id']) && isset($_SESSION['userid'])) {
    $userid = $_SESSION['userid'];
    try {
        $sql = "UPDATE web_topup_log 
                SET status = 'cancelled' 
                WHERE userid = :uid AND status = 'wait_pay'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':uid' => $userid]);
    } catch (PDOException $e) {
        // กรณี Error ไม่ต้องทำอะไร ปล่อยผ่านไปหน้าเลือกราคาเลย
    }
}

header("location: topup_select");
exit;
?>