<?php

/**
 * @package WooCommerce Notifications via Flexacore
 * @link https://www.flexacore.com
 * @version 0.20.60
 * @since 0.20.40
 * @author Flexacore < hello@flexacore.com >
 */

namespace Flexacore\Notify\Notifications;

use Flexacore\SDK\Flexacore;

class Service
{
    public function get_option($option, $section = 'gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    public function wallet_balance()
    {
        $url = ($this->get_option('username') === 'sandbox')
            ? 'https://sms.flexacore.com/api/v3/balance'
            : 'https://sms.flexacore.com/api/v3/balance';
        $url = "{$url}?username={$this->get_option('username')}";

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'apiKey'       => $this->get_option('key'),
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
        } elseif (is_array($receipients)) {
            $phones = $receipients;
        } else {
            $phones = [$receipients];
        }

        $res = wp_remote_post('https://one.flexacore.com/api/v3/sms/send', [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_option('key'),
            ],
            'body'    => wp_json_encode([
                'recipient' => implode(',', $phones),
                'sender_id' => $this->get_option('shortcode'),
                'type'      => 'plain',
                'message'   => $message
            ])
        ]);

        if (is_wp_error($res)) {
            return $res->get_error_message();
        }

        $res = json_decode($res['body'], true);

        return $res;
    }
}