
<div class="card">
    <div class="card-header">Description</div>
    <div class="card-body">
        <p>Command injection is an attack where the goal is execution of arbitrary commands on the host OS via a vulnerable application. It is possible when an application passes unsafe user-supplied data to a system shell.</p>
        <p>This differs from Code Injection: in Command Injection the attacker extends what the application already does (runs system commands), without injecting new code.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Vulnerable Code Example</div>
    <pre><code class="language-php">@verbatim&lt;?php
public function getTokenByConfirmationCode(int $accountId, string $confirmationCode, string $appClientId, string $appSecret): State
{
    $request = sprintf(
        'curl https://test.com/oauth/v2/token -u "%s:%s" -d grant_type=authorization_code -d code="%s"',
        $appClientId,
        $appSecret,
        $confirmationCode
    );

    $response = shell_exec($request);
    if (!$response) {
        return new State(false);
    }

    $responseData = json_decode($response, true);
    if (!array_key_exists('access_token', $responseData)) {
        return new State(false, 'Test request error', $responseData);
    }

    return new State(true, '', $responseData);
}@endverbatim</code></pre>
</div>
