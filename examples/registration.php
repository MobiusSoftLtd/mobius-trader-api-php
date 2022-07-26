<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test4@mobius-soft.org';
$name = 'Test';
$lang = 'en';

// Create Account
$response = $mt7->call('AccountCreate', array(
     'Name' => $name,
    'Email' => $email,
    'Lang' => $lang,
));

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $account = $response['data'];
    $account_id = (int)$account['Id'];

    // Set account password
    $mt7->call('PasswordSet', array(
        'AccountId' => $account_id,
        'Login' => $email,
        'Password' => 'aaa111',
        'SessionType' => MobiusTrader::SESSION_TYPE_TRADER,
    ));
    
    $leverage = 100;
    $settings_template = 'USD';
    $display_name = 'Dollar';
    $tags =  array('USD');

    $response = $mt7->call('AccountNumberCreate', array(
        'AccountId' => (int)$account_id,
        'Leverage' => (int)$leverage,
        'SettingsTemplate' => $settings_template,
        'DisplayName' => $display_name,
        'Tags' => $tags,
        'Type' => MobiusTrader::ACCOUNT_NUMBER_TYPE_REAL,
    ));

    if ($response['status'] === MobiusTrader::STATUS_OK) {
        $account_number = $response['data'];
        $account_number_id = (int)$account_number['Id'];

        $mt7->call('BalanceAdd', array(
            'AccountNumberId' => $account_number_id,
            'Amount' => $mt7->deposit_to_int('USD', 100),
            'Comment' => 'Test',
        ));

        die(var_dump($account, $account_number));
    }
}
