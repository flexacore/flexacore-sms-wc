<?php

/**
 * @package WooCommerce Notifications via Flexacore
 * @subpackage Main alert class
 * @link https://www.flexacore.com
 * @version 0.20.60
 * @since 0.20.40
 * @author Flexacore < hello@flexacore.com >
 */

namespace Flexacore\Notify\Notifications;

class Alert extends Service
{
    public function __construct()
    {
        add_action('woocommerce_created_customer', [$this, 'send_customer_sms'], 10, 1);

        add_action('woocommerce_new_order', [$this, 'created'], 10, 3);
        add_action("woocommerce_order_status_changed", [$this, 'check_and_send'], 10, 3);
    }

    function send_customer_sms($user_id)
    {
        $customer = new \WC_Customer($user_id);

        if ($this->get_option("customer_enable", 'registration') == 'on') {
            $customer_message = $this->get_option("customer_msg", 'registration');
            $customer_message = $this->parse_customer_msg($customer_message, $customer);

            $this->send($customer->get_billing_phone(), $customer_message);
        }

        if ($this->get_option("admin_enable", 'registration') == 'on') {
            $admin_message = $this->get_option("admin_msg", 'registration');
            $admin_message = $this->parse_customer_msg($admin_message, $customer);
            $phone         = $this->get_option('phones');

            $this->send($phone, $admin_message);
        }
    }

    /**
     * Parse customer message to be sent
     *
     * @param string $message
     * @param \WC_Customer $customer
     * @return void
     */
    public function parse_customer_msg(string $message, \WC_Customer $customer): string
    {
        $first_name = $customer->get_billing_first_name();
        $last_name  = $customer->get_billing_last_name();
        $phone      = $customer->get_billing_phone();

        $variables = array(
            "first_name" => $first_name,
            "last_name"  => $last_name,
            "phone"      => $phone,
            "site"       => get_bloginfo('name'),
        );

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Parse message to be sent
     *
     * @param \WC_Order $order
     * @param string $message
     * @return void
     */
    public function parse(\WC_Order $order, string $message): string
    {
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $order_no   = $order->get_order_number();
        $phone      = $order->get_billing_phone();
        $amount     = $order->get_total();

        $variables = array(
            "first_name" => $first_name,
            "last_name"  => $last_name,
            "order"      => $order_no,
            "phone"      => $phone,
            "amount"     => $amount,
            "site"       => get_bloginfo('name'),
        );

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    public function check_and_send($order_id, $from, $to)
    {
        return $this->notify($order_id, false, $to, $from);
    }

    public function created($order_id)
    {
        return $this->notify($order_id, true);
    }

    public function notify($order_id, $new = false, $to = '', $from = '')
    {
        $order  = new \WC_Order($order_id);
        $phone  = $order->get_billing_phone();
        $status = $new ? 'created' : $order->status;

        if ($this->get_option("customer_enable", "{wc-}$status") == 'on') {
            $customer_message = $this->get_option("customer_msg", $status);
            $customer_message = $this->parse($order, $customer_message);

            try {
                $msg = $this->send($phone, $customer_message);

                if ($msg["status"] == "success") {
                    $order->add_order_note("SMS message successfuly sent to {$phone} on {$status} status");
                }
            } catch (\Throwable $th) {
                $order->add_order_note($th->getMessage());
            }
        }

        if ($this->get_option("admin_enable", "{wc-}$status") == 'on') {
            $admin_message = $this->get_option("admin_msg", $status);
            $admin_message = $this->parse($order, $admin_message);
            $phone         = $this->get_option('phones');

            try {
                $msg = $this->send($phone, $admin_message);

                if ($msg["status"] == "success") {
                    $order->add_order_note("Admin(s) notified via SMS on {$status} status");
                }
            } catch (\Throwable $th) {
                $order->add_order_note($th->getMessage());
            }
        }
    }
}
