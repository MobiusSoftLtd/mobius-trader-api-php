<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$email = 'test4@mobius-soft.org';
$name = 'Test';
$lang = 'en';

// Create Client
$response = $mt7->call('ClientCreate', array(
     'Name' => $name,
    'Email' => $email,
    'Lang' => $lang,
));

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $client = $response['data'];
    $client_id = (int)$client['Id'];

    // Set account password
    $mt7->call('PasswordSet', array(
        'ClientId' => $client_id,
        'Login' => $email,
        'Password' => 'aaa111',
        'SessionType' => MobiusTrader::SESSION_TYPE_TRADER,
    ));

    $leverage = 100;
    $settings_template = 'USD';
    $display_name = 'Dollar';
    $tags =  array('USD');

    $response = $mt7->call('TradingAccountCreate', array(
        'ClientId' => (int)$client_id,
        'Leverage' => (int)$leverage,
        'SettingsTemplate' => $settings_template,
        'DisplayName' => $display_name,
        'Tags' => $tags,
        'Type' => MobiusTrader::ACCOUNT_NUMBER_TYPE_REAL,
    ));

    if ($response['status'] === MobiusTrader::STATUS_OK) {
        $trading_account = $response['data'];
        $trading_account_id = (int)$trading_account['Id'];

        $mt7->call('BalanceAdd', array(
            'TradingAccountId' => $trading_account_id,
            'Amount' => $mt7->deposit_to_int('USD', 100),
            'Comment' => 'Test',
        ));

        die(var_dump($client, $trading_account));
    }
}
