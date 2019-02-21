<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$quotes = $mt7->get_quotes(array('BTCUSD', 'EURUSD'));

die(var_dump($quotes));