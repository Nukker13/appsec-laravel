<?php

use Doctrine\DBAL\DriverManager;
use Stored\Helpers\Config;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Helpers/db.php';

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
<?php

//xdebug_info();

if (!empty($messages)): ?>
    <ul>
        <?php foreach ($messages as $msg): ?>
            <li>
                <?php echo htmlspecialchars($msg['user_id'], ENT_QUOTES, 'UTF-8'); ?>
                <strong><?php echo htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                <?php echo htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8'); ?>
                <em>(<?php echo htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8'); ?>)</em>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No messages yet.</p>
<?php endif; ?>

</body>
</html>
