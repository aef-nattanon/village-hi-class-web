<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to RO Village</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#ffc107',
                        dark: '#050505',
                        panel: '#11151c',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Sarabun', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body { background-color: #050505; color: #ffffff; overflow: hidden; }
        .mro-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://wallpaperaccess.com/full/1138072.jpg') no-repeat center center;
            background-size: cover; 
            z-index: -1;
        }
        .overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }

        .swal2-popup {
            background: #11151c !important;
            border: 2px solid #ffc107;
            border-radius: 15px !important;
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.3);
        }
        .popup-btn {
            display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 12px; margin-bottom: 8px;
            text-decoration: none; font-weight: bold; border-radius: 8px;
            transition: 0.3s; text-transform: uppercase; font-size: 14px;
        }
        .popup-btn:hover { transform: translateY(-2px); filter: brightness(1.2); }
        
        .btn-home { background: linear-gradient(45deg, #ffc107, #ff8f00); color: black; font-size: 16px; border: 1px solid #ffc107; }
        .btn-reg { background: #1f2937; color: white; border: 1px solid #374151; }
        .btn-dl { background: #1f2937; color: white; border: 1px solid #374151; }
        .btn-info { background: #1f2937; color: white; border: 1px solid #374151; }
        
        .logo-text {
            text-shadow: 0 0 20px rgba(255, 193, 7, 0.8);
        }
    </style>
</head>
<body class="flex items-center justify-center h-screen">

    <div class="mro-bg"></div>
    <div class="overlay"></div>

    <div class="relative z-10 text-center">
        <img src="images/logo.png" 
             alt="RO Village Logo" 
             class="w-64 md:w-96 mx-auto mb-6 drop-shadow-[0_0_30px_rgba(255,193,7,0.6)] animate-pulse">
        
        <p class="text-xl text-gray-300 tracking-widest font-semibold text-shadow-gold uppercase">Legendary Ragnarok Online</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                imageUrl: 'images/logo.png',
                imageWidth: 500,
                imageAlt: 'RO Village Popup',
                background: '#11151c',
                showConfirmButton: false, 
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: `rgba(0,0,0,0.8)`,
                html: `
                    <div class="mt-4 px-2">
                        <a href="index" class="popup-btn btn-home shadow-lg shadow-yellow-500/20">
                            <i class="fas fa-dungeon mr-2"></i> เข้าสู่เว็บไซต์ (Enter Site)
                        </a>
                        
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <a href="register" class="popup-btn btn-reg">
                                <i class="fas fa-user-plus mr-2 text-gold"></i> สมัครสมาชิก
                            </a>
                            <a href="download" class="popup-btn btn-dl">
                                <i class="fas fa-download mr-2 text-gold"></i> ดาวน์โหลด
                            </a>
                        </div>

                        <a href="info" class="popup-btn btn-info mt-1">
                            <i class="fas fa-info-circle mr-2 text-gray-400"></i> ข้อมูลเซิร์ฟเวอร์
                        </a>
                    </div>
                `
            });
        });
    </script>
</body>
</html>