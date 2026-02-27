# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ragnarok Online private server web portal with payment/topup system, user management, and game server integration. Stack: PHP 7.4+ (procedural), MySQL (PDO), no build tools, CDN-based frontend (Tailwind CSS, Bootstrap 5, SweetAlert2).

## Development Setup

- **Local dev**: XAMPP — files served from `C:\xampp\htdocs\ro_village\`
- **Access**: `http://localhost/ro_village/index.php` (or `/index` via .htaccess rewrite — `.php` extension optional)
- **Database**: `village_main_db2` — credentials in `db_con.php`
- **No build/test/lint commands** — pure PHP, no package managers, no test framework

## Architecture

**Entry flow**: All pages are standalone PHP files. `db_con.php` is included for DB connection + config. `menu.php` is included for the shared navigation bar.

**Auth flow**: `register.php` → `login.php` → `member.php` (protected). Login stores `$_SESSION['account_id']`, `$_SESSION['userid']`, and `$_SESSION['group_id']`. Protected pages must check session and redirect to `login` if missing.

**Admin access**: `group_id = 99` is admin. Check pattern:

```php
if (!isset($_SESSION['group_id']) || $_SESSION['group_id'] != 99) {
    header("location: index"); exit;
}
```

**Payment flow**: `topup_select.php` (pick package) → `topup.php` (generate QR/PromptPay, create transaction with random satang) → `topup_process.php` (upload slip, verify via EasySlip API, grant `#CASHPOINTS` in `acc_reg_num` table). `webhook_slip.php` is a fallback callback endpoint. `cancel_topup.php` handles cancellation.

**Posts/Blog flow**: `posts.php` (public) ← `web_posts` table ← `admin_posts.php` (admin only, requires `group_id = 99`). Categories managed via `admin_categories.php`. Images uploaded via TinyMCE to `upload_image.php` → `uploads/posts/`.

**Key database tables**: `login` (accounts), `char` (characters), `guild`/`guild_castle` (guilds), `acc_reg_num` (cash points via `#CASHPOINTS` key), `web_topup_log` (transactions), `web_posts`, `web_post_categories`.

**New table migration**: Run `web_posts_migration.sql` to create `web_posts` and `web_post_categories` tables.

## Code Conventions

- **PHP style**: Procedural with embedded HTML, lowercase filenames with underscores
- **Database**: Always PDO prepared statements with **named parameters** (`:uid` not `?`). Use backticks for reserved column names (`` `key` ``, `` `char` ``, `` `index` ``). Short array syntax: `[':key' => $value]`
- **Output escaping**: Always `htmlspecialchars()` for user data in HTML
- **Form handling**: POST method, named submit buttons (`name="btn_login"`), server-side validation first
- **Notifications**: SweetAlert2 in Thai, dark theme (`background: '#11151c'`, `color: '#fff'`, `confirmButtonColor: '#ffc107'`)
- **Encoding**: UTF-8 throughout (Thai language support)
- **Timezone**: `Asia/Bangkok`

## Security Notes

- **MD5 passwords** (legacy) — do NOT add new MD5 usage; plan migration to `password_hash()`/`password_verify()`
- **No CSRF tokens** — add for any new sensitive operations
- **SSL verification disabled** in cURL calls — enable in production with proper CA bundle
- **File uploads**: `upload_image.php` (TinyMCE) has MIME + size validation. The payment slip upload in `topup_process.php` may still lack server-side MIME validation — add `getimagesize()` when modifying
- **Forbidden userids**: `admin`, `gm` (checked via `stripos`)
- **Known inconsistency**: `menu.php` desktop checks `group_id == 99` for admin nav links, but mobile menu checks `account_id == 1` — these should be unified

## Payment Config (in db_con.php)

- `$config_easyslip_api_key` — EasySlip API key for slip verification
- `$config_promptpay_id` — PromptPay number for QR generation
- `$config_account_names` — whitelist of valid payment receiver names
- `calculatePoints($amount)` — converts THB to cash points (currently 1:1)
- Amount validation uses ±1 THB tolerance; duplicate prevention via `bank_ref` column

## Blog & Posts System

- **Rich text editor**: TinyMCE 6 (dark theme, `oxide-dark` skin) in `admin_posts.php`
- **Image upload endpoint**: `upload_image.php` (admin-only, accepts JPG/PNG/GIF/WebP up to 5MB, saves to `uploads/posts/`)
- **Post content**: stored as HTML (`LONGTEXT`), previewed with `strip_tags()`, displayed as plain text in modal via `textContent` (not `innerHTML`)
- **Post status**: `published` or `draft`; only `published` posts visible on `posts.php`

## Production Checklist

- Update `db_con.php` credentials
- Set `CURLOPT_SSL_VERIFYPEER` to `true`
- Secure/rotate `webhook_log.txt`, `debug_curl.txt`
- Restrict `uploads/` directory execution via `.htaccess`
- Set `session.cookie_secure` and `session.cookie_httponly` in php.ini
