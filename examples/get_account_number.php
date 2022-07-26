<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$account_number_id = 1;

$response = $mt7->call('AccountNumberGet', array(
    'Id' => $account_number_id
));

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $account_number = $response['data'];
    die(var_dump($account_number));
} else {
    echo 'Error: ' . $response['data'];
}
