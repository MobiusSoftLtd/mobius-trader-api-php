<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);


$login = 'test@example.com';
$password = 'testPassword';
$client_ip = '10.11.12.13';
$user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36';


$response = $mt7->call('ApiTraderAuth', array(
          'Login' => $login,
          'Password' => $password,
          'IP' => $client_ip,
          'UserAgent' => $user_agent,
        ));

if ($response['status'] === MobiusTrader::STATUS_OK)
{
    var_dump($response['data']);
    $jwt = $response['data']['JWT'];
    $accountId = $response['data']['AccountId'];
    echo 'JWT: ' . $jwt . ' ' . 'AccountId: ' . $accountId;
} 
else
{
    echo 'Error: ' . $response['data'];
}