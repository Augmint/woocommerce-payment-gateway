<?php
/**
 * Class WC_Gateway_Augmint_Response file.
 *
 * @package WooCommerce\Gateways
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles Responses.
 */
abstract class WC_Gateway_Augmint_Response
{

    /**
     * Sandbox mode
     *
     * @var bool
     */
    protected $sandbox = false;

    /**
     * Payment Gateway class
     *
     * @var WC_Gateway_Augmint
     */
    protected $gateway = null;

    /**
     * Complete order, add transaction ID and note.
     *
     * @param  WC_Order $order Order object.
     * @param  string   $txn_id Transaction ID.
     * @param  string   $note Payment note.
     */
    protected function payment_complete($order, $txn_id = '', $note = '')
    {
        $order->add_order_note($note);
        $order->payment_complete($txn_id);
        WC()->cart->empty_cart();
    }

    /**
     * Hold order and add note.
     *
     * @param  WC_Order $order Order object.
     * @param  string   $reason Reason why the payment is on hold.
     */
    protected function payment_on_hold($order, $reason = '')
    {
        $order->update_status('on-hold', $reason);
        wc_reduce_stock_levels($order->get_id());
        WC()->cart->empty_cart();
    }
}
