<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$symbols = $mt7->get_symbols();

die(var_dump($symbols));