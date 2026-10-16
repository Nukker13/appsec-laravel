<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Corp — Register</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        header { background: #d4480a; color: #fff; padding: 0 32px; height: 56px; display: flex; align-items: center; }
        header h1 { font-size: 20px; letter-spacing: 1px; }
        .center { flex: 1; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 8px; padding: 36px 40px; width: 360px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .card h2 { margin-bottom: 24px; font-size: 18px; color: #1a1a1a; }
        label { display: block; font-size: 13px; color: #555; margin-bottom: 4px; }
        input[type="text"], input[type="password"], select {
            width: 100%; padding: 9px 12px; border: 1px solid #ccc; border-radius: 4px;
            font-size: 14px; margin-bottom: 16px; background: #fafafa;
        }
        button[type="submit"] {
            width: 100%; background: #d4480a; color: #fff; border: none;
            padding: 10px; border-radius: 4px; font-size: 14px; cursor: pointer;
        }
        button[type="submit"]:hover { background: #b33d08; }
        .error { background: #fdecea; color: #c0392b; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; font-size: 14px; }
        .link { text-align: center; margin-top: 16px; font-size: 13px; }
        .link a { color: #d4480a; text-decoration: none; }
        footer { text-align: center; color: #aaa; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
<header><h1>🦐 SHRIMP CORP</h1></header>
<div class="center">
    <div class="card">
        <h2>Create account</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/register">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="off">
            <label for="password">Password</label>
            <input type="password" id="password" name="password">
            <label for="algorithm">Hashing algorithm</label>
            <select id="algorithm" name="algorithm">
                <option value="md5">MD5</option>
                <option value="sha1">SHA-1</option>
                <option value="sha256">SHA-256</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <div class="link"><a href="/">Back to login</a></div>
    </div>
</div>
<footer>Shrimp Corp Internal Portal &copy; 2026</footer>
</body>
</html>