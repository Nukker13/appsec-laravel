
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>DOM-based XSS vulnerabilities arise when JavaScript takes data from an attacker-controllable source, such as the URL, and passes it to a sink that supports dynamic code execution — like <code>eval()</code> or <code>innerHTML</code>. This enables attackers to execute malicious JavaScript and typically allows them to hijack other users' accounts.</p>
        <p>To deliver a DOM-based XSS attack, place data into a source so that it propagates to a sink and causes execution of arbitrary JavaScript. The most common source is the URL, typically accessed via <code>window.location</code>. An attacker can construct a link to send a victim to a vulnerable page with a payload in the query string or fragment. In this lab, an admin bot checks submitted reports every 15 seconds.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-javascript">@verbatim// Source: the URL fragment. Sink: innerHTML (no sanitisation)
const raw = decodeURIComponent(location.hash.slice(1));
viewer.innerHTML = raw || defaultHtml;@endverbatim</code></pre>
</div>
