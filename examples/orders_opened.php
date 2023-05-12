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
    'SymbolId',
    'Profit',
    'Commission',
    'Swap',
))
    ->from(MobiusTrader::from_orders())
    ->where('TradingAccountId', '=', 1)
    ->and_where('CloseTime', '=', 0)
    ->and_where('SymbolId', '=', 7)
    ->and_where('TradeCmd', 'IN', array(
        MobiusTrader::ORDER_CMD_BUY,
        MobiusTrader::ORDER_CMD_SELL,
    ))
    ->limit(10)
    ->offset(0)
    ->order_by('Ticket', 'DESC')
    ->execute()
    ->as_array();

foreach ($orders as &$order)
{
    $symbol_id = $order['SymbolId'];
    $currency_id = $order['TradingAccounts.CurrencyId'];
    $order['OpenPrice'] = $mobius_trader->price_from_int($symbol_id, $order['OpenPrice']);
    $order['ClosePrice'] = $mobius_trader->price_from_int($symbol_id, $order['ClosePrice']);
    $order['Profit'] = $mobius_trader->deposit_from_int($currency_id, $order['Profit']);
    $order['Commission'] = $mobius_trader->deposit_from_int($currency_id, $order['Commission']);
}

die(var_dump($orders));
