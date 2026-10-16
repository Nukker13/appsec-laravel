<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shrimp Hotel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Georgia, serif; background: #fdf8f0; color: #2c2c2c; }
        header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2e6da4 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        header h1 { font-size: 3rem; letter-spacing: 2px; margin-bottom: 8px; }
        header p { font-size: 1.2rem; opacity: 0.85; }
        .hero {
            background: #e8f4f8;
            border-bottom: 3px solid #2e6da4;
            padding: 80px 40px;
            text-align: center;
        }
        .hero-icon { font-size: 5rem; margin-bottom: 20px; }
        .hero h2 { font-size: 2rem; margin-bottom: 16px; color: #1a3a5c; }
        .hero p { font-size: 1.1rem; max-width: 600px; margin: 0 auto 32px; line-height: 1.7; color: #444; }
        .btn {
            display: inline-block;
            background: #2e6da4;
            color: white;
            padding: 16px 40px;
            font-size: 1.1rem;
            text-decoration: none;
            border-radius: 4px;
            letter-spacing: 1px;
            transition: background 0.2s;
        }
        .btn:hover { background: #1a3a5c; }
        .features {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 60px 40px;
            flex-wrap: wrap;
        }
        .feature {
            text-align: center;
            max-width: 220px;
        }
        .feature-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .feature h3 { margin-bottom: 8px; color: #1a3a5c; }
        .feature p { font-size: 0.95rem; color: #555; line-height: 1.6; }
        footer {
            text-align: center;
            padding: 24px;
            background: #1a3a5c;
            color: #aac4df;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>&#127844; The Shrimp Hotel</h1>
        <p>Where every guest is a pearl &mdash; luxury on the ocean floor</p>
    </header>

    <div class="hero">
        <div class="hero-icon">&#127958;</div>
        <h2>Experience Oceanic Luxury</h2>
        <p>
            Nestled beneath the azure waves, The Shrimp Hotel offers unparalleled comfort,
            world-class cuisine, and panoramic views of the coral reef. Your perfect getaway awaits.
        </p>
        <a href="/book" class="btn">Book a Room</a>
    </div>

    <div class="features">
        <div class="feature">
            <div class="feature-icon">&#127949;</div>
            <h3>Luxury Suites</h3>
            <p>From cosy Standard Shrimp rooms to the extravagant Presidential Suite.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">&#127837;</div>
            <h3>Fine Dining</h3>
            <p>Our award-winning chef crafts exquisite dishes from the freshest ocean ingredients.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">&#127946;</div>
            <h3>Reef Spa</h3>
            <p>Rejuvenate with our signature seaweed treatments and thermal pools.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">&#11088;</div>
            <h3>5-Star Service</h3>
            <p>Our dedicated staff ensures every detail of your stay is perfection.</p>
        </div>
    </div>

    <footer>
        &copy; 2026 The Shrimp Hotel. All rights reserved.
    </footer>
</body>
</html>
