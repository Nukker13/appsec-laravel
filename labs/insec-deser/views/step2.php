<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Hotel &mdash; Guest Details</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #fdf8f0; color: #2c2c2c; }
        header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2e6da4 100%);
            color: white;
            padding: 20px 40px;
        }
        header a { color: #aac4df; text-decoration: none; font-size: 0.9rem; }
        header h1 { font-size: 1.8rem; margin-top: 4px; }
        .steps {
            display: flex;
            justify-content: center;
            gap: 0;
            background: #e8f4f8;
            border-bottom: 2px solid #2e6da4;
        }
        .step {
            padding: 14px 32px;
            font-size: 0.9rem;
            color: #888;
            border-right: 1px solid #c5daea;
        }
        .step.active { background: #2e6da4; color: white; font-weight: bold; }
        .step.done { color: #2e6da4; }
        .container { max-width: 560px; margin: 50px auto; padding: 0 20px; }
        h2 { color: #1a3a5c; margin-bottom: 24px; font-size: 1.5rem; }
        label { display: block; margin-bottom: 6px; font-size: 0.95rem; color: #333; }
        input, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: 20px;
        }
        input:focus, textarea:focus { outline: none; border-color: #2e6da4; }
        textarea { resize: vertical; min-height: 100px; }
        .row { display: flex; gap: 16px; }
        .row > div { flex: 1; }
        .btn {
            background: #2e6da4;
            color: white;
            border: none;
            padding: 14px 36px;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            letter-spacing: 0.5px;
        }
        .btn:hover { background: #1a3a5c; }
    </style>
</head>
<body>
    <header>
        <a href="/">&#8592; The Shrimp Hotel</a>
        <h1>&#127844; Book a Room</h1>
    </header>

    <div class="steps">
        <div class="step done">1. Room Selection &#10003;</div>
        <div class="step active">2. Guest Details</div>
        <div class="step">3. Summary</div>
    </div>

    <div class="container">
        <h2>Your Details</h2>
        <form action="/book/details" method="post">
            <div class="row">
                <div>
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="Jane">
                </div>
                <div>
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Smith">
                </div>
            </div>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="jane@example.com">

            <label for="special_requests">Special Requests <span style="color:#999;font-size:0.85rem;">(optional)</span></label>
            <textarea id="special_requests" name="special_requests"
                      placeholder="e.g. high floor, extra pillows, late check-in..."></textarea>

            <button type="submit" class="btn">Review Booking &rarr;</button>
        </form>
    </div>
</body>
</html>
