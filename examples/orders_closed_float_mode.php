<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$orders = $mt7->search_array(array(
    'TradingAccounts.CurrencyId',
    'Ticket',
    'OpenTime',
    'OpenTime',
    'TradeCmd',
    'Volume',
    'OpenPrice',
    'ClosePrice',
    'SymbolId',
    'Profit',
    'Commission',
    'Swap',
))
    ->from(MobiusTrader::from_orders())
    ->where('TradingAccountId', '=', 1)
    ->and_where('CloseTime', '>', 0)
    ->and_where('TradeCmd', 'IN', array(
        MobiusTrader::ORDER_CMD_BUY,
        MobiusTrader::ORDER_CMD_SELL,
    ))
    ->limit(10)
    ->offset(0)
    ->order_by('Ticket', 'DESC')
    ->float_mode(true)
    ->execute()
    ->as_array();

die(var_dump($orders));
