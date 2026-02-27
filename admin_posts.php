<?php
session_start();
require_once 'db_con.php';

// ตรวจสอบว่าเข้าสู่ระบบและเป็นแอดมิน
if (!isset($_SESSION['account_id'])) {
  header("location: login");
  exit;
}

// ตรวจสอบสิทธิแอดมิน (group_id = 99 เป็นแอดมิน)
if (!isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
  header("location: index");
  exit;
}

$alert = "";
$posts = [];
$categories = [];
$action = isset($_GET['act']) ? $_GET['act'] : 'list';
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// == LOAD CATEGORIES (hierarchical) ==
$cat_tree = [];
try {
  $stmt = $conn->prepare("SELECT id, name, parent_id FROM web_post_categories ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, name ASC");
  $stmt->execute();
  $all_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($all_cats as $c) {
    if (is_null($c['parent_id'])) $cat_tree[$c['id']] = ['data' => $c, 'children' => []];
  }
  foreach ($all_cats as $c) {
    if (!is_null($c['parent_id']) && isset($cat_tree[$c['parent_id']])) {
      $cat_tree[$c['parent_id']]['children'][] = $c;
    }
  }
} catch (PDOException $e) {
}
$categories = $all_cats ?? [];

// == DELETE POST ==
if (isset($_POST['btn_delete'])) {
  try {
    $stmt = $conn->prepare("DELETE FROM web_posts WHERE id = :id");
    if ($stmt->execute([':id' => $post_id])) {
      $alert = "Swal.fire({icon: 'success', title: 'ลบสำเร็จ!', text: 'โพสต์ถูกลบแล้ว', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_posts.php'; });";
    }
  } catch (PDOException $e) {
    $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
  }
}

// == ADD/EDIT POST ==
if (isset($_POST['btn_save'])) {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
  $status = $_POST['status'] ?? 'published';

  if (empty($title) || empty($content)) {
    $alert = "Swal.fire({icon: 'warning', title: 'กรุณากรอกข้อมูลให้ครบ', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
  } else {
    try {
      if ($action === 'edit' && $post_id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE web_posts SET title = :title, content = :content, category_id = :cat_id, status = :status, updated_at = NOW() WHERE id = :id");
        if ($stmt->execute([':title' => $title, ':content' => $content, ':cat_id' => $category_id, ':status' => $status, ':id' => $post_id])) {
          $alert = "Swal.fire({icon: 'success', title: 'สำเร็จ!', text: 'อัปเดตโพสต์เรียบร้อย', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_posts.php'; });";
        }
      } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO web_posts (title, content, category_id, status, created_at, updated_at) VALUES (:title, :content, :cat_id, :status, NOW(), NOW())");
        if ($stmt->execute([':title' => $title, ':content' => $content, ':cat_id' => $category_id, ':status' => $status])) {
          $alert = "Swal.fire({icon: 'success', title: 'สำเร็จ!', text: 'เพิ่มโพสต์ใหม่เรียบร้อย', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'}).then(() => { window.location = 'admin_posts.php'; });";
        }
      }
    } catch (PDOException $e) {
      $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด DB', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
    }
  }
}

// == GET ALL POSTS ==
try {
  $stmt = $conn->prepare("SELECT p.id, p.title, p.content, p.category_id, p.status, p.created_at, p.updated_at, c.name as category_name FROM web_posts p LEFT JOIN web_post_categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
  $stmt->execute();
  $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $alert = "Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '{$e->getMessage()}', confirmButtonColor: '#d33', background: '#11151c', color: '#fff'});";
}

// == GET POST FOR EDIT ==
$edit_post = null;
if ($action === 'edit' && $post_id > 0) {
  try {
    $stmt = $conn->prepare("SELECT id, title, content, category_id, status FROM web_posts WHERE id = :id");
    $stmt->execute([':id' => $post_id]);
    $edit_post = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
  }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>จัดการโพสต์ - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      tinymce.init({
        selector: 'textarea[name="content"]',
        plugins: 'advlist autolink lists link image charmap print preview hr anchor searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table directionality undo redo emoticons',
        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | forecolor backcolor emoticons | code fullscreen',
        height: 400,
        skin: 'oxide-dark',
        content_css: 'dark',
        branding: false,
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; color: #fff; }',
        relative_urls: false,
        remove_script_host: false,
        images_upload_url: 'upload_image.php',
        automatic_uploads: true,
        file_picker_types: 'image',
        setup: function(editor) {
          editor.on('init', function() {
            console.log('✓ TinyMCE initialized successfully');
          });
        },
        file_picker_callback: function(cb, value, meta) {
          var input = document.createElement('input');
          input.setAttribute('type', 'file');
          input.setAttribute('accept', 'image/*');
          input.onchange = function() {
            var file = this.files[0];
            var reader = new FileReader();
            reader.onload = function() {
              var id = 'blobid' + (new Date()).getTime();
              var blobCache = tinymce.activeEditor.editorUpload.blobCache;
              var base64 = reader.result.split(',')[1];
              var blobInfo = blobCache.create(id, file, base64);
              blobCache.add(blobInfo);
              cb(blobInfo.blobUri(), {
                title: file.name
              });
            };
            reader.readAsDataURL(file);
          };
          input.click();
        }
      });

      // form submit handler
      var form = document.getElementById('post-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          tinymce.triggerSave();
        });
      }
    });
  </script>
</head>

<body class="bg-gray-900 text-white">
  <?php require_once 'menu.php'; ?>

  <div class="pt-24 min-h-screen">
    <div class="container mx-auto px-4 py-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-yellow-400 mb-2">
          <i class="fas fa-newspaper mr-2"></i>จัดการโพสต์
        </h1>
        <p class="text-gray-300">ระบบจัดการโพสต์สำหรับแอดมิน</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form Add/Edit -->
        <div class="lg:col-span-1">
          <div class="bg-gray-800 rounded-lg border border-yellow-500/20 p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">
              <i class="fas fa-<?php echo ($action === 'edit' && $edit_post) ? 'edit' : 'plus-circle'; ?> mr-2"></i>
              <?php echo ($action === 'edit' && $edit_post) ? 'แก้ไขโพสต์' : 'เพิ่มโพสต์ใหม่'; ?>
            </h2>

            <form id="post-form" method="POST" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">ชื่อเรื่อง *</label>
                <input type="text" name="title" required placeholder="กรุณากรอกชื่อเรื่อง"
                  class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-yellow-400"
                  value="<?php echo htmlspecialchars($edit_post['title'] ?? ''); ?>">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">หมวดหมู่</label>
                <select name="category_id" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-yellow-400">
                  <option value="">-- ไม่มีหมวดหมู่ --</option>
                  <?php foreach ($cat_tree as $node): ?>
                    <option value="<?php echo $node['data']['id']; ?>" <?php echo ($edit_post['category_id'] ?? '') == $node['data']['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($node['data']['name']); ?>
                    </option>
                    <?php foreach ($node['children'] as $sub): ?>
                      <option value="<?php echo $sub['id']; ?>" <?php echo ($edit_post['category_id'] ?? '') == $sub['id'] ? 'selected' : ''; ?>>
                        &nbsp;&nbsp;└ <?php echo htmlspecialchars($sub['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">เนื้อหา *</label>
                <textarea name="content" placeholder="กรุณากรอกเนื้อหา"><?php echo htmlspecialchars($edit_post['content'] ?? ''); ?></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-200 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-yellow-400">
                  <option value="published" <?php echo ($edit_post['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>เผยแพร่</option>
                  <option value="draft" <?php echo ($edit_post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>ร่าง</option>
                </select>
              </div>

              <button type="submit" name="btn_save" class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i><?php echo ($action === 'edit' && $edit_post) ? 'อัปเดต' : 'เพิ่มโพสต์'; ?>
              </button>

              <?php if ($action === 'edit'): ?>
                <a href="admin_posts.php" class="block text-center bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 rounded-lg transition">
                  <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Posts List -->
        <div class="lg:col-span-2">
          <div class="bg-gray-800 rounded-lg border border-yellow-500/20 p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">
              <i class="fas fa-list mr-2"></i>รายการโพสต์ทั้งหมด
            </h2>

            <?php if (empty($posts)): ?>
              <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-4 block opacity-50"></i>
                <p>ยังไม่มีโพสต์ใดๆ กรุณาสร้างโพสต์ใหม่</p>
              </div>
            <?php else: ?>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach ($posts as $post): ?>
                  <div class="bg-gray-700 border border-gray-600 rounded-lg p-4 hover:border-yellow-400/50 transition">
                    <div class="flex justify-between items-start mb-2">
                      <div class="flex-1">
                        <h3 class="font-bold text-white text-lg"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php if ($post['category_name']): ?>
                          <span class="inline-block bg-gray-600 text-gray-200 text-xs px-2 py-1 rounded mr-2 mb-2">
                            <i class="fas fa-folder-open mr-1"></i><?php echo htmlspecialchars($post['category_name']); ?>
                          </span>
                        <?php endif; ?>
                        <p class="text-gray-400 text-sm line-clamp-2"><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 100)); ?>...</p>
                      </div>
                      <span class="ml-2 px-3 py-1 rounded-full text-xs font-medium <?php echo ($post['status'] === 'published') ? 'bg-green-500/20 text-green-300' : 'bg-orange-500/20 text-orange-300'; ?>">
                        <?php echo ($post['status'] === 'published') ? 'เผยแพร่' : 'ร่าง'; ?>
                      </span>
                    </div>

                    <div class="text-xs text-gray-500 mb-3">
                      สร้าง: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                      <?php if ($post['updated_at'] !== $post['created_at']): ?>
                        | แก้ไข: <?php echo date('d/m/Y H:i', strtotime($post['updated_at'])); ?>
                      <?php endif; ?>
                    </div>

                    <div class="flex gap-2">
                      <a href="admin_posts.php?act=edit&id=<?php echo $post['id']; ?>" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-1 rounded transition text-center text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>แก้ไข
                      </a>
                      <form method="POST" action="admin_posts.php?id=<?php echo $post['id']; ?>" class="flex-1" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="btn_delete">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white py-1 rounded transition text-center text-sm font-medium">
                          <i class="fas fa-trash mr-1"></i>ลบ
                        </button>
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
    <script>
      <?php echo $alert; ?>
    </script>
  <?php endif; ?>
</body>

</html>