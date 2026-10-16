<?php
$provider = $_GET['provider'] ?? $_POST['provider'] ?? 'google';
$state    = $_GET['state']    ?? $_POST['state']    ?? '';
$mode     = $_GET['mode']     ?? $_POST['mode']     ?? 'login';
$error    = $error ?? null;
$isGoogle = $provider === 'google';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isGoogle ? 'Sign in – Google Accounts' : 'Sign in with Apple' ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: <?= $isGoogle ? "'Roboto', Arial, sans-serif" : "-apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif" ?>;
            background: <?= $isGoogle ? '#fff' : '#1c1c1e' ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: <?= $isGoogle ? '#fff' : '#2c2c2e' ?>;
            border-radius: <?= $isGoogle ? '8px' : '18px' ?>;
            padding: <?= $isGoogle ? '48px 40px' : '40px 36px' ?>;
            width: 400px;
            box-shadow: <?= $isGoogle ? '0 2px 10px rgba(0,0,0,.12)' : '0 8px 32px rgba(0,0,0,.5)' ?>;
            text-align: center;
        }
        .logo { margin-bottom: 24px; }
        h1 {
            font-size: <?= $isGoogle ? '1.5rem' : '1.7rem' ?>;
            font-weight: <?= $isGoogle ? '400' : '600' ?>;
            color: <?= $isGoogle ? '#202124' : '#f5f5f7' ?>;
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 0.9rem;
            color: <?= $isGoogle ? '#5f6368' : '#a1a1a6' ?>;
            margin-bottom: 28px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 14px;
            border: <?= $isGoogle ? '1px solid #dadce0' : '1px solid #48484a' ?>;
            border-radius: <?= $isGoogle ? '4px' : '10px' ?>;
            font-size: 1rem;
            background: <?= $isGoogle ? '#fff' : '#3a3a3c' ?>;
            color: <?= $isGoogle ? '#202124' : '#f5f5f7' ?>;
            outline: none;
        }
        input:focus {
            border-color: <?= $isGoogle ? '#1a73e8' : '#0a84ff' ?>;
            box-shadow: <?= $isGoogle ? '0 0 0 2px rgba(26,115,232,.2)' : '0 0 0 3px rgba(10,132,255,.25)' ?>;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: <?= $isGoogle ? '4px' : '10px' ?>;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            background: <?= $isGoogle ? '#1a73e8' : '#0a84ff' ?>;
            color: #fff;
            transition: opacity .15s;
        }
        .btn-submit:hover { opacity: .9; }
        .error {
            background: #fce8e6;
            color: #d93025;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 0.88rem;
        }
        .disclaimer {
            font-size: 0.75rem;
            color: <?= $isGoogle ? '#5f6368' : '#6e6e73' ?>;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <?php if ($isGoogle): ?>
            <svg width="48" height="48" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.5 30.2 0 24 0 14.7 0 6.7 5.5 2.8 13.5l7.8 6C12.5 13.2 17.8 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8C43.5 37.2 46.5 31.3 46.5 24.5z"/>
                <path fill="#FBBC05" d="M10.6 28.5A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.1.7-4.5l-7.8-6A23.9 23.9 0 0 0 0 24c0 3.9.9 7.5 2.4 10.8l8.2-6.3z"/>
                <path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.6 2.2-7.7 2.2-6.2 0-11.5-4.2-13.4-9.9l-8.2 6.3C6.7 42.5 14.7 48 24 48z"/>
            </svg>
        <?php else: ?>
            <svg width="48" height="48" viewBox="0 0 814 1000" fill="#f5f5f7">
                <path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76 0-103.7 40.8-165.9 40.8s-105-42.3-150.3-109.7C34 378.9 0 305.8 0 235.2c0-165.6 107.6-253.2 213.4-253.2 56.5 0 103.7 37.4 139.2 37.4 34 0 87.1-39.5 151.2-39.5 24.7 0 108.2 2.6 168.6 80.3zm-171.6-38.4c-9.7 45.2-35.1 92-66.9 121.9-32.4 30.5-72.5 52.7-112 52.7-4.5 0-9-.6-13.5-1.3-1.9-47.3 17.8-97.1 46.2-128.6 29.7-33.1 78.8-58.5 122.9-63.2a148.2 148.2 0 0 1 23.3-1.5z"/>
            </svg>
        <?php endif; ?>
    </div>

    <h1><?= $isGoogle ? 'Sign in with Google' : 'Sign in with Apple' ?></h1>
    <p class="subtitle">
        <?= $isGoogle
            ? 'Use your Google Account to continue'
            : 'Use your Apple ID to continue' ?>
    </p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/oauth/provider">
        <input type="hidden" name="provider" value="<?= htmlspecialchars($provider) ?>">
        <input type="hidden" name="state"    value="<?= htmlspecialchars($state) ?>">
        <input type="hidden" name="mode"     value="<?= htmlspecialchars($mode) ?>">

        <input type="email"    name="email"    placeholder="<?= $isGoogle ? 'Email or phone' : 'Apple ID (email)' ?>" required autocomplete="email">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">

        <button type="submit" class="btn-submit">
            <?= $isGoogle ? 'Next' : 'Continue' ?>
        </button>
    </form>

    <p class="disclaimer">
        <?= $isGoogle
            ? 'This is a simulated Google sign-in page for educational purposes.'
            : 'This is a simulated Apple sign-in page for educational purposes.' ?>
    </p>
</div>
</body>
</html>
