<?php
session_start();
require_once 'db_con.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
    header("location: posts");
    exit;
}

$post = null;
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.content, p.created_at, p.updated_at,
               c.name AS cat_name, c.slug AS cat_slug,
               pc.name AS parent_cat_name, pc.slug AS parent_slug
        FROM web_posts p
        LEFT JOIN web_post_categories c ON p.category_id = c.id
        LEFT JOIN web_post_categories pc ON c.parent_id = pc.id
        WHERE p.id = :id AND p.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (!$post) {
    header("location: posts");
    exit;
}

// Badge: use sub-category slug if available, else top-level slug
$badge_slug = $post['cat_slug'] ?? '';
$badge_label = strtoupper($badge_slug);

$tag_color_map = [
    'update' => '#2563eb', 'fix'   => '#4338ca', 'maint' => '#64748b',
    'event'  => '#dc2626', 'hot'   => '#e11d48', 'new'   => '#16a34a',
    'patch'  => '#0891b2', 'news'  => '#9333ea', 'sale'  => '#ea580c',
];
$badge_color = $tag_color_map[$badge_slug] ?? '#ca8a04';
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($post['title']); ?> - RO Village</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: 'Sarabun', sans-serif; background-color: #050505; color: #fff; }

    /* Styles for TinyMCE HTML content */
    .post-content { line-height: 1.8; font-size: 1rem; color: #d1d5db; }
    .post-content h1, .post-content h2, .post-content h3,
    .post-content h4, .post-content h5, .post-content h6 {
      font-family: 'Poppins', sans-serif;
      color: #fbbf24; font-weight: 700; margin: 1.5rem 0 0.75rem;
    }
    .post-content h1 { font-size: 1.75rem; }
    .post-content h2 { font-size: 1.4rem; }
    .post-content h3 { font-size: 1.2rem; }
    .post-content p  { margin: 0.75rem 0; }
    .post-content ul, .post-content ol { margin: 0.75rem 0 0.75rem 1.5rem; }
    .post-content ul { list-style: disc; }
    .post-content ol { list-style: decimal; }
    .post-content li { margin: 0.25rem 0; }
    .post-content a  { color: #60a5fa; text-decoration: underline; }
    .post-content a:hover { color: #93c5fd; }
    .post-content blockquote {
      border-left: 4px solid #fbbf24; padding-left: 1rem;
      margin: 1rem 0; color: #9ca3af; font-style: italic;
    }
    .post-content img {
      max-width: 100%; border-radius: 8px; margin: 1rem auto; display: block;
    }
    .post-content table {
      width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.9rem;
    }
    .post-content th, .post-content td {
      border: 1px solid rgba(255,255,255,0.15);
      padding: 0.5rem 0.75rem; text-align: left;
    }
    .post-content th { background: rgba(255,193,7,0.1); color: #fbbf24; font-weight: 600; }
    .post-content tr:nth-child(even) td { background: rgba(255,255,255,0.03); }
    .post-content pre, .post-content code {
      background: #1f2937; border-radius: 4px;
      font-family: monospace; font-size: 0.875rem;
    }
    .post-content pre  { padding: 1rem; overflow-x: auto; margin: 1rem 0; }
    .post-content code { padding: 0.1rem 0.4rem; }
    .post-content hr { border-color: rgba(255,255,255,0.1); margin: 1.5rem 0; }
    .post-content strong { color: #f9fafb; font-weight: 700; }
    .post-content em { color: #e5e7eb; }
  </style>
</head>

<body class="antialiased">
  <?php require_once 'menu.php'; ?>

  <div class="pt-28 pb-16 min-h-screen">
    <div class="container mx-auto px-4 max-w-4xl">

      <!-- Breadcrumb -->
      <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="index" class="hover:text-yellow-400 transition">หน้าแรก</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <a href="posts" class="hover:text-yellow-400 transition">ข่าวสาร</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-300 truncate max-w-xs"><?php echo htmlspecialchars($post['title']); ?></span>
      </nav>

      <!-- Post Card -->
      <article class="bg-[#11151c] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
        <!-- Top accent -->
        <div class="h-1 bg-gradient-to-r from-yellow-500 to-orange-500"></div>

        <div class="p-6 md:p-10">
          <!-- Meta -->
          <div class="flex flex-wrap items-center gap-3 mb-4">
            <?php if ($badge_slug): ?>
              <span class="text-white text-xs font-bold px-3 py-1 rounded-full" style="background:<?php echo $badge_color; ?>">
                <?php echo htmlspecialchars($badge_label); ?>
              </span>
            <?php endif; ?>
            <?php if ($post['parent_cat_name']): ?>
              <span class="text-gray-400 text-sm">
                <i class="fas fa-folder-open mr-1 text-yellow-500/60"></i>
                <?php echo htmlspecialchars($post['parent_cat_name']); ?>
              </span>
            <?php elseif ($post['cat_name']): ?>
              <span class="text-gray-400 text-sm">
                <i class="fas fa-folder-open mr-1 text-yellow-500/60"></i>
                <?php echo htmlspecialchars($post['cat_name']); ?>
              </span>
            <?php endif; ?>
            <span class="text-gray-500 text-sm ml-auto">
              <i class="fas fa-calendar mr-1"></i>
              <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
            </span>
          </div>

          <!-- Title -->
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-6 leading-snug" style="font-family:'Poppins',sans-serif">
            <?php echo htmlspecialchars($post['title']); ?>
          </h1>

          <hr class="border-white/10 mb-6">

          <!-- Content (HTML from TinyMCE) -->
          <div class="post-content">
            <?php echo $post['content']; ?>
          </div>

          <!-- Footer -->
          <?php if ($post['updated_at'] && $post['updated_at'] !== $post['created_at']): ?>
            <p class="text-xs text-gray-600 mt-8 pt-4 border-t border-white/5">
              <i class="fas fa-edit mr-1"></i>แก้ไขล่าสุด: <?php echo date('d/m/Y H:i', strtotime($post['updated_at'])); ?>
            </p>
          <?php endif; ?>
        </div>
      </article>

      <!-- Back Button -->
      <div class="mt-6">
        <a href="posts" class="inline-flex items-center gap-2 px-6 py-2 bg-white/5 border border-white/10 rounded-lg text-gray-300 hover:bg-white/10 hover:text-white transition">
          <i class="fas fa-arrow-left text-sm"></i> กลับไปหน้าข่าวสาร
        </a>
      </div>

    </div>
  </div>
</body>

</html>
