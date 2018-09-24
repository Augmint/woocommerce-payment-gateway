<?php
/**
 * Settings for Augmint Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */

defined('ABSPATH') || exit;

return array(
    'enabled'               => array(
        'title'   => __('Enable/Disable', 'augmint'),
        'type'    => 'checkbox',
        'label'   => __('Enable Augmint Standard', 'augmint'),
        'default' => 'no',
    ),
    'title'                 => array(
        'title'       => __('Title', 'augmint'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'augmint'),
        'default'     => __('Augmint', 'augmint'),
        'desc_tip'    => true,
    ),
    'description'           => array(
        'title'       => __('Description', 'augmint'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __('This controls the description which the user sees during checkout.', 'augmint'),
        'default'     => __("Pay via Augmint; you can pay with your credit card if you don't have a Augmint account.", 'augmint'),
    ),
    'store_settings'              => array(
        'title'       => __('Store settings', 'augmint'),
        'type'        => 'title',
        'description' => '',
    ),
    'store_name'                 => array(
        'title'       => __('Name', 'augmint'),
        'type'        => 'text',
        'description' => '',
        'default'     => __('Augmint', 'augmint'),
        'desc_tip'    => true,
    ),
    'store_ethereum_address'   => array(
        'title'       => __('Store ethereum address', 'augmint'),
        'type'        => 'text',
        'description' => '',
        'default'     => '',
        'desc_tip'    => true,
    ),
    'payment_reference' => array(
        'title'       => __('Payment reference', 'augmint'),
        'type'        => 'text',
        'description' => 'The reference which will be included in Augmint transfer. NB: this reference will be public on blockchain. Use {order_id} to insert the order number',
        'default'     => __('Order number: {order_id}', 'augmint'),
        'desc_tip'    => true,
    ),
    'advanced'              => array(
        'title'       => __('Advanced options', 'augmint'),
        'type'        => 'title',
        'description' => '',
    ),
    'testmode'              => array(
        'title'       => __('Augmint sandbox', 'augmint'),
        'type'        => 'checkbox',
        'label'       => __('Enable Augmint sandbox', 'augmint'),
        'default'     => 'no',
        /* translators: %s: URL */
        'description' => sprintf(__('Augmint sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'augmint'), 'https://developer.augmint.com/'),
    ),
    'debug'                 => array(
        'title'       => __('Debug log', 'augmint'),
        'type'        => 'checkbox',
        'label'       => __('Enable logging', 'augmint'),
        'default'     => 'no',
        /* translators: %s: URL */
        'description' => sprintf(__('Log Augmint events, such as IPN requests, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'augmint'), '<code>' . WC_Log_Handler_File::get_log_file_path('augmint') . '</code>'),
    ),
);
