<?php
session_start();
require_once 'db_con.php';

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไปหน้า index
if (isset($_SESSION['account_id'])) {
    header("location: index");
    exit;
}

$chk_alert = "";

if (isset($_POST['btn_login'])) {
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT account_id, userid, user_pass FROM login WHERE userid = :uid LIMIT 1");
        $stmt->execute([':uid' => $userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['user_pass'] === md5($password)) {
            $_SESSION['account_id'] = $row['account_id'];
            $_SESSION['userid'] = $row['userid'];

            $chk_alert = "
                Swal.fire({
                    icon: 'success',
                    title: 'เข้าสู่ระบบสำเร็จ!',
                    text: 'กำลังพาท่านไปที่หน้าแรก...',
                    background: 'rgba(17, 21, 28, 0.95)',
                    color: '#fff',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => { window.location = 'index'; });
            ";
        } else {
            $chk_alert = "Swal.fire({icon: 'error', title: 'เข้าสู่ระบบไม่สำเร็จ', text: 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
        }
    } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RO Village</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #050505; }
        .mro-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center; background-size: cover; filter: blur(3px) brightness(0.4); z-index: -2; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="w-full max-w-md px-4">
        <div class="bg-[#11151c]/90 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-yellow-600 via-yellow-400 to-yellow-600"></div>

            <div class="text-center mb-8">
                <h2 class="text-3xl font-extrabold text-white uppercase tracking-wider"><i class="fas fa-user-lock text-yellow-500 mr-2"></i> Login</h2>
                <p class="text-gray-400 text-sm mt-2">เข้าสู่ระบบเพื่อจัดการตัวละคร</p>
            </div>
            
            <form action="" method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-500"></i>
                        </div>
                        <input type="text" name="userid" required class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 transition placeholder-gray-600" placeholder="ระบุชื่อผู้ใช้งาน">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-500"></i>
                        </div>
                        <input type="password" name="password" required class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 transition placeholder-gray-600" placeholder="ระบุรหัสผ่าน">
                    </div>
                </div>

                <button type="submit" name="btn_login" class="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-black font-bold py-3 px-4 rounded-full shadow-lg hover:shadow-yellow-500/30 hover:scale-[1.02] transition transform duration-200">
                    เข้าสู่ระบบ
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">ยังไม่มีไอดี? <a href="register" class="text-yellow-500 hover:text-yellow-400 font-bold transition">สมัครสมาชิก</a></p>
            </div>
        </div>
    </div>

    <script><?php echo $chk_alert; ?></script>
</body>
</html>