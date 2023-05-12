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

    const SESSION_TYPE_TRADER = 0;
    const SESSION_TYPE_WITHDRAW = 4;

    private $_config;
    private $_client;
    private $_cache;

    public function __construct($config = array())
    {
        $this->_config = $config;
        $this->_cache = new MobiusTrader_Cache($config);
        $this->_client = new MobiusTrader_Client($config);
    }

    public function is_float_mode()
    {
        return !empty($this->_config['float_mode']) ? $this->_config['float_mode'] === true : false;
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

    /**
     * @deprecated Use call() instead.
     */
    public function get_quotes(array $symbols)
    {
        $result = $this->_client->call('SymbolQuotesGet', array(
            'Symbols' => $symbols
        ));
        return $result['data'];
    }

    /**
     * @deprecated Use call() instead.
     */
    public function get_client($client_id)
    {
        return $this->_client->call('ClientGet', array(
            'Id' => $client_id
        ));
    }

    /**
     * @deprecated Use call() instead.
     */
    public function get_trading_account($trading_account_id)
    {
        return $this->_client->call('TradingAccountGet', array(
            'Id' => $trading_account_id
        ));
    }

    /**
     * @deprecated Use call() instead.
     */
    public function get_trading_accounts($client_id)
    {
        $result = $this->_client->call('TradingAccountsGet', array(
            'Id' => $client_id
        ));
        return $result['data'];
    }

    public function get_client_balance($client_id, $currency = 'USD')
    {
        $trading_accounts = array();
        $trading_accounts_all = $this->get_trading_accounts($client_id);
        foreach ($trading_accounts_all as $trading_account) {
            if ($trading_account['Type'] === self::ACCOUNT_NUMBER_TYPE_REAL) {
                $trading_accounts[] = $trading_account['Id'];
            }
        }

        $money_info = $this->money_info($trading_accounts, $currency);

        $balance = 0;
        foreach ($trading_accounts as $trading_account_id) {
            $money = $money_info[$trading_account_id];
            $balance += $money['Free'] - $money['Credit'];
        }
        return $balance;
    }

    /**
     * @deprecated Use call() instead.
     */
    public function create_client($email,
                                   $name,
                                   $agent_client = null,
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
            'AgentClient' => $agent_client,
            'Country' => $country,
            'City' => $city,
            'Phone' => $phone,
            'State' => $state,
            'ZipCode' => $zip_code,
            'Address' => $address,
            'Comment' => $comment,
        );

        return $this->_client->call('ClientCreate', $data);
    }

    /**
     * @deprecated Use call() instead.
     */
    public function create_trading_account($type, $client_id, $leverage, $settings_template, $display_name, $tags = array())
    {
        $data = array(
            'ClientId' => (int)$client_id,
            'Leverage' => (int)$leverage,
            'SettingsTemplate' => $settings_template,
            'DisplayName' => $display_name,
            'Tags' => $tags,
            'Type' => $type,
        );

        return $this->_client->call('TradingAccountCreate', $data);
    }

    /**
     * @deprecated Use call() instead.
     */
    public function password_set($client_id, $login, $password)
    {
        $result = $this->_client->call('PasswordSet', array(
            'ClientId' => (int)$client_id,
            'Login' => $login,
            'Password' => $password,
            'SessionType' => self::SESSION_TYPE,
        ));

        return $result;
    }

    /**
     * @deprecated Use call() instead.
     */
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

    public function funds_deposit($currency, $trading_account_id, $amount, $pay_system_code, $purse = '')
    {
        $comment = trim(implode(' ', array(
            'DP',
            $pay_system_code,
            $this->is_float_mode() ? $amount : $this->deposit_from_int($currency, $amount),
            $purse
        )));
        return $this->balance_add($trading_account_id, $amount, $comment);
    }

    public function funds_withdraw($currency, $trading_account_id, $amount, $pay_system_code, $purse = '')
    {
        $money = $this->money_info($trading_account_id);

        if ($money['Free'] - $money['Credit'] < $amount) {
            throw new Exception('NotEnoughMoney');
        }

        $comment = trim(implode(' ', array(
            'WD',
            $pay_system_code,
            $this->is_float_mode() ? $amount : $this->deposit_from_int($currency, $amount),
            $purse
        )));

        return $this->balance_add($trading_account_id, $amount, $comment);
    }

    public function money_info($trading_accounts, $currency = '')
    {
        $result = $this->_client->call('MoneyInfo', array(
            'TradingAccounts' => (array)$trading_accounts,
            'Currency' => $currency,
        ));

        return is_numeric($trading_accounts) ? $result['data'][$trading_accounts] : $result['data'];
    }

    /**
     * @deprecated Use call() instead.
     */
    public function balance_add($trading_account_id, $amount, $comment)
    {
        $result = $this->_client->call('BalanceAdd', array(
            'TradingAccountId' => $trading_account_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    /**
     * @deprecated Use call() instead.
     */
    public function bonus_add($trading_account_id, $amount, $comment)
    {
        $result = $this->_client->call('BonusAdd', array(
            'TradingAccountId' => $trading_account_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    /**
     * @deprecated Use call() instead.
     */
    public function credit_add($trading_account_id, $amount, $comment)
    {
        $result = $this->_client->call('CreditAdd', array(
            'TradingAccountId' => $trading_account_id,
            'Amount' => $amount,
            'Comment' => $comment,
        ));

        return $result['status'] == self::STATUS_OK ? $result['data']['Ticket'] : false;
    }

    /**
     * @deprecated Use call() instead.
     */
    public function order_open($trading_account_id, $symbol_id, $volume, $trade_cmd, $price = 0, $sl = 0, $tp = 0, $comment = '')
    {
        return $this->_client->call('AdminOpenOrder', array(
            'TradingAccountId' => $trading_account_id,
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
     * @deprecated Use call() instead.
     */
    public function order_modify($ticket, $params = array())
    {
        return $this->_client->call('AdminModifyOrder', array_merge(array(
            'Ticket' => $ticket,
        ), $params));
    }

    /**
     * @deprecated Use call() instead.
     */
    public function order_close($ticket, $params = array())
    {
        return $this->_client->call('AdminCloseOrder', array_merge(array(
            'Ticket' => $ticket,
        ), $params));
    }

    /**
     * @deprecated Use call() instead.
     */
    public function order_delete($ticket)
    {
        return $this->_client->call('AdminDeleteOrder', array(
            'Ticket' => $ticket,
        ));
    }

    /**
     * @deprecated Use call() instead.
     */
    public function get_jwt($client_id, $ip, $user_agent)
    {
        return $this->_client->call('GetJWT', array(
            'ClientId' => $client_id,
            'IP' => $ip,
            'UserAgent' => $user_agent,
        ));
    }

    /**
     *
     * @deprecated Use call() instead.
     * @param type $login
     * @param type $password
     * @param type $ip
     * @param type $user_gent
     * @return type
     */
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

    public static function from_clients()
    {
        return 'Clients';
    }

    public static function from_trading_accounts()
    {
        return 'TradingAccounts';
    }
}
