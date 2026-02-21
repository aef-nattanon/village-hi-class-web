<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['account_id'])) { header("location: login"); exit; }

$userid = $_SESSION['userid'];
$account_id = $_SESSION['account_id'];

$pkg_price_req = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

$current_txn = null;

$sql = "SELECT * FROM web_topup_log 
        WHERE userid = :uid 
        AND status = 'wait_pay' 
        AND created_at > (NOW() - INTERVAL 5 MINUTE) 
        ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':uid' => $userid]);
$current_txn = $stmt->fetch(PDO::FETCH_ASSOC);

if ($current_txn) {
    $pay_amount = floatval($current_txn['amount']);
    $transaction_id = $current_txn['transaction_id'];
    $expire_timestamp = strtotime($current_txn['created_at']) + 300; 
} else {
    if ($pkg_price_req <= 0) { header("location: topup_select"); exit; }

    $random_satang = rand(1, 99) / 100;
    $pay_amount = ($pkg_price_req - 1) + $random_satang;
    
    $points_to_give = 0;
    if ($pkg_price_req == 50) $points_to_give = 500;
    elseif ($pkg_price_req == 100) $points_to_give = 1000;
    elseif ($pkg_price_req == 150) $points_to_give = 1500;
    elseif ($pkg_price_req == 300) $points_to_give = 3000;
    elseif ($pkg_price_req == 500) $points_to_give = 5250;
    elseif ($pkg_price_req == 1000) $points_to_give = 11000;
    else $points_to_give = floor($pkg_price_req * 10);

    $transaction_id = "TXN" . date("YmdHis") . rand(100,999);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $expire_timestamp = time() + 300;

    try {
        $sql_ins = "INSERT INTO web_topup_log (transaction_id, account_id, userid, amount, points_receive, status, ip_address, created_at) 
                    VALUES (:txid, :aid, :uid, :amt, :pts, 'wait_pay', :ip, NOW())";
        $stmt_ins = $conn->prepare($sql_ins);
        $stmt_ins->execute([
            ':txid' => $transaction_id,
            ':aid' => $account_id,
            ':uid' => $userid,
            ':amt' => $pay_amount,
            ':pts' => $points_to_give,
            ':ip' => $ip_address
        ]);
    } catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
}

$remaining_seconds = $expire_timestamp - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;

if (!isset($config_promptpay_id)) { $config_promptpay_id = "0812345678"; }
$clean_promptpay = preg_replace('/[^0-9]/', '', $config_promptpay_id);
$url_amount = number_format($pay_amount, 2, '.', '');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - RO Village</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --mro-primary: #ffc107; --mro-panel-bg: rgba(20, 30, 60, 0.90); --mro-text-light: #e0e0e0; }
        body { font-family: 'Poppins', sans-serif; background-color: #0a0a0a; color: var(--mro-text-light); min-height: 100vh; }
        .mro-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center; background-size: cover; filter: blur(5px) brightness(0.5); z-index: -2; }
        .payment-container { max-width: 900px; margin: 100px auto 50px; padding: 20px; }
        .pay-card { background: var(--mro-panel-bg); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 30px; height: 100%; }
        .amount-display { font-size: 2.8rem; font-weight: 800; color: var(--mro-primary); margin-bottom: 5px; line-height: 1; text-shadow: 0 0 15px rgba(255,193,7,0.3); }
        .qr-box { background: white; padding: 15px; border-radius: 10px; display: inline-block; margin-bottom: 20px; }
        .qr-img { width: 100%; max-width: 250px; }
        .timer-box { font-size: 1.2rem; font-weight: bold; color: #ff6b6b; margin-bottom: 15px; border: 1px solid #ff6b6b; display: inline-block; padding: 5px 15px; border-radius: 20px; background: rgba(255, 107, 107, 0.1); }
        .upload-area { border: 2px dashed rgba(255,255,255,0.3); border-radius: 10px; padding: 30px 20px; text-align: center; cursor: pointer; position: relative; }
        .upload-area:hover { border-color: var(--mro-primary); background: rgba(255, 193, 7, 0.05); }
        input[type="file"] { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; }
        .btn-confirm { background: linear-gradient(180deg, #ffc107, #ff8f00); border: none; color: black; font-weight: 800; width: 100%; padding: 12px; border-radius: 50px; text-transform: uppercase; margin-top: 20px; }
        .btn-back { color: #aaa; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="payment-container">
        <h2 class="text-center text-white mb-4 text-uppercase fw-bold">Scan to Pay</h2>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="pay-card text-center">
                    <div class="text-white-50 text-uppercase mb-2">ยอดที่ต้องโอน (ภายใน 5 นาที)</div>
                    <div class="amount-display"><?php echo number_format($pay_amount, 2); ?></div>
                    <div class="text-white-50 mb-3">THB</div>

                    <div class="timer-box">
                        <i class="fas fa-clock me-2"></i> <span id="countdown">05:00</span>
                    </div>

                    <div class="qr-box">
                        <img src="https://promptpay.io/<?php echo $clean_promptpay; ?>/<?php echo $url_amount; ?>.png" class="qr-img" alt="QR Code">
                    </div>
                    <small class="text-white-50 d-block">กรุณาโอนยอดให้ตรงเศษสตางค์ (คุณจะได้ Cash Point เต็มจำนวน)</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="pay-card">
                    <h4 class="text-white mb-4"><i class="fas fa-file-upload text-warning me-2"></i> ยืนยันการชำระเงิน</h4>
                    
                    <form action="topup_process" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">
                        
                        <div class="upload-area" id="drop-zone">
                            <input type="file" name="slip_file" id="slip_file" accept="image/jpeg,image/png" required onchange="showFileName()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
                            <div class="fw-bold text-white">คลิกเพื่อแนบสลิป</div>
                            <small class="text-white-50">JPG/PNG Max 5MB</small>
                        </div>
                        <div id="file-name" class="mt-2 text-warning fw-bold text-center" style="display:none;"></div>

                        <button type="submit" class="btn-confirm">ยืนยันการโอนเงิน</button>
                        <div class="text-center mt-3">
                            <a href="cancel_topup" class="btn-back">ยกเลิกรายการ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let timeLeft = <?php echo $remaining_seconds; ?>;
        function updateTimer() {
            if (timeLeft <= 0) {
                document.getElementById('countdown').innerHTML = "หมดเวลา!";
                setTimeout(() => window.location.reload(), 1000);
            } else {
                let m = Math.floor(timeLeft / 60);
                let s = timeLeft % 60;
                document.getElementById('countdown').innerHTML = `${m}:${s < 10 ? '0' : ''}${s}`;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }
        updateTimer();

        function showFileName() {
            const input = document.getElementById('slip_file');
            if (input.files[0]) {
                document.getElementById('file-name').textContent = "Selected: " + input.files[0].name;
                document.getElementById('file-name').style.display = 'block';
                document.getElementById('drop-zone').style.borderColor = '#ffc107';
            }
        }
    </script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>