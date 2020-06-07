<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test2@mobius-soft.org';
$current_password = 'test222';
$new_password = 'test111';

try {
    if (!$mt7->password_check($email, $current_password)) 
    {
        throw new Exception('InvalidPassword');
    }

    $account_id = $mt7->search('Id')
        ->from(MobiusTrader::from_accounts())
        ->where('Email', '=', $email)
        ->limit(1)
        ->execute()
        ->get('Id');

    if (!$account_id) 
    {
        throw new Exception('AccountNotFound');
    }

    $mt7->password_set($account_id, $email, $new_password);

    echo 'Password changed';
} catch (Exception $e) {
    echo 'err: ' . $e->getMessage();
}
