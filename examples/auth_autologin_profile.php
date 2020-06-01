<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$profile_url = 'https://my.example.com/';
$account_id = 123;
$client_ip = '10.11.12.13';
$user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36';

$response = $mt7->getJWT($account_id, $client_ip, $user_agent);

if ($response['status'] === MobiusTrader::STATUS_OK)
{
    $jwt = $response['data'];
    
    echo '<a href="' . $profile_url . '?jwt=' . $jwt . '">Login to profile</a>';
} 
else 
{
    echo 'Error: ' . $response['data'];
}