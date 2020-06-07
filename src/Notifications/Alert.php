<?php

namespace Osen\Notify\Notifications;

class Alert extends Service
{
    public function __construct()
    {
        add_action('woocommerce_new_order', [$this, 'created'], 10, 3);
        add_action("woocommerce_order_status_changed", [$this, 'check_and_send'], 5);
    }

    public function parse($order, $message)
    {
        $customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_no    = $order->get_order_number();
        $phone    = $order->get_billing_phone();

        $variables = array(
            "customer" => $customer,
            "order"    => $order_no,
            "phone"    => $phone,
            "site"     => get_bloginfo('name')
        );

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    function check_and_send($order_id)
    {
        return $this->notify($order_id);
    }

    public function notify($order_id, $new = false)
    {
        $order    = new \WC_Order($order_id);
        $phone    = $order->get_billing_phone();
        $status   = $new ? 'created' : $order->status;

        if ($this->get_option("customer_enable", $status) == 'on') {
            $customer_message = $this->get_option("customer_msg", $status);
            $customer_message = $this->parse($order, $customer_message);

            try {
                $msg = $this->send($phone, $customer_message);

                if($msg["status"] == "success"){
                    $order->add_order_note("SMS message successfuly sent to {$phone} on {$status} status");
                }
            } catch (\Throwable $th) {
                $order->add_order_note($th->getMessage());
            }
        }

        if ($this->get_option("admin_enable", $status) == 'on') {
            $admin_message = $this->get_option("admin_msg", $status);
            $admin_message = $this->parse($order, $admin_message);
            $phone         = $this->get_option('phones');

            try {
                $msg = $this->send($phone, $admin_message);

                if($msg["status"] == "success"){
                    $order->add_order_note("Admin(s) notified via SMS on {$status} status");
                }
            } catch (\Throwable $th) {
                $order->add_order_note($th->getMessage());
            }

        }

    }

    public function created($order_id)
    {
        return $this->notify($order_id, true);
    }
}
