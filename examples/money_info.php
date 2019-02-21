<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$account_number_id = 1;

$money_info = $mt7->money_info(array($account_number_id));

die(var_dump($money_info));