<?php

namespace Osen\Notify\Notifications;

use AfricasTalking\SDK\AfricasTalking;

class Service
{
    public function get_option($option, $section = 'gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    public function wallet_balance()
    {
        $url = ($this->username == 'sandbox')
            ? 'https://payments.sandbox.africastalking.com/query/wallet/balance'
            : 'https://payments.africastalking.com/query/wallet/balance';
        $url = "{$url}?username={$this->username}";

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'apiKey'       => $this->key,
                ),
            )
        );

        if (is_wp_error($response)) {
            $balance = 'Could not connect to AT';
        } else {
            $response = json_decode($response['body'], true);
            $balance  = $response['balance'] ?? $response['status'] ?? 0;
        }

        return $balance;
    }

    public function send($to, $message = 'Test message')
    {
        $receipients = strip_tags(trim($to));

        $phones = array();
        if (strpos(',', $receipients) !== false) {
            $phones = explode(',', $receipients);
        } else {
            $phones = $receipients;
        }

        $AT  = new AfricasTalking($this->get_option('username'), $this->get_option('key'));
        $sms = $AT->sms();
        return $sms->send([
            'to'      => $phones,
            'message' => $message,
            'from'    => $this->get_option('shortcode'),
        ]);
    }
}
