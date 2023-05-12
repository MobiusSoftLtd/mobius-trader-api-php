<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$trading_account_id = 1;
$currency = 'USD';

$money_info = $mt7->call('MoneyInfo', array(
    'TradingAccounts' => array($trading_account_id),
    'Currency' => $currency,
));

die(var_dump($money_info));
