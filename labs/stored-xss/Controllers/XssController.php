<?php

declare(strict_types=1);

namespace Stored\Controllers;

use Stored\Helpers\SessionManager;
use Stored\Models\XssModel;

class XssController
{
    private XssModel $model;
    private SessionManager $session;

    public function __construct()
    {
        $this->model = new XssModel();
        $this->session = new SessionManager();
    }

    public function storeMessage(): void
    {
        $message = $_POST["message"] ?? "";
        $userId = $this->session->get('id');
        $email = $this->session->get('email');
        $this->model->saveMessage($userId, $email, $message);
        header("Location: /");
        exit;
    }

    public function safeView(): void
    {
        $rows = $this->model->getMessages();
        include __DIR__ . "/../Views/xss_student.php";
    }

    public function adminView(): void
    {
        $this->session->requireRole('admin');
        $rows = $this->model->getMessages();
        include __DIR__ . "/../Views/xss_admin.php";
    }

    public function flagView(): void
    {
        $this->session->requireRole('admin');
        include __DIR__ . '/../Views/xss_flag.php';
    }

    public function roleChange(): void
    {
        $roles =['user', 'admin'];

        //POST method check
//        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
//            http_response_code(405);
//            echo 'only POST methods are allowed';
//            return;
//        }

        //admin role check
        $this->session->requireRole('admin');

        //getting data from POST
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = $_POST['role'];

        //data check
        if ($userId < 0 || !in_array($role, $roles)) {
            http_response_code(400);
            echo 'invalid user or role';
            return;
        }

        //calling model method
        try {
            $this->model->roleChange($userId, $role);
            http_response_code(200);
            echo 'Role updated';
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo $e->getMessage();
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Error occurred while updating role" . $e->getMessage();
        }

    }

    public function adminLogin()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}

        if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
            header("Location: /");
            exit;
        }
        else {
            $this->session->adminLogin();
            print_r($_SESSION);
        }

    }

}