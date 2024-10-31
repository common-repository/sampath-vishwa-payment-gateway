<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
Plugin Name: Sampath Vishwa IPG
Plugin URI: www.oganro.com/plugins
Description: Sampath Vishwa Payment Gateway from Oganro (Pvt)Ltd.
Version: 1.1
Author: Oganro
Author URI: www.oganro.com
*/


/*
|-----------------------------------------------------
| Initiating Methods to run on plugin activation
|-----------------------------------------------------
*/

global $jal_db_version;
$jal_db_version = '1.0';


function sampath_vishwa_db_install() {
	
	$plugin_path = plugin_dir_path( __FILE__ );
	$file = $plugin_path.'includes/auth.php';
  	if(file_exists($file)){
  		include 'includes/auth.php';
  		$auth = new Auth();
  		$auth->check_auth();
  		if ( !$auth->get_status() ) {
  			deactivate_plugins( plugin_basename( __FILE__ ) );
			if($auth->get_code() == 2){
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/plugin/profile'>www.oganro.com/profile</a> and change the domain" ,"Activation Error","ltr" );
			}else{
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com'>www.oganro.com</a> for more info" ,"Activation Error","ltr" );
			}
		}
  	}else{
  		deactivate_plugins( plugin_basename( __FILE__ ) );
  		wp_die( "<h1>Buy serial key to activate this plugin</h1><br><img src=".site_url('wp-content/plugins/sampath_paycorp_ipg/support.jpg')." style='width:700px;height:auto;' /><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
  	}
	
	global $wpdb;
	global $jal_db_version;
	
	$table_name = $wpdb->prefix . 'sampath_vishwa_ipg';
	$charset_collate = '';
	
	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}
	
	$sql = "CREATE TABLE $table_name (
	id int(9) NOT NULL AUTO_INCREMENT,
	transaction_id VARCHAR(30) NOT NULL,
	merchant_reference_no VARCHAR(20) NOT NULL,
	transaction_type_code VARCHAR(20) NOT NULL,
	currency_code VARCHAR(10) NOT NULL,
	amount VARCHAR(20) NOT NULL,
	status VARCHAR(6) NOT NULL,
	or_date DATE NOT NULL,
	message Text NOT NULL,
	UNIQUE KEY id (id)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	add_option( 'jal_db_version', $jal_db_version );
	
}
register_activation_hook( __FILE__, 'sampath_vishwa_db_install' );


/*
|-----------------------------------------------------
| Initiating Methods to run after plugin loaded
|-----------------------------------------------------
*/
add_action('plugins_loaded', 'woocommerce_sampath_vishwa_gateway', 0);

function woocommerce_sampath_vishwa_gateway(){
	
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_SampathVishwa extends WC_Payment_Gateway{
  	
    public function __construct(){
    	
	  $plugin_dir = plugin_dir_url(__FILE__);
      $this->id = 'SampathVishwaIPG';	  
	  $this->icon = apply_filters('woocommerce_Paysecure_icon', ''.$plugin_dir.'sampath.png');
      $this->medthod_title = 'SampathVishwaIPG';
      $this->has_fields = false;
 
      $this->init_form_fields();
      $this->init_settings(); 
	  
      $this->title 					= $this -> settings['title'];
      $this->description 			= $this -> settings['description'];
      $this->payee_id 				= $this -> settings['payee_id'];
	  $this->request_mode 			= $this -> settings['request_mode'];
	  $this->currency_code 			= $this -> settings['currency_code'];	
      $this->liveurl 				= $this-> settings['pg_domain'];
      $this->verification_url 		= $this-> settings['verification_url'];
	  $this->sucess_responce_code	= $this-> settings['sucess_responce_code'];	  
	  $this->responce_url_sucess	= $this-> settings['responce_url_sucess'];
	  $this->responce_url_fail		= $this-> settings['responce_url_fail'];	  	  
	  $this->checkout_msg			= $this-> settings['checkout_msg'];	  
	   
      $this->msg['message'] 	= "";
      $this->msg['class'] 		= "";
 
      add_action('init', array(&$this, 'check_Sampath_vishwa_IPG_response_data'));	  
	  	  
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        	add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( &$this, 'process_admin_options' ) );
		} else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        
      add_action('woocommerce_receipt_SampathVishwaIPG', array(&$this, 'receipt_page'));
	 
   }
	
    function init_form_fields(){
 		
       $this -> form_fields = array(
                'enabled' 	=> array(
                    'title' 		=> __('Enable/Disable', 'ogn'),
                    'type' 			=> 'checkbox',
                    'label' 		=> __('Enable Sampath Vishwa IPG Module.', 'ognro'),
                    'default' 		=> 'no'),
					
                'title' 	=> array(
                    'title' 		=> __('Title:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('This controls the title which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Sampath Vishwa IPG', 'ognro')),
				
				'description'=> array(
                    'title' 		=> __('Description:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('This controls the description which the user sees during checkout.', 'ognro'),
                    'default' 		=> __('Sampath Vishwa IPG', 'ognro')),	
					
				'pg_domain' => array(
                    'title' 		=> __('PG Domain:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('IPG data will be submitted to this URL', 'ognro'),
                    'default' 		=> __('https://www.sampathvishwa.com/SVRClientWeb/ActionController?Action.SMPayments.Init=Y', 'ognro')),	
					
				'verification_url' => array(
                    'title' 		=> __('Verification Url:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('varification data will be submitted to this url', 'ognro'),
                    'default' 		=> __('https://www.sampathvishwa.com/SVRClientWeb/ActionController?Action.SMPayments.SMPaymentsVerify=Y', 'ognro')),	
					
				'payee_id' => array(
                    'title' 		=> __('PG Payee Id:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Unique ID for the merchant acc, given by bank.', 'ognro'),
                    'default' 		=> __('', 'ognro')),
				
				'request_mode' => array(
                    'title' 		=> __('PG Request Mode:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Indicates the transaction type, given by bank.', 'ognro'),
                    'default' 		=> __('P', 'ognro')),
				
				'currency_code' => array(
                    'title' 		=> __('currency:', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Three character ISO code of the currency such as LKR,USD. ', 'ognro'),
                    'default' 		=> __(get_woocommerce_currency(), 'ognro')),
					
				'sucess_responce_code' => array(
                    'title' 		=> __('Sucess responce code :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('Y - Transaction Passed', 'ognro'),
                    'default' 		=> __('Y', 'ognro')),	  
								
				'checkout_msg' => array(
                    'title' 		=> __('Checkout Message:', 'ognro'),
                    'type'			=> 'textarea',
                    'description' 	=> __('Message display when checkout'),
                    'default' 		=> __('Thank you for your order, please click the button below to pay with the secured Sampath Bank payment gateway.', 'ognro')),		
					
				'responce_url_sucess' => array(
                    'title' 		=> __('Sucess redirect URL :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('After payment is sucess redirecting to this page.'),
                    'default' 		=> __('http://your-site.com/thank-you-page/', 'ognro')),
				
				'responce_url_fail' => array(
                    'title' 		=> __('Fail redirect URL :', 'ognro'),
                    'type'			=> 'text',
                    'description' 	=> __('After payment if there is an error redirecting to this page.', 'ognro'),
                    'default' 		=> __('http://your-site.com/error-page/', 'ognro'))	
            );
    }
 
    //----------------------------------------
    //	Generate admin panel fields
    //----------------------------------------
	public function admin_options(){
		
			$plugin_path = plugin_dir_path( __FILE__ );
	$file = $plugin_path.'includes/auth.php';
  	if(file_exists($file)){
  		include 'includes/auth.php';
  		$auth = new Auth();
  		$auth->check_auth();
  		if ( !$auth->get_status() ) {
  			deactivate_plugins( plugin_basename( __FILE__ ) );
			if($auth->get_code() == 2){
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com/plugin/profile'>www.oganro.com/profile</a> and change the domain" ,"Activation Error","ltr" );
			}else{
				wp_die( "<h1>".ucfirst($auth->get_message())."</h1><br>Visit <a href='http://www.oganro.com'>www.oganro.com</a> for more info" ,"Activation Error","ltr" );
			}
		}
  	}else{
  		deactivate_plugins( plugin_basename( __FILE__ ) );
  		wp_die( "<h1>Buy serial key to activate this plugin</h1><br><img src=".site_url('wp-content/plugins/sampath_paycorp_ipg/support.jpg')." style='width:700px;height:auto;' /><p>Visit <a href='http://www.oganro.com/plugins'>www.oganro.com/plugins</a> to buy this plugin<p>" ,"Activation Error","ltr" );
  	}
	
		   echo '<style type="text/css">
			.wpimage {
			margin:3px;
			float:left;
			}		
			</style>';
	    	echo '<h3>'.__('Sampath Vishwa online payment gateway', 'ognro').'</h3>';
	        echo '<p>'.__('<a target="_blank" href="http://www.oganro.com">Oganro</a> is a fresh and dynamic web design and custom software development company with offices based in East London, Essex, Brisbane (Queensland, Australia) and in Colombo (Sri Lanka).').'</p>';
	        //echo'<a href="http://www.oganro.com/support-tickets" target="_blank"><img src="/wp-content/plugins/sampath-bank-ipg/plug-inimg.jpg" alt="payment gateway" class="wpimage"/></a>';
	        
	        echo '<table class="form-table">';        
	        $this->generate_settings_html();
	        echo '</table>'; 
    }
	

    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }

    //----------------------------------------
    //	Generate checkout form
    //----------------------------------------
    function receipt_page($order){        		
		global $woocommerce;
        $order_details = new WC_Order($order);
        
        echo $this->generate_ipg_form($order);		
		echo '<br>'.$this->checkout_msg.'</b>';        
    }
    	
    public function generate_ipg_form($order_id){
    	
        global $wpdb;
        global $woocommerce;
        
        $order          = new WC_Order($order_id);
		$productinfo    = "Order $order_id";		
        $currency_code  = $this -> currency_code;		
		$curr_symbole 	= get_woocommerce_currency();		
		
		
						
		$table_name = $wpdb->prefix . 'sampath_vishwa_ipg';		
		$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_reference_no = '".$order_id."'" );
        
		if($check_oder > 0){
			$wpdb->update( 
				$table_name, 
				array( 
					'transaction_id' 		=> '',					
					'transaction_type_code' 			=> $this->request_mode,
					'currency_code' 		=> $this->currency_code,
					'amount' 				=> ($order->order_total),
					'status' 				=> 0000,
					'or_date' 				=> date('Y-m-d'),
					'message' 				=> 'pending payment',
				), 
				array( 'merchant_reference_no' => $order_id ));								
		}else{
			
			$wpdb->insert(
				$table_name, 
				array( 
					'transaction_id'		=> '', 
					'merchant_reference_no'	=> $order_id, 
					'transaction_type_code'	=> $this->request_mode, 
					'currency_code'			=> $this->currency_code, 
					'amount'				=> $order->order_total, 
					'status'				=> 0000,
					'or_date' 				=> date('Y-m-d'), 
					'message'				=> 'pending payment', 
					),
					array( '%s', '%d' ) );					
		}		
		
		
		$SVR_URL = $this->liveurl;
		$SVR_PID = $this->payee_id;
		$SVR_RURL = site_url('?sampath_vishwa=y');
		//Set request mode
		$sbUrl = $SVR_URL."&MD=".$this->request_mode;
		//Set payee id
		$sbUrl .= "&PID=".$SVR_PID;
		//Set transaction reference number
		$sbUrl .= "&PRN=".$order_id; //Merchant transaction reference
		//Set amount
		$sbUrl .= "&AMT=".number_format($order->order_total,2); //Payment amount
		//Set currency
		$sbUrl .= "&CRN=LKR&RU=";
		//Set return URL
		$sbUrl .= $SVR_RURL;
       
        return '<p>'.$percentage_msg.'</p>
		<p>Total amount will be <b>'.$curr_symbole.' '.number_format($order->order_total,2).'</b></p>
			<a class="button cancel" href="'.$sbUrl.'" >'.__('Pay via Credit Card', 'ognro').'</a>
			<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'ognro').'</a>';
        
    }
    	
    function process_payment($order_id){
        $order = new WC_Order($order_id);
        return array('result' => 'success', 'redirect' => add_query_arg('order',           
		   $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
        );
    }
 
    
    //----------------------------------------
    //	Save response data and redirect
    //----------------------------------------
    function check_Sampath_vishwa_IPG_response_data(){
    	
        global $wpdb;
        global $woocommerce;
        
        
		if(isset($_REQUEST['PAID']) && isset($_REQUEST['BID'])){	
			

			$vishwaTranID = $_REQUEST['BID'];
			$merchantTranID = $_REQUEST['PRN'];
			$order_id = $merchantTranID;
			
			
			if($order_id != ''){
				
				$order 	= new WC_Order($order_id);
				
				if($this->sucess_responce_code == $_REQUEST['PAID']){
					
					
					$SVR_VERIFY_URL = $this->verification_url;
					$url = $SVR_VERIFY_URL."&MD=V"; //Mode
					$url .= "&PID=".$this->payee_id; //PID
					$url .= "&PRN=".$merchantTranID;
					$url .= "&TRN=".$vishwaTranID;
					$url .= "&AMT=".number_format($order->order_total,2);//Amount from session
					//Create connection through CURL
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HEADER, false);
					$data = curl_exec($ch);
					
					$verData = $data;
					$verDataArr = explode("\n", $data);
					$accepted = "NON";
					$verPos = 0;
					$asPos = 0;
					
					
					if(strpos($verData,"ACCEPT")){
						$asPos = strpos($verData,"ACCEPT");
					}
					
					
					if($asPos != 0){
						$verData = substr($verData,$asPos);
						if(strpos($verData,"Y")){
							//Transaction Verification Successfull
					
							$table_name = $wpdb->prefix . 'sampath_vishwa_ipg';
							$wpdb->update(
									$table_name,
									array(
											'transaction_id' => $vishwaTranID,
											'status' => $_REQUEST['PAID'],
											'message' => 'TRANSACTION SUCCESSFUL - PAYMENT VERIFIED'
									),
									array( 'merchant_reference_no' => $merchantTranID ));
							
							$order->add_order_note('Sampath Vishwa payment successful<br/>Unnique Id from Sampath IPG: '.$vishwaTranID);
							$order->add_order_note($this->msg['message']);
							$woocommerce->cart->empty_cart();
							
							$mailer = $woocommerce->mailer();
							
							$admin_email = get_option( 'admin_email', '' );
							
							$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$merchantTranID.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
							$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );
								
								
							$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$merchantTranID.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
							$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );
							
							$order->payment_complete();
							wp_redirect( $this->responce_url_sucess ); exit;
							
						}else if(strpos($verData,"N")){
							//Transaction Verification Unsuccessfull
					
							$table_name = $wpdb->prefix . 'sampath_vishwa_ipg';
							$wpdb->update(
									$table_name,
									array(
											'transaction_id' => $vishwaTranID,
											'status' => $_REQUEST['PAID'],
											'message' => 'TRANSACTION SUCCESSFUL - PAYMENT VERIFIED FAILED'
									),
									array( 'merchant_reference_no' => $merchantTranID ));
							
							$order->add_order_note('Sampath Vishwa payment successful<br/>Unnique Id from Sampath IPG: '.$vishwaTranID);
							$order->add_order_note($this->msg['message']);
							$woocommerce->cart->empty_cart();
							
							$mailer = $woocommerce->mailer();
							
							$admin_email = get_option( 'admin_email', '' );
							
							$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$merchantTranID.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
							$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );
								
								
							$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$merchantTranID.' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
							$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );
							
							$order->payment_complete();
							wp_redirect( $this->responce_url_sucess ); exit;
						}
					}
						
					
					
				}else{	
					
					global $wpdb;
                    $order->update_status('failed');
                    $order->add_order_note('Failed - Code'.$_REQUEST['PAID']);
                    $order->add_order_note($this->msg['message']);
							
					$table_name = $wpdb->prefix . 'sampath_vishwa_ipg';	
					$wpdb->update( 
					$table_name, 
					array( 
						'transaction_id' => $vishwaTranID,				
						'status' => $_REQUEST['PAID'],
						'message' => 'TRANSACTION FAILED'
					), 
					array( 'merchant_reference_no' => $merchantTranID ));
					
					wp_redirect( $this->responce_url_fail ); exit();
				}				 
			}
			
		}
    }
    
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';            
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }            
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }
    
}


	if(isset($_REQUEST['PAID']) && isset($_REQUEST['BID'])){
		$WC = new WC_SampathVishwa();
	}

   
   function woocommerce_add_sampath_vishwa_gateway($methods) {
       $methods[] = 'WC_SampathVishwa';
       return $methods;
   }
	 	
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sampath_vishwa_gateway' );
}