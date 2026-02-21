<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['account_id'])) { header("location: login"); exit; }

$process_status = "";
$result_title = "";
$result_message = "";
$points_received = 0;
$debug_info = "";

if (!isset($_POST['transaction_id']) || !isset($_FILES['slip_file'])) {
    header("location: topup_select"); exit;
}

$transaction_id = $_POST['transaction_id'];
$userid = $_SESSION['userid'];

$stmt = $conn->prepare("SELECT * FROM web_topup_log WHERE transaction_id = :txid AND userid = :uid AND status = 'wait_pay' LIMIT 1");
$stmt->execute([':txid' => $transaction_id, ':uid' => $userid]);
$txn = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$txn) {
    $process_status = "error";
    $result_title = "รายการไม่ถูกต้อง";
    $result_message = "ไม่พบรายการสั่งซื้อ หรือรายการหมดอายุแล้ว";
} else {
    $expected_amount = floatval($txn['amount']);
    $points_to_give = intval($txn['points_receive']);
    $db_id = $txn['id'];
    $account_id = $txn['account_id'];

    if (strtotime($txn['created_at']) + 300 < time()) {
        $process_status = "error";
        $result_title = "รายการหมดอายุ";
        $result_message = "คุณทำรายการเกินเวลาที่กำหนด (5 นาที)";
    } elseif ($_FILES['slip_file']['error'] !== UPLOAD_ERR_OK) {
        $process_status = "error";
        $result_title = "อัพโหลดล้มเหลว";
        $result_message = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
    } else {
        $file_tmp  = $_FILES['slip_file']['tmp_name'];
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $new_filename = "slip_" . time() . ".jpg";
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $target_file)) {
            $real_path = str_replace('\\', '/', realpath($target_file));
            $conn->prepare("UPDATE web_topup_log SET slip_filename = :fname WHERE id = :id")->execute([':fname'=>$new_filename, ':id'=>$db_id]);

            // Call API
            $ch = curl_init();
            $cfile = new CURLFile($real_path, 'image/jpeg', 'slip.jpg');
            $post_data = ['file' => $cfile];

            curl_setopt($ch, CURLOPT_URL, 'https://developer.easyslip.com/api/v1/verify');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $config_easyslip_api_key, 'Expect:']);

            $response = curl_exec($ch);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($curl_err) {
                $process_status = "error"; $result_title = "API Error"; $result_message = "Connection Failed: $curl_err";
            } else {
                $result = json_decode($response, true);
                
                if (isset($result['status']) && $result['status'] == 200) {
                    $slip_amount = floatval($result['data']['amount']['amount']);
                    $bank_ref = $result['data']['transRef'];
                    $api_receiver = $result['data']['receiver']['account']['name'] ?? '';
                    
                    $receiver_str_check = is_array($api_receiver) ? implode(" ", $api_receiver) : (string)$api_receiver;
                    $receiver_clean = preg_replace('/\s+/', ' ', trim($receiver_str_check));
                    $my_valid_names = [];
                    if (isset($config_account_names) && is_array($config_account_names)) $my_valid_names = array_merge($my_valid_names, $config_account_names);
                    if (isset($config_account_name) && !is_array($config_account_name)) $my_valid_names[] = $config_account_name;
                    $my_valid_names = array_filter($my_valid_names, function($val) { return !empty($val) && mb_strlen(trim($val)) > 2; });

                    $name_matched = false;
                    if (empty($my_valid_names)) { $name_matched = true; } 
                    else {
                        foreach ($my_valid_names as $valid_name) {
                            $valid_name_clean = preg_replace('/\s+/', ' ', trim($valid_name));
                            if (stripos($receiver_clean, $valid_name_clean) !== false) { $name_matched = true; break; }
                        }
                    }

                    $amount_ok = false;
                    $price_plan_guess = ceil($expected_amount);
                    if (number_format($slip_amount, 2) == number_format($expected_amount, 2)) { $amount_ok = true; } 
                    elseif (number_format($slip_amount, 2) == number_format($price_plan_guess, 2)) { $amount_ok = true; }

                    if (!$amount_ok) {
                        $process_status = "error"; $result_title = "ยอดเงินไม่ตรง";
                        $result_message = "ระบบรอรับยอด " . number_format($expected_amount, 2) . " (สลิปมี " . number_format($slip_amount, 2) . ")";
                    } elseif (!$name_matched) {
                        $process_status = "error"; $result_title = "ชื่อผู้รับไม่ถูกต้อง";
                        $result_message = "ชื่อในสลิปไม่ตรงกับระบบ";
                    } else {
                        $chk_dup = $conn->prepare("SELECT id FROM web_topup_log WHERE bank_ref = :bref AND status = 'success'");
                        $chk_dup->execute([':bref' => $bank_ref]);
                        
                        if ($chk_dup->rowCount() > 0) {
                            $process_status = "error"; $result_title = "สลิปซ้ำ"; $result_message = "สลิปนี้ถูกใช้งานไปแล้ว";
                        } else {
                            $process_status = "success";
                            $result_title = "เติมเงินสำเร็จ";
                            $result_message = "ได้รับ " . number_format($points_to_give) . " Cash Points";
                            $points_received = $points_to_give;

                            $sql_cash = "INSERT INTO acc_reg_num (account_id, `key`, `index`, value) 
                                         VALUES (:aid, '#CASHPOINTS', 0, :pts) 
                                         ON DUPLICATE KEY UPDATE value = value + :pts";
                            
                            $stmt_cash = $conn->prepare($sql_cash);
                            $stmt_cash->execute([':aid' => $account_id, ':pts' => $points_to_give]);

                            $conn->prepare("UPDATE web_topup_log SET status = 'success', api_response = :res, bank_ref = :bref WHERE id = :id")->execute([':res'=>$response, ':bref'=>$bank_ref, ':id'=>$db_id]);
                        }
                    }
                } else {
                    $process_status = "error"; $result_title = "สลิปไม่ผ่าน";
                    $result_message = "EasySlip ปฏิเสธ: " . ($result['message'] ?? 'Unknown');
                }
            }
            if ($process_status == "error") {
                $conn->prepare("UPDATE web_topup_log SET status = 'failed', api_response = :res WHERE id = :id")->execute([':res'=>$response ?? $curl_err, ':id'=>$db_id]);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result - RO Village</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mro-primary: #ffc107; --mro-panel-bg: rgba(20, 30, 60, 0.95); --mro-text-light: #e0e0e0; }
        body { font-family: 'Poppins', sans-serif; background-color: #0a0a0a; color: var(--mro-text-light); min-height: 100vh; }
        .mro-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center; background-size: cover; filter: blur(8px) brightness(0.4); z-index: -2; }
        .result-container { min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .result-card { background: var(--mro-panel-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 50px 30px; text-align: center; max-width: 500px; width: 100%; box-shadow: 0 10px 40px rgba(0,0,0,0.6); position: relative; overflow: hidden; }
        .status-icon { font-size: 80px; margin-bottom: 20px; display: inline-block; border-radius: 50%; width: 120px; height: 120px; line-height: 120px; }
        .icon-success { color: #2ecc71; background: rgba(46, 204, 113, 0.1); border: 2px solid #2ecc71; }
        .icon-error { color: #e74c3c; background: rgba(231, 76, 60, 0.1); border: 2px solid #e74c3c; }
        .result-title { font-size: 2rem; font-weight: 800; margin-bottom: 10px; color: white; }
        .result-msg { font-size: 1.1rem; color: #aaa; margin-bottom: 30px; }
        .points-badge { background: linear-gradient(45deg, #ffc107, #ff9800); color: black; font-weight: 800; font-size: 1.5rem; padding: 10px 30px; border-radius: 50px; display: inline-block; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4); }
        .btn-action { display: block; width: 100%; padding: 12px; border-radius: 50px; font-weight: 700; text-transform: uppercase; text-decoration: none; transition: 0.3s; font-size: 1rem; }
        .btn-home { background: transparent; border: 2px solid rgba(255,255,255,0.2); color: white; }
        .btn-home:hover { background: white; color: black; }
        .btn-retry { background: #e74c3c; border: none; color: white; margin-bottom: 10px; }
        .btn-retry:hover { background: #c0392b; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>
    <div class="result-container">
        <?php if ($process_status == "success"): ?>
            <div class="result-card">
                <div class="status-icon icon-success"><i class="fas fa-check"></i></div>
                <div class="result-title">PAYMENT SUCCESS</div>
                <div class="result-msg"><?php echo htmlspecialchars($result_message); ?></div>
                <div class="points-badge"><i class="fas fa-coins me-2"></i> +<?php echo number_format($points_received); ?> Points</div>
                <a href="member" class="btn-action btn-home">Back to Member Center</a>
            </div>
        <?php else: ?>
            <div class="result-card" style="border-color: #e74c3c;">
                <div class="status-icon icon-error"><i class="fas fa-times"></i></div>
                <div class="result-title text-danger">PAYMENT FAILED</div>
                <div class="result-msg text-white"><?php echo htmlspecialchars($result_message); ?></div>
                <a href="topup_select" class="btn-action btn-retry">Try Again</a>
                <a href="member" class="btn-action btn-home">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>