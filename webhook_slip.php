<?php
header('Content-Type: application/json');
require_once 'db_con.php';

$json_str = file_get_contents('php://input');
$data = json_decode($json_str, true);

file_put_contents('webhook_log.txt', "[" . date("Y-m-d H:i:s") . "] " . $json_str . "\n\n", FILE_APPEND);

if (isset($data['success']) && $data['success'] === true) {
    $amount = $data['data']['amount'];
    $account_id = isset($data['data']['receiver']['ref1']) ? intval($data['data']['receiver']['ref1']) : 0;

    if ($account_id > 0) {
        $points = floor($amount); 
        try {
            $chk = $conn->prepare("SELECT * FROM acc_reg_num WHERE account_id = :aid AND `key` = '#CASHPOINTS'");
            $chk->bindParam(':aid', $account_id);
            $chk->execute();
            
            if ($chk->rowCount() > 0) {
                $sql = "UPDATE acc_reg_num SET value = value + :pts WHERE account_id = :aid AND `key` = '#CASHPOINTS'";
            } else {
                $sql = "INSERT INTO acc_reg_num (account_id, `key`, `index`, `value`) VALUES (:aid, '#CASHPOINTS', 0, :pts)";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':pts', $points);
            $stmt->bindParam(':aid', $account_id);
            $stmt->execute();

            $log_sql = "INSERT INTO web_topup_log (account_id, userid, amount, points_receive, status) VALUES (:aid, 'WebhookAPI', :amt, :pts, 'success')";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bindParam(':aid', $account_id);
            $log_stmt->bindParam(':amt', $amount);
            $log_stmt->bindParam(':pts', $points);
            $log_stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Points added']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No Account ID (Ref1) found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Data']);
}
?>