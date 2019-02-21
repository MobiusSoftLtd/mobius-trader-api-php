<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$currency_by_name = $mt7->get_currency('USD');
$currency_by_id = $mt7->get_currency(6);

die(var_dump(array(
    'by_name' => $currency_by_name,
    'by_id' => $currency_by_id,
)));