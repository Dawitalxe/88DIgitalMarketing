<?php

// declare(strict_types=1);

/*
 * 88 Digi Mark — ERP & Client Portal
 * public/index.php
 *
 * IMPORTANT:
 * Web server document root must point to:
 *     /path/to/project/public
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($basePath === '.') {
    $basePath = '';
}

// Make sure the session is available.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check authentication.
if (!Auth::check()) {
    $csrf = Auth::csrf();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="csrf-token"
              content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <title>88 Digi Mark — Sign in</title>

        <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
    </head>

    <body class="auth-page">

        <main class="auth-card">

            <div class="brand">
                <span>88</span> Digi Mark
            </div>

            <h1>ERP &amp; Client Portal</h1>

            <form id="loginForm" method="post" novalidate>

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                >

                <label for="email">
                    Email
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                    >
                </label>

                <label for="password">
                    Password
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </label>

                <button
                    type="submit"
                    id="loginBtn"
                    class="btn btn-primary"
                >
                    Sign in
                </button>

                <p
                    id="loginError"
                    class="error"
                    role="alert"
                    aria-live="polite"
                ></p>

            </form>

        </main>

        <script nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
        window.APP_BASE = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        </script>
        <script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
    exit;
}


// ---------------------------------------------------------
// Authenticated user
// ---------------------------------------------------------

$user = Auth::user();
$csrf = Auth::csrf();

if (!is_array($user)) {
    // Session is invalid. Log out safely.
    if (method_exists('Auth', 'logout')) {
        Auth::logout();
    }

    header('Location: /');
    exit;
}

$userName = (string)($user['full_name'] ?? 'User');
$userRoles = (string)($user['roles'] ?? '');


/*
 * Safely pass data to JavaScript.
 */
$appData = [
    'csrf' => $csrf,
    'user' => $user,
];

$appJson = json_encode(
    $appData,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($appJson === false) {
    $appJson = '{}';
}
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
    >

    <title>88 Digi Mark — Dashboard</title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css"
    >

    <!-- Chart.js -->
    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"
        defer
    ></script>

</head>

<body>

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <span>88</span> Digi Mark
        </div>

        <nav id="nav" aria-label="Main navigation">

            <a
                href="#overview"
                data-view="overview"
            >
                Overview
            </a>

            <a
                href="#tasks"
                data-view="tasks"
            >
                Tasks
            </a>

            <a
                href="#approvals"
                data-view="approvals"
            >
                Approvals
            </a>

            <a
                href="#companies"
                data-view="companies"
            >
                Companies
            </a>

            <a
                href="#hr"
                data-view="hr"
            >
                HR / Payroll
            </a>

            <a
                href="#reception"
                data-view="reception"
            >
                Reception
            </a>

            <a
                href="#client"
                data-view="client"
            >
                Client
            </a>

            <a
                href="#security"
                data-view="security"
            >
                Security
            </a>

        </nav>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main">

        <header class="topbar">

            <div>
                <strong>
                    <?= htmlspecialchars(
                        $userName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        $userRoles,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </small>
            </div>

            <button
                type="button"
                id="logoutBtn"
                class="btn btn-ghost"
            >
                Sign out
            </button>

        </header>


        <!-- AJAX views are loaded here -->
        <section
            id="view"
            class="content"
            aria-live="polite"
        ></section>

    </main>

</div>


<script nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
window.APP = <?= $appJson ?>;
window.APP_BASE = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js"></script>

</body>
</html>
