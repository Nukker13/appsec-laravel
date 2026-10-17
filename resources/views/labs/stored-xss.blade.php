
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Stored XSS (also called Persistent or Type-II XSS) occurs when the injected script is permanently stored on the target server — in a database, message forum, comment field, or visitor log. The victim retrieves the malicious script from the server whenever they request the stored information.</p>
        <p>Unlike reflected XSS, stored XSS does not require tricking the victim into clicking a crafted link — anyone who views the page containing the stored payload is affected. In this lab, an admin bot checks the messages page every 15 seconds.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-php">@verbatim&lt;?php
// saved as-is by the message handler ...
$db-&gt;insert('messages', ['content' =&gt; $message /* ... */]);

// ... and echoed unescaped on the admin page
foreach ($messages as $msg) {
    echo $msg['content'];
}@endverbatim</code></pre>
</div>
