<?php

namespace BogPay;

use GuzzleHttp\Client;

class BogPay
{
    private $api_url = 'https://ipay.ge/opay/api/v1';

    private $intend = [
        'CAPTURE',  //0
        'AUTHORIZE' //1
    ];

    private $locale = [
        'ka',  //0
        'en-US' //1
    ];

    private $capture_method = [
        'AUTOMATIC',  //0
        'MANUAL'  //1
    ];

    private $token;

    private $client_id;

    private $secret_key;

    private $http_client;

    public function __construct($client_id,$secret_key){
        $this->client_id = $client_id;
        $this->secret_key = $secret_key;
        $this->http_client = new Client();

        $this->token = $this->get_token();
    }

    private function get_token(){

        $response = $this->http_client->request('POST', $this->api_url, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($this->client_id . ':' . $this->secret_key)
            ],

            'form_params' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $body = $response->getBody();

        $body = json_decode($body,true);

        return $body['access_token'];

    }

    public function make_order(
        $intend = 0,
        string $redirect_url,
        array $purchase_units,
        $items = null,
        $locale = null,
        $shop_order_id = null,
        $show_shop_order_id_on_extract = null,
        $capture_method = null
    ){

        $json = [];

        $json['intend'] = $this->intend[(int)$intend];

        $json['redirect_url'] = $redirect_url;

        $_purchase_units = [];

        $n = 0;

        foreach ($purchase_units as $item){
            $_purchase_units[$n]['amount'] = $item;
            $n++;
        }

        $json['purchase_units'] = $_purchase_units;

        if(is_array($items)) $json['items'] = $items;

        if($locale !== null) $json['locale'] = $this->locale[(int)$locale];

        if($shop_order_id !== null) $json['shop_order_id'] = $shop_order_id;

        if($show_shop_order_id_on_extract !== null) $json['show_shop_order_id_on_extract'] = (bool)$show_shop_order_id_on_extract;

        if($capture_method !== null) $json['capture_method'] = $this->capture_method[(int)$capture_method];


        $response = $this->http_client->request('POST', $this->api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->token
            ],

            'json' => $json
        ]);

        return $response->getBody();

    }

    public function refund($order_id, $amount){
        $response = $this->http_client->request('POST', $this->api_url, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer '.$this->token
            ],

            'form_params' => [
                'order_id' => $order_id,
                'amount' => $amount
            ]
        ]);

        return $response->getStatusCode();
    }
}