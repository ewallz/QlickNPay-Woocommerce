<?php

/**
 * Qlicknpay Payment Gateway Class
 */
class Qlicknpay extends WC_Payment_Gateway {
	function __construct() {
		$this->id = "Qlicknpay";

		$this->method_title = __( "QlicknPay", 'Qlicknpay' );

		$this->method_description = __( "QlicknPay Payment Gateway Plug-in for WooCommerce", 'Qlicknpay' );

		$this->title = __( "QlicknPay - Pay with internet banking (FPX) or credit / debit cards", 'Qlicknpay' );

		// $this->icon = 'https://www.Qlicknpay.com/merchant/assets/images/pdtlogonew.png';

		$this->has_fields = true;

		$this->init_form_fields();

		$this->init_settings();

		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'check_Qlicknpay_response' ) );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}

		add_action( 'woocommerce_receipt_' . $this->id, array(
        $this,
        'pay_for_order'
    ) );




	}

	# Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable / Disable', 'Qlicknpay' ),
				'label'   => __( 'Enable this payment gateway', 'Qlicknpay' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'payment_gateway_status' => array(
		     'title' => 'Payment Gateway Status',
		     'description' => 'Improtant: Please make sure you select the correct status.',
		     'type' => 'select',
		     'default' => '1',
		     'label' => 'Label', // checkbox only
		     'options' => array(
		          '1' => 'Demo',
							'2' => 'Live Production'
		     ) // array of options for select/multiselects only
			),
			'universal_form' => array(
				'title'    => __( 'Merchant ID', 'Qlicknpay' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the merchant ID that you can obtain from profile page in QlicknPay', 'Qlicknpay' ),
			),
			'secretkey'      => array(
				'title'    => __( 'API Key', 'Qlicknpay' ),
				'type'     => 'text',
				'desc_tip' => __( 'This is the API key that you can obtain from profile page in QlicknPay profile', 'Qlicknpay' ),
			),
			'title' => array(
				'title' => __( 'Title', 'Qlicknpay' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'Qlicknpay' ),
				'default' => __( 'QlicknPay - Pay with internet banking (FPX) or credit / debit cards', 'Qlicknpay' ),
				'desc_tip'      => true,
				),
			'description' => array(
			'title' => __( 'Payment Description', 'Qlicknpay' ),
			'type' => 'textarea',
			'default' => ''
			)
		);
	}

	public function admin_options() {
		?>
		<h3><?php _e( 'Custom Payment Settings', 'woocommerce-other-payment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
					<div id="postbox-container-1" class="postbox-container">
	                        <div id="side-sortables" class="meta-box-sortables ui-sortable">

     							<div class="postbox ">
	                                <div class="handlediv" title="Click to toggle"><br></div>
	                                <h3 class="hndle"><span align="center">&nbsp;&nbsp;QlicknPay <br>
																		Payment. Collection. Recovery.</span></h3>
	                                <div class="inside">
	                                    <div class="support-widget">
	                                        <ul>
	                                            <li>» Pay Bills & Collect Faster</li>
	                                            <li>» Instant Settlements From Your Online Sales</li>
	                                            <li>» Collect Your Recurring Payments On Time</li>
	                                        </ul>
																					<a href="https://www.Qlicknpay.com/" class="button wpruby_button" target="_blank">Register Now</a>
	                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                    </div>
                    </div>
				</div>
				<div class="clear"></div>
				<style type="text/css">
				.wpruby_button{
					background-color:#f3b007 !important;
					border-color:#f3b007 !important;
					color:#ffffff !important;
					width:100%;
					padding:5px !important;
					text-align:center;
					height:35px !important;
					font-size:12pt !important;
				}
				</style>
				<?php
	}

	# Submit payment
	public function process_payment( $order_id ) {
		# Get this order's information so that we know who to charge and how much


		$order = wc_get_order($order_id);
		$order->update_status('pending');

		return array(
			'result'   => 'success',
			'redirect' => './wc-api/Qlicknpay/?submit_data=true&order_id='.$order_id
		);
	}


	public function check_Qlicknpay_response() {

		$submit_data = sanitize_text_field($_GET['submit_data']);

		if(isset($submit_data) && $submit_data == 'true')
		{
			$order_id = sanitize_text_field($_GET['order_id']);

			$customer_order = wc_get_order( $order_id );

			$x = 1;
			$old_wc = version_compare( WC_VERSION, '3.0', '<' );

			foreach ($customer_order->get_items() as $item_id => $item_data)
			{


	    // Get an instance of corresponding the WC_Product object
			$product = $old_wc ? $item_data->product : $item_data->get_product();
			$product_name = $old_wc ? $item_data['name'] : $product->get_name();
			$product_quantity = $old_wc ? $customer_order->get_item_meta($item_id, '_qty', true) : $item_data->get_quantity();
			$product_total = $old_wc ? $customer_order->get_item_meta($item_id, '_line_total', true) : $item_data->get_total();

			$product_id = $item_data['product_id'];

			$products = wc_get_product( $product_id );

			$product_image = $products->get_image();

			preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $product_image, $match);

			$path = parse_url($match[0][0], PHP_URL_PATH);

				if(!isset($prod) || $prod == '')
				{
					$prod = array(
						"pn$x" => $product_name,
						"pq$x" => $product_quantity,
						"pt$x" => $product_total,
						"pi$x" => $path
					);
				}

				else
				{
					$prod["pn$x"] = $product_name;
					$prod["pq$x"] = $product_quantity;
					$prod["pt$x"] = $product_total;
					$prod["pi$x"] = $path;

				}
				$x++;
			}

			# Prepare the data to send to Qlicknpay
			$detail = "Your order is : ".$order_id;

			$order_id = sanitize_text_field($old_wc ? $customer_order->id : $customer_order->get_id());
			$amount = number_format($old_wc ? $customer_order->order_total : $customer_order->get_total(), 2);
			$name = sanitize_text_field($old_wc ? $customer_order->billing_first_name . ' ' . $customer_order->billing_last_name : $customer_order->get_billing_first_name() . ' ' . $customer_order->get_billing_last_name());
			$email = sanitize_email($old_wc ? $customer_order->billing_email : $customer_order->get_billing_email());
			$phone = sanitize_text_field($old_wc ? $customer_order->billing_phone : $customer_order->get_billing_phone());
			$address_1 = sanitize_text_field($old_wc ? $customer_order->shipping_address_1 : $customer_order->get_billing_address_1());
			$address_2 = sanitize_text_field($old_wc ? $customer_order->shipping_address_2 : $customer_order->get_billing_address_2());
			$city = sanitize_text_field($old_wc ? $customer_order->shipping_city : $customer_order->get_billing_city());
			$state = sanitize_text_field($old_wc ? $customer_order->shipping_state : $customer_order->get_billing_state());
			$postcode = sanitize_text_field($old_wc ? $customer_order->shipping_postcode : $customer_order->get_billing_postcode());
			$order_notes = sanitize_text_field($old_wc ? $customer_order->customer_note : $customer_order->get_customer_note());

			$universal_form = sanitize_text_field($this->universal_form);
			$secretkey = sanitize_text_field($this->secretkey);

			$hash_send = md5( $secretkey . $universal_form . $order_id . $amount . $detail );

			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';

			for ($i = 0; $i < 3; $i++) {
					$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			$new_order_id = $universal_form."-".$order_id."-".date("his")."-".$randomString;

			$post_args = array(
				'merchant_id'			=> $universal_form,
				'order_detail'   	=> $detail,
				'total_amount'   	=> $amount,
				'invoice' 				=> $order_id,
				'invoice_temp' 		=> $new_order_id,
				'total_item_type'	=> $x,
				'hash'    				=> $hash_send,
				'buyer_name'    	=> $name,
				'buyer_email'    	=> $email,
				'buyer_phone'    	=> $phone,
				'address_1'    		=> $address_1,
				'address_2'    		=> $address_2,
				'city'    				=> $city,
				'state'    				=> $state,
				'postcode'    		=> $postcode,
				'comment'    		=> $order_notes
			);

			$payment_gateway_status = sanitize_text_field($this->payment_gateway_status);

			if($payment_gateway_status == '1')
			{
				echo "<form action='https://www.demo.Qlicknpay.com/merchant/api/v2/woocommerce_receiver.php' id='submit_data' method='POST'>";
			}
			else
			{
				echo "<form action='https://www.Qlicknpay.com/merchant/api/v2/woocommerce_receiver.php' id='submit_data' method='POST'>";
			}

				foreach ($post_args as $key => $value) {
					echo "<input type='hidden' name='$key' value='$value' >";
				}

				foreach ($prod as $key => $value) {
					echo "<input type='hidden' name='$key' value='$value' >";
				}
			echo "</form>";
			echo "<script>document.getElementById('submit_data').submit();</script>";
		}
		else if(isset($_REQUEST['order_id']) && isset($_REQUEST['status']))
		{
			$secretkey = sanitize_text_field($this->secretkey);

			$order_id_req = sanitize_text_field($_REQUEST['order_id']);
			$status_req = sanitize_text_field($_REQUEST['status']);
			$key_req = sanitize_text_field($_REQUEST['key_'.$order_id_req]);

			$hash_received_order = md5( $secretkey . $order_id_req .  $status_req);

			if(isset($key_req) && $hash_received_order == $key_req)
			{
				$order = wc_get_order( str_replace('WC', '', $order_id_req) );

				if($status_req == 'Transaction Approved' && $order != null)
				{

					$old_wc = version_compare( WC_VERSION, '3.0', '<' );


					$order_id = sanitize_text_field($old_wc ? $order->id : $order->get_id());
					$order_total = number_format($old_wc ? $order->order_total : $order->get_total(),2);
					$first_name = sanitize_text_field($old_wc ? $order->billing_first_name : $order->get_billing_first_name());
					$last_name = sanitize_text_field($old_wc ? $order->billing_last_name : $order->get_billing_last_name());
					$billing_email = sanitize_email($old_wc ? $order->billing_email : $order->get_billing_email());
					$billing_phone = sanitize_text_field($old_wc ? $order->billing_phone : $order->get_billing_phone());
					$shipping_address_1 = sanitize_text_field($old_wc ? $order->shipping_address_1 : $order->get_billing_address_1());
					$shipping_address_2 = sanitize_text_field($old_wc ? $order->shipping_address_2 : $order->get_billing_address_2());
					$shipping_city = sanitize_text_field($old_wc ? $order->shipping_city : $order->get_billing_city());
					$state = sanitize_text_field($old_wc ? $order->shipping_state : $order->get_billing_state());
					$shipping_postcode = sanitize_text_field($old_wc ? $order->shipping_postcode : $order->get_billing_postcode());
					$order_notes = sanitize_text_field($old_wc ? $order->customer_note : $order->get_customer_note());

					$currency = get_woocommerce_currency_symbol();


					if($state == 'JHR'){$state = 'Johor';}
					else if($state == 'KDH'){$state = 'Kedah';}
					else if($state == 'KTN'){$state = 'Kelantan';}
					else if($state == 'LBN'){$state = 'Labuan';}
					else if($state == 'MLK'){$state = 'Malacca (Melaka)';}
					else if($state == 'NSN'){$state = 'Negeri Sembilan';}
					else if($state == 'PHG'){$state = 'Pahang';}
					else if($state == 'PNG'){$state = 'Penang (Pulau Pinang)';}
					else if($state == 'PRK'){$state = 'Perak';}
					else if($state == 'PLS'){$state = 'Perlis';}
					else if($state == 'SBH'){$state = 'Sabah';}
					else if($state == 'SWK'){$state = 'Sarawak';}
					else if($state == 'SGR'){$state = 'Selangor';}
					else if($state == 'TRG'){$state = 'Terengganu';}
					else if($state == 'PJY'){$state = 'Putrajaya';}
					else if($state == 'KUL'){$state = 'KUL';}

					get_header();
					echo "
					<style>
					.qnpwrapper {
						margin: 5% 30% 5% 30%;
						margin-top: 0%;
						}
					.qnptext {
						font-size: 15px;
						font-weight: bold;
						}
						/* MOBILE SIZE */
						@media only screen and (max-width: 768px) {
							.qnpwrapper {
								margin: 5% 5% 5% 5%;
								margin-top: 0%;
								}

							.qnptext {
								font-size: 10px;
								font-weight: bold;
								}
						}
					</style>
					<div class='qnpwrapper'>
						<h5><font color='grey'>Order #$order_id</font></h5>
						<h3>Thank you $first_name $last_name</h3>
						<table width='100%'>
							<tr>
								<td class='qnptext' colspan='2'><b>CUSTOMER INFORMATION</b></td>
						  </tr>
							<tr>
								<td class='qnptext'>Email</td>
								<td><small>$billing_email</small></td>
							</tr>
							<tr>
								<td class='qnptext'>Phone</td>
								<td><small>$billing_phone</small></td>
							</tr>
							<tr>
								<td class='qnptext'>Shipping Address</td>
								<td><small>$shipping_address_1<br>$shipping_address_2</small></td>
							</tr>
							<tr>
								<td class='qnptext'>City</td>
								<td><small>$shipping_city</small></td>
							</tr>
							<tr>
								<td class='qnptext'>State</td>
								<td><small>$state</small></td>
							</tr>
							<tr>
								<td class='qnptext'>Poscode</td>
								<td><small>$shipping_postcode</small></td>
							</tr>
							<tr>
								<td class='qnptext'>Customer note</td>
								<td><small>$order_notes</small></td>
							</tr>
						</table>
						<br>
						<table width='100%'>
							<tr>
								<td class='qnptext' colspan='4'><b>ORDER DETAILS</b></td>
							</tr>
							<tr>
								<td class='qnptext' >Product Name</td>
								<td class='qnptext' >Product Image</td>
								<td class='qnptext' >Product Quantity</td>
								<td class='qnptext' >Product Price</td>
							</tr>

					";
					$total = 0;
					foreach ($order->get_items() as $item_id => $item_data) {

					    // Get an instance of corresponding the WC_Product object
							if(!$old_wc)
							{
								$product = $item_data->get_product();
							}

							$product_name = $old_wc ? $item_data['name'] : $product->get_name();

							$item_quantity = $old_wc ? $order->get_item_meta($item_id, '_qty', true) : $item_data->get_quantity();
							$item_total = $old_wc ? $order->get_item_meta($item_id, '_line_total', true) : $item_data->get_total();


							$item_total = number_format( $item_total, 2 );

							$total = number_format( $total + $item_total, 2 );

							$product_id = $item_data['product_id'];

							$products = wc_get_product( $product_id );

							$item_image = $old_wc ? $products->get_image(array( 80,80 )) : $products->get_image(array( 80 ));

							echo "
							<tr>
								<td><small>$product_name</small></td>
								<td><center>$item_image</center></td>
								<td><center><small>x$item_quantity</small></center></td>
								<td><small>$currency$item_total</small></td>
							</tr>
							";
					    // Displaying this data (to check)
						}

				$final_total = number_format( $order_total , 2);
				$others = number_format( $order_total - $total, 2);

					echo "<tr>
									<td class='qnptext' colspan='3'>Subtotal</td>
									<td><small>$currency$total</small></td>
								</tr>
								<tr>
									<td class='qnptext' colspan='3'>Others</td>
									<td><small>$currency$others</small></td>
								</tr>
								<tr>
									<td class='qnptext' colspan='3'>Payment Method</td>
									<td><small><a href='https://www.Qlicknpay.com' target='_blank'><img src='https://www.Qlicknpay.com/merchant/assets/images/pdtlogonew.png' width='100'></a></small></td>
								</tr>
								<tr>
									<td class='qnptext' colspan='3'>Final Amount</td>
									<td class='qnptext' ><u>$currency$final_total</u></td>
								</tr>
					";
					echo "</table>
					</div>";
					get_footer();
					exit;
				}

				else
				{
					get_header();

					$string = wc_get_cart_url();
					echo "<div style='min-height: 50vh;'>";
					echo "<br><br><center>Sorry, your payment is failed. <a href='$string'><font color='blue'>Click here</font></a> to view your pending cart.</center>";
					echo "</div>";

					get_footer();
					exit;
				}

			}
			else if(isset($key_req) && $hash_received_order != $key_req)
			{
				get_header();

				echo "<center>Invalid data.</center>";

				get_footer();
				exit;
			}
			}

			else if ((isset( $_REQUEST['fpx_fpxTxnId'] ) || isset( $_REQUEST['paypal_trx_id'] ) || isset( $_REQUEST['mastercard_trx_id'] ) || isset( $_REQUEST['others_trx_id'] )) && isset( $_REQUEST['invoice'] ) && isset( $_REQUEST['msg'] ) && isset( $_REQUEST['hash'] ) ) {
				global $woocommerce;

				if(isset($_REQUEST['pay_method']) &&  sanitize_text_field($_REQUEST['pay_method']) == 'paypal')
				{
					$trx_id = sanitize_text_field($_REQUEST['paypal_trx_id']);
				}
				else if(isset($_REQUEST['pay_method']) &&  $_REQUEST['pay_method'] == 'mastercard')
				{
					$trx_id = sanitize_text_field($_REQUEST['mastercard_trx_id']);
				}
				else if(isset($_REQUEST['pay_method']) &&  $_REQUEST['pay_method'] == 'others')
				{
					$trx_id = sanitize_text_field($_REQUEST['others_trx_id']);

					$_REQUEST['pay_method'] = sanitize_text_field($_REQUEST['trx_txt']);
				}
				else
				{
					$trx_id = sanitize_text_field($_REQUEST['fpx_fpxTxnId']);
				}

				$is_callback = isset( $_REQUEST['invoice'] ) ? true : false;

				$order = wc_get_order( sanitize_text_field($_REQUEST['invoice']) );

				$old_wc = version_compare( WC_VERSION, '3.0', '<' );

				$order_id = $old_wc ? $order->id : $order->get_id();
				$order_notes = $old_wc ? $order->customer_note : $order->get_customer_note();

				if ( $order && $order_id != 0 ) {
					# Check if the data sent is valid based on the hash value
					$secretkey = sanitize_text_field($this->secretkey);

					$hash_value = md5( $secretkey . $trx_id . sanitize_text_field($_REQUEST['invoice']) . sanitize_text_field($_REQUEST['msg']) );

					if ( $hash_value == sanitize_text_field($_REQUEST['hash']) ) {
						if ( sanitize_text_field($_REQUEST['msg']) == 'Transaction Approved' || sanitize_text_field($_REQUEST['msg']) == 'Success' ) {
							if ( strtolower( $order->get_status() ) == 'pending' || strtolower( $order->get_status() ) == 'processing' ) {
								# only update if order is pending
								if ( strtolower( $order->get_status() ) == 'pending' ) {
									$order->payment_complete();
									$order->add_order_note( 'Payment successfully made through QlicknPay ('.sanitize_text_field($_REQUEST['pay_method']).'). Transaction reference is ' . sanitize_text_field($_REQUEST['cart_id']) );
									$order->add_order_note( 'Customer Notes: '.$order_notes);
									$order->update_status('processing');
									$woocommerce->cart->empty_cart();
								}

								if ( $is_callback ) {
									echo 'OK';
								} else {
									# redirect to order receive page
									wp_redirect( $order->get_checkout_order_received_url() );
								}

								exit();
							}
						}
						else if ( sanitize_text_field($_REQUEST['msg']) != 'Transaction Approved' ){
							if ( strtolower( $order->get_status() ) == 'pending' ) {
									$order->add_order_note( 'Payment was unsuccessful' );
									$order->update_status('failed');
									add_filter( 'the_content', 'Qlicknpay_payment_declined_msg' );
							}
						}
					} else {
						add_filter( 'the_content', 'Qlicknpay_hash_error_msg' );
					}
				}

				if ( $is_callback ) {
					echo 'OK';
					exit();
				}
			}



	}

	# Validate fields, do nothing for the moment
	public function validate_fields() {
		return true;
	}

	# Check if we are forcing SSL on checkout pages, Custom function not required by the Gateway for now
	public function do_ssl_check() {
		if ( $this->enabled == "yes" ) {
			if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
			}
		}
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 * Note: Not used for the time being
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), array( 'MYR' ) );
	}
}
