<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test2@mobius-soft.org';
$current_password = 'test222';
$new_password = 'test111';

try {
    $result = $mt7->call('PasswordCheck', array(
            'Login' => $email,
            'Password' => $current_password,
            'SessionType' => MobiusTrader::SESSION_TYPE_TRADER,
        ));

    if ($result['status'] != MobiusTrader::STATUS_OK && $result['data'] == true)
    {
        throw new Exception('InvalidPassword');
    }

    $client_id = $mt7->search('Id')
        ->from(MobiusTrader::from_clients())
        ->where('Email', '=', $email)
        ->limit(1)
        ->execute()
        ->get('Id');

    if (!$client_id)
    {
        throw new Exception('ClientNotFound');
    }

    $mt7->call('PasswordSet', array(
        'ClientId' => (int)$client_id,
        'Login' => $email,
        'Password' => $new_password,
        'SessionType' => MobiusTrader::SESSION_TYPE_TRADER,
    ));

    echo 'Password changed';
} catch (Exception $e) {
    echo 'err: ' . $e->getMessage();
}
