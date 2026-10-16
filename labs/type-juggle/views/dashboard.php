<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Corp — Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        header { background: #d4480a; color: #fff; padding: 0 32px; height: 56px; display: flex; align-items: center; justify-content: space-between; }
        header h1 { font-size: 20px; letter-spacing: 1px; }
        header a { color: #fff; font-size: 13px; text-decoration: none; opacity: .85; }
        header a:hover { opacity: 1; }
        .container { max-width: 600px; margin: 48px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 8px; padding: 28px 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .card p { font-size: 15px; color: #444; line-height: 1.6; }
        .flag-box { margin-top: 24px; border-top: 1px solid #eee; padding-top: 20px; }
        .flag-box h3 { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
        .flag-value { font-family: monospace; font-size: 15px; background: #fdf2f2; border: 1px solid #f5c6c6; border-radius: 4px; padding: 12px 16px; word-break: break-all; color: #c0392b; }
        footer { text-align: center; color: #aaa; font-size: 12px; padding: 20px; margin-top: auto; }
    </style>
</head>
<body>
<header>
    <h1>🦐 SHRIMP CORP</h1>
    <a href="/logout">Logout</a>
</header>
<div class="container">
    <div class="card">
        <p>You are logged in as <strong><?= htmlspecialchars(currentUser()['username']) ?></strong>.
        <?php if (currentUser()['role'] !== 'admin'): ?>
            You don't have admin privileges.
        <?php else: ?>
            You have admin access.
            <div class="flag-box">
                <h3>System Flag</h3>
                <div class="flag-value"><?= htmlspecialchars(file_get_contents(__DIR__ . '/../flag.txt')) ?></div>
            </div>
        <?php endif; ?>
        </p>
    </div>
</div>
<footer>Shrimp Corp Internal Portal &copy; 2026</footer>
</body>
</html>
