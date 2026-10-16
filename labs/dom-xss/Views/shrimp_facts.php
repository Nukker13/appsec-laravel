<?php //var_dump($_SESSION); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shrimp Facts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #fafafa;
            color: #333;
        }
        h1 {
            color: #444;
        }
        img {
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
            margin-bottom: 20px;
        }
        .fact-box ul {
            margin: 0;
            padding-left: 20px;
            line-height: 1.8;
        }
        .controls {
            margin-top: 10px;
        }
        .btn {
            margin: 0 6px 6px 0;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f5f5f5;
            cursor: pointer;
        }
        .btn.primary {
            background: #2563eb;
            color: #fff;
            border-color: #1e4db7;
        }
    </style>
</head>
<body>
<h1>🦐 Fun facts about shrimp</h1>

<!-- блок с картинкой (DOM XSS рендер) -->
<div class="fact-box">
    <div id="shrimp-view">
        <!-- дефолтная картинка, если хэша нет -->
        <img src="/img/shrimp1.png" alt="Shrimp 1">
    </div>

    <div class="controls">
        <button class="btn" type="button" onclick="setPicture('1')">Shrimp #1</button>
        <button class="btn" type="button" onclick="setPicture('2')">Shrimp #2</button>
        <button class="btn" type="button" onclick="setPicture('3')">Shrimp #3</button>
    </div>
</div>

<!-- блок с фактами так же, как на кошачьей странице -->
<div class="fact-box">
    <ul>
        <li>There are over <strong>2,000 species</strong> of shrimp around the world.</li>
        <li>Shrimp can swim backwards by rapidly flicking their tail.</li>
        <li>A shrimp’s heart is located in its <strong>head</strong>, not its chest.</li>
        <li>Some shrimp can live more than <strong>6 years</strong> depending on habitat.</li>
        <li>The smallest shrimp are only a few millimeters, while the largest reach over <strong>30 cm</strong>.</li>
        <li>Mantis shrimp can punch with the speed of a <strong>bullet</strong> leaving a gun.</li>
    </ul>
</div>

<script>
    const defaultHtml = '<img src="/views/img/shrimp1.png" alt="Shrimp 1">';

    function renderFromHash() {
        const viewer = document.getElementById('shrimp-view');

        if (!location.hash || location.hash.length <= 1) {
            viewer.innerHTML = defaultHtml;
            return;
        }

        const raw = decodeURIComponent(location.hash.slice(1));

        viewer.innerHTML = raw || defaultHtml;
    }

    function setPicture(num) {
        const html = `<img src="/Views/img/shrimp${num}.png" alt="Shrimp ${num}">`;
        location.hash = encodeURIComponent(html);
        renderFromHash();
    }

    // Первый рендер при загрузке
    renderFromHash();
</script>
</body>
</html>