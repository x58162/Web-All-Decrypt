<?php

require __DIR__ . '/vendor/autoload.php';

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();

echo "PUBLIC KEY\n";
echo $keys['publicKey'] . "\n\n";

echo "PRIVATE KEY\n";
echo $keys['privateKey'] . "\n";