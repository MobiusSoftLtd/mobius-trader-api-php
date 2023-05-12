<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$client_id = 1;

$response = $mt7->call('ClientGet', array(
    'Id' => $client_id
));

if ($response['status'] === MobiusTrader::STATUS_OK)
{
    $client = $response['data'];
    die(var_dump($client));
}
else
{
    echo 'Error: ' . $response['data'];
}
