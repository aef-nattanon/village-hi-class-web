# Project Guidelines

## Overview

Ragnarok Online private server web portal with payment/topup system, user management, and game server integration. Stack: PHP (procedural), MySQL (PDO), no build tools, CDN-based frontend (Tailwind, Bootstrap, SweetAlert2).

## Code Style

**Language**: PHP 7.4+, procedural style with embedded HTML templates  
**Files**: Lowercase with underscores (`topup_process.php`, `check_api.php`)  
**Encoding**: UTF-8 (Thai language support required)  
**Output Escaping**: Always use `htmlspecialchars()` for user data in HTML

Reference: [login.php](login.php), [register.php](register.php)

## Database Patterns

**Always use PDO prepared statements with named parameters:**

```php
$stmt = $conn->prepare("SELECT * FROM login WHERE userid = :uid LIMIT 1");
$stmt->execute([':uid' => $userid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

- Named placeholders (`:uid`) not positional `?`
- Short array syntax for bindings: `[':key' => $value]`
- Backticks for reserved words: `` `key` ``, `` `char` ``, `` `index` ``
- Connection via `db_con.php` (already configured)

Examples: [member.php](member.php#L20-L23), [topup_process.php](topup_process.php#L95-L97)

## Authentication & Sessions

**Session Management**: Session checks required on all protected pages

```php
if (!isset($_SESSION['account_id'])) {
    header("location: login");
    exit;
}
```

**⚠️ CRITICAL SECURITY ISSUE**: Passwords currently use MD5 (insecure). When modifying auth code:

- DO NOT add new MD5 usage
- Consider migration plan to `password_hash()` / `password_verify()`

Current pattern: [login.php](login.php#L35-L37)

## Form Handling

**Pattern**: Server-side validation + client-side feedback

- POST method exclusively
- Named submit buttons: `name="btn_login"`, `name="btn_register"`
- Validation rules before DB interaction
- Errors via SweetAlert2 (Thai language, dark theme)

```php
$chk_alert = "Swal.fire({icon: 'error', title: 'ไม่สำเร็จ', text: 'ข้อความ', confirmButtonColor: '#ffc107', background: '#11151c', color: '#fff'});";
```

Examples: [register.php](register.php#L20-L90), [login.php](login.php#L16-L50)

## Blog & Posts Management

**Rich Text Editor**: TinyMCE 6 with dark theme for post creation

- Admin panel: [admin_posts.php](admin_posts.php) (requires `group_id = 99`)
- Category management: [admin_categories.php](admin_categories.php)
- Public posts page: [posts.php](posts.php) (displays published posts only)
- Database tables: `web_posts`, `web_post_categories`

**Features:**

- TinyMCE WYSIWYG editor with toolbar: bold, italic, lists, links, images, tables, colors, emoticons
- Categories system with slug support
- Post status: `published` or `draft`
- Auto timestamps: `created_at`, `updated_at`
- Thai language support
- Strip HTML tags when displaying previews with `strip_tags()`

**Admin Access:**

```php
if (!isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
    header("location: index");
    exit;
}
```

**Database Schema:**

- `web_posts`: id, title, content (LONGTEXT), category_id (FK), status, created_at, updated_at
- `web_post_categories`: id, name (UNIQUE), slug (UNIQUE), description, created_at

**Migration**: [web_posts_migration.sql](web_posts_migration.sql)

## Payment Integration

**EasySlip API**: Payment slip verification via cURL

- Config in [db_con.php](db_con.php) (`$config_easyslip_api_key`, `$config_account_names`)
- Amount validation with ±1 THB tolerance
- Duplicate prevention via `bank_ref` check
- Points granted via `acc_reg_num` table (`#CASHPOINTS` key)

Flow: [topup_select.php](topup_select.php) → [topup.php](topup.php) → [topup_process.php](topup_process.php)

## File Uploads

**Current pattern** (needs enhancement):

```php
$new_filename = "slip_" . time() . ".jpg";
move_uploaded_file($_FILES['slip_file']['tmp_name'], __DIR__ . '/uploads/' . $new_filename);
```

**⚠️ Missing validations** - Add when working on upload features:

- Server-side MIME type check (`getimagesize()`)
- File size enforcement (5MB limit)
- Allowed types: `image/jpeg`, `image/png` only

Reference: [topup_process.php](topup_process.php#L60-L75)

## Project Conventions

- **No .htaccess rewriting**: Files accessed directly (`/login.php` not `/login`)
- **Error handling**: Try-catch around all DB operations
- **Validation**: Check user input server-side with regex + blacklists
- **Dates**: MySQL datetime format, Thai timezone considerations
- **Reserved words**: admin, gm forbidden in userids

## Build and Run

**No build system** - Pure PHP with CDN dependencies

1. **Local dev**: Place in `C:\xampp\htdocs\ro_village\`
2. **Access**: `http://localhost/ro_village/index.php`
3. **Database**: MySQL credentials in [db_con.php](db_con.php)
4. **Tables**: `login`, `char`, `guild`, `guild_castle`, `acc_reg_num`, `web_topup_log`, `web_posts`, `web_post_categories`

## Deployment (Production)

**Server Requirements**: PHP 7.4+, MySQL 5.7+, Apache/Nginx

**Configuration changes for production:**

- Update `db_con.php` with production MySQL credentials
- Set `CURLOPT_SSL_VERIFYPEER` to `true` (update CA bundle path)
- Remove or protect debug files: `debug_curl.txt`, `webhook_log.txt`
- Restrict `uploads/` directory execution (`.htaccess` or nginx config)
- Set proper `session.cookie_secure` and `session.cookie_httponly` in php.ini

**Environment-specific:**

- API keys in `db_con.php`: `$config_easyslip_api_key`, `$config_promptpay_id`
- Account names for payment validation: `$config_account_names` array

**Logging**: Webhook activity logged to `webhook_log.txt` - rotate/secure in production

## Security Checklist

- ✅ Prepared statements prevent SQL injection
- ✅ Output escaping with `htmlspecialchars()`
- ❌ MD5 password hashing (legacy - avoid in new code)
- ❌ No CSRF tokens (add for sensitive operations)
- ⚠️ File upload validation incomplete
- ⚠️ SSL verification disabled in cURL (update CA bundle instead)
