<?php

/**
 * @package WooCommerce Notifications via Africa's Talking
 * @link https://osen.co.ke
 * @version 0.20.60
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 */

namespace Osen\Notify\Settings;

class Admin
{

    private $settings;

    private $statuses = [];

    public function __construct()
    {
        $this->settings = new Base;

        $this->statuses = \array_merge(['created' => 'Created'], wc_get_order_statuses());

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'), 99);
    }

    public function admin_init()
    {
        //set the settings
        $this->settings->set_sections($this->get_settings_sections());
        $this->settings->set_fields($this->get_settings_fields());

        //initialize settings
        $this->settings->admin_init();
    }

    public function admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'SMS Notifications Via Africa\'s Talking',
            'SMS Notifications',
            'manage_options',
            'at_notify',
            array($this, 'settings_page')
        );
    }

    public function get_settings_sections()
    {
        $sections = array(
            array(
                'id'      => 'gateway',
                'title'   => __('Admin Options', 'woocommerce'),
                'heading' => __('Admin Options', 'woocommerce'),
                'desc'    => 'Setup your Africa\'s talking configuration here.',
            ),
            array(
                'id'      => 'registration',
                'title'   => __('Registration', 'woocommerce'),
                'heading' => __('On Customer Registration', 'woocommerce'),
                'desc'    => 'You can use placeholders such as <code>{first_name}</code>, <code>{last_name}</code>, <code>{site}</code>, <code>{phone}</code> to show customer names, website name and customer phone respectively.',
            ),
        );

        foreach ($this->statuses as $key => $status) {
            $sections[] = array(
                'id'      => $key,
                'title'   => ucwords($status),
                'heading' => 'On ' . ucwords($status) . ' Status',
                'desc'    => 'You can use placeholders such as <code>{first_name}</code>, <code>{last_name}</code>, <code>{order}</code>, <code>{site}</code>, <code>{phone}</code> to show customer names, order number, website name and customer phone respectively.',
            );
        }

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields()
    {
        $settings_fields = array(
            'gateway'      => array(
                array(
                    'name'              => 'username',
                    'label'             => __('AT Username', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your Africa\'s Talking API Username',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'shortcode',
                    'label'             => __('AT Sender ID', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your Africa\'s Talking Sender ID',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'key',
                    'label'             => __('AT API Key', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your Africa\'s Talking API Key',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'        => 'phones',
                    'label'       => __('Admin Contacts', 'woocommerce'),
                    'desc'        => __('Comma-separated list of phone numbers to notify on status change', 'woocommerce'),
                    'type'        => 'textarea',
                    'placeholder' => 'E.g 254...,255...,256..',
                ),
                // array(
                //     'name'    => 'statuses',
                //     'label'   => __('Enabled Statuses', 'woocommerce'),
                //     'desc'    => __('Select which statuses to show.'.print_r($this->get_option('statuses')), 'woocommerce'),
                //     'type'    => 'multicheck',
                //     'options' => $this->statuses,
                // ),
            ),

            'registration' => array(
                array(
                    'name'  => 'customer_enable',
                    'label' => __('Customer Enable', 'woocommerce'),
                    'desc'  => __('Notify customer on registration', 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'customer_msg',
                    'label'   => __('Customer Message', 'woocommerce'),
                    'desc'    => __('Message to send to customer on registration', 'woocommerce'),
                    'type'    => 'textarea',
                    'default' => 'Hello {first_name}, thank you for registering on {site}.',
                ),
                array(
                    'name'  => 'admin_enable',
                    'label' => __('Admin Enable', 'woocommerce'),
                    'desc'  => __('Notify admin(s) on registration', 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'admin_msg',
                    'label'   => __('Admin Message', 'woocommerce'),
                    'desc'    => __('Message to send to admin(s) on customer registration', 'woocommerce'),
                    'type'    => 'textarea',
                    'rows'    => 2,
                    'default' => 'A new customer has just registered on {site}.',
                ),
            ),
        );

        foreach ($this->statuses as $key => $status) {
            $settings_fields[$key] = array(
                array(
                    'name'  => 'customer_enable',
                    'label' => __('Customer Enable', 'woocommerce'),
                    'desc'  => __('Notify customer when order is ' . \strtolower($status), 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'customer_msg',
                    'label'   => __('Customer Message', 'woocommerce'),
                    'desc'    => __('Message to send to customer when order status is ' . \strtolower($status), 'woocommerce'),
                    'type'    => 'textarea',
                    'default' => 'Hello {first_name}, the status of your order on {site} is ' . \strtolower($status) . '.',
                ),
                array(
                    'name'  => 'admin_enable',
                    'label' => __('Admin Enable', 'woocommerce'),
                    'desc'  => __('Notify admin(s) when order is ' . \strtolower($status), 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'admin_msg',
                    'label'   => __('Admin Message', 'woocommerce'),
                    'desc'    => __('Message to send to admin(s) when order status is ' . \strtolower($status), 'woocommerce'),
                    'type'    => 'textarea',
                    'rows'    => 2,
                    'default' => 'An order on {site} has ' . \strtolower($status) . ' status.',
                ),
            );
        }

        return $settings_fields;
    }

    public function get_option($option, $section = 'gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    public function settings_page()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        $this->settings->show_navigation();
        $this->settings->show_forms();

        echo '</div>';
    }
}
