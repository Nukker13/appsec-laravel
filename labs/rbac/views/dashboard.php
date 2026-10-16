<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Corp — Employee Portal</title>
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
        nav a {
            color: #cde;
            text-decoration: none;
            margin-left: 24px;
            font-size: 14px;
        }
        nav a:hover { color: #fff; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .welcome {
            background: #fff;
            border-radius: 6px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .welcome h2 { margin-bottom: 8px; color: #1a3c5e; }
        .welcome p { color: #555; line-height: 1.6; }
        .cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .card {
            background: #fff;
            border-radius: 6px;
            padding: 20px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .card h3 { color: #1a3c5e; margin-bottom: 12px; font-size: 15px; }
        .card ul { padding-left: 18px; color: #555; line-height: 2; font-size: 14px; }
        .announcements { background: #fff; border-radius: 6px; padding: 20px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .announcements h3 { color: #1a3c5e; margin-bottom: 16px; font-size: 15px; }
        .announcement { border-left: 3px solid #1a3c5e; padding: 8px 14px; margin-bottom: 14px; background: #f9fbfd; }
        .announcement .date { font-size: 12px; color: #888; margin-bottom: 4px; }
        .announcement p { font-size: 14px; color: #444; line-height: 1.5; }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background: #d1e8d1;
            color: #2a6e2a;
        }
        footer { text-align: center; color: #aaa; font-size: 12px; margin: 40px 0 20px; }
    </style>
</head>
<body>
<header>
    <div class="logo">SHRIMP CORP</div>
    <nav>
        <a href="/">Dashboard</a>
        <a href="/flag" style="display:none">Admin Panel</a>
    </nav>
    <div style="font-size:13px;color:#cde;">
        <?= htmlspecialchars($_SESSION['username'] ?? 'unknown') ?>
        &nbsp;|&nbsp;
        <span class="badge"><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></span>
    </div>
</header>

<div class="container">
    <div class="welcome">
        <h2>Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Employee') ?>!</h2>
        <p>This is the Shrimp Corp internal employee portal. Use the links above to navigate between sections.
           Your current role is <strong><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></strong>.</p>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="#">HR Self-Service</a></li>
                <li><a href="#">IT Helpdesk</a></li>
                <li><a href="#">Expense Reports</a></li>
                <li><a href="#">Benefits Portal</a></li>
                <li><a href="#">Company Directory</a></li>
            </ul>
        </div>
        <div class="card">
            <h3>My Profile</h3>
            <ul style="list-style:none;padding:0;">
                <li><strong>Name:</strong> <?= htmlspecialchars($_SESSION['username'] ?? '') ?></li>
                <li><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email'] ?? '') ?></li>
                <li><strong>Department:</strong> Engineering</li>
                <li><strong>Location:</strong> San Francisco, CA</li>
                <li><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></li>
            </ul>
        </div>
    </div>

    <div class="announcements">
        <h3>Company Announcements</h3>
        <div class="announcement">
            <div class="date">March 7, 2026</div>
            <p>Q1 all-hands meeting scheduled for March 15th at 10:00 AM PST. Zoom link will be sent via email.</p>
        </div>
        <div class="announcement">
            <div class="date">March 3, 2026</div>
            <p>The IT team will be performing scheduled maintenance on the VPN gateway this Saturday from 2–4 AM PST.
               Expect brief connectivity disruptions.</p>
        </div>
        <div class="announcement">
            <div class="date">February 28, 2026</div>
            <p>New security training modules are now available in the Learning Management System. All employees must
               complete the "Phishing Awareness" module by March 31st.</p>
        </div>
    </div>
</div>

<footer>Shrimp Corp Internal Portal &copy; 2026 &mdash; Confidential</footer>
</body>
</html>
