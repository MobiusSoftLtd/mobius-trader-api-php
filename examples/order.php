<?php

require_once '../lib/MobiusTrader.php';
require_once './config.php';

$mt7 = new MobiusTrader($config);

$trading_account_id = 1;
$symbol_id = 1;

$response = $mt7->call('AdminOpenOrder', array(
    'TradingAccountId' => $trading_account_id,
    'SymbolId' => $symbol_id,
    'Volume' => $mt7->volume_to_int($symbol_id, 0.001),
    'TradeCmd' => MobiusTrader::ORDER_CMD_SELL,
));

if ($response['status'] === MobiusTrader::STATUS_OK)
{
    $order = $response['data'];
    $ticket = $order['Ticket'];

    $mt7->order_modify($ticket, array(
        'Comment' => 'Test ' . time(),
    ));

    $mt7->order_close($ticket);
//    $response = $mt7->order_delete($ticket); // For Limit and Stop

    die(var_dump($ticket));
}
