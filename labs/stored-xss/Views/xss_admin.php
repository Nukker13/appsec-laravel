<?php

use Doctrine\DBAL\DriverManager;
use Stored\Helpers\Config;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Helpers/db.php';

//echo "<pre>";
//var_dump($_SESSION);


$db = DriverManager::getConnection(Config::db());

$messages = $db->fetchAllAssociative('SELECT * FROM messages');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stored XSS Lab - Messages</title>
</head>
<body>
<h1>Messages</h1>

<!-- Safe messages -->
<?php if (!empty($messages)): ?>
    <ul>
        <?php foreach ($messages as $msg): ?>
            <li>
                <strong><?php echo $msg['email']; ?>:</strong>
                <?php echo $msg['content']; ?>
                <em>(<?php echo $msg['created_at']; ?>)</em>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No messages yet.</p>
<?php endif; ?>

</body>
</html>
