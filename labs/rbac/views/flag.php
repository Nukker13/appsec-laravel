<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Corp — Admin Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; color: #333; }
        header {
            background: #1a3c5e;
            color: #fff;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }
        header .logo { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        nav a { color: #cde; text-decoration: none; margin-left: 24px; font-size: 14px; }
        nav a:hover { color: #fff; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .flag-box {
            background: #fff;
            border-radius: 6px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            border-left: 4px solid #c0392b;
        }
        .flag-box h2 { color: #c0392b; margin-bottom: 12px; }
        .flag-value {
            font-family: monospace;
            font-size: 16px;
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-radius: 4px;
            padding: 12px 16px;
            word-break: break-all;
        }
        .users-table {
            background: #fff;
            border-radius: 6px;
            padding: 20px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .users-table h3 { color: #1a3c5e; margin-bottom: 16px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f0f4f8; text-align: left; padding: 10px 12px; color: #555; font-weight: 600; }
        td { padding: 10px 12px; border-top: 1px solid #eee; }
        tr:hover td { background: #fafbfc; }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.admin { background: #fde8e8; color: #c0392b; }
        .badge.user  { background: #d1e8d1; color: #2a6e2a; }
        footer { text-align: center; color: #aaa; font-size: 12px; margin: 40px 0 20px; }
    </style>
</head>
<body>
<header>
    <div class="logo">SHRIMP CORP</div>
    <nav>
        <a href="/">Dashboard</a>
        <a href="/flag">Admin Panel</a>
    </nav>
    <div style="font-size:13px;color:#cde;">
        <?= htmlspecialchars($_SESSION['username'] ?? 'unknown') ?>
    </div>
</header>

<div class="container">
    <div class="flag-box">
        <h2>System Flag</h2>
        <div class="flag-value"><?= htmlspecialchars(file_get_contents(__DIR__ . '/../flag.txt')) ?></div>
    </div>

    <div class="users-table">
        <h3>System Users</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>admin</td>
                    <td>admin@shrimpcorp.internal</td>
                    <td><span class="badge admin">admin</span></td>
                    <td>2026-03-09 08:12:44</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>student</td>
                    <td>student@shrimpcorp.internal</td>
                    <td><span class="badge user">user</span></td>
                    <td>2026-03-09 09:05:31</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>jane.smith</td>
                    <td>jane.smith@shrimpcorp.internal</td>
                    <td><span class="badge user">user</span></td>
                    <td>2026-03-08 17:22:10</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>bob.wilson</td>
                    <td>bob.wilson@shrimpcorp.internal</td>
                    <td><span class="badge user">user</span></td>
                    <td>2026-03-07 14:55:02</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<footer>Shrimp Corp Internal Portal &copy; 2026 &mdash; Confidential</footer>
</body>
</html>
