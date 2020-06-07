<?php

namespace Osen\Notify\Settings;

class Admin extends Base
{

    private $settings;

    private $statuses = [
        'created', 'pending', 'failed', 'on-hold',  'processing', 'completed', 'refunded', 'cancelled'
    ];

    function __construct()
    {
        $this->settings = new Base;

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'), 99);
    }

    function admin_init()
    {

        //set the settings
        $this->settings->set_sections($this->get_settings_sections());
        $this->settings->set_fields($this->get_settings_fields());

        //initialize settings
        $this->settings->admin_init();
    }

    function admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'SMS Notifications Via Africa\'s Talking',
            'SMS Notifications',
            'manage_options',
            'at_notify',
            array($this, 'plugin_page')
        );
    }

    function get_settings_sections()
    {
        $sections = array(
            array(
                'id'    => 'gateway',
                'title' => __('Admin Options', 'woocommerce')
            )
        );
        foreach ($this->statuses as $status) {
            $sections[] = array(
                'id'    => $status,
                'title' => ucwords($status)
            );
        }

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields()
    {
        $settings_fields = array(
            'gateway' => array(
                array(
                    'name'              => 'key',
                    'label'             => __('API Key', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'           => 'Your API Key',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'              => 'username',
                    'label'             => __('API Username', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'           => 'Your API Username',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'              => 'shortcode',
                    'label'             => __('Sender ID', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'           => 'Your Sender ID',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'        => 'phones',
                    'label'       => __('Admin Numbers', 'woocommerce'),
                    'desc'        => __('Comma-separated list of numbers to notify on status change', 'woocommerce'),
                    'type'        => 'textarea',
                    'placeholder' => 'E.g 254...,255...,256..'
                )
            )
        );

        foreach ($this->statuses as $status) {
            $settings_fields[$status] = array(
                array(
                    'name'  => 'customer_enable',
                    'label' => __('Customer Enable', 'woocommerce'),
                    'desc'  => __('Notify customer on ' . $status . ' status', 'woocommerce'),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'        => 'customer_msg',
                    'label'       => __('Customer Message', 'woocommerce'),
                    'desc'        => __('Message to send to customer for ' . $status . ' status', 'woocommerce'),
                    'type'        => 'textarea',
                    'default'     => 'Hello {customer}, your order on {site} is ' . $status . '.'
                ),
                array(
                    'name'  => 'admin_enable',
                    'label' => __('Admin Enable', 'woocommerce'),
                    'desc'  => __('Notify admin(s) on ' . $status . ' status', 'woocommerce'),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'        => 'admin_msg',
                    'label'       => __('Admin Message', 'woocommerce'),
                    'desc'        => __('Message to send to admin(s) for ' . $status . ' status', 'woocommerce'),
                    'type'        => 'textarea',
                    'rows'        => 2,
                    'default'     => 'An order is ' . $status . ' on {site}.'
                )
            );
        }

        return $settings_fields;
    }

    function get_option($option, $section = 'gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    function plugin_page()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        $this->settings->show_navigation();
        echo '<p>You can use placeholders such as <code>{customer}</code>, <code>{order}</code>, <code>{site}</code>, <code>{phone}</code> to show customer name, order number, website name and customer phone respectively.</p>';

        $this->settings->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages()
    {
        $pages = get_pages();
        $pages_options = array();
        if ($pages) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }
}
