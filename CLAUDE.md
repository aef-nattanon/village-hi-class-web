# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ragnarok Online private server web portal with payment/topup system, user management, and game server integration. Stack: PHP 7.4+ (procedural), MySQL (PDO), no build tools, CDN-based frontend (Tailwind CSS, Bootstrap 5, SweetAlert2).

## Development Setup

- **Local dev**: XAMPP — files served from `C:\xampp\htdocs\ro_village\`
- **Access**: `http://localhost/ro_village/index.php` (or `/index` via .htaccess rewrite)
- **Database config**: All credentials and API keys in `db_con.php`
- **No build/test/lint commands** — pure PHP, no package managers, no test framework

## Architecture

**Entry flow**: All pages are standalone PHP files. `db_con.php` is included for DB connection + config. `menu.php` is included for the shared navigation bar.

**Auth flow**: `register.php` → `login.php` → `member.php` (protected). Sessions use `$_SESSION['account_id']` and `$_SESSION['userid']`. Protected pages must check session and redirect to `login` if missing.

**Payment flow**: `topup_select.php` (pick package) → `topup.php` (generate QR/PromptPay, create transaction with random satang) → `topup_process.php` (upload slip, verify via EasySlip API, grant `#CASHPOINTS` in `acc_reg_num` table). `webhook_slip.php` is a fallback callback endpoint.

**Key database tables**: `login` (accounts), `char` (characters), `guild`/`guild_castle` (guilds), `acc_reg_num` (cash points via `#CASHPOINTS` key), `web_topup_log` (transactions).

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
- **File uploads** missing server-side MIME/size validation — add `getimagesize()` and size checks when modifying upload code
- Forbidden userids: `admin`, `gm` (checked via `stripos`)

## Payment Config (in db_con.php)

- `$config_easyslip_api_key` — EasySlip API key for slip verification
- `$config_promptpay_id` — PromptPay number for QR generation
- `$config_account_names` — whitelist of valid payment receiver names
- `calculatePoints($amount)` — converts THB to cash points
- Amount validation uses ±1 THB tolerance; duplicate prevention via `bank_ref` column
