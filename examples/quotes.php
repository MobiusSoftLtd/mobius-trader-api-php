<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$symbols = array('BTCUSD', 'EURUSD');

$quotes = $mt7->call('SymbolQuotesGet', array(
    'Symbols' => $symbols
));

die(var_dump($quotes));