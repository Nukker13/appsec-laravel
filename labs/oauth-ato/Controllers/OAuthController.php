<?php

declare(strict_types=1);

namespace OAUTH\Controllers;

use OAUTH\Helpers\SessionManager;
use OAUTH\Models\OAuthModel;

class OAuthController
{
    private const GOOGLE_USERS = ['student@gmail.com' => 'google123', 'admin@gmail.com' => 'admin_google_secret'];
    private const APPLE_USERS  = ['student@icloud.com' => 'apple123', 'admin@icloud.com' => 'admin_apple_secret'];

    private OAuthModel $model;
    private SessionManager $session;

    public function __construct()
    {
        $this->model   = new OAuthModel();
        $this->session = new SessionManager();
    }

    /**
     * POST /oauth/start — begin provider linking (for logged-in users)
     */
    public function start(): void
    {
        $provider = $_POST['provider'] ?? 'google';
        $state    = bin2hex(random_bytes(16));

        file_put_contents('/tmp/oauth_state', $state);

        $query = http_build_query([
            'state'    => $state,
            'provider' => $provider,
            'mode'     => 'link',
        ]);
        header("Location: /oauth/provider?$query");
        exit;
    }

    /**
     * POST /oauth/social-start — begin provider login (for guests)
     */
    public function socialStart(): void
    {
        $provider = $_POST['provider'] ?? 'google';
        $state    = bin2hex(random_bytes(16));

        file_put_contents('/tmp/oauth_state', $state);

        $query = http_build_query([
            'state'    => $state,
            'provider' => $provider,
            'mode'     => 'login',
        ]);
        header("Location: /oauth/provider?$query");
        exit;
    }

    /**
     * GET /oauth/provider — show fake provider login page
     */
    public function providerView(): void
    {
        include __DIR__ . '/../Views/oauth_provider.php';
    }

    /**
     * POST /oauth/provider — validate fake provider credentials, show callback URL
     */
    public function providerSubmit(): void
    {
        $email    = $_POST['email']    ?? '';
        $password = $_POST['password'] ?? '';
        $provider = $_POST['provider'] ?? 'google';
        $state    = $_POST['state']    ?? '';
        $mode     = $_POST['mode']     ?? 'login';

        $users = match ($provider) {
            'google' => self::GOOGLE_USERS,
            'apple'  => self::APPLE_USERS,
            default  => [],
        };

        if (!isset($users[$email]) || $users[$email] !== $password) {
            $error = 'Invalid credentials. Please try again.';
            include __DIR__ . '/../Views/oauth_provider.php';
            return;
        }

        $code = base64_encode($email);
        $callbackUrl = '/oauth/callback?' . http_build_query([
            'code'     => $code,
            'state'    => $state,
            'mode'     => $mode,
            'provider' => $provider,
        ]);

        header("Location: $callbackUrl");
        exit;
    }

    /**
     * GET /oauth/callback — process OAuth callback
     */
    public function callback(): void
    {
        $state    = $_GET['state']    ?? '';
        $code     = $_GET['code']     ?? '';
        $provider = $_GET['provider'] ?? '';
        $mode     = $_GET['mode']     ?? 'login';

        $savedState = @file_get_contents('/tmp/oauth_state');

        if ($savedState === false || $state !== $savedState) {
            http_response_code(400);
            echo 'Invalid or expired state parameter.';
            return;
        }

        // VULNERABILITY: state file is NOT cleared after use
        // file_put_contents('/tmp/oauth_state', ''); // <- intentionally omitted

        $email = base64_decode($code);

        if ($mode === 'link') {
            $userId = (int)($_SESSION['id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                echo 'You must be logged in to link a provider.';
                return;
            }
            $this->model->linkProvider($userId, $provider, $email);
            header('Location: /');
            exit;
        }

        // mode = login: find account by provider identity, regardless of current session
        $user = $this->model->findByProvider($provider, $email);
        if ($user) {
            $this->session->loginUser($user);
            header('Location: /');
            exit;
        }

        http_response_code(401);
        echo 'No account linked to this identity.';
    }

    /**
     * GET /flag — show flag (admin only)
     */
    public function flagView(): void
    {
        $this->session->requireRole('admin');
        include __DIR__ . '/../Views/flag.php';
    }

    /**
     * GET /form — report form
     */
    public function reportView(): void
    {
        include __DIR__ . '/../Views/report_form.php';
    }

    /**
     * POST /report — save reported URL
     */
    public function reportLink(): void
    {
        $url = $_POST['url'] ?? '';
        $this->model->saveLink($url);
        header('Location: /form');
        exit;
    }

    /**
     * GET /next-report — return next queued URL (for bot)
     */
    public function nextReport(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $url = $this->model->getLink();
        echo $url ?? '';
    }

    /**
     * GET /secret — log in as admin (for bot)
     */
    public function adminLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
            header('Location: /');
            exit;
        }

        $this->session->adminLogin();
        print_r($_SESSION);
    }

    /**
     * POST /reset — unlink all providers and clear state (restart)
     */
    public function reset(): void
    {
        $userId = (int)($_SESSION['id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            echo 'Not logged in.';
            return;
        }
        $this->model->resetProviders($userId);
        file_put_contents('/tmp/oauth_state', '');
        header('Location: /');
        exit;
    }

    /**
     * POST /logout — destroy session
     */
    public function logout(): void
    {
        $this->session->logout();
        header('Location: /');
        exit;
    }
}
