<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$account_number_id = 1;

$money_info = $mt7->call('MoneyInfo', array(
    'AccountNumbers' => array($account_number_id),
    'Currency' => $currency,
));

die(var_dump($money_info));