<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Hotel &mdash; Book a Room</title>
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
        .container { max-width: 560px; margin: 50px auto; padding: 0 20px; }
        h2 { color: #1a3a5c; margin-bottom: 24px; font-size: 1.5rem; }
        label { display: block; margin-bottom: 6px; font-size: 0.95rem; color: #333; }
        input, select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: 20px;
            background: white;
        }
        input:focus, select:focus { outline: none; border-color: #2e6da4; }
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
        <div class="step active">1. Room Selection</div>
        <div class="step">2. Guest Details</div>
        <div class="step">3. Summary</div>
    </div>

    <div class="container">
        <h2>Choose Your Room</h2>
        <form action="/book" method="post">
            <label for="room_type">Room Type</label>
            <select id="room_type" name="room_type">
                <option value="Standard Shrimp">Standard Shrimp &mdash; $120/night</option>
                <option value="Deluxe Shrimp">Deluxe Shrimp &mdash; $220/night</option>
                <option value="Presidential Suite">Presidential Suite &mdash; $580/night</option>
            </select>

            <div class="row">
                <div>
                    <label for="check_in">Check-in Date</label>
                    <input type="date" id="check_in" name="check_in" required
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="check_out">Check-out Date</label>
                    <input type="date" id="check_out" name="check_out" required
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
            </div>

            <label for="guests">Number of Guests</label>
            <select id="guests" name="guests">
                <option value="1">1 Guest</option>
                <option value="2">2 Guests</option>
                <option value="3">3 Guests</option>
                <option value="4">4 Guests</option>
            </select>

            <button type="submit" class="btn">Continue to Guest Details &rarr;</button>
        </form>
    </div>
</body>
</html>
