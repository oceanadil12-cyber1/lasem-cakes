<?php
/**
 * Sign-in for the admin panel: a user name and password (stored only as a hash in
 * private/admin.json), a PHP session cookie, a CSRF token on every form and a limit
 * on password guesses.
 *
 * First run: there is no sign-in yet, so the panel asks for the one-time setup code
 * it writes to private/setup-code.txt. Only someone with access to the server's files
 * can read it, so a stranger who finds /admin/ first can't claim the panel.
 */
if (!defined('ADMIN') && !defined('KDS')) { http_response_code(403); exit('Forbidden'); }

const ADMIN_IDLE_SECONDS = 43200; // signed out after 12 hours without a click
const ADMIN_MIN_PASSWORD = 10;

/** Headers for every admin page: never cached, never indexed, never framed, no outside scripts. */
function admin_send_headers(): void
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' blob: data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
}

/** The saved sign-in, or null before the first-run setup. */
function admin_account(): ?array
{
    $a = json_read(private_dir() . '/admin.json', null);
    return is_array($a) && !empty($a['user']) && !empty($a['hash']) ? $a : null;
}

function admin_save_account(string $user, string $password): bool
{
    return json_write(private_dir() . '/admin.json', [
        'user'    => $user,
        'hash'    => password_hash($password, PASSWORD_DEFAULT),
        'changed' => date('c'),
    ]);
}

/** A short fingerprint of the password hash: changing the password signs out every other session. */
function admin_account_version(?array $a): string
{
    return $a ? substr(hash('sha256', $a['hash']), 0, 16) : '';
}

function admin_session_start(): void
{
    // Sessions live in the private folder so the host's own cleanup (often 24 minutes)
    // doesn't sign the team out, and other sites on the server can't read them.
    $dir = private_dir('sessions');
    if (is_dir($dir) && is_writable($dir)) session_save_path($dir);
    ini_set('session.gc_maxlifetime', (string) ADMIN_IDLE_SECONDS);
    ini_set('session.use_strict_mode', '1');
    session_name('lasem_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => ADMIN_BASE,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Is this browser signed in? A real click ($touch) keeps the session alive; the background badge refresh doesn't. */
function admin_signed_in(bool $touch = true): bool
{
    if (empty($_SESSION['admin_ok'])) return false;
    $expired = time() - (int) ($_SESSION['admin_seen'] ?? 0) > ADMIN_IDLE_SECONDS;
    if ($expired || ($_SESSION['admin_v'] ?? '') !== admin_account_version(admin_account())) {
        $_SESSION = [];
        return false;
    }
    if ($touch) $_SESSION['admin_seen'] = time();
    return true;
}

function admin_user(): string
{
    return (string) ($_SESSION['admin_user'] ?? '');
}

function admin_csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(admin_csrf()) . '">';
}

/** Stop a POST that didn't come from one of our own forms (a stale page or a forged request). */
function require_csrf(): void
{
    if (!hash_equals(admin_csrf(), (string) ($_POST['csrf'] ?? ''))) {
        flash('That page had expired, so nothing was changed. Please try again.', 'err');
        redirect(admin_url((string) ($_GET['page'] ?? '')));
    }
}

/** Start a signed-in session for $user. */
function admin_begin(string $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_ok'] = true;
    $_SESSION['admin_user'] = $user;
    $_SESSION['admin_seen'] = time();
    $_SESSION['admin_v'] = admin_account_version(admin_account());
    unset($_SESSION['csrf']);
}

function admin_logo(): string
{
    return '<div class="login-logo"><img src="' . h(SITE_BASE) . 'images/logo.png" alt="La Sem Cakes" height="64"></div>';
}

function admin_error_html(string $error): string
{
    return $error !== '' ? '<div class="flash err" role="alert">' . icon('alert', 16) . '<span>' . h($error) . '</span></div>' : '';
}

/** Problems with a new password, or ''. */
function admin_password_problem(string $password, string $again): string
{
    if (mb_strlen($password) < ADMIN_MIN_PASSWORD) return 'Use at least ' . ADMIN_MIN_PASSWORD . ' characters for the password.';
    if ($password !== $again) return 'The two passwords don\'t match.';
    return '';
}

/** One-time setup code for the first run, created on first visit. */
function admin_setup_code(): string
{
    $file = private_dir() . '/setup-code.txt';
    $code = is_file($file) ? trim((string) file_get_contents($file)) : '';
    if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
        $code = implode('-', str_split(strtoupper(substr(bin2hex(random_bytes(6)), 0, 12)), 4));
        @file_put_contents($file, $code . "\n");
    }
    return $code;
}

/** First run: create the sign-in. Always ends the request. */
function admin_setup(): void
{
    $error = '';
    $user = 'admin';
    $code = admin_setup_code();
    if (is_post()) {
        $user = strtolower(trim(post_str('user', 40)));
        $password = (string) ($_POST['password'] ?? '');
        if (rate_blocked('admin-setup', 10, 900)) {
            $error = 'Too many attempts. Please wait a few minutes and try again.';
        } elseif (!hash_equals($code, strtoupper(trim(post_str('code', 20))))) {
            rate_hit('admin-setup', 900);
            $error = 'That setup code isn\'t right.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,40}$/', $user)) {
            $error = 'Choose a user name of 3 to 40 letters or numbers (no spaces).';
        } elseif (($error = admin_password_problem($password, (string) ($_POST['again'] ?? ''))) === '') {
            if (admin_save_account($user, $password)) {
                @unlink(private_dir() . '/setup-code.txt');
                admin_begin($user);
                admin_log('setup', $user);
                flash('Welcome! Your admin sign-in is ready.');
                redirect(ADMIN_BASE);
            }
            $error = 'The sign-in couldn\'t be saved. Check that the website/private folder can be written to.';
        }
    }
    admin_render_bare('Set up',
        '<form class="login" method="post">' . admin_logo()
        . '<h1>Set up the admin panel</h1>'
        . '<p class="muted">Enter the one-time setup code from <code>private/setup-code.txt</code> on the server, then choose the sign-in for the La Sem team.</p>'
        . admin_error_html($error)
        . '<label class="field"><span>Setup code</span><input name="code" autocomplete="off" required autofocus placeholder="XXXX-XXXX-XXXX"></label>'
        . '<label class="field"><span>User name</span><input name="user" value="' . h($user) . '" autocomplete="username" required></label>'
        . '<label class="field"><span>Password <small class="muted">(at least ' . ADMIN_MIN_PASSWORD . ' characters)</small></span><input type="password" name="password" autocomplete="new-password" required minlength="' . ADMIN_MIN_PASSWORD . '"></label>'
        . '<label class="field"><span>Password again</span><input type="password" name="again" autocomplete="new-password" required></label>'
        . '<button class="btn primary block" type="submit">' . icon('lock', 16) . '<span>Create sign-in</span></button>'
        . '</form>');
}

/** The sign-in page (or the redirect after a right password). Always ends the request. */
function admin_login(array $account): void
{
    $error = '';
    $user = '';
    if (is_post() && isset($_POST['password']) && is_string($_POST['password'])) {
        $user = strtolower(trim(post_str('user', 40)));
        if (rate_blocked('admin-login', 8, 900)) {
            $error = 'Too many wrong attempts. Please wait 15 minutes and try again.';
        } elseif (hash_equals($account['user'], $user) && password_verify($_POST['password'], $account['hash'])) {
            rate_clear('admin-login');
            if (password_needs_rehash($account['hash'], PASSWORD_DEFAULT)) admin_save_account($account['user'], $_POST['password']);
            admin_begin($account['user']);
            admin_log('login', $account['user']);
            redirect(ADMIN_BASE . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        } else {
            rate_hit('admin-login', 900);
            admin_log('login.failed', $user);
            $error = 'That user name or password isn\'t right.';
        }
    }
    admin_render_bare('Sign in',
        '<form class="login" method="post">' . admin_logo()
        . '<h1>Admin sign in</h1><p class="muted">Private area for the La Sem team.</p>'
        . admin_error_html($error)
        . '<label class="field"><span>User name</span><input name="user" value="' . h($user) . '" autocomplete="username" required' . ($user === '' ? ' autofocus' : '') . '></label>'
        . '<label class="field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required' . ($user !== '' ? ' autofocus' : '') . '></label>'
        . '<button class="btn primary block" type="submit">' . icon('lock', 16) . '<span>Sign in</span></button>'
        . '</form>');
}

/** Change the password (Store settings). Returns an error message, or '' when it worked. */
function admin_change_password(string $current, string $new, string $again): string
{
    $a = admin_account();
    if (!$a) return 'No sign-in found.';
    if (rate_blocked('admin-password', 8, 900)) return 'Too many attempts. Please wait a few minutes and try again.';
    if (!password_verify($current, $a['hash'])) {
        rate_hit('admin-password', 900);
        return 'Your current password isn\'t right.';
    }
    if (($p = admin_password_problem($new, $again)) !== '') return $p;
    if (!admin_save_account($a['user'], $new)) return 'The new password couldn\'t be saved.';
    $_SESSION['admin_v'] = admin_account_version(admin_account()); // this browser stays signed in, others are signed out
    admin_log('password');
    return '';
}

/** Sign out: forget the session and go back to the sign-in page. */
function admin_logout(): void
{
    admin_log('logout', admin_user());
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
    redirect(ADMIN_BASE);
}
