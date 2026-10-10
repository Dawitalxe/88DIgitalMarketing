<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($basePath === '.') {
    $basePath = '';
}
$csrf = Auth::csrf();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>88 Digi Mark — Register</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="brand"><span>88</span> Digi Mark</div>
        <h1>Create your account</h1>

        <form id="registerForm" novalidate>
            <label for="full_name">
                Full name
                <input id="full_name" type="text" name="full_name" required maxlength="160" autocomplete="name">
            </label>

            <label for="email">
                Email
                <input id="email" type="email" name="email" required autocomplete="email">
            </label>

            <label for="password">
                Password
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>

            <label for="password_confirmation">
                Confirm password
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </label>

            <button type="submit" id="registerBtn" class="btn btn-primary">Register</button>
            <p id="registerMessage" class="error" role="alert" aria-live="polite"></p>
        </form>

        <p><a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">Already have an account? Sign in</a></p>
    </main>

    <script nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
    const registerForm = document.getElementById('registerForm');
    registerForm.addEventListener('submit', async event => {
        event.preventDefault();
        const message = document.getElementById('registerMessage');
        const button = document.getElementById('registerBtn');
        message.textContent = '';
        button.disabled = true;

        try {
            const response = await fetch(<?= json_encode($basePath . '/api/register.php', JSON_UNESCAPED_SLASHES) ?>, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(Object.fromEntries(new FormData(registerForm)))
            });
            const data = await response.json();
            if (!response.ok || data.ok !== true) {
                throw new Error(data.error || 'Registration failed.');
            }
            window.location.href = <?= json_encode($basePath . '/', JSON_UNESCAPED_SLASHES) ?>;
        } catch (error) {
            message.textContent = error.message;
            button.disabled = false;
        }
    });
    </script>
</body>
</html>
