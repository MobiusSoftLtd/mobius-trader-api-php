<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$account_id = 1;
$response = $mt7->get_account($account_id);

if ($response['status'] === MobiusTrader::STATUS_OK) {
    $account = $response['data'];
    die(var_dump($account));
} else {
    echo 'Error: ' . $response['data'];
}
