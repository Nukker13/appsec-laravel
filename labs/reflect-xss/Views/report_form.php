<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report a broken page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px auto;
            max-width: 600px;
            line-height: 1.6;
        }
        h2 {
            margin-bottom: 10px;
        }
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
        button:hover {
            background: #0d5bab;
        }
    </style>
</head>
<body>

<h2>Report a broken page</h2>
<p>If you found a page on the site that does not work properly, please submit the link here.</p>

<div class="report-box">
    <form action="/report" method="post">
        <label for="url">Page URL:</label><br>
        <input type="text" id="url" name="url" placeholder="e.g. http://localhost/facts/cat" required>

        <button type="submit">Submit report</button>
    </form>
</div>

</body>
</html>