<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @class WC_Gateway_Augmint
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Augmint extends WC_Payment_Gateway
{

    /**
     * Whether or not logging is enabled
     *
     * @var bool
     */
    public static $log_enabled = false;

    /**
     * Logger instance
     *
     * @var WC_Logger
     */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                = 'augmint';
        $this->has_fields        = false;
        $this->order_button_text = __('Proceed to Augmint', 'augmint');
        $this->method_title      = __('Augmint', 'augmint');
        /* translators: %s: Link to WC system status page */
        $this->method_description = __('Augmint redirects customers to Augmint to enter their payment information.', 'augmint');
        $this->supports           = array(
            'products',
            'refunds',
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title          = $this->get_option('title');
        $this->description    = $this->get_option('description');
        $this->testmode       = 'yes' === $this->get_option('testmode', 'no');
        $this->debug          = 'yes' === $this->get_option('debug', 'no');
        $this->email          = $this->get_option('email');
        $this->receiver_email = $this->get_option('receiver_email', $this->email);
        $this->identity_token = $this->get_option('identity_token');
        self::$log_enabled    = $this->debug;

        if ($this->testmode) {
            /* translators: %s: URL */
            $this->description .= ' ' . sprintf(__('TEST payment. You can use Rinkeby test ETH and A-EUR without value. No real order will be fulfilled.', 'augmint'), '');
            $this->description  = trim($this->description);
        }

        add_action('admin_enqueue_scripts', array( $this, 'admin_scripts' ));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        add_action('woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ));
        add_action('woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ));

        if (! $this->is_valid_for_use()) {
            $this->enabled = 'no';

            return;
        }

        include_once dirname(__FILE__) . '/includes/class-wc-gateway-augmint-response-handler.php';

        $augmint_request = new WC_Gateway_Augmint_Response_Handler($this->debug, $this);
    }

    /**
     * Return whether or not this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.
     *
     * @since 3.4.0
     * @return bool
     */
    public function needs_setup()
    {
        return ! is_email($this->email);
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'. Possible values:
     *                      emergency|alert|critical|error|warning|notice|info|debug.
     */
    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, array( 'source' => 'augmint' ));
        }
    }

    public function get_return_url($order = null)
    {
        return str_replace('https:', 'http:', add_query_arg(array('wc-api' => 'WC_Gateway_Augmint'), home_url('/')));
    }

    public function get_thankyou_url($order)
    {
        return parent::get_return_url($order);
    }

    /**
     * Processes and saves options.
     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
     *
     * @return bool was anything saved?
     */
    public function process_admin_options()
    {
        $saved = parent::process_admin_options();

        // Maybe clear logs.
        if ('yes' !== $this->get_option('debug', 'no')) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->clear('augmint');
        }

        return $saved;
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        $icon_html = '';
        $icon      = (array) $this->get_icon_image(WC()->countries->get_base_country());

        foreach ($icon as $i) {
            $icon_html .= '<img src="' . esc_attr($i) . '" alt="' . esc_attr__('Augmint acceptance mark', 'augmint') . '" />';
        }

        $icon_html .= sprintf('<a href="%1$s" class="about_augmint" onclick="javascript:window.open(\'%1$s\',\'WIAugmint\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;">' . esc_attr__('What is Augmint?', 'augmint') . '</a>', esc_url($this->get_icon_url(WC()->countries->get_base_country())));

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Get the link for an icon based on country.
     *
     * @param  string $country Country two letter code.
     * @return string
     */
    protected function get_icon_url($country)
    {
        $url           = 'https://www.augmint.com/' . strtolower($country);
        $home_counties = array( 'BE', 'CZ', 'DK', 'HU', 'IT', 'JP', 'NL', 'NO', 'ES', 'SE', 'TR', 'IN' );
        $countries     = array( 'DZ', 'AU', 'BH', 'BQ', 'BW', 'CA', 'CN', 'CW', 'FI', 'FR', 'DE', 'GR', 'HK', 'ID', 'JO', 'KE', 'KW', 'LU', 'MY', 'MA', 'OM', 'PH', 'PL', 'PT', 'QA', 'IE', 'RU', 'BL', 'SX', 'MF', 'SA', 'SG', 'SK', 'KR', 'SS', 'TW', 'TH', 'AE', 'GB', 'US', 'VN' );

        if (in_array($country, $home_counties, true)) {
            return $url . '/webapps/mpp/home';
        } elseif (in_array($country, $countries, true)) {
            return $url . '/webapps/mpp/augmint-popup';
        } else {
            return $url . '/cgi-bin/webscr?cmd=xpt/Marketing/general/WIAugmint-outside';
        }
    }

    /**
     * Get Augmint images for a country.
     *
     * @param string $country Country code.
     * @return array of image URLs
     */
    protected function get_icon_image($country)
    {
        return apply_filters('woocommerce_augmint_icon', plugin_dir_url(__FILE__) . 'assets/images/logo.png');
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     *
     * @return bool
     */
    public function is_valid_for_use()
    {
        return in_array(
            get_woocommerce_currency(),
            apply_filters(
                'woocommerce_augmint_supported_currencies',
                array( 'EUR', 'AEUR', 'A-EUR' )
            ),
            true
        );
    }

    /**
     * Admin Panel Options.
     * - Options for bits like 'title' and availability on a country-by-country basis.
     *
     * @since 1.0.0
     */
    public function admin_options()
    {
        if ($this->is_valid_for_use()) {
            parent::admin_options();
        } else {
            ?>
<div class="inline error">
    <p>
        <strong>
            <?php esc_html_e('Gateway disabled', 'augmint'); ?></strong>:
        <?php esc_html_e('Augmint does not support your store currency.', 'augmint'); ?>
    </p>
</div>
<?php
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = include 'includes/settings-augmint.php';
    }

    /**
     * Get the transaction URL.
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    public function get_transaction_url($order)
    {
        if ($this->testmode) {
            $this->view_transaction_url = 'https://www.sandbox.augmint.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        } else {
            $this->view_transaction_url = 'https://www.augmint.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
        }
        return parent::get_transaction_url($order);
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        include_once dirname(__FILE__) . '/includes/class-wc-gateway-augmint-request.php';

        $order          = wc_get_order($order_id);
        $augmint_request = new WC_Gateway_Augmint_Request($this);

        return array(
            'result'   => 'success',
            'redirect' => $augmint_request->get_request_url($order, $this->testmode),
        );
    }

    /**
     * Can the order be refunded via Augmint?
     *
     * @param  WC_Order $order Order object.
     * @return bool
     */
    public function can_refund_order($order)
    {
        return false;
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (! $this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'augmint'));
        }
    }

    /**
     * Capture payment when the order is changed from on-hold to complete or processing
     *
     * @param  int $order_id Order ID.
     */
    public function capture_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ('augmint' === $order->get_payment_method() && 'pending' === get_post_meta($order->get_id(), '_augmint_status', true) && $order->get_transaction_id()) {
        }
    }

    /**
     * Load admin scripts.
     *
     * @since 3.3.0
     */
    public function admin_scripts()
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ('woocommerce_page_wc-settings' !== $screen_id) {
            return;
        }

        wp_enqueue_script('woocommerce_augmint_admin', WC()->plugin_url() . '/includes/gateways/augmint/assets/js/augmint-admin.js', array(), WC_VERSION, true);
    }
}
