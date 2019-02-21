<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$currency = 'USD';
$account_number_id = 1;
$amount = $mt7->deposit_to_int($currency, 100);
$pay_system_code = 'YM';
$purse = '123456712345';

try {
    $ticket = $mt7->funds_withdraw($currency, $account_number_id, $amount, $pay_system_code, $purse);
    die(var_dump($ticket));
} catch (Exception $e) {
    echo $e->getMessage();
}

