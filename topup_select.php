<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['account_id'])) {
    header("location: login");
    exit;
}

$packages = [
    ['price' => 2,   'points' => 20,    'bonus' => ''],
    ['price' => 50,   'points' => 500,    'bonus' => ''],
    ['price' => 100,  'points' => 1000,   'bonus' => ''],
    ['price' => 150,  'points' => 1500,   'bonus' => ''],
    ['price' => 300,  'points' => 3000,   'bonus' => ''],
    ['price' => 500,  'points' => 5250,   'bonus' => '+5% Bonus'],
    ['price' => 1000, 'points' => 11000,  'bonus' => '+10% Bonus'],
];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topup Store - RO Village</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --mro-primary: #ffc107;
            --mro-panel-bg: rgba(20, 30, 60, 0.90);
            --mro-card-bg: rgba(255, 255, 255, 0.05);
            --mro-text-light: #e0e0e0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a0a0a;
            color: var(--mro-text-light);
            min-height: 100vh;
        }

        .mro-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('no_image.jpg') no-repeat center center;
            background-size: cover;
            filter: blur(5px) brightness(0.5);
            z-index: -2;
        }

        .shop-container {
            max-width: 1000px;
            margin: 100px auto 50px;
            padding: 20px;
        }

        .shop-title {
            text-align: center;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .shop-title h2 {
            font-weight: 800;
            font-size: 3rem;
            color: white;
            text-shadow: 0 0 20px rgba(255, 193, 7, 0.5);
            margin: 0;
        }

        .shop-title h2 span {
            color: var(--mro-primary);
        }

        .shop-title p {
            color: #aaa;
            font-size: 1.1rem;
        }

        .pkg-card {
            background: var(--mro-card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .pkg-card:hover {
            background: rgba(255, 193, 7, 0.05);
            border-color: var(--mro-primary);
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(255, 193, 7, 0.1);
        }

        .pkg-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            background: linear-gradient(to bottom, #ffd700, #ff8c00);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            filter: drop-shadow(0 4px 10px rgba(255, 165, 0, 0.3));
            display: inline-block; 
        }

        .pkg-points {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
            line-height: 1;
        }

        .pkg-unit {
            font-size: 0.9rem;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .btn-buy {
            display: block;
            width: 100%;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 700;
            padding: 10px;
            border-radius: 50px;
            margin-top: 25px;
            transition: 0.3s;
            text-decoration: none;
            font-size: 1.1rem;
        }

        .pkg-card:hover .btn-buy {
            background: var(--mro-primary);
            border-color: var(--mro-primary);
            color: black;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.6);
        }

        .pkg-badge {
            position: absolute;
            top: 20px;
            right: -35px;
            background: #ff4757;
            color: white;
            width: 150px;
            padding: 5px 0;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            transform: rotate(45deg);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
        }

        .pkg-badge.bonus {
            background: #ff4757;
        }

        .pkg-card::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            opacity: 0;
            transition: 0.5s;
        }

        .pkg-card:hover::before {
            opacity: 1;
            transform: scale(1.5);
        }
    </style>
</head>

<body>

    <?php include 'menu.php'; ?>
    <div class="mro-bg"></div>

    <div class="shop-container">

        <div class="shop-title">
            <h2>Cash <span>Shop</span></h2>
            <p>กรุณาเลือกราคาที่ต้องการเติม</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($packages as $pkg): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="pkg-card">
                        <?php if (!empty($pkg['bonus'])): ?>
                            <div class="pkg-badge <?php echo (strpos($pkg['bonus'], '%') !== false) ? 'bonus' : ''; ?>">
                                <?php echo $pkg['bonus']; ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <div class="pkg-icon"><i class="fas fa-coins"></i></div>
                            <div class="pkg-points"><?php echo number_format($pkg['points']); ?></div>
                            <div class="pkg-unit">Cash Points</div>
                        </div>

                        <a href="topup?amount=<?php echo $pkg['price']; ?>" class="btn-buy">
                            <?php echo number_format($pkg['price']); ?> บาท
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="member" class="text-decoration-none text-white-50 hover-white">
                <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
            </a>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>