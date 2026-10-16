<?php
declare(strict_types=1);

session_start();

class BookingOptions {
    public string $roomType;
    public string $checkIn;
    public string $checkOut;
    public int $guests;
}

class Invoice {
    public string $path;
    public string $content;

    public function generate(BookingOptions $booking, string $name, string $email): void {
        $this->content = implode("\n", [
            '=== Shrimp Hotel Booking Invoice ===',
            'Room     : ' . $booking->roomType,
            'Check-in : ' . $booking->checkIn,
            'Check-out: ' . $booking->checkOut,
            'Guests   : ' . $booking->guests,
            'Name     : ' . $name,
            'Email    : ' . $email,
            'Date     : ' . date('Y-m-d H:i:s'),
        ]);
    }

    public function __destruct() {
        file_put_contents($this->path, $this->content);
    }
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if ($uri === '/' && $method === 'GET') {
    include __DIR__ . '/views/home.php';
    exit;
}

if ($uri === '/book' && $method === 'GET') {
    include __DIR__ . '/views/step1.php';
    exit;
}

if ($uri === '/book' && $method === 'POST') {
    $booking = new BookingOptions();
    $booking->roomType = $_POST['room_type'] ?? 'Standard Shrimp';
    $booking->checkIn  = $_POST['check_in']  ?? '';
    $booking->checkOut = $_POST['check_out'] ?? '';
    $booking->guests   = (int)($_POST['guests'] ?? 1);

    $serialized = serialize($booking);
    setcookie('booking', urlencode($serialized), [
        'path'     => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);

    header('Location: /book/details');
    exit;
}

if ($uri === '/book/details' && $method === 'GET') {
    include __DIR__ . '/views/step2.php';
    exit;
}

if ($uri === '/book/details' && $method === 'POST') {
    $_SESSION['guest_first']    = $_POST['first_name']       ?? '';
    $_SESSION['guest_last']     = $_POST['last_name']        ?? '';
    $_SESSION['guest_email']    = $_POST['email']            ?? '';
    $_SESSION['guest_requests'] = $_POST['special_requests'] ?? '';

    header('Location: /book/summary');
    exit;
}

if ($uri === '/book/summary' && $method === 'GET') {
    $booking = null;
    if (!empty($_COOKIE['booking'])) {
        $booking = unserialize(urldecode($_COOKIE['booking']));
    }

    if ($booking instanceof BookingOptions) {
        $invoice = new Invoice();
        $invoice->path = __DIR__ . '/storage/invoices/' . date('Ymd_His') . '_' . substr(session_id(), 0, 8) . '.txt';
        $invoice->generate(
            $booking,
            trim(($_SESSION['guest_first'] ?? '') . ' ' . ($_SESSION['guest_last'] ?? '')),
            $_SESSION['guest_email'] ?? 'unknown'
        );
    }

    include __DIR__ . '/views/step3.php';
    exit;
}

http_response_code(404);
echo '404 Not Found';
