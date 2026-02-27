<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['account_id'])) {
  header("location: login");
  exit;
}
if (!isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
  header("location: index");
  exit;
}

$alert = "";
$action = isset($_GET['act']) ? $_GET['act'] : 'list';
$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// == DELETE CATEGORY ==
if (isset($_POST['btn_delete'])) {
  try {
    // Orphan children → become top-level
    $stmt = $conn->prepare("UPDATE web_post_categories SET parent_id = NULL WHERE parent_id = :id");
    $stmt->execute([':id' => $cat_id]);
    $stmt = $conn->prepare("DELETE FROM web_post_categories WHERE id = :id");
    if ($stmt->execute([':id' => $cat_id])) {
      $alert = "Swal.fire({icon: 'success', title: 'ลบสำเร็จ!', text: 'หมวดหมู่ถูกลบแล้ว', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_categories.php'; });";
    }
  } catch (PDOException $e) {
    $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
  }
}

// == ADD/EDIT CATEGORY ==
if (isset($_POST['btn_save'])) {
  $name = trim($_POST['name']);
  $slug = trim($_POST['slug']);
  $description = trim($_POST['description']);
  $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

  if (empty($name) || empty($slug)) {
    $alert = "Swal.fire({icon: 'warning', title: 'กรุณากรอกข้อมูลให้ครบ', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
  } else {
    // Prevent self-reference
    if ($parent_id === $cat_id) $parent_id = null;
    try {
      if ($action === 'edit' && $cat_id > 0) {
        $stmt = $conn->prepare("UPDATE web_post_categories SET name = :name, slug = :slug, description = :desc, parent_id = :pid WHERE id = :id");
        if ($stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':pid' => $parent_id, ':id' => $cat_id])) {
          $alert = "Swal.fire({icon: 'success', title: 'สำเร็จ!', text: 'อัปเดตหมวดหมู่เรียบร้อย', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_categories.php'; });";
        }
      } else {
        $stmt = $conn->prepare("INSERT INTO web_post_categories (name, slug, description, parent_id) VALUES (:name, :slug, :desc, :pid)");
        if ($stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':pid' => $parent_id])) {
          $alert = "Swal.fire({icon: 'success', title: 'สำเร็จ!', text: 'เพิ่มหมวดหมู่ใหม่เรียบร้อย', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_categories.php'; });";
        }
      }
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $alert = "Swal.fire({icon: 'error', title: 'ชื่อหรือ slug ซ้ำกัน', text: 'กรุณาใช้ชื่อ/slug อื่น', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
      } else {
        $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด DB', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
      }
    }
  }
}

// == GET ALL CATEGORIES (flat, with parent name + post count) ==
$all_categories = [];
try {
  $stmt = $conn->prepare("
    SELECT c.id, c.name, c.slug, c.description, c.parent_id, c.created_at,
           p.name AS parent_name,
           (SELECT COUNT(*) FROM web_posts WHERE category_id = c.id) AS post_count
    FROM web_post_categories c
    LEFT JOIN web_post_categories p ON c.parent_id = p.id
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.name ASC
  ");
  $stmt->execute();
  $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
}

// Build tree: parents → children
$tree = [];
$orphans = []; // children whose parent was deleted
foreach ($all_categories as $cat) {
  if (is_null($cat['parent_id'])) {
    $tree[$cat['id']] = ['data' => $cat, 'children' => []];
  }
}
foreach ($all_categories as $cat) {
  if (!is_null($cat['parent_id'])) {
    if (isset($tree[$cat['parent_id']])) {
      $tree[$cat['parent_id']]['children'][] = $cat;
    } else {
      $orphans[] = $cat;
    }
  }
}

// Top-level categories for parent dropdown (exclude self when editing)
$top_level = array_filter($all_categories, fn($c) => is_null($c['parent_id']) && $c['id'] !== $cat_id);

// == GET CATEGORY FOR EDIT ==
$edit_cat = null;
if ($action === 'edit' && $cat_id > 0) {
  try {
    $stmt = $conn->prepare("SELECT id, name, slug, description, parent_id FROM web_post_categories WHERE id = :id");
    $stmt->execute([':id' => $cat_id]);
    $edit_cat = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
  }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>จัดการหมวดหมู่ - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-900 text-white">
  <?php require_once 'menu.php'; ?>

  <div class="pt-24 min-h-screen">
    <div class="container mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-yellow-400 mb-2">
          <i class="fas fa-folder-open mr-2"></i>จัดการหมวดหมู่
        </h1>
        <p class="text-gray-300">ระบบจัดการหมวดหมู่สำหรับแอดมิน</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Add/Edit -->
        <div class="lg:col-span-1">
          <div class="bg-gray-800 rounded-lg border border-yellow-500/20 p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">
              <i class="fas fa-<?php echo ($action === 'edit' && $edit_cat) ? 'edit' : 'plus-circle'; ?> mr-2"></i>
              <?php echo ($action === 'edit' && $edit_cat) ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่'; ?>
            </h2>

            <form method="POST" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">หมวดหมู่แม่</label>
                <select name="parent_id" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-yellow-400">
                  <option value="">-- ไม่มี (หมวดหมู่หลัก) --</option>
                  <?php foreach ($top_level as $p): ?>
                    <option value="<?php echo $p['id']; ?>"
                      <?php echo ($edit_cat['parent_id'] ?? null) == $p['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-400 mt-1">หมวดหมู่ย่อยสามารถมีได้ 1 ระดับเท่านั้น</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">ชื่อหมวดหมู่ *</label>
                <input type="text" name="name" required placeholder="เช่น Patch Notes"
                  class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-yellow-400"
                  value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>"
                  oninput="autoSlug(this.value)">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">Slug (URL) *</label>
                <input type="text" id="slug-input" name="slug" required placeholder="เช่น patch-notes" pattern="[a-z0-9-]+"
                  class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-yellow-400"
                  value="<?php echo htmlspecialchars($edit_cat['slug'] ?? ''); ?>">
                <p class="text-xs text-gray-400 mt-1">ตัวเล็ก ตัวเลข และขีดกลาง (-) เท่านั้น</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">คำอธิบาย</label>
                <textarea name="description" rows="3" placeholder="คำอธิบายเพิ่มเติม (ไม่จำเป็น)"
                  class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-yellow-400"><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
              </div>

              <button type="submit" name="btn_save" class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i><?php echo ($action === 'edit' && $edit_cat) ? 'อัปเดต' : 'เพิ่มหมวดหมู่'; ?>
              </button>

              <?php if ($action === 'edit'): ?>
                <a href="admin_categories.php" class="block text-center bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 rounded-lg transition">
                  <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Categories Tree List -->
        <div class="lg:col-span-2">
          <div class="bg-gray-800 rounded-lg border border-yellow-500/20 p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">
              <i class="fas fa-sitemap mr-2"></i>รายการหมวดหมู่ทั้งหมด
            </h2>

            <?php if (empty($tree) && empty($orphans)): ?>
              <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-4 block opacity-50"></i>
                <p>ยังไม่มีหมวดหมู่ใดๆ กรุณาสร้างหมวดหมู่ใหม่</p>
              </div>
            <?php else: ?>
              <div class="space-y-3 max-h-[600px] overflow-y-auto">

                <?php foreach ($tree as $node): $cat = $node['data']; ?>
                  <!-- Parent Category -->
                  <div class="bg-gray-700 border border-yellow-500/30 rounded-lg p-4 hover:border-yellow-400/60 transition">
                    <div class="flex justify-between items-start mb-2">
                      <div class="flex-1">
                        <h3 class="font-bold text-white text-lg flex items-center">
                          <i class="fas fa-folder text-yellow-400 mr-2 text-base"></i>
                          <?php echo htmlspecialchars($cat['name']); ?>
                        </h3>
                        <p class="text-gray-400 text-sm">slug: <code class="bg-gray-800 px-2 py-0.5 rounded"><?php echo htmlspecialchars($cat['slug']); ?></code></p>
                        <?php if ($cat['description']): ?>
                          <p class="text-gray-400 text-sm mt-1 line-clamp-1"><?php echo htmlspecialchars($cat['description']); ?></p>
                        <?php endif; ?>
                      </div>
                      <div class="ml-2 text-right">
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-500/20 text-blue-300 block mb-1">
                          <?php echo $cat['post_count']; ?> โพสต์
                        </span>
                        <?php if (!empty($node['children'])): ?>
                          <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-300">
                            <?php echo count($node['children']); ?> ย่อย
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="text-xs text-gray-500 mb-3">
                      สร้าง: <?php echo date('d/m/Y H:i', strtotime($cat['created_at'])); ?>
                    </div>

                    <div class="flex gap-2">
                      <a href="admin_categories.php?act=edit&id=<?php echo $cat['id']; ?>" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-1 rounded transition text-center text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>แก้ไข
                      </a>
                      <form method="POST" action="admin_categories.php?id=<?php echo $cat['id']; ?>" class="flex-1" onsubmit="return confirm('ยืนยันการลบ?\nหมวดหมู่ย่อยทั้งหมดจะถูกย้ายขึ้นเป็นหมวดหมู่หลัก');">
                        <input type="hidden" name="btn_delete">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white py-1 rounded transition text-center text-sm font-medium">
                          <i class="fas fa-trash mr-1"></i>ลบ
                        </button>
                      </form>
                    </div>

                    <!-- Sub-categories -->
                    <?php if (!empty($node['children'])): ?>
                      <div class="mt-3 space-y-2 pl-4 border-l-2 border-yellow-500/30">
                        <?php foreach ($node['children'] as $sub): ?>
                          <div class="bg-gray-600/50 border border-gray-500/50 rounded-lg p-3 hover:border-yellow-400/40 transition">
                            <div class="flex justify-between items-start mb-2">
                              <div class="flex-1">
                                <h4 class="font-semibold text-white flex items-center">
                                  <i class="fas fa-folder-open text-yellow-300/70 mr-2 text-sm"></i>
                                  <?php echo htmlspecialchars($sub['name']); ?>
                                </h4>
                                <p class="text-gray-400 text-xs">slug: <code class="bg-gray-800 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($sub['slug']); ?></code></p>
                              </div>
                              <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300">
                                <?php echo $sub['post_count']; ?> โพสต์
                              </span>
                            </div>
                            <div class="flex gap-2">
                              <a href="admin_categories.php?act=edit&id=<?php echo $sub['id']; ?>" class="flex-1 bg-blue-700 hover:bg-blue-600 text-white py-1 rounded transition text-center text-xs font-medium">
                                <i class="fas fa-edit mr-1"></i>แก้ไข
                              </a>
                              <form method="POST" action="admin_categories.php?id=<?php echo $sub['id']; ?>" class="flex-1" onsubmit="return confirm('ยืนยันการลบหมวดหมู่ย่อยนี้?');">
                                <input type="hidden" name="btn_delete">
                                <button type="submit" class="w-full bg-red-700 hover:bg-red-600 text-white py-1 rounded transition text-center text-xs font-medium">
                                  <i class="fas fa-trash mr-1"></i>ลบ
                                </button>
                              </form>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>

                <?php foreach ($orphans as $cat): ?>
                  <!-- Orphaned sub-cat (parent deleted) shown as top-level -->
                  <div class="bg-gray-700 border border-gray-500 rounded-lg p-4">
                    <h3 class="font-bold text-white"><?php echo htmlspecialchars($cat['name']); ?></h3>
                    <div class="flex gap-2 mt-2">
                      <a href="admin_categories.php?act=edit&id=<?php echo $cat['id']; ?>" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-1 rounded text-center text-sm">แก้ไข</a>
                      <form method="POST" action="admin_categories.php?id=<?php echo $cat['id']; ?>" class="flex-1" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="btn_delete">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white py-1 rounded text-sm">ลบ</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>

              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($alert): ?>
    <script><?php echo $alert; ?></script>
  <?php endif; ?>

  <script>
    function autoSlug(value) {
      var slugInput = document.getElementById('slug-input');
      if (slugInput.dataset.manual) return;
      slugInput.value = value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    }
    document.getElementById('slug-input').addEventListener('input', function() {
      this.dataset.manual = '1';
    });
  </script>
</body>

</html>
