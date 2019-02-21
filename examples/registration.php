<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test4@mobius-soft.org';

// Create Account
$response = $mt7->create_account($email, 'Test');

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $account = $response['data'];
    $account_id = $account['Id'];

    // Set account password
    $mt7->password_set($account_id, $email, 'aaa111');

    $response = $mt7->create_account_number(
        MobiusTrader::ACCOUNT_NUMBER_TYPE_REAL,
        $account_id,
        100,
        'USD',
        'Dollar',
        array('USD')
    );

    if ($response['status'] === MobiusTrader::STATUS_OK) {
        $account_number = $response['data'];
        $account_number_id = $account_number['Id'];

        $mt7->balance_add($account_number_id, $mt7->deposit_to_int('USD', 100), 'Test');

        die(var_dump($account, $account_number));
    }
}
