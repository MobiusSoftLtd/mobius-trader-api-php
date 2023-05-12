<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$client_id = 1;
$client_ip = '10.11.12.13';
$user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36';

$response = $mt7->call('GetJWT', array(
    'ClientId' => $client_id,
    'IP' => $client_ip,
    'UserAgent' => $user_agent,
));

if ($response['status'] === MobiusTrader::STATUS_OK)
{
    $jwt = $response['data'];
    echo 'JWT: ' . $jwt;
}
else
{
    echo 'Error: ' . $response['data'];
}
