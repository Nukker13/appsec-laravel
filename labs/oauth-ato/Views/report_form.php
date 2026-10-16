<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report a page – OAuth Lab</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px auto; max-width: 600px; line-height: 1.6; }
        h2 { margin-bottom: 10px; }
        .report-box {
            background: #f7f7f7;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #bbb;
            box-sizing: border-box;
        }
        button {
            padding: 10px 20px;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { background: #0d5bab; }
        .back { margin-top: 16px; display: inline-block; color: #555; font-size: 0.9rem; }
    </style>
</head>
<body>
<h2>Report a page – OAuth Lab</h2>
<p>Found a broken or suspicious page? Submit the URL and our admin will review it.</p>

<div class="report-box">
    <form action="/report" method="post">
        <label for="url">Page URL:</label><br>
        <input type="text" id="url" name="url" placeholder="e.g. /oauth/callback?code=...&state=...&mode=link&provider=google" required>
        <button type="submit">Submit report</button>
    </form>
</div>

<a class="back" href="/">← Back to dashboard</a>
</body>
</html>
