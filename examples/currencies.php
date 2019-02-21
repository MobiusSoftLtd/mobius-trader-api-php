<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$currencies = $mt7->get_currencies();

die(var_dump($currencies));