<?php
/**
 * Class WC_Gateway_Augmint_Request file.
 *
 * @package WooCommerce\Gateways
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to Augmint.
 */
class WC_Gateway_Augmint_Request
{

    /**
     * Stores line items to send to Augmint.
     *
     * @var array
     */
    protected $line_items = array();

    /**
     * Pointer to gateway making the request.
     *
     * @var WC_Gateway_Augmint
     */
    protected $gateway;

    /**
     * Endpoint for requests from Augmint.
     *
     * @var string
     */
    protected $notify_url;

    /**
     * Endpoint for requests to Augmint.
     *
     * @var string
     */
    protected $endpoint;


    /**
     * Constructor.
     *
     * @param WC_Gateway_Augmint $gateway Augmint gateway object.
     */
    public function __construct($gateway)
    {
        $this->gateway    = $gateway;
    }

    /**
     * Get the Augmint request URL for an order.
     *
     * @param  WC_Order $order Order object.
     * @param  bool     $sandbox Whether to use sandbox mode or not.
     * @return string
     */
    public function get_request_url($order, $sandbox = false)
    {
        $this->endpoint = $sandbox ? 'https://www.augmint.org/transfer/request?network_id=4&' : 'https://www.augmint.org/transfer/request?network_id=1&';
        $augmint_args    = $this->get_augmint_args($order);

        WC_Gateway_Augmint::log('Augmint Request Args for order ' . $order->get_order_number() . ': ' . wc_print_r($augmint_args, true));

        update_post_meta($order->get_id(), 'augmint_network_id', $sandbox ? 4 : 1);

        $order->add_order_note('Network id ' . ($sandbox ? 4 : 1));

        return $this->endpoint . http_build_query($augmint_args, '', '&');
    }

    /**
     * Limit length of an arg.
     *
     * @param  string  $string Argument to limit.
     * @param  integer $limit Limit size in characters.
     * @return string
     */
    protected function limit_length($string, $limit = 127)
    {
        // As the output is to be used in http_build_query which applies URL encoding, the string needs to be
        // cut as if it was URL-encoded, but returned non-encoded (it will be encoded by http_build_query later).
        $url_encoded_str = rawurlencode($string);

        if (strlen($url_encoded_str) > $limit) {
            $string = rawurldecode(substr($url_encoded_str, 0, $limit - 3) . '...');
        }
        return $string;
    }

    /**
     * Get transaction args for augmint request, except for line item args.
     *
     * @param WC_Order $order Order object.
     * @return array
     */
    protected function get_transaction_args($order)
    {
        update_post_meta($order->get_id(), 'augmint_order_token', md5($order->get_id() . $order->get_cart_hash()));

        $token = md5($order->get_id() . $order->get_cart_hash());
        $order->add_order_note('Token ' . $token);

        update_post_meta($order->get_id(), 'augmint_beneficiary_address', $this->gateway->get_option('store_ethereum_address'));

        return array_merge(
            array(
                'order_id' => $order->get_id(),
                'token' => $token,
                'beneficiary_address' => $this->gateway->get_option('store_ethereum_address'),
                'beneficiary_name' => $this->gateway->get_option('store_name'),
                'amount' => $order->get_total(),
                'currency_code' => 'AEUR',
                'reference' => str_replace(array('{order_id}'), array($order->get_id()), $this->gateway->get_option('payment_reference')),
                'notify_url' => $this->gateway->get_return_url($order)
            )
        );
    }

    /**
     * If the default request with line items is too long, generate a new one with only one line item.
     *
     * If URL is longer than 2,083 chars, ignore line items and send cart to Augmint as a single item.
     * One item's name can only be 127 characters long, so the URL should not be longer than limit.
     * URL character limit via:
     * https://support.microsoft.com/en-us/help/208427/maximum-url-length-is-2-083-characters-in-internet-explorer.
     *
     * @param WC_Order $order Order to be sent to Augmint.
     * @param array    $augmint_args Arguments sent to Augmint in the request.
     * @return array
     */
    protected function fix_request_length($order, $augmint_args)
    {
        $max_augmint_length = 2083;
        $query_candidate   = http_build_query($augmint_args, '', '&');

        if (strlen($this->endpoint . $query_candidate) <= $max_augmint_length) {
            return $augmint_args;
        }

        return apply_filters(
            'woocommerce_augmint_args',
            array_merge(
                $this->get_transaction_args($order),
                $this->get_line_item_args($order, true)
            ),
            $order
        );
    }

    /**
     * Get Augmint Args for passing to PP.
     *
     * @param  WC_Order $order Order object.
     * @return array
     */
    protected function get_augmint_args($order)
    {
        WC_Gateway_Augmint::log('Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url);

        $augmint_args = apply_filters(
            'woocommerce_augmint_args',
            array_merge(
                $this->get_transaction_args($order)
            ),
            $order
        );

        return $this->fix_request_length($order, $augmint_args);
    }

}
