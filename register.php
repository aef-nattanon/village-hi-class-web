<?php
session_start();
require_once 'db_con.php';

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไปหน้า index
if (isset($_SESSION['account_id'])) {
    header("location: index");
    exit;
}

$chk_alert = "";

if (isset($_POST['btn_register'])) {
    $userid = $_POST['userid'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $email = $_POST['email'];
    $sex = $_POST['sex'];

    if ($pass != $confirm_pass) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'รหัสผ่านไม่ตรงกัน', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    } 
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $userid)) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'Username ผิดเงื่อนไข', text: 'อนุญาตเฉพาะภาษาอังกฤษและตัวเลขเท่านั้น', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    elseif (strlen($userid) < 6) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'Username สั้นเกินไป', text: 'ต้องมีความยาวอย่างน้อย 6 ตัวอักษร', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    elseif (stripos($userid, 'admin') !== false || stripos($userid, 'gm') !== false) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'Username ไม่สุภาพ', text: 'ห้ามใช้คำว่า Admin หรือ GM', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'อีเมลไม่ถูกต้อง', text: 'กรุณากรอกอีเมลให้ถูกต้อง', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $pass)) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'รหัสผ่านผิดเงื่อนไข', text: 'อนุญาตเฉพาะภาษาอังกฤษและตัวเลขเท่านั้น', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    elseif (strlen($pass) < 6) {
        $chk_alert = "Swal.fire({icon: 'warning', title: 'รหัสผ่านสั้นเกินไป', text: 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
    }
    else {
        try {
            $check = $conn->prepare("SELECT userid FROM login WHERE userid = :uid");
            $check->execute([':uid' => $userid]);
            if ($check->rowCount() > 0) {
                $chk_alert = "Swal.fire({icon: 'error', title: 'ไอดีนี้มีผู้ใช้งานแล้ว', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
            } else {
                $sql = "INSERT INTO login (userid, user_pass, sex, email) VALUES (:uid, :pass, :sex, :email)";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute([':uid'=>$userid, ':pass'=>md5($pass), ':sex'=>$sex, ':email'=>$email])) {
                    $chk_alert = "
                        Swal.fire({
                            icon: 'success',
                            title: 'สมัครสมาชิกสำเร็จ!',
                            text: 'กรุณาเข้าสู่ระบบ',
                            background: 'rgba(17, 21, 28, 0.95)',
                            color: '#fff',
                            confirmButtonColor: '#ffc107'
                        }).then(() => { window.location = 'login'; });
                    ";
                }
            }
        } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RO Village</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #050505; }
        .mro-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center; background-size: cover; filter: blur(3px) brightness(0.4); z-index: -2; }
        
        input[type="radio"]:checked {
            background-color: #eab308;
            border-color: #eab308;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen py-20">

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="w-full max-w-lg px-4">
        <div class="bg-[#11151c]/90 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-yellow-600 via-yellow-400 to-yellow-600"></div>

            <div class="text-center mb-8">
                <h2 class="text-3xl font-extrabold text-white uppercase tracking-wider"><i class="fas fa-user-plus text-yellow-500 mr-2"></i> Register</h2>
                <p class="text-gray-400 text-sm mt-2">สมัครสมาชิกใหม่</p>
            </div>
            
            <form action="" method="POST" class="space-y-5" id="registerForm">
                
                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-500"></i>
                        </div>
                        <input type="text" name="userid" id="userid" required 
                               class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 transition placeholder-gray-600" 
                               placeholder="ID (อังกฤษ/ตัวเลข, 6 ตัวขึ้นไป)">
                    </div>
                    <p id="userid-error" class="text-red-500 text-xs mt-1 ml-1 hidden font-semibold"></p>
                </div>

                <div>
                     <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">Email</label>
                     <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-500"></i>
                        </div>
                        <input type="email" name="email" id="email" required 
                               class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 transition placeholder-gray-600" 
                               placeholder="อีเมล (เช่น name@domain.com)">
                     </div>
                     <p id="email-error" class="text-red-500 text-xs mt-1 ml-1 hidden font-semibold"></p>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-500"></i>
                        </div>
                        <input type="password" name="password" id="password" required 
                               class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 transition placeholder-gray-600" 
                               placeholder="กำหนดรหัสผ่าน (อังกฤษ/ตัวเลข, 6 ตัวขึ้นไป)">
                    </div>
                    <p id="password-error" class="text-red-500 text-xs mt-1 ml-1 hidden font-semibold"></p>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1 ml-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-check-circle text-gray-500"></i>
                        </div>
                        <input type="password" name="confirm_password" id="confirm_password" required 
                               class="w-full bg-black/20 text-white border border-white/10 rounded-lg py-3 pl-10 pr-3 focus:outline-none focus:border-yellow-500 transition placeholder-gray-600" 
                               placeholder="ยืนยันรหัสผ่านอีกครั้ง">
                    </div>
                    <p id="confirm-password-error" class="text-red-500 text-xs mt-1 ml-1 hidden font-semibold"></p>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-2 ml-1">Gender</label>
                    <div class="flex space-x-6 bg-black/20 border border-white/10 rounded-lg p-3">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="sex" value="M" class="w-4 h-4 appearance-none border border-gray-500 rounded-full checked:bg-yellow-500 checked:border-yellow-500 transition cursor-pointer relative" checked>
                            <span class="ml-2 text-gray-300 group-hover:text-white transition text-sm">Male (ชาย)</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="sex" value="F" class="w-4 h-4 appearance-none border border-gray-500 rounded-full checked:bg-yellow-500 checked:border-yellow-500 transition cursor-pointer relative">
                            <span class="ml-2 text-gray-300 group-hover:text-white transition text-sm">Female (หญิง)</span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="btn_register" id="btn-submit" class="w-full mt-2 bg-gradient-to-r from-gray-600 to-gray-700 text-gray-300 font-bold py-3 px-4 rounded-full shadow-lg transition transform duration-200 opacity-50 cursor-not-allowed" disabled>
                    ยืนยันการสมัคร
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">มีไอดีอยู่แล้ว? <a href="login" class="text-yellow-500 hover:text-yellow-400 font-bold transition">เข้าสู่ระบบ</a></p>
            </div>
        </div>
    </div>

    <script>
        <?php echo $chk_alert; ?>

        const inputs = {
            userid: document.getElementById('userid'),
            email: document.getElementById('email'),
            password: document.getElementById('password'),
            confirm: document.getElementById('confirm_password')
        };
        
        const errors = {
            userid: document.getElementById('userid-error'),
            email: document.getElementById('email-error'),
            password: document.getElementById('password-error'),
            confirm: document.getElementById('confirm-password-error')
        };

        const submitBtn = document.getElementById('btn-submit');

        function checkFormValidity() {
            const hasError = Object.values(errors).some(el => !el.classList.contains('hidden'));
            const hasEmpty = Object.values(inputs).some(el => el.value.trim() === '');

            if (hasError || hasEmpty) {
                submitBtn.classList.remove('from-yellow-500', 'to-yellow-600', 'text-black', 'hover:shadow-yellow-500/30', 'hover:scale-[1.02]');
                submitBtn.classList.add('from-gray-600', 'to-gray-700', 'text-gray-300', 'opacity-50', 'cursor-not-allowed');
                submitBtn.disabled = true;
            } else {
                submitBtn.classList.remove('from-gray-600', 'to-gray-700', 'text-gray-300', 'opacity-50', 'cursor-not-allowed');
                submitBtn.classList.add('from-yellow-500', 'to-yellow-600', 'text-black', 'hover:shadow-yellow-500/30', 'hover:scale-[1.02]');
                submitBtn.disabled = false;
            }
        }

        // --- 1. Username Validation ---
        inputs.userid.addEventListener('input', function() {
            const val = this.value;
            let error = "";
            const validChars = /^[a-zA-Z0-9]*$/;
            
            if (val.length > 0) {
                if (!validChars.test(val)) { error = "อนุญาตเฉพาะภาษาอังกฤษและตัวเลขเท่านั้น"; } 
                else if (val.length < 6) { error = "ต้องมีความยาวอย่างน้อย 6 ตัวอักษร"; }
                else if (/admin|gm/i.test(val)) { error = "ห้ามใช้คำว่า Admin หรือ GM"; }
            }
            updateInputStatus(this, errors.userid, error);
        });

        // --- 2. Email Validation ---
        inputs.email.addEventListener('input', function() {
            const val = this.value;
            let error = "";
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (val.length > 0 && !emailPattern.test(val)) {
                error = "รูปแบบอีเมลไม่ถูกต้อง";
            }
            updateInputStatus(this, errors.email, error);
        });

        // --- 3. Password Validation ---
        inputs.password.addEventListener('input', function() {
            const val = this.value;
            let error = "";
            const validChars = /^[a-zA-Z0-9]*$/;
            
            if (val.length > 0) {
                if (!validChars.test(val)) { error = "ห้ามใช้อักขระพิเศษหรือภาษาไทย"; }
                else if (val.length < 6) { error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร"; }
            }
            updateInputStatus(this, errors.password, error);
            
            // เช็ค confirm password ซ้ำด้วยเผื่อแก้ password ทีหลัง
            if (inputs.confirm.value !== '') {
                validateConfirmPassword();
            }
        });

        // --- 4. Confirm Password Validation ---
        inputs.confirm.addEventListener('input', validateConfirmPassword);

        function validateConfirmPassword() {
            const pass = inputs.password.value;
            const confirm = inputs.confirm.value;
            let error = "";

            if (confirm.length > 0 && pass !== confirm) {
                error = "รหัสผ่านยืนยันไม่ตรงกัน";
            }
            updateInputStatus(inputs.confirm, errors.confirm, error);
        }

        function updateInputStatus(input, errorElement, errorMsg) {
            if (errorMsg) {
                input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                input.classList.remove('border-white/10', 'focus:border-yellow-500');
                errorElement.textContent = errorMsg;
                errorElement.classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                input.classList.add('border-white/10', 'focus:border-yellow-500');
                errorElement.classList.add('hidden');
            }
            checkFormValidity();
        }

        checkFormValidity();
    </script>
</body>
</html>