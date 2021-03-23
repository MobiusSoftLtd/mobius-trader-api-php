<?php

include_once 'MobiusTrader/Client.php';
include_once 'MobiusTrader/Cache.php';
include_once 'MobiusTrader/Search.php';

class MobiusTrader
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    const ORDER_CMD_BUY = 0;
    const ORDER_CMD_SELL = 1;
    const ORDER_CMD_BUY_LIMIT = 2;
    const ORDER_CMD_SELL_LIMIT = 3;
    const ORDER_CMD_BUY_STOP = 4;
    const ORDER_CMD_SELL_STOP = 5;
    const ORDER_CMD_BALANCE = 6;
    const ORDER_CMD_CREDIT = 7;

    const ACCOUNT_NUMBER_TYPE_TEST = 0;
    const ACCOUNT_NUMBER_TYPE_REAL = 1;
    const ACCOUNT_NUMBER_TYPE_DEMO = 2;

    const SESSION_TYPE = 0;

    private $_config;
    private $_client;
    private $_cache;

    public function __construct($config = array())
    {
        $this->_config = $config;
        $this->_cache = new MobiusTrader_Cache($config);
        $this->_client = new MobiusTrader_Client($config);
    }

    public function get_symbols()
    {
        $cmd = 'SymbolsGet';
        $data = $this->_cache->get($cmd);
        if (!$data) {
            $result = $this->_client->call($cmd);

            if ($result['status'] === MobiusTrader::STATUS_OK) {
                $data = $result['data'];
                $this->_cache->set($cmd, $data);
            }
        }

        return $data;
    }

    public function get_symbol($symbol)
    {
        $symbols = $this->get_symbols();
        $key = array_search($symbol, array_column($symbols, is_numeric($symbol) ? 'Id' : 'Name'));
        return $key >= 0 ? $symbols[$key] : null;
    }

    public function price_from_int($symbol, $price)
    {
        $symbol_info = $this->get_symbol($symbol);

        return (double)($price * pow(10, -$symbol_info['FractionalDigits']));
    }

    public function price_to_int($symbol, $price)
    {
        $symbol_info = $this->get_symbol($symbol);

        return (int)($price * pow(10, $symbol_info['FractionalDigits']));
    }

    public function volume_from_int($symbol, $volume)
    {
        $symbol_info = $this->get_symbol($symbol);
        $margin_currency = $this->get_currency($symbol_info['MarginCurrencyId']);
        return (double)($volume * pow(10, -$margin_currency['VolumeFractionalDigits']));
    }

    public function volume_to_int($symbol, $volume)
    {
        $symbol_info = $this->get_symbol($symbol);
        $margin_currency = $this->get_currency($symbol_info['MarginCurrencyId']);
        return (int)($volume * pow(10, $margin_currency['VolumeFractionalDigits']));
    }

    public function get_currencies()
    {
        $cmd = 'CurrenciesGet';
        $data = $this->_cache->get($cmd);
        if (!$data) {
            $result = $this->_client->call($cmd);

            if ($result['status'] === MobiusTrader::STATUS_OK) {
                $data = $result['data'];
                $this->_cache->set($cmd, $data);
            }
        }
        return $data;
    }

    public function get_currency($currency)
    {
        $currencies = $this->get_currencies();
        $key = array_search($currency, array_column($currencies, is_numeric($currency) ? 'Id' : 'Name'));
        return $key ? $currencies[$key] : null;
    }

    public function get_quotes(array $symbols)
    {
        $result = $this->_client->call('SymbolQuotesGet', array(
            'Symbols' => $symbols

        ));
        return $result['data'];
    }

    public function get_account($account_id)
    {
        return $this->_client->call('AccountGet', array(
            'Id' => $account_id
        ));
    }

    public function get_account_number($account_number_id)
    {
        return $this->_client->call('AccountNumberGet', array(
            'Id' => $account_number_id
        ));
    }

    public function get_account_numbers($account_id)
    {
        $result = $this->_client->call('AccountNumbersGet', array(
            'Id' => $account_id
        ));
        return $result['data'];
    }

    public function get_account_balance($account_id, $currency = 'USD')
    {
        $account_numbers = array();
        $account_numbers_all = $this->get_account_numbers($account_id);
        foreach ($account_numbers_all as $account_number) {
            if ($account_number['Type'] === self::ACCOUNT_NUMBER_TYPE_REAL) {
                $account_numbers[] = $account_number['Id'];
            }
        }

        $money_info = $this->money_info($account_numbers, $currency);

        $balance = 0;
        foreach ($account_numbers as $account_number_id) {
            $money = $money_info[$account_number_id];
            $balance += $money['Free'] - $money['Credit'];
        }
        return $balance;
    }

    public function create_account($email,
                                   $name,
                                   $agent_account = null,
                                   $country = '',
                                   $city = '',
                                   $address = '',
                                   $phone = '',
                                   $zip_code = '',
                                   $state = '',
                                   $comment = '')
    {
        $data = array(
            'Name' => $name,
            'Email' => $email,
            'AgentAccount' => $agent_account,
            'Country' => $country,
            'City' => $city,
            'Phone' => $phone,
            'State' => $state,
            'ZipCode' => $zip_code,
            'Address' => $address,
            'Comment' => $comment,
        );

        return $this->_client->call('AccountCreate', $data);
    }

    public function create_account_number($type, $account_id, $leverage, $settings_template, $display_name, $tags = array())
    {
        $data = array(
            'AccountId' => (int)$account_id,
            'Leverage' => (int)$leverage,
            'SettingsTemplate' => $settings_template,
            'DisplayName' => $display_name,
            'Tags' => $tags,
            'Type' => $type,
        );

        return $this->_client->call('AccountNumberCreate', $data);
    }

    public function password_set($account_id, $login, $password)
    {
        $result = $this->_client->call('PasswordSet', array(
            'AccountId' => (int)$account_id,
            'Login' => $login,
            'Password' => $password,
            'SessionType' => self::SESSION_TYPE,
        ));

        return $result;
    }

    public function password_check($login, $password)
    {
        $result = $this->_client->call('PasswordCheck', array(
            'Login' => $login,
            'Password' => $password,
            'SessionType' => self::SESSION_TYPE,
        ));

        return $result['status'] == self::STATUS_OK && $result['data'] == true;
    }

    public function deposit_to_int($currency, $amount)
    {
        $currency_info = $this->get_currency($currency);
        return (int)($amount * pow(10, $currency_info['DepositFractionalDigits']));
    }

    public function deposit_from_int($currency, $amount)
    {
        $currency_info = $this->get_currency($currency);
        return (double)($amount * pow(10, -$currency_info['DepositFractionalDigits']));
    }

    public function funds_deposit($currency, $account_number_id, $amount, $pay_system_code, $purse = '')
    {
        $comment = trim(implode(' ', ['DP', $pay_system_code, $this->deposit_from_int($currency, $amount), $purse]));
        return $this->balance_add($account_number_id, $amount, $comment);
    }

    public function funds_withdraw($currency, $account_number_id, $amount, $pay_system_code, $purse = '')
    {
        $money = $this->money_info($account_number_id);

        if ($money['Free'] - $money['Credit'] < $amount) {
            throw new Exception('NotEnoughMoney');
        }

        $comment = trim(implode(' ', ['WD', $pay_system_code, $this->deposit_from_int($currency, $amount), $purse]));

        return $this->balance_add($account_number_id, $amount, $comment);
    }

    public function money_info($account_numbers, $currency = '')
    {
        $result = $this->_client->call('MoneyInfo', array(
            'AccountNumbers' => (array)$account_numbers,
            'Currency' => $currency,
        ));

        return is_numeric($account_numbers) ? $result['data'][$account_numbers] : $result['data'];
    }

    public function balance_add($account_number_id, $amount, $comment)
    {
        $result = $this->_client->call('BalanceAdd', array(
            'AccountNumberId' => $account_number_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    public function bonus_add($account_number_id, $amount, $comment)
    {
        $result = $this->_client->call('BonusAdd', array(
            'AccountNumberId' => $account_number_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    public function credit_add($account_number_id, $amount, $comment)
    {
        $result = $this->_client->call('CreditAdd', array(
            'AccountNumberId' => $account_number_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    public function order_open($account_number_id, $symbol_id, $volume, $trade_cmd, $price = 0, $sl = 0, $tp = 0, $comment = '')
    {
        return $this->_client->call('AdminOpenOrder', array(
            'AccountNumberId' => $account_number_id,
            'SymbolId' => $symbol_id,
            'Volume' => $volume,
            'TradeCmd' => $trade_cmd,
            'Price' => $price,
            'Sl' => $sl,
            'Tp' => $tp,
            'Comment' => $comment,
        ));
    }

    /**
     * @param $ticket
     * @param array $params Volume, Sl, Tp, OpenPrice, ClosePrice, Comment, UserData
     *
     * @return array
     */
    public function order_modify($ticket, $params = array())
    {
        return $this->_client->call('AdminModifyOrder', array_merge(array(
            'Ticket' => $ticket,
        ), $params));
    }

    /**
     * @param $ticket
     * @param array $params Volume, Price
     * @return array
     */
    public function order_close($ticket, $params = array())
    {
        return $this->_client->call('AdminCloseOrder', array_merge(array(
            'Ticket' => $ticket,
        ), $params));
    }

    /**
     * @param $ticket
     * @return array
     */
    public function order_delete($ticket)
    {
        return $this->_client->call('AdminDeleteOrder', array(
            'Ticket' => $ticket,
        ));
    }

    public function get_jwt($account_id, $ip, $user_agent)
    {
        return $this->_client->call('GetJWT', array(
            'AccountId' => $account_id,
            'IP' => $ip,
            'UserAgent' => $user_agent,
        ));
    }

    public function trader_auth($login, $password, $ip, $user_gent) 
    {
        return $this->_client->call('ApiTraderAuth', array(
          'Login' => $login,
          'Password' => $password,
          'IP' => $ip,
          'UserAgent' => $user_gent,
        ));
    }

    public function call($method, array $params = NULL) {
        return $this->_client->call($method, $params);
    }

    public function search($columns = NULL)
    {
        return new MobiusTrader_Search($this->_client, func_get_args());
    }

    public function search_array(array $columns)
    {
        return new MobiusTrader_Search($this->_client, $columns);
    }

    public static function expr($string)
    {
        return MobiusTrader_Search::expr($string);
    }

    public static function from_orders()
    {
        return 'Orders';
    }

    public static function from_accounts()
    {
        return 'Accounts';
    }

    public static function from_account_numbers()
    {
        return 'AccountNumbers';
    }

    private function post($url, $data = array())
    {
        $json_data = json_encode($data);

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => "Content-type: application/json\r\nContent-Length:" . strlen($json_data) . "\r\n",
                'content' => $json_data
            )
        );

        $context = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);

        return json_decode($content, true);
    }
}