<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test@mobius-soft.org';
$password = 'aaa111';

if ($mt7->password_check($email, $password)) 
{
    echo 'Right';
} 
else
{
    echo 'Wrong';
}