<?php
/*
Plugin Name: WooCommerce 3.5 AssetPayments
Plugin URI: 
Description: AssetPayments gateway for WooCommerce
Version: 3.5.5
Author: AssetPayments
Author URI: https://assetpayments.com
*/
if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', 'assetpayments_woocommerce_init', 0);

function assetpayments_woocommerce_init() {

    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_AssetPayments extends WC_Payment_Gateway
    {
        private $_checkout_url = 'https://assetpayments.us/checkout/pay';
        protected $_supportedCurrencies = array('EUR','UAH','USD','RUB','RUR','KZT');

        public function __construct() {

            global $woocommerce;

            $this->id = 'assetpayments';
            $this->has_fields = false;
            $this->method_title = __('assetpayments', 'woocommerce');
            $this->method_description = __('Payment processing platform AssetPayments', 'woocommerce');
            $this->init_form_fields();
            $this->init_settings();
            $this->public_key = $this->get_option('public_key');
            $this->private_key = $this->get_option('private_key');
            $this->template_id = $this->get_option('template_id');
			$this->status = $this->get_option('status');
			$this->redirect_page = $this->get_option('redirect_page');
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->pay_message = $this->get_option('pay_message');
			
            // Actions
            add_action('woocommerce_receipt_assetpayments', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_assetpayments', array($this, 'check_ipn_response'));

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }
		
        public function admin_options() { ?>

            <h3><?php _e('Payment processing platform AssetPayments', 'woocommerce'); ?></h3>

            <?php if ($this->is_valid_for_use()) { ?>
                <table class="form-table"><?php $this->generate_settings_html(); ?></table>
            <?php } else { ?>

                <div class="inline error">
                    <p>
                        <strong><?php _e('Шлюз отключен', 'woocommerce'); ?></strong>: <?php _e('AssetPayments не поддерживает валюты Вашего магазина.', 'woocommerce'); ?>
                    </p>
                </div>

            <?php } ?>

        <?php }

        public function init_form_fields() {

            $this->form_fields = array(
                'enabled'     => array(
                    'title'   => __('Enable/Disable', 'woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable', 'woocommerce'),
                    'default' => 'yes',
                ),
                'title'       => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Method title', 'woocommerce'),
                    'default'     => __('Card Visa/MasterCard (AssetPayments)'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Method description', 'woocommerce'),
                    'default'     => __('Pay with AssetPayments payment processing system', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'pay_message' => array(
                    'title'       => __('Message before payment', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Message before payment', 'woocommerce'),
                    'default'     => __('Thank you for your order. Please, click the button below.'),
                    'desc_tip'    => true,
                ),
                'public_key'  => array(
                    'title'       => __('Merchant ID', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Merchant ID AssetPayments. Required', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'private_key' => array(
                    'title'       => __('Secret key', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Secret key AssetPayments. Required', 'woocommerce'),
                    'desc_tip'    => true,
                ),
				'template_id' => array(
                    'title'       => __('Template ID', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Template ID. Required', 'woocommerce'),
                    'desc_tip'    => true,
                ),
				'status'     => array(
                    'title'       => __('Successful payment status', 'woocommerce'),
                    'type'        => 'text',
					'default'     => 'processing',
                    'description' => __('Статус заказа после успешной оплаты', 'woocommerce'),
                    'desc_tip'    => true,
                ),
            );
        }

        function is_valid_for_use() {
            if (!in_array(get_option('woocommerce_currency'), array('EUR','UAH','USD','RUB','RUR','KZT'))) {
                return false;
            }
            return true;
        }

        function process_payment($order_id) {
            $order = new WC_Order($order_id);
            return array(
                'result'   => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(wc_get_page_id('pay'))))
            );
        }

        public function receipt_page($order) {
            echo '<p>' . __(esc_attr($this->pay_message), 'woocommerce') . '</p><br/>';
            echo $this->generate_form($order);
        }

        public function generate_form($order_id) {

            global $woocommerce;
			$currency= get_woocommerce_currency();
			$orderdata = wc_get_order( $order_id );
			$address = $orderdata->get_billing_address_1().','.$orderdata->get_billing_city().','.$orderdata->get_billing_state().','.$orderdata->get_shipping_postcode().','.$orderdata->get_billing_country();
			
			//****Adding cart details****//
			foreach ($orderdata->get_items() as $product) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product['product_id'] ), 'single-post-thumbnail' );
			
			$request_cart['Products'][] = array(
					"ProductId" => $product['product_id'],
					"ProductName" => $product['name'],
					"ProductPrice" => $product['line_total'] / $product['quantity'],
					"ProductItemsNum" => $product['quantity'],
					"ImageUrl" => $image[0],
				);
			}
			
			//****Adding shipping method****//
			$request_cart['Products'][] = array(
					"ProductId" => '12345',
					"ProductName" => 'Delivery method: ' . ' ' . $orderdata->get_shipping_method(),
					"ProductPrice" => $orderdata->get_shipping_total(),
					"ImageUrl" => 'https://assetpayments.com/dist/css/images/delivery.png',
					"ProductItemsNum" => 1,
				);	
		
			//****Country ISO fix****//
			$country = $orderdata->get_billing_country();
			if ($country == '' || strlen($country) > 3) {
				$country = 'UKR';
			}
			
			//****Currency fix****//
			$currency = $orderdata->get_currency();
			if ($currency == 'RUR' ) {
				$currency = 'RUB';
			}
			
			//****Check WordPress & WooCommerce Version****//
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_folder = get_plugins( '/' . 'woocommerce' );
			$plugin_file = 'woocommerce.php';
			$plugin_name = $plugin_folder[$plugin_file]['Name'];
			$plugin_version = $plugin_folder[$plugin_file]['Version'];			
			global $wp_version;
			$wordpress_version = $wp_version;
			$send_version = 'Wordpress : '.$wordpress_version.' - '.$plugin_name.' : '.$plugin_version;
				
			$request = $this->cnb_form(array(
                'TemplateId' => intval($this->template_id),
				'MerchantInternalOrderId' => $orderdata->get_id(),
				'StatusURL' => add_query_arg('wc-api', 'WC_Gateway_AssetPayments', home_url('/')),
				'ReturnURL' => $orderdata->get_checkout_order_received_url(),
				'FirstName' => $orderdata->get_billing_first_name(),
				'LastName' => $orderdata->get_billing_last_name(),
				'Email' => $orderdata->get_billing_email(),
				'Phone' => $orderdata->get_billing_phone(),           
				'Address' => $address,           
				'CountryISO' => $country, 
				'Amount' => $orderdata->get_total(),
				'Currency' =>$currency,
				'AssetPaymentsKey' => $this->public_key,
				'IpAddress' => $orderdata->get_customer_ip_address(),
				'CustomMerchantInfo' => $send_version,
				'Products' => $request_cart['Products']
            ));
            return $request;

        }

		//****Form a payment request****//
        public function cnb_form($params) {

            //var_dump ($params);
			
            $data      = base64_encode( json_encode($params) );
            return sprintf('
            <form method="POST" action="https://assetpayments.us/checkout/pay" accept-charset="utf-8">
                <input type="hidden" name="data" value='.$data.' />
                <input type="submit" value="Submit payment" class="btn btn-primary" />
            </form>'
            );
        }

        //****Catch callback and signature check****//
		function check_ipn_response() {
		 global $woocommerce;
		$json = json_decode(file_get_contents('php://input'), true);

		$key = $this->public_key;
		$secret = $this->private_key;;
		$transactionId = $json['Payment']['TransactionId'];
		$signature = $json['Payment']['Signature'];
		$order_id = $json['Order']['OrderId'];
		$status = $json['Payment']['StatusCode'];

		$requestSign =$key.':'.$transactionId.':'.strtoupper($secret);
		$sign = hash_hmac('md5',$requestSign,$secret);
		
		$order = new WC_Order($order_id);
		
		if ($status == 1 && $sign == $signature) {
			 $order->update_status($this->status, __('Successful payment (AssetPayments)', 'woocommerce'));
             $order->add_order_note(__('AssetPayments TransactionID: ' .$transactionId, 'woocommerce'));
             $woocommerce->cart->empty_cart();
		} 
		if ($status == 2 && $sign == $signature) {
			$order->update_status('failed', __('Payment failed', 'woocommerce'));
            wp_redirect($order->get_cancel_order_url());
            exit;	
		}
	}
		

    }

    function assetpayments ($methods) {
        $methods[] = 'WC_Gateway_AssetPayments';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'assetpayments');

}
