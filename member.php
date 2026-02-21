<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['account_id'])) {
    header("location: login");
    exit;
}

$account_id = $_SESSION['account_id'];
$userid = $_SESSION['userid'];

$swal_alert = "";

if (isset($_POST['btn_save_pass'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
        $swal_alert = "Swal.fire({icon: 'warning', title: 'กรุณากรอกข้อมูลให้ครบ', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    } elseif ($new_pass !== $confirm_pass) {
        $swal_alert = "Swal.fire({icon: 'error', title: 'รหัสผ่านใหม่ไม่ตรงกัน', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    } elseif (mb_strlen($new_pass) < 4) {
        $swal_alert = "Swal.fire({icon: 'warning', title: 'รหัสสั้นเกินไป', text: 'ต้องมีอย่างน้อย 4 ตัวอักษร', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_pass FROM login WHERE userid = :uid LIMIT 1");
            $stmt->execute([':uid' => $userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['user_pass'] === md5($old_pass)) {
                $update = $conn->prepare("UPDATE login SET user_pass = :pass WHERE userid = :uid");
                if ($update->execute([':pass' => md5($new_pass), ':uid' => $userid])) {
                    $swal_alert = "Swal.fire({icon: 'success', title: 'สำเร็จ!', text: 'เปลี่ยนรหัสผ่านเรียบร้อย', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
                }
            } else {
                $swal_alert = "Swal.fire({icon: 'error', title: 'รหัสผ่านเดิมไม่ถูกต้อง', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
            }
        } catch (PDOException $e) { $swal_alert = "Swal.fire({icon: 'error', title: 'DB Error', text: '".$e->getMessage()."'});"; }
    }
}

$cash_points = 0; $email = "-"; $sex = "-"; $last_login = "-";
try {
    $stmt = $conn->prepare("SELECT value FROM acc_reg_num WHERE account_id = :aid AND `key` = '#CASHPOINTS'");
    $stmt->execute([':aid' => $account_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $cash_points = $row['value']; }

    $stmt2 = $conn->prepare("SELECT email, sex, lastlogin FROM login WHERE account_id = :aid");
    $stmt2->execute([':aid' => $account_id]);
    if ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $email = $row2['email']; $sex = ($row2['sex'] == 'M') ? 'Male' : 'Female'; $last_login = $row2['lastlogin'];
    }
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member - RO Village</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #050505; color: #fff; }
        .mro-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center; background-size: cover; filter: blur(3px) brightness(0.4); z-index: -2; }
    </style>
</head>
<body class="antialiased">

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="container mx-auto px-4 pt-28 pb-10 max-w-5xl">
        
        <div class="bg-[#11151c]/90 backdrop-blur border border-white/10 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center shadow-xl mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-yellow-500"></div>
            
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-900 to-indigo-900 flex items-center justify-center border-4 border-white/10 shadow-[0_0_15px_rgba(255,193,7,0.3)] mb-4 md:mb-0 md:mr-8">
                <i class="fas fa-user-astronaut text-4xl text-yellow-500"></i>
            </div>
            
            <div class="text-center md:text-left flex-1">
                <h2 class="text-3xl font-bold uppercase tracking-wide"><?php echo htmlspecialchars($userid); ?></h2>
                <p class="text-gray-400 text-sm mt-1">Status: <span class="text-green-400 font-bold">Active</span></p>
            </div>

            <div class="mt-4 md:mt-0 bg-white/5 border-l-4 border-yellow-500 rounded px-6 py-3 text-right min-w-[200px]">
                <div class="text-xs text-yellow-400 uppercase tracking-widest">Cash Balance</div>
                <div class="text-2xl font-extrabold text-white mt-1">
                    <i class="fas fa-coins text-yellow-500 mr-2 text-lg"></i><?php echo number_format($cash_points); ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="md:col-span-2 bg-[#11151c]/90 backdrop-blur border border-white/10 rounded-xl p-6 shadow-lg h-full">
                <div class="border-b border-white/10 pb-4 mb-4 flex items-center">
                    <i class="fas fa-id-card text-yellow-500 mr-3 text-xl"></i>
                    <h3 class="text-xl font-bold">ข้อมูลสมาชิก</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between border-b border-white/5 pb-3">
                        <span class="text-gray-400">Email</span>
                        <span class="font-medium"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-white/5 pb-3">
                        <span class="text-gray-400">เพศ</span>
                        <span class="font-medium"><?php echo $sex; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-white/5 pb-3">
                        <span class="text-gray-400">Last Login</span>
                        <span class="font-medium text-yellow-100"><?php echo $last_login; ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-[#11151c]/90 backdrop-blur border border-white/10 rounded-xl p-6 shadow-lg h-full">
                <div class="border-b border-white/10 pb-4 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-3 text-xl"></i>
                    <h3 class="text-xl font-bold">เมนูจัดการ</h3>
                </div>
                
                <div class="space-y-3">
                    <a href="topup_select" class="flex items-center p-4 bg-white/5 border border-white/10 rounded-lg hover:bg-yellow-500/10 hover:border-yellow-500 transition group">
                        <i class="fas fa-wallet text-2xl text-yellow-500 w-10 text-center mr-3 group-hover:scale-110 transition"></i>
                        <div>
                            <div class="font-bold group-hover:text-yellow-400 transition">ระบบเติมเงิน</div>
                            <div class="text-xs text-gray-500">Topup Cash Point</div>
                        </div>
                    </a>

                    <a href="#" class="flex items-center p-4 bg-white/5 border border-white/10 rounded-lg opacity-60 cursor-not-allowed">
                        <i class="fas fa-gift text-2xl text-gray-500 w-10 text-center mr-3"></i>
                        <div>
                            <div class="font-bold text-gray-400">Item Shop</div>
                            <div class="text-xs text-gray-600">Coming Soon</div>
                        </div>
                    </a>

                    <button onclick="openModal()" class="w-full flex items-center p-4 bg-white/5 border border-white/10 rounded-lg hover:bg-yellow-500/10 hover:border-yellow-500 transition group text-left">
                        <i class="fas fa-key text-2xl text-yellow-500 w-10 text-center mr-3 group-hover:scale-110 transition"></i>
                        <div>
                            <div class="font-bold group-hover:text-yellow-400 transition">เปลี่ยนรหัสผ่าน</div>
                            <div class="text-xs text-gray-500">Change Password</div>
                        </div>
                    </button>

                    <a href="#" onclick="confirmLogout(event)" class="flex items-center p-4 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 hover:border-red-500 transition group mt-4">
                        <i class="fas fa-sign-out-alt text-2xl text-red-400 w-10 text-center mr-3 group-hover:rotate-180 transition duration-500"></i>
                        <div class="font-bold text-red-400 group-hover:text-red-300">ออกจากระบบ</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="passModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="bg-[#11151c] border border-white/20 rounded-xl shadow-2xl w-full max-w-md p-6 relative animate-[fadeIn_0.3s_ease-out]">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            
            <h3 class="text-xl font-bold mb-6 flex items-center text-white"><i class="fas fa-lock text-yellow-500 mr-2"></i> เปลี่ยนรหัสผ่าน</h3>
            
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="old_pass" required class="w-full bg-black/30 border border-white/10 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">รหัสผ่านใหม่</label>
                    <input type="password" name="new_pass" required class="w-full bg-black/30 border border-white/10 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_pass" required class="w-full bg-black/30 border border-white/10 rounded px-3 py-2 text-white focus:border-yellow-500 focus:outline-none">
                </div>
                <button type="submit" name="btn_save_pass" class="w-full bg-yellow-500 text-black font-bold py-2 rounded hover:bg-yellow-400 transition">บันทึกข้อมูล</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('passModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('passModal').classList.add('hidden'); }

        function confirmLogout(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'success', title: 'ออกจากระบบเรียบร้อย',
                text: 'กำลังกลับสู่หน้าหลัก...',
                background: '#11151c', color: '#fff',
                showConfirmButton: false, timer: 2000,
                timerProgressBar: true,
                didOpen: () => { fetch('logout.php'); }
            }).then(() => { window.location = 'index'; });
        }
        <?php echo $swal_alert; ?>
    </script>
</body>
</html>