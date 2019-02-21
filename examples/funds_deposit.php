<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$currency = 'USD';
$account_number_id = 1;
$amount = $mt7->deposit_to_int($currency, 1000);
$pay_system_code = 'YM';
$purse = '123456712345';

$ticket = $mt7->funds_deposit($currency, $account_number_id, $amount, $pay_system_code, $purse);

die(var_dump($ticket));