<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OAuth Lab</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f0f2f5; }
        .container { max-width: 640px; margin: 60px auto; background: #fff; border-radius: 10px; padding: 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; font-size: 1.6rem; color: #1a1a2e; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .badge-user  { background: #e0f0ff; color: #1565c0; }
        .badge-admin { background: #fce4ec; color: #b71c1c; }
        .section { margin: 24px 0; padding: 16px; border: 1px solid #e0e0e0; border-radius: 8px; }
        .section h3 { margin: 0 0 12px; font-size: 1rem; color: #555; }
        .provider-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 6px; border: 1px solid #dadce0; background: #fff; cursor: pointer; font-size: 0.9rem; text-decoration: none; color: #3c4043; transition: background .15s; }
        .btn:hover { background: #f8f9fa; }
        .btn-google { border-color: #dadce0; }
        .btn-apple  { background: #000; color: #fff; border-color: #000; }
        .btn-apple:hover { background: #333; }
        .btn-primary { background: #1976d2; color: #fff; border-color: #1565c0; }
        .btn-primary:hover { background: #1565c0; }
        .btn-danger { background: #d32f2f; color: #fff; border-color: #b71c1c; }
        .btn-danger:hover { background: #b71c1c; }
        .linked-info { font-size: 0.85rem; color: #388e3c; margin-top: 6px; }
        .meta { color: #757575; font-size: 0.9rem; margin-bottom: 20px; }
        hr { border: none; border-top: 1px solid #eee; margin: 24px 0; }
        .failure-banner { background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:16px; margin-top:16px; animation:fadeInDown .5s ease-out; }
        .failure-banner p { margin:0 0 12px; font-size:0.9rem; color:#664d03; }
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
<div class="container">
    <h1>OAuth Lab</h1>

    <?php if (empty($_SESSION['id'])): ?>

        <p class="meta">You are signed out.</p>

        <div class="section">
            <h3>Sign in with a Social Account</h3>
            <div class="provider-row">
                <form method="post" action="/oauth/social-start">
                    <input type="hidden" name="provider" value="google">
                    <button type="submit" class="btn btn-google">
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.5 30.2 0 24 0 14.7 0 6.7 5.5 2.8 13.5l7.8 6C12.5 13.2 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8C43.5 37.2 46.5 31.3 46.5 24.5z"/><path fill="#FBBC05" d="M10.6 28.5A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.1.7-4.5l-7.8-6A23.9 23.9 0 0 0 0 24c0 3.9.9 7.5 2.4 10.8l8.2-6.3z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.6 2.2-7.7 2.2-6.2 0-11.5-4.2-13.4-9.9l-8.2 6.3C6.7 42.5 14.7 48 24 48z"/></svg>
                        Login with Google
                    </button>
                </form>
                <form method="post" action="/oauth/social-start">
                    <input type="hidden" name="provider" value="apple">
                    <button type="submit" class="btn btn-apple">
                        <svg width="18" height="18" viewBox="0 0 814 1000" fill="white"><path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76 0-103.7 40.8-165.9 40.8s-105-42.3-150.3-109.7C34 378.9 0 305.8 0 235.2c0-165.6 107.6-253.2 213.4-253.2 56.5 0 103.7 37.4 139.2 37.4 34 0 87.1-39.5 151.2-39.5 24.7 0 108.2 2.6 168.6 80.3zm-171.6-38.4c-9.7 45.2-35.1 92-66.9 121.9-32.4 30.5-72.5 52.7-112 52.7-4.5 0-9-.6-13.5-1.3-1.9-47.3 17.8-97.1 46.2-128.6 29.7-33.1 78.8-58.5 122.9-63.2a148.2 148.2 0 0 1 23.3-1.5z"/></svg>
                        Login with Apple ID
                    </button>
                </form>
            </div>
        </div>

    <?php elseif (($_SESSION['role'] ?? '') === 'admin'): ?>

        <p class="meta">
            Signed in as <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>
            &nbsp;<span class="badge badge-admin">ADMIN</span>
        </p>

        <div class="section">
            <h3>Admin Panel</h3>
            <p>You have admin access. Retrieve the flag below.</p>
            <a class="btn btn-primary" href="/flag">Get the Flag!</a>
        </div>

    <?php else: ?>

        <p class="meta">
            Signed in as <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>
            &nbsp;<span class="badge badge-user">USER</span>
        </p>

        <div class="section">
            <h3>Connect Social Accounts</h3>
            <p style="font-size:0.85rem;color:#666;">Link your social identity to this account.</p>
            <div class="provider-row">
                <!-- Connect Google -->
                <form method="post" action="/oauth/start">
                    <input type="hidden" name="provider" value="google">
                    <button type="submit" class="btn btn-google">
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.2l6.7-6.7C35.7 2.5 30.2 0 24 0 14.7 0 6.7 5.5 2.8 13.5l7.8 6C12.5 13.2 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8C43.5 37.2 46.5 31.3 46.5 24.5z"/><path fill="#FBBC05" d="M10.6 28.5A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.1.7-4.5l-7.8-6A23.9 23.9 0 0 0 0 24c0 3.9.9 7.5 2.4 10.8l8.2-6.3z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.6 2.2-7.7 2.2-6.2 0-11.5-4.2-13.4-9.9l-8.2 6.3C6.7 42.5 14.7 48 24 48z"/></svg>
                        Connect with Google
                    </button>
                </form>

                <!-- Connect Apple -->
                <form method="post" action="/oauth/start">
                    <input type="hidden" name="provider" value="apple">
                    <button type="submit" class="btn btn-apple">
                        <svg width="18" height="18" viewBox="0 0 814 1000" fill="white"><path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76 0-103.7 40.8-165.9 40.8s-105-42.3-150.3-109.7C34 378.9 0 305.8 0 235.2c0-165.6 107.6-253.2 213.4-253.2 56.5 0 103.7 37.4 139.2 37.4 34 0 87.1-39.5 151.2-39.5 24.7 0 108.2 2.6 168.6 80.3zm-171.6-38.4c-9.7 45.2-35.1 92-66.9 121.9-32.4 30.5-72.5 52.7-112 52.7-4.5 0-9-.6-13.5-1.3-1.9-47.3 17.8-97.1 46.2-128.6 29.7-33.1 78.8-58.5 122.9-63.2a148.2 148.2 0 0 1 23.3-1.5z"/></svg>
                        Connect with Apple ID
                    </button>
                </form>
            </div>

            <?php
            // Show currently linked providers
            $db  = \Doctrine\DBAL\DriverManager::getConnection(\OAUTH\Helpers\Config::db());
            $row = $db->fetchAssociative(
                "SELECT google_email, apple_email FROM users WHERE id = ?",
                [$_SESSION['id']]
            );
            $bothLinked = $row && $row['google_email'] && $row['apple_email'];
            if ($row && $row['google_email']): ?>
                <p class="linked-info">✓ Google: <?= htmlspecialchars($row['google_email']) ?></p>
            <?php endif;
            if ($row && $row['apple_email']): ?>
                <p class="linked-info">✓ Apple ID: <?= htmlspecialchars($row['apple_email']) ?></p>
            <?php endif; ?>

            <?php if ($bothLinked): ?>
            <div class="failure-banner">
                <p><strong>Not quite.</strong> You've linked both providers to your own account — the goal is to trick the <em>admin</em> into linking their identity instead.</p>
                <form method="post" action="/reset">
                    <button type="submit" class="btn btn-danger">↺ Start Over</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Report a Page</h3>
            <p style="font-size:0.85rem;color:#666;">Found something that doesn't work? Let the admin know.</p>
            <a class="btn" href="/form">Report a page</a>
        </div>

    <?php endif; ?>

    <hr>
    <form method="post" action="/logout">
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
</body>
</html>
