<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test@mobius-soft.org';
$password = 'aaa111';

$result = $mt7->call('PasswordCheck', array(
    'Login' => $email,
    'Password' => $password,
    'SessionType' => MobiusTrader::SESSION_TYPE_TRADER,
));

if ($result['status'] == MobiusTrader::STATUS_OK && $result['data'] == true) 
{
    echo 'Right';
} 
else
{
    echo 'Wrong';
}