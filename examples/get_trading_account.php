<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$trading_account_id = 1;

$response = $mt7->call('TradingAccountGet', array(
    'Id' => $trading_account_id
));

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $trading_account = $response['data'];
    die(var_dump($trading_account));
} else {
    echo 'Error: ' . $response['data'];
}
