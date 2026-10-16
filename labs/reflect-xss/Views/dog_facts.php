<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dogs Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #fafafa;
            color: #333;
        }
        h1 { color: #444; }
        img, video {
            max-width: 400px;
            border-radius: 10px;
            margin: 10px 0;
            display: block;
        }
        .fact-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-width: 600px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .fact-box ul {
            line-height: 1.8;
            padding-left: 20px;
            margin: 0;
        }
    </style>
</head>
<body>
<h1>🐶 Fun Facts About Dogs</h1>

<!-- Контейнер для картинки/видео собаки -->
<div id="dog-container">
    <p>Loading a random dog...</p>
</div>

<div class="fact-box">
    <ul>
        <li>Dogs can understand up to 250 words and gestures.</li>
        <li>Their sense of smell is up to 100,000 times stronger than humans.</li>
        <li>Dogs’ nose prints are unique, like human fingerprints.</li>
        <li>Some breeds were originally bred for very specific jobs: guarding, herding, hunting.</li>
        <li>The Basenji is known as the “barkless dog” — it makes a yodel-like sound.</li>
    </ul>
</div>

<script>
    async function loadDogMedia() {
        const container = document.getElementById("dog-container");
        container.innerHTML = "<p>Loading a random dog...</p>";

        try {
            const res = await fetch("https://random.dog/woof.json");
            const { url } = await res.json();

            if (url.match(/\.(mp4|webm)$/i)) {
                container.innerHTML = `
                        <video src="${url}" controls autoplay loop></video>
                    `;
            } else {
                container.innerHTML = `
                        <img src="${url}" alt="Random dog">
                    `;
            }
        } catch (e) {
            container.innerHTML = "<p>Failed to load dog media :(</p>";
        }
    }

    loadDogMedia();
</script>
</body>
</html>