<?php

class MobiusTrader_Client
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    private $options;

    public function __construct($options = array())
    {
        $default_options = array(
            'url' => NULL,
            'user_agent' => "MobiusTrader-Client/2.0.1",
            'broker' => NULL,
            'password' => NULL,
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
        $url = str_replace('https', 'http', $this->options['url']);
        $payload = new stdClass;

        $payload->jsonrpc = '2.0';
        $payload->id = $this->generate_id();
        $payload->method = $method;

        if ($params) {
            $payload->params = $params;
        }

        $curl = curl_init();

        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->options['user_agent'],
            CURLOPT_POST => 1,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->options['broker'] . ':' . $this->options['password'])
            ),
            CURLOPT_POSTFIELDS => json_encode($payload)
        ));

        // Send the request & save response to $resp
        $response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close request to clear up some resources
        curl_close($curl);

        $response = json_decode($response, true);

        if (!empty($response['error']) || $status !== 200) {
            $status = self::STATUS_ERROR;
            if (!empty($response['error']['error']['Key'])) {
                $data = $response['error']['error']['Key'];
            } else if (!empty($response['error']['error'])) {
                $data = $response['error']['error'];
            } else {
                $data = 'Unknown error';
            }
        } else {
            $status = self::STATUS_OK;
            $data = $response['result'];
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    protected function generate_id()
    {
        return mt_rand(1, 100000000);
    }
}