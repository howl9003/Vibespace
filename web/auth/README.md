# Archspace Auth Service

A minimal PHP 8.x email/password authentication service that replaces the
legacy C "portal" daemon.  It handles registration, login, logout, and
password reset; and exposes an internal endpoint so the CGI adapter can
resolve an incoming session cookie to an account id.

---

## Requirements

| Component | Version |
|-----------|---------|
| PHP       | 8.x (tested on 8.3 / php-fpm) |
| MariaDB   | 10.x+ |
| Web server | nginx or Apache with PHP-FPM |

No Composer packages are required.

---

## Schema setup

```bash
# The Archspace database must already exist.
mysql -u root -p Archspace < web/auth/schema.sql
```

This creates two tables:

- **`accounts`** — one row per registered player / admin.
- **`sessions`** — server-side session rows keyed by the `as_session` cookie.

---

## Environment variables

All variables are optional; the defaults shown are used when a variable is
absent.

### Database

| Variable  | Default     | Purpose               |
|-----------|-------------|-----------------------|
| `DB_HOST` | `127.0.0.1` | MariaDB host          |
| `DB_USER` | `root`      | MariaDB username      |
| `DB_PASS` | `comconq1`  | MariaDB password      |
| `DB_NAME` | `Archspace`  | MariaDB database name |

### Mail

| Variable    | Default | Purpose                                                              |
|-------------|---------|----------------------------------------------------------------------|
| `SMTP_HOST` | *(none)*| SMTP relay hostname. **If unset, mail is logged to `/var/log/archspace/mail.log`** (dev mode). |
| `SMTP_PORT` | `587`   | SMTP port (STARTTLS used on 587 or when server advertises it)        |
| `SMTP_USER` | *(none)*| SMTP AUTH username                                                   |
| `SMTP_PASS` | *(none)*| SMTP AUTH password                                                   |
| `SMTP_FROM` | *(same as SMTP_USER)* | Envelope / `From:` address                      |

### Misc

| Variable   | Default | Purpose                                                           |
|------------|---------|-------------------------------------------------------------------|
| `BASE_URL` | *(empty)* | Absolute URL prefix for password-reset links, e.g. `https://archspace.example.com`. Leave empty for relative links. |

---

## Session cookie

| Property     | Value         |
|--------------|---------------|
| Cookie name  | `as_session`  |
| Value format | 64 hex characters (32 random bytes from `random_bytes`) |
| Lifetime     | 7 days        |
| Flags        | `HttpOnly`, `SameSite=Lax`, `Secure` (when request is over HTTPS) |

POST forms also use a short-lived `as_csrf` cookie plus a hidden
`csrf_token` field. The cookie uses the same `HttpOnly`, `SameSite=Lax`, and
HTTPS-only `Secure` behavior; login, registration, forgot-password, and
reset-password POSTs reject requests when the submitted token and cookie do not
match.

---

## Endpoints

All endpoints live under `/auth/`.

| File | Method | Purpose |
|------|--------|---------|
| `register.php` | GET | Show registration form |
| `register.php` | POST `{csrf_token, email, password, password2}` | Create account; on success set session + redirect 303 to `/main.php` |
| `login.php` | GET | Show login form |
| `login.php` | POST `{csrf_token, email, password}` | Authenticate; on success set session + redirect 303 to `/main.php` |
| `logout.php` | GET / any | Delete session row + clear cookie, redirect 303 to `/auth/login.php` |
| `forgot.php` | GET | Show "enter email" form |
| `forgot.php` | POST `{csrf_token, email}` | Generate reset token (1 h TTL), email link; always shows neutral confirmation |
| `reset.php` | GET `?token=<hex>` | Show "set new password" form if token valid |
| `reset.php` | POST `{csrf_token, token, password, password2}` | Update password, clear token, redirect 303 to `/auth/login.php` |
| `session_lookup.php` | GET | **Internal only.** Resolves `as_session` cookie → JSON account data or 401 |

### `session_lookup.php` — CGI adapter integration

The CGI adapter (same host) calls this endpoint to map the current request's
session cookie to an account id:

```
GET /auth/session_lookup.php
Cookie: as_session=<64-hex-token>

HTTP 200
{ "id": 42, "is_admin": 0, "email": "player@example.com" }

or

HTTP 401
{ "error": "no valid session" }
```

The adapter can also pass the token as a query-string parameter for
environments that cannot forward cookies:

```
GET /auth/session_lookup.php?token=<64-hex-token>
```

**Restrict this endpoint to `127.0.0.1` in your web-server config.**

---

## Internal PHP API (lib.php)

Other PHP scripts on the same host can call `current_account()` directly:

```php
require_once '/path/to/web/auth/lib.php';

$account = current_account();
// null  — not logged in / session expired
// array — ['id' => int, 'email' => string, 'is_admin' => int]
```

---

## Account id / portal_id mapping

`accounts.id` (INT UNSIGNED AUTO_INCREMENT) plays the role of the legacy
`portal_id`.  The CGI adapter reads the `id` field from `session_lookup.php`
and passes it to the game server.

---

## Dev mail log

When `SMTP_HOST` is not set, outbound mail is appended to:

```
/var/log/archspace/mail.log
```

The directory is created automatically (best-effort).  Useful for verifying
password-reset links during local development.
