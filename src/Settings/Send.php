<?php

/**
 * @package WooCommerce Notifications via Flexacore
 * @link https://osen.co.ke
 * @version 0.20.60
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 */

namespace Osen\Notify\Settings;

use Osen\Notify\Notifications\Service;

class Send extends Service
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'at_bulk_settings_init']);
        add_action('admin_menu', array($this, 'admin_menu'), 99);
        add_action('wp_ajax_process_at_bulk_form', [$this, 'process_at_bulk_form']);
        add_action('wp_ajax_nopriv_process_at_bulk_form', [$this, 'process_at_bulk_form']);
    }

    public function admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __("Send Bulk SMS To Customers", "woocommerce"),
            __("Send Bulk SMS", "woocommerce"),
            "manage_options",
            "at_bulk",
            [$this, "at_bulk_options_page_html"]
        );
    }

    public function at_bulk_settings_init()
    {

        register_setting('at_bulk', 'at_bulk_options');

        add_settings_section('at_bulk_section_sms', __('Bulk SMS Sending.', 'woocommerce'), [$this, 'at_bulk_section_at_bulk_sms_cb'], 'at_bulk');

        add_settings_field(
            'phone',
            __('Select Customers', 'woocommerce'),
            [$this, 'at_bulk_fields_at_bulk_sms_shortcode_cb'],
            'at_bulk',
            'at_bulk_section_sms',
            [
                'label_for'           => 'phone',
                'class'               => 'at_bulk_row',
                'at_bulk_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'message',
            __('Message Content', 'woocommerce'),
            [$this, 'at_bulk_fields_at_bulk_sms_username_cb'],
            'at_bulk',
            'at_bulk_section_sms',
            [
                'label_for'           => 'message',
                'class'               => 'at_bulk_row',
                'at_bulk_custom_data' => 'custom',
            ]
        );
    }

    public function at_bulk_section_at_bulk_sms_cb($args)
    {
        $options      = get_option('b2c_wcsms_options');
        $instructions = isset($options['instructions']) ? $options['instructions'] : 'Crosscheck values before submission'; ?>
                <p id="<?php echo esc_attr($args['id']); ?>">
                <p><?php echo esc_attr($instructions); ?></p>
                </p>
            <?php
    }

    function customer_list()
    {
        $customer_query = new \WP_User_Query(
            array(
                'fields' => 'ID',
                'role'   => 'customer',
            )
        );
        return $customer_query->get_results();
    }

    public function at_bulk_fields_at_bulk_sms_shortcode_cb($args)
    { ?>
                <select id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['at_bulk_custom_data']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" class="regular-text option-tree-ui-select wc-enhanced-select" multiple>
                    <option value="all">Send To All</option>
                    <?php foreach ($this->customer_list() as $customer_id): ?>
                            <?php $customer = new \WC_Customer($customer_id); ?>
                            <option value="<?php echo $customer->get_billing_phone(); ?>"><?php echo $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name(); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Customer(s) to send SMS to. Leave Blank To Send To All.', 'at_bulk'); ?>
                </p>
            <?php
    }

    public function at_bulk_fields_at_bulk_sms_username_cb($args)
    { ?>
                <textarea id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['at_bulk_custom_data']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" class="regular-text" required></textarea>
                <p class="description">
                    <?php esc_html_e('Message Content to send.', 'at_bulk'); ?>
                </p>
            <?php
    }

    /**
     * top level menu:
     * callback functions
     */
    public function at_bulk_options_page_html()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        } ?>
                <div class="wrap">
                    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                    <form id="at_bulk_ajax_form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST">
                        <?php
                        // output setting sections and their fields
                        // (sections are registered for "at_bulk", each field is registered to a specific section)
                        do_settings_sections('at_bulk');

                        wp_nonce_field('process_at_bulk_form', 'at_bulk_form_nonce');
                        ?>
                        <input type="hidden" name="action" value="process_at_bulk_form">
                        <button class="button button-primary">Send Message</button>
                    </form>
                    <?php
                    //add_settings_error('at_bulk_messages', 'at_bulk_message', __('WPay C2B Settings Updated', 'woocommerce'), 'updated');
                    //settings_errors('at_bulk_messages');
                    ?>
                    <script id="at_bulk-ajax" type="text/javascript">
                        jQuery(document).ready(function($) {
                            $('#at_bulk_ajax_form').submit(function(e) {
                                e.preventDefault();

                                var form = $(this);

                                $.post(form.attr('action'), form.serialize(), function(data) {
                                    if (data['status']) {
                                        if (data['status'] == 'success') {
                                            $('#wpbody-content .wrap h1').after(
                                                '<div class="updated settings-error notice is-dismissible"><p>' +
                                                data['data']['SMSMessageData']['Message'] +
                                                '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                            );
                                        } else {
                                            $('#wpbody-content .wrap h1').after(
                                                '<div class="error settings-error notice is-dismissible"><p>' + data['data'] + '.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                            );
                                        }
                                    } else {
                                        $('#wpbody-content .wrap h1').after(
                                            '<div class="error settings-error notice is-dismissible"><p>An Error Occured.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                        );
                                    }
                                }, 'json');
                            });
                        });
                    </script>
                </div>
        <?php
    }

    /**
     * Parse message to be sent
     *
     * @param WC_Order $order
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

    public function process_at_bulk_form()
    {
        if (!isset($_POST['at_bulk_form_nonce']) || !wp_verify_nonce($_POST['at_bulk_form_nonce'], 'process_at_bulk_form')) {
            exit(wp_send_json(['errorCode' => 'The form is not valid']));
        }

        $message = trim($_POST['message']);
        $phone   = $_POST['phone'] ? trim($_POST['phone']) : null;

        if ($phone == 'all' || is_null($phone)) {
            $phones = [];
            $orders = wc_get_orders(
                array(
                    'limit' => -1
                )
            );

            foreach ($orders as $order) {
                $PhoneNumber = str_replace("+", "", $order->get_billing_phone());
                $PhoneNumber = preg_replace('/^0/', '254', $PhoneNumber);
                $phones[]    = $PhoneNumber;
            }

            $phone = implode(',', array_unique($phones));
        }

        wp_send_json($this->send($phone, $message));
    }
}