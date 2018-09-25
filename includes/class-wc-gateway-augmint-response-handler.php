<?php
/**
 * Class WC_Gateway_Augmint_PDT_Handler file.
 *
 * @package WooCommerce\Gateways
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/class-wc-gateway-augmint-response.php';

/**
 * Handle PDT Responses from Augmint.
 */
class WC_Gateway_Augmint_Response_Handler extends WC_Gateway_Augmint_Response
{

    /**
     * Constructor.
     *
     * @param bool   $sandbox Whether to use sandbox mode or not.
     * @param string $identity_token Identity token for PDT support.
     */
    public function __construct($sandbox = false, $gateway = null)
    {
        add_action('woocommerce_thankyou_augmint', array( $this, 'check_response' ));

        $this->sandbox        = $sandbox;
        $this->gateway        = $gateway;
    }

    public function validation_failed()
    {
        echo __("Payment validation failed!", 'Augmint');

        return false;
    }

    /**
     * Check Response for validation data
     */
    public function check_response()
    {
        if (empty($_REQUEST['order_id']) || empty($_REQUEST['token']) || empty($_REQUEST['network_id'])
            || empty($_REQUEST['beneficiary_address']) || empty($_REQUEST['amount']) || empty($_REQUEST['currency_code'])
            || empty($_REQUEST['tx_hash'])
        ) {
            return;
        }

        $order_id    = wc_clean(wp_unslash($_REQUEST['order_id']));
        $order = wc_get_order($order_id);


        if (! $order || ! $order->has_status('pending')) {
            return false;
        }

        $token = wc_clean(wp_unslash($_REQUEST['token']));

        if ($token != get_post_meta($order_id, 'augmint_order_token', true)) {
            return false;
        }

        $amount = wc_clean(wp_unslash($_REQUEST['amount']));

        if ($order->get_total() != $amount) {
            return $this->validation_failed();
        }

        $network_id = wc_clean(wp_unslash($_REQUEST['network_id']));
        $beneficiary_address = wc_clean(wp_unslash($_REQUEST['beneficiary_address']));

        if (get_post_meta($order_id, 'augmint_network_id', true) != $network_id) {
            return $this->validation_failed();
        }
        if (get_post_meta($order_id, 'augmint_beneficiary_address', true) != $beneficiary_address) {
            return $this->validation_failed();
        }
        
        $currency_code = wc_clean(wp_unslash($_REQUEST['currency_code']));

        if ($currency_code != 'AEUR') {
            return $this->validation_failed();
        }

        $tx_hash = wc_clean(wp_unslash($_REQUEST['tx_hash']));

        // the meta_key 'color' with the meta_value 'blue'
        $query_args = array(
            'posts_per_page' => 5,
            'post_type'      => 'shop_order',
            'meta_query'     => array(
                array(
                    'key' => 'augmint_tx_hash',
                    'value' => $tx_hash
                )
            )
        );
        $query = new WP_Query($query_args);
        
        if ($query->have_posts()) {
            return $this->validation_failed();
        }

        $order->add_order_note('Tx hash: ' . $tx_hash);

        $this->payment_complete($order, $tx_hash, __('Payment completed', 'augmint'));

        update_post_meta($order->get_id(), 'augmint_tx_hash', $tx_hash);

        $order_status_change = $this->gateway->get_option('order_status_after_payment');

        if ('_default' == $order_status_change) {
            return true;
        }
        if ('_virtual_payment_received' == $order_status_change) {
            $is_virtual = true;

            if (count($this->get_items()) > 0) {
                foreach ($this->get_items() as $item) {
                    if ($item->is_type('line_item')) {
                        $product = $item->get_product();

                        if (! $product) {
                            continue;
                        }

                        $is_virtual &= $product->is_virtual();
                    }
                }
            }

            if ($is_virtual) {
                $order->set_status('payment_received');
            }

            return true;
        }

        $order->set_status($order_status_change);
    }
}
