<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test@mobius-soft.org';

// Create Account
if ($mt7->password_check($email, 'aaa111')) {
    echo 'Right';
} else {
    echo 'Wrong';
}