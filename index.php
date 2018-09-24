<?php

/**
 * Plugin Name: WooCommerce Augmint Plugin
 */

add_filter('woocommerce_payment_gateways', 'add_augmint_gateway_class');
add_filter('woocommerce_currencies', 'add_augmint_currency');
add_filter('woocommerce_currency_symbol', 'add_augmint_currency_symbol', 10, 2);

function add_augmint_gateway_class($methods)
{
    include_once __DIR__ . '/class-wc-gateway-augmint.php';

    $methods[] = 'WC_Gateway_Augmint';
    return $methods;
}

function add_augmint_currency($currencies)
{
    $currencies['A-EUR'] = __('Augmint EUR', 'augmint');

    return $currencies;
}

function add_augmint_currency_symbol($currency_symbol, $currency)
{
    switch ($currency) {
        case 'A-EUR': $currency_symbol = 'A€'; break;
    }

    return $currency_symbol;
}
