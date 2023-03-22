<?php

class MobiusTrader_Client
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    private $options;

    public function __construct($options = array())
    {
        if (! function_exists('base64_encode')) {
            throw new Exception('base64_encode not supported');
        }
        if (! function_exists('json_encode')) {
            throw new Exception('JSON not supported');
        }
        if (! function_exists('curl_init') || ! extension_loaded('curl')) {
            throw new Exception('cURL must be installed');
        }
        
        $default_options = array(
            'url' => NULL,
            'user_agent' => 'MT7-PHP/2.0.2',
            'broker' => NULL,
            'password' => NULL,
            'float_mode' => false,
            'response' => array(
                'status' => array(
                    'field' => 'status',
                    'ok' => true,
                    'error' => false,
                ),
                'result' => array(
                    'field' => 'data',
                ),
            ),
        );

        $this->options = array_replace_recursive($default_options, $options);
    }

    public function call($method, array $params = NULL)
    {
        $url = 'https://api.mtrader7.com/v1';
        $payload = new stdClass;

        $payload->jsonrpc = '2.0';
        $payload->id = $this->generate_id();
        $payload->method = $method;

        if ($params) {
            $payload->params = $params;
        }

        $curl = curl_init();
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->options['broker'] . ':' . $this->options['password']),
        );
        
        if ($this->options['float_mode']) 
        {
            $headers[] = 'X-FloatMode: true';
        }

        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->options['user_agent'],
            CURLOPT_POST => 1,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ));

        // Send the request & save response to $resp
        $response = curl_exec($curl);
        
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($http_code !== 200) {
            return array(
                'status' => self::STATUS_ERROR,
                'data' => $http_code ? $http_code : 'UnknownError',
                'message' => curl_error($curl) ? curl_error($curl) : $response,
            ); 
        }

        // Close request to clear up some resources
        curl_close($curl);

        $response = json_decode($response, true);
        
        $message = '';
        $args = array();

        if (!empty($response['error'])) {
            $status = self::STATUS_ERROR;
            
            if (!empty($response['error']['error']['Key'])) {
                $data = $response['error']['error']['Key'];
                $message = $response['error']['error']['Message'];
                $args = $response['error']['error']['Args'];
            } else if (!empty($response['error']['error'])) {
                $data = $response['error']['error'];
            } else {
                $data = json_encode($response['error']);
            }
        } else {
            $status = self::STATUS_OK;
            $data = $response['result'];
        }

        return array(
            'status' => $status,
            'data' => $data,
            'message' => $message,
            'args' => $args,
        );
    }

    protected function generate_id()
    {
        return mt_rand(1, 100000000);
    }
}