<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Hotel &mdash; Booking Summary</title>
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
        .container { max-width: 600px; margin: 50px auto; padding: 0 20px; }
        h2 { color: #1a3a5c; margin-bottom: 24px; font-size: 1.5rem; }
        .summary-box {
            background: white;
            border: 1px solid #c5daea;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 32px;
        }
        .summary-section {
            padding: 20px 24px;
            border-bottom: 1px solid #e8f4f8;
        }
        .summary-section:last-child { border-bottom: none; }
        .summary-section h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2e6da4;
            margin-bottom: 14px;
        }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .row .label { color: #666; font-size: 0.95rem; }
        .row .value { font-weight: bold; color: #1a3a5c; font-size: 0.95rem; }
        .confirm-box {
            background: #e8f8ee;
            border: 1px solid #6abf8e;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 28px;
            text-align: center;
        }
        .confirm-box .icon { font-size: 2rem; margin-bottom: 8px; }
        .confirm-box p { color: #2d6a4f; font-size: 1rem; }
        .btn {
            display: inline-block;
            background: #2e6da4;
            color: white;
            border: none;
            padding: 14px 36px;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
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
        <div class="step done">2. Guest Details &#10003;</div>
        <div class="step active">3. Summary</div>
    </div>

    <div class="container">
        <h2>Booking Summary</h2>

        <div class="summary-box">
            <div class="summary-section">
                <h3>Room Details</h3>
                <?php /* BookingOptions — see index.php */ if ($booking instanceof BookingOptions): ?>
                    <div class="row">
                        <span class="label">Room Type</span>
                        <span class="value"><?= htmlspecialchars((string)($booking->roomType ?? '')) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Check-in</span>
                        <span class="value"><?= htmlspecialchars((string)($booking->checkIn ?? '')) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Check-out</span>
                        <span class="value"><?= htmlspecialchars((string)($booking->checkOut ?? '')) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Guests</span>
                        <span class="value"><?= htmlspecialchars((string)($booking->guests ?? '')) ?></span>
                    </div>
                <?php else: ?>
                    <p style="color:#999;">No booking data found.</p>
                <?php endif; ?>
            </div>

            <div class="summary-section">
                <h3>Guest Information</h3>
                <div class="row">
                    <span class="label">Name</span>
                    <span class="value">
                        <?= htmlspecialchars(($_SESSION['guest_first'] ?? '') . ' ' . ($_SESSION['guest_last'] ?? '')) ?>
                    </span>
                </div>
                <div class="row">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($_SESSION['guest_email'] ?? '') ?></span>
                </div>
                <?php if (!empty($_SESSION['guest_requests'])): ?>
                <div class="row">
                    <span class="label">Special Requests</span>
                    <span class="value"><?= htmlspecialchars($_SESSION['guest_requests']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="confirm-box">
            <div class="icon">&#127881;</div>
            <p>Your booking has been confirmed! A confirmation email will be sent shortly.</p>
            <p style="margin-top:10px;font-size:0.85rem;color:#2d6a4f;">Your booking details have been recorded in our system.</p>
        </div>

        <a href="/" class="btn">&#8592; Back to Home</a>
    </div>
</body>
</html>
