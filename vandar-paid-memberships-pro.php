<?php
/**
 * Plugin Name: Vandar Paid Memberships Pro
 * Description: Vandar payment gateway for Paid Memberships Pro
 * Author: Vandar
 * Version: 2.1.2
 * License: GPL v2.0.
 * Author URI: https://vandar.io
 * Author Email: info@vandar.ir
 * Text Domain: vandar-paid-memberships-pro
 * Domain Path: /languages/
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function vandar_pmpro_load_textdomain() {
	load_plugin_textdomain( 'vandar-paid-memberships-pro', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'vandar_pmpro_load_textdomain' );

//load classes init method
add_action( 'plugins_loaded', 'load_vandar_pmpro_class', 11 );
add_action( 'plugins_loaded', [ 'PMProGateway_Vandar', 'init' ], 12 );

function load_vandar_pmpro_class() {
	if ( class_exists( 'PMProGateway' ) ) {
		class PMProGateway_Vandar extends PMProGateway {

			public function __construct( $gateway = NULL ) {
				$this->gateway = $gateway;

				return $this->gateway;
			}

			public static function init() {
				//make sure Vandar is a gateway option
				add_filter( 'pmpro_gateways', [
					'PMProGateway_Vandar',
					'pmpro_gateways',
				] );

				//add fields to payment settings
				add_filter( 'pmpro_payment_options', [
					'PMProGateway_Vandar',
					'pmpro_payment_options',
				] );
				add_filter( 'pmpro_payment_option_fields', [
					'PMProGateway_Vandar',
					'pmpro_payment_option_fields',
				], 10, 2 );

				// Add some currencies
				add_filter( 'pmpro_currencies', [
					'PMProGateway_Vandar',
					'pmpro_currencies',
				] );

				//code to add at checkout if Vandar is the current gateway
				$gateway = pmpro_getOption( 'gateway' );
				if ( $gateway == 'vandar' ) {
					add_filter( 'pmpro_checkout_before_change_membership_level', [
						'PMProGateway_Vandar',
						'pmpro_checkout_before_change_membership_level',
					], 10, 2 );
					add_filter( 'pmpro_include_billing_address_fields', '__return_false' );
					add_filter( 'pmpro_include_payment_information_fields', '__return_false' );
					add_filter( 'pmpro_required_billing_fields', [
						'PMProGateway_Vandar',
						'pmpro_required_billing_fields',
					] );
				}

				add_action( 'wp_ajax_nopriv_vandar-ins', [
					'PMProGateway_Vandar',
					'pmpro_wp_ajax_vandar_ins',
				] );
				add_action( 'wp_ajax_vandar-ins', [
					'PMProGateway_Vandar',
					'pmpro_wp_ajax_vandar_ins',
				] );
			}

			/**
			 * Adds Iranian currencies
			 *
			 * @param $currencies
			 *
			 * @return mixed
			 */
			public static function pmpro_currencies( $currencies ) {

				$currencies['IRT'] = array(
					'name'     => __( 'Iranian Toman', 'vandar-paid-memberships-pro' ),
					'symbol'   => __( 'Toman', 'vandar-paid-memberships-pro' ),
					'position' => 'right',
				);
				$currencies['IRR'] = array(
					'name'     => __( 'Iranian Rial', 'vandar-paid-memberships-pro' ),
					'symbol'   => ' &#65020;',
					'position' => 'right',
				);

				return $currencies;
			}

			/**
			 * Make sure Vandar is in the gateways list.
			 *
			 * @since 1.0
			 */
			public static function pmpro_gateways( $gateways ) {
				if ( empty( $gateways['vandar'] ) ) {
					$gateways['vandar'] = 'Vandar';
				}

				return $gateways;
			}

			/**
			 * Get a list of payment options that the Vandar gateway needs/supports.
			 *
			 * @since 1.0
			 */
			public static function getGatewayOptions() {
				$options = [
					'vandar_api_key',
					'currency',
					'tax_rate',
				];

				return $options;
			}

			/**
			 * Set payment options for payment settings page.
			 *
			 * @since 1.0
			 */
			public static function pmpro_payment_options( $options ) {
				//get gateway options
				$vandar_options = self::getGatewayOptions();

				//merge with others.
				$options = array_merge( $vandar_options, $options );

				return $options;
			}

			/**
			 * Remove required billing fields.
			 *
			 * @since 1.8
			 */
			public static function pmpro_required_billing_fields( $fields ) {
				unset( $fields['bfirstname'] );
				unset( $fields['blastname'] );
				unset( $fields['baddress1'] );
				unset( $fields['bcity'] );
				unset( $fields['bstate'] );
				unset( $fields['bzipcode'] );
				unset( $fields['bphone'] );
				unset( $fields['bemail'] );
				unset( $fields['bcountry'] );
				unset( $fields['CardType'] );
				unset( $fields['AccountNumber'] );
				unset( $fields['ExpirationMonth'] );
				unset( $fields['ExpirationYear'] );
				unset( $fields['CVV'] );

				return $fields;
			}

			/**
			 * Display fields for Vandar options.
			 *
			 * @since 1.0
			 */
			public static function pmpro_payment_option_fields( $values, $gateway ) {
				?>
                <tr class="pmpro_settings_divider gateway gateway_vandar"
					<?php if ( $gateway != 'vandar' ): ?>
                        style="display: none;"
					<?php endif; ?>
                >
                    <td colspan="2">
						<?php _e( 'Vandar Configuration', 'vandar-paid-memberships-pro' ); ?>
                    </td>
                </tr>
                <tr class="gateway gateway_vandar"
					<?php if ( $gateway != 'vandar' ) : ?>
                        style="display: none;"
					<?php endif; ?>
                >
                    <th scope="row" valign="top">
                        <label for="vandar_api_key"><?php _e( 'API Key', 'vandar-paid-memberships-pro' ); ?>
                            :</label>
                    </th>

                    <td>
                        <input type="text" id="vandar_api_key"
                               name="vandar_api_key" size="60"
                               value="<?php echo esc_attr( $values['vandar_api_key'] ); ?>"
                        />
                    </td>
                </tr>

				<?php

			}

			/**
			 * Instead of change membership levels, send users to Vandar to pay.
			 *
			 * @since 1.8
			 */
			public static function pmpro_checkout_before_change_membership_level( $user_id, $morder ) {

				global $wpdb, $discount_code_id;

				//if no order, no need to pay
				if ( empty( $morder ) ) {
					return;
				}

				$morder->user_id = $user_id;
				$morder->saveOrder();

				//save discount code use
				if ( ! empty( $discount_code_id ) ) {
					$wpdb->query( "INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $user_id . "', '" . $morder->id . "', now())" );
				}


				$gtw_env = pmpro_getOption( 'gateway_environment' );
				$api_key = pmpro_getOption( 'vandar_api_key' );

				if ( $gtw_env == '' || $gtw_env == 'sandbox' ) {
					$sandbox = 1;
				} else {
					$sandbox = 0;
				}

				$order_id = $morder->code;
                $callback = admin_url('admin-ajax.php')."?action=vandar-ins&oid=$order_id";


				global $pmpro_currency;

				$amount = intval( $morder->subtotal );
				if ( $pmpro_currency == 'IRT' ) {
					$amount *= 10;
				}

				$data = [
					'api_key' => $api_key,
					'amount'   => $amount,
					'name'     => '',
					'phone'    => '',
					'mail'     => '',
					'desc'     => '',
					'callback_url' => $callback,
				];

				$headers = [
					'Content-Type' => 'application/json',
					'X-API-KEY'    => $api_key,
					'X-SANDBOX'    => $sandbox,
				];

				$args = [
					'body'    => json_encode( $data ),
					'headers' => $headers,
					'timeout' => 30,
				];

				$response = self::call_gateway_endpoint( 'https://ipg.vandar.io/api/v3/send', $args );

				if ( is_wp_error( $response ) ) {
					$note = $response->get_error_message();
					wp_die( $note );
					exit;
				}

				$http_status = wp_remote_retrieve_response_code( $response );
				$result      = wp_remote_retrieve_body( $response );
				$result      = json_decode( $result );


				if ( ! empty( $result->status ) && $result->status ) {
                    $payment_URL = sprintf( 'https://ipg.vandar.io/v3/%s', $result->token );
                    $morder->status = 'pending';
					$morder->saveOrder();
					wp_redirect( $payment_URL );
					exit;

				} else {
 
					$note           = sprintf( __( 'An error occurred while creating a transaction. error status: %s, error code: %s, error message: %s', 'vandar-paid-memberships-pro' ), $http_status, $result->error_code, $result->error_message );
					$morder->status = 'error';
					$morder->notes  = $note;
					$morder->saveOrder();
					wp_die( $note );
					exit;
				}

			}

			public static function pmpro_wp_ajax_vandar_ins()
            {

                if (!isset($_GET['oid']) || is_null($_GET['oid'])) {
                    die(__('The oid parameter is not set.', 'vandar-paid-memberships-pro'));
                }

                $oid = $_GET['oid'];

                $morder = NULL;
                try {
                    $morder = new MemberOrder($oid);
                    $morder->getMembershipLevel();
                    $morder->getUser();
                } catch (Exception $exception) {
                    die(__('The oid parameter is not correct.', 'vandar-paid-memberships-pro'));
                }

                $current_user_id = get_current_user_id();

                if ($current_user_id !== intval($morder->user_id)) {
                    die(__('This order does not belong to you.', 'vandar-paid-memberships-pro'));
                }

//
                $status = sanitize_text_field($_GET['status']);

                $id = sanitize_text_field($_POST['id']);
//				

                        $api_key = pmpro_getOption('vandar_api_key');

                    $trans_id = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

                    $data = [
                        'api_key' => $api_key,
                        'token' => $trans_id,
                    ];

                    $headers = [
                        'Content-Type' => 'application/json',
//						'X-API-KEY'    => $api_key,
//						'X-SANDBOX'    => $sandbox,
                    ];

                    $args = [
                        'body' => json_encode($data),
                        'headers' => $headers,
                        'timeout' => 30,
                    ];

                    $response = self::call_gateway_endpoint('https://ipg.vandar.io/api/v3/verify', $args);

                    if (is_wp_error($response)) {
                        $note = $response->get_error_message();
                        wp_die($note);
                        exit;
                    }


                    $http_status = wp_remote_retrieve_response_code($response);
                    $result = wp_remote_retrieve_body($response);
                    $result = json_decode($result);

                    if ($http_status == 200) {

                        if ($result->status == 1) {

                            if (self::do_level_up($morder, $id)) {
                                $note = sprintf(__('Payment succeeded. $trans_id: %s, status: %s, card_no: %s', 'vandar-paid-memberships-pro'), $result->transId, $result->status, $result->payment->card_no);
                                $morder->notes = $note;
                                $morder->status = 'success';
                                $morder->saveOrder();
                                $redirect = pmpro_url('confirmation', '?level=' . $morder->membership_level->id);
                                wp_redirect($redirect);
                                exit;
                            } else {

                                $note = sprintf(__('Payment failed. track_id: %s, status: %s, card_no: %s', 'vandar-paid-memberships-pro'), $result->transId, $result->status, $result->payment->card_no);

                                $morder->notes = $note;
                                $morder->status = 'cancelled';
                                $morder->saveOrder();
                                header('Location: '.pmpro_url());
                                die();

                            }
                        }

                    } else {

                        $note = sprintf(__('An errorr occurred while verifying a transaction. error status: %s, error code: %s, error message: %s', 'vandar-paid-memberships-pro'), $http_status, $result->error_code, $result->error_message);
                        $morder->status = 'error';
                        $morder->notes = $note;
                        $morder->saveOrder();
                        
                        header('Location: '.pmpro_url());

die();

                    }

                }

			public static function do_level_up( &$morder, $txn_id ) {

				global $wpdb;
				//filter for level
				$morder->membership_level = apply_filters( 'pmpro_inshandler_level', $morder->membership_level, $morder->user_id );

				//fix expiration date
				if ( ! empty( $morder->membership_level->expiration_number ) ) {
					$enddate = "'" . date( 'Y-m-d', strtotime( '+ ' . $morder->membership_level->expiration_number . ' ' . $morder->membership_level->expiration_period, current_time( 'timestamp' ) ) ) . "'";
				} else {
					$enddate = 'NULL';
				}

				//get discount code
				$morder->getDiscountCode();
				if ( ! empty( $morder->discount_code ) ) {
					//update membership level
					$morder->getMembershipLevel( TRUE );
					$discount_code_id = $morder->discount_code->id;
				} else {
					$discount_code_id = '';
				}

				//set the start date to current_time('mysql') but allow filters
				$startdate = apply_filters( 'pmpro_checkout_start_date', "'" . current_time( 'mysql' ) . "'", $morder->user_id, $morder->membership_level );

				//custom level to change user to
				$custom_level = [
					'user_id'         => $morder->user_id,
					'membership_id'   => $morder->membership_level->id,
					'code_id'         => $discount_code_id,
					'initial_payment' => $morder->membership_level->initial_payment,
					'billing_amount'  => $morder->membership_level->billing_amount,
					'cycle_number'    => $morder->membership_level->cycle_number,
					'cycle_period'    => $morder->membership_level->cycle_period,
					'billing_limit'   => $morder->membership_level->billing_limit,
					'trial_amount'    => $morder->membership_level->trial_amount,
					'trial_limit'     => $morder->membership_level->trial_limit,
					'startdate'       => $startdate,
					'enddate'         => $enddate,
				];

				global $pmpro_error;
				if ( ! empty( $pmpro_error ) ) {
					echo $pmpro_error;
					inslog( $pmpro_error );
				}

				if ( pmpro_changeMembershipLevel( $custom_level, $morder->user_id ) !== FALSE ) {
					//update order status and transaction ids
					$morder->status                      = 'success';
					$morder->payment_transaction_id      = $txn_id;
					$morder->subscription_transaction_id = '';
					$morder->saveOrder();

					//add discount code use
					if ( ! empty( $discount_code ) && ! empty( $use_discount_code ) ) {
						$wpdb->query( "INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $morder->user_id . "', '" . $morder->id . "', '" . current_time( 'mysql' ) . "')" );
					}

					//save first and last name fields
					if ( ! empty( $_POST['first_name'] ) ) {
						$old_firstname = get_user_meta( $morder->user_id, 'first_name', TRUE );
						if ( ! empty( $old_firstname ) ) {
							update_user_meta( $morder->user_id, 'first_name', $_POST['first_name'] );
						}
					}
					if ( ! empty( $_POST['last_name'] ) ) {
						$old_lastname = get_user_meta( $morder->user_id, 'last_name', TRUE );
						if ( ! empty( $old_lastname ) ) {
							update_user_meta( $morder->user_id, 'last_name', $_POST['last_name'] );
						}
					}

					//hook
					if ( version_compare( PMPRO_VERSION, '2.0', '>=' ) ) {
						do_action( 'pmpro_after_checkout', $morder->user_id, $morder ); //added $morder param in v2.0
					} else {
						do_action( 'pmpro_after_checkout', $morder->user_id );
					}

					//setup some values for the emails
					if ( ! empty( $morder ) ) {
						$invoice = new MemberOrder( $morder->id );
					} else {
						$invoice = NULL;
					}

					$user = get_userdata( intval( $morder->user_id ) );
					if ( empty( $user ) ) {
						return FALSE;
					}

					$user->membership_level = $morder->membership_level;  //make sure they have the right level info
					//send email to member
					$pmproemail = new PMProEmail();
					$pmproemail->sendCheckoutEmail( $user, $invoice );

					//send email to admin
					$pmproemail = new PMProEmail();
					$pmproemail->sendCheckoutAdminEmail( $user, $invoice );

					return TRUE;
				} else {
					return FALSE;
				}
			}

			/**
			 * Calls the gateway endpoints.
			 *
			 * Tries to get response from the gateway for 4 times.
			 *
			 * @param $url
			 * @param $args
			 *
			 * @return array|\WP_Error
			 */
			private static function call_gateway_endpoint( $url, $args ) {
				$number_of_connection_tries = 4;
				while ( $number_of_connection_tries ) {
					$response = wp_safe_remote_post( $url, $args );
					if ( is_wp_error( $response ) ) {
						$number_of_connection_tries --;
						continue;
					} else {
						break;
					}
				}

				return $response;
			}
		}
	}
}
