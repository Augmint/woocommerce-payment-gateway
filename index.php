<?php

/**
 * Plugin Name: WooCommerce Augmint Plugin
 */

add_action('woocommerce_init', 'init_augmint_gateway');

function init_augmint_gateway()
{
    include __DIR__ . '/class-wc-gateway-augmint.php';
}

function add_augmint_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_Augmint';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_augmint_gateway_class');
