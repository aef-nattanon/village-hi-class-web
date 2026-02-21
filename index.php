<?php
session_start();
require_once 'db_con.php';

$server_status = "Online";
$online_count = 0;
$top_players = [];
$castle_owners = [];

// Array ชื่อบ้าน
$castle_map = [
    0 => 'Kriemhild',
    1 => 'Swanhild',
    2 => 'Fadrig',
    3 => 'Skoegul',
    4 => 'Gondul',
    5 => 'Neuschwanstein',
    6 => 'Hohenschwangau',
    7 => 'Nuernberg',
    8 => 'Wuerzburg',
    9 => 'Rothenburg',
    10 => 'Repherion',
    11 => 'Eeyolbriggar',
    12 => 'Yesnelph',
    13 => 'Bergel',
    14 => 'Mersetzdeitz',
    15 => 'Bright Arbor',
    16 => 'Scarlet Palace',
    17 => 'Holy Shadow',
    18 => 'Sacred Altar',
    19 => 'Bamboo Grove',
];

try {
    // 1. Online Count
    $stmt = $conn->query("SELECT count(*) as total FROM `char` WHERE online = 1");
    $online_count = $stmt->fetch()['total'];
    $online_count = $online_count * 1 + 50;

    // 2. Top 10 Players (Base Level)
    $stmt_rank = $conn->query("SELECT name, base_level, job_level, class FROM `char` WHERE group_id = 0 ORDER BY base_level DESC, job_level DESC LIMIT 10");
    $top_players = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);

    // 3. Castle Guilds
    $stmt_guild = $conn->query("SELECT C.castle_id, C.guild_id, G.name as guild_name, G.master 
                                FROM guild_castle C 
                                LEFT JOIN guild G ON C.guild_id = G.guild_id 
                                WHERE C.guild_id > 0 
                                ORDER BY C.castle_id ASC");
    $castle_owners = $stmt_guild->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RO Village - Hi-Class Ragnarok Online</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#ffc107',
                        goldhover: '#ffca28',
                        dark: '#050505',
                        panel: '#11151c',
                        border: 'rgba(255,255,255,0.1)'
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Sarabun', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #050505;
            color: #ffffff;
        }

        .mro-bg {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            z-index: -2;

            background: url('images/bg_ragnarok4.png') no-repeat top center;
            background-size: cover;
        }

        @media (max-width: 768px) {
            .mro-bg {
                background: url('images/bg_ragnarok4.png') no-repeat top center;
                background-size: cover;
            }
        }

        .text-shadow-gold {
            text-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
        }

        .rank-number {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 12px;
            font-weight: bold;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .sidebar-btn:hover {
            transform: translateY(-3px);
            filter: brightness(1.15);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
        }

        .sidebar-btn:last-child {
            margin-bottom: 0;
        }

        .sidebar-btn i {
            font-size: 1.5rem;
            margin-right: 10px;
        }

        .btn-system {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .btn-download {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
    </style>
</head>

<body class="antialiased">

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="pt-32 pb-16 text-center">
        <div class="container mx-auto px-4">
            <img src="images/logo.png" alt="RO Village Logo" class="h-64 md:h-80 mx-auto mb-6 drop-shadow-[0_0_15px_rgba(255,193,7,0.8)] hover:scale-105 transition duration-500">
        </div>
    </div>

    <div class="bg-panel/95 border-y-2 border-gold py-4 mb-10 shadow-lg backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 divide-x divide-white/10 text-center">
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-widest">Server Status</div>
                    <div class="text-xl font-bold text-green-400 mt-1 drop-shadow-[0_0_5px_rgba(74,222,128,0.5)]">
                        <i class="fas fa-circle text-[10px] mr-1 align-middle"></i> <?php echo $server_status; ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-widest">Online Players</div>
                    <div class="text-xl font-bold text-white mt-1"><?php echo number_format($online_count); ?></div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-widest">Exp Rate</div>
                    <div class="text-xl font-bold text-gold mt-1">x5 / x5</div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-widest">Drop Rate</div>
                    <div class="text-xl font-bold text-gold mt-1">x3</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="flex flex-wrap -mx-4">

            <div class="w-full lg:w-8/12 px-4">

                <div class="bg-panel border border-white/10 rounded-xl p-6 mb-6 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                        <i class="fas fa-sync fa-5x text-blue-500"></i>
                    </div>
                    <div class="flex justify-between items-center border-b-2 border-blue-500 pb-3 mb-5 relative z-10">
                        <h3 class="text-xl font-bold uppercase text-blue-400"><i class="fas fa-sync mr-2"></i> Update Patches</h3>
                        <a href="#" class="text-xs text-gray-400 hover:text-white transition">ดูทั้งหมด <i class="fas fa-chevron-right text-[10px]"></i></a>
                    </div>

                    <div class="space-y-0 relative z-10">
                        <a href="#" class="flex items-center py-3 border-b border-white/10 hover:bg-white/5 transition px-2 rounded group/item">
                            <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 min-w-[60px] text-center">UPDATE</span>
                            <span class="text-gray-300 group-hover/item:text-blue-400 transition">เปิดให้บริการเต็มรูปแบบแล้ววันนี้! (Grand Opening)</span>
                            <span class="ml-auto text-xs text-gray-500">31/01</span>
                        </a>
                        <a href="#" class="flex items-center py-3 border-b border-white/10 hover:bg-white/5 transition px-2 rounded group/item">
                            <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 min-w-[60px] text-center">FIX</span>
                            <span class="text-gray-300 group-hover/item:text-blue-400 transition">แก้ไขบัคสกิล Bowling Bash และปรับสมดุล Monster</span>
                            <span class="ml-auto text-xs text-gray-500">29/01</span>
                        </a>
                        <a href="#" class="flex items-center py-3 hover:bg-white/5 transition px-2 rounded group/item">
                            <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 min-w-[60px] text-center">MAINT</span>
                            <span class="text-gray-300 group-hover/item:text-blue-400 transition">ปิดปรับปรุงเซิร์ฟเวอร์ประจำสัปดาห์</span>
                            <span class="ml-auto text-xs text-gray-500">28/01</span>
                        </a>
                    </div>
                </div>

                <div class="bg-panel border border-white/10 rounded-xl p-6 mb-8 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                        <i class="fas fa-gift fa-5x text-red-500"></i>
                    </div>
                    <div class="flex justify-between items-center border-b-2 border-red-500 pb-3 mb-5 relative z-10">
                        <h3 class="text-xl font-bold uppercase text-red-400"><i class="fas fa-gift mr-2"></i> Event Activities</h3>
                        <a href="#" class="text-xs text-gray-400 hover:text-white transition">ดูทั้งหมด <i class="fas fa-chevron-right text-[10px]"></i></a>
                    </div>

                    <div class="space-y-0 relative z-10">
                        <a href="#" class="flex items-center py-3 border-b border-white/10 hover:bg-white/5 transition px-2 rounded group/item">
                            <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 min-w-[60px] text-center">EVENT</span>
                            <span class="text-gray-300 group-hover/item:text-red-400 transition">กิจกรรม Level Up รับไอเทมฟรีไม่อั้น</span>
                            <span class="ml-auto text-xs text-gray-500">30/01</span>
                        </a>
                        <a href="#" class="flex items-center py-3 hover:bg-white/5 transition px-2 rounded group/item">
                            <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 min-w-[60px] text-center">HOT</span>
                            <span class="text-gray-300 group-hover/item:text-red-400 transition">Guild War ชิงเงินรางวัล 10,000 บาท ครั้งที่ 1</span>
                            <span class="ml-auto text-xs text-gray-500">25/01</span>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="bg-panel border border-white/10 rounded-xl p-6 shadow-lg">
                        <div class="border-b-2 border-gold pb-3 mb-4">
                            <h3 class="text-xl font-bold uppercase"><i class="fas fa-trophy text-gold mr-2"></i> Top Players</h3>
                        </div>
                        <div class="overflow-hidden">
                            <table class="w-full text-sm">
                                <?php if ($top_players): $i = 1;
                                    foreach ($top_players as $p): ?>
                                        <tr class="border-b border-white/5 hover:bg-white/5 transition">
                                            <td class="py-2 pl-2 w-10">
                                                <span class="rank-number <?php echo ($i == 1) ? 'bg-gold text-black shadow-[0_0_10px_#ffc107]' : (($i == 2) ? 'bg-gray-300 text-black' : (($i == 3) ? 'bg-[#bcaaa4] text-black' : 'bg-gray-700 text-white')); ?>">
                                                    <?php echo $i; ?>
                                                </span>
                                            </td>
                                            <td class="py-2 text-gray-300 font-medium truncate max-w-[120px]"><?php echo htmlspecialchars($p['name']); ?></td>
                                            <td class="py-2 text-right text-gray-500 text-xs"><?php echo $p['class']; ?></td>
                                            <td class="py-2 pr-2 text-right text-gold font-bold">Lv.<?php echo $p['base_level']; ?></td>
                                        </tr>
                                    <?php $i++;
                                    endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-gray-500">ยังไม่มีข้อมูล</td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <div class="bg-panel border border-white/10 rounded-xl p-6 shadow-lg">
                        <div class="border-b-2 border-gold pb-3 mb-4">
                            <h3 class="text-xl font-bold uppercase"><i class="fas fa-shield-alt text-gold mr-2"></i> Castle Owners</h3>
                        </div>
                        <div class="overflow-hidden">
                            <table class="w-full text-sm">
                                <?php if ($castle_owners): foreach ($castle_owners as $c):
                                        $c_name = isset($castle_map[$c['castle_id']]) ? $castle_map[$c['castle_id']] : 'Castle #' . $c['castle_id'];
                                ?>
                                        <tr class="border-b border-white/5 hover:bg-white/5 transition">
                                            <td class="py-3 pl-2">
                                                <div class="text-gold font-bold text-xs uppercase mb-0.5"><?php echo $c_name; ?></div>
                                                <div class="text-white text-sm"><i class="fas fa-crown text-[10px] text-yellow-500 mr-1"></i> <?php echo htmlspecialchars($c['guild_name']); ?></div>
                                            </td>
                                            <td class="py-3 pr-2 text-right">
                                                <span class="text-xs text-gray-500">Leader: <?php echo htmlspecialchars($c['master']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center py-8 text-gray-500">
                                            <i class="fas fa-chess-rook text-4xl mb-2 opacity-50"></i><br>ยังไม่มีผู้ครอบครองปราสาท
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="w-full lg:w-4/12 px-4 mt-8 lg:mt-0">

                <div class="bg-panel border border-white/10 rounded-xl p-6 mb-6 shadow-lg">
                    <div class="border-b-2 border-gold pb-3 mb-4">
                        <h3 class="text-xl font-bold uppercase"><i class="fas fa-gamepad text-gold mr-2"></i> MAIN MENU</h3>
                    </div>

                    <a href="info" class="sidebar-btn btn-system">
                        <i class="fas fa-book-open"></i> System Info
                    </a>

                    <a href="register" class="sidebar-btn btn-register">
                        <i class="fas fa-user-plus"></i> Register
                    </a>

                    <a href="download" class="sidebar-btn btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
                <div class="bg-panel border border-white/10 rounded-xl p-6 mb-6 shadow-lg">
                    <div class="border-b-2 border-gold pb-3 mb-4">
                        <h3 class="text-xl font-bold uppercase"><i class="fas fa-users text-gold mr-2"></i> Community</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="#" class="flex items-center justify-center py-3 bg-[#1877f2] text-white font-bold rounded hover:brightness-110 transition">
                            <i class="fab fa-facebook-f mr-2"></i> Fanpage
                        </a>
                        <a href="#" class="flex items-center justify-center py-3 bg-[#5865f2] text-white font-bold rounded hover:brightness-110 transition">
                            <i class="fab fa-discord mr-2"></i> Discord
                        </a>
                    </div>
                </div>

                <div class="bg-panel border border-white/10 rounded-xl p-6 shadow-lg">
                    <div class="border-b-2 border-gold pb-3 mb-4">
                        <h3 class="text-xl font-bold uppercase"><i class="fas fa-info-circle text-gold mr-2"></i> Server Info</h3>
                    </div>
                    <ul class="space-y-3 text-sm">
                        <li class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Base Exp</span> <span class="text-white font-bold">x5</span>
                        </li>
                        <li class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Job Exp</span> <span class="text-white font-bold">x5</span>
                        </li>
                        <li class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Drop Rate</span> <span class="text-white font-bold">x3</span>
                        </li>
                        <li class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Max Level</span> <span class="text-white font-bold">99/70</span>
                        </li>
                        <li class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Max ASPD</span> <span class="text-white font-bold">190</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-gray-400">Guild War</span> <span class="text-gold font-bold">Tue / Thu / Sat</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <footer class="bg-black border-t border-gray-800 mt-16 py-10 text-center">
        <div class="container mx-auto px-4">
            <p class="text-white font-medium mb-1">&copy; 2026 RO Village. All rights reserved.</p>
            <p class="text-xs text-gray-600">Ragnarok Online is a trademark of Gravity Co., Ltd.</p>
        </div>
    </footer>

</body>

</html>