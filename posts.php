<?php
session_start();
require_once 'db_con.php';

$posts = [];

try {
  $stmt = $conn->prepare("SELECT p.id, p.title, p.content, p.status, p.created_at, c.name as category_name FROM web_posts p LEFT JOIN web_post_categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.created_at DESC");
  $stmt->execute();
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ข่าวสาร - RO Village</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-900 text-white">
  <?php require_once 'menu.php'; ?>

  <div class="pt-24 min-h-screen">
    <div class="container mx-auto px-4 py-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-yellow-400 mb-2">
          <i class="fas fa-newspaper mr-2"></i>ข่าวสาร
        </h1>
        <p class="text-gray-300">อ่านข่าวสารและประกาศจากเซิร์ฟเวอร์</p>
      </div>

      <!-- Posts Grid -->
      <?php if (empty($posts)): ?>
        <div class="bg-gray-800 rounded-lg border border-yellow-500/20 p-12 text-center">
          <i class="fas fa-inbox text-6xl text-gray-600 mb-4 block"></i>
          <p class="text-gray-400 text-lg">ยังไม่มีข่าวสารใดๆ โปรดติดตามซ่อมแซมในภายหลัง</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($posts as $post): ?>
            <div class="bg-gray-800 rounded-lg border border-yellow-500/20 overflow-hidden hover:border-yellow-400/50 transition shadow-lg transform hover:scale-105 duration-300">
              <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2"></div>

              <div class="p-6">
                <h2 class="text-xl font-bold text-yellow-400 mb-2 line-clamp-2">
                  <?php echo htmlspecialchars($post['title']); ?>
                </h2>

                <?php if ($post['category_name']): ?>
                  <span class="inline-block bg-gray-700 text-gray-200 text-xs px-2 py-1 rounded mb-2 mr-2">
                    <i class="fas fa-folder-open mr-1"></i><?php echo htmlspecialchars($post['category_name']); ?>
                  </span>
                <?php endif; ?>

                <p class="text-gray-300 text-sm mb-4 line-clamp-3">
                  <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)); ?>...
                </p>

                <div class="text-xs text-gray-500 mb-4">
                  <i class="fas fa-calendar mr-1"></i>
                  <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                </div>

                <a href="post_view?id=<?php echo $post['id']; ?>" class="block w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded-lg transition text-center">
                  <i class="fas fa-arrow-right mr-1"></i>อ่านเพิ่มเติม
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</body>

</html>