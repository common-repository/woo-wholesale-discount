<?php
/**
* The core plugin class.
*
* This is used to define internationalization, admin-specific hooks, and
* public-facing site hooks.
*
* @since 0.1
*
*/
 
class WwsdWholesaleDiscountClass 
{
	public function __construct()
	{
		
	}

	public static function init()
	{
		//$plugin = plugin_basename(__FILE__); 
		
		add_action('wp_enqueue_scripts', __CLASS__ . '::wwsd_wholesale_front_style');
		add_action('admin_enqueue_scripts', __CLASS__ . '::wwsd_wholesale_wp_admin_style');
		add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::wwsd_wholesale_add_settings_tab', 50);
        add_action('woocommerce_settings_tabs_wwsd_wholesale_settings', __CLASS__ . '::wwsd_wholesale_settings_tab');
        add_action('woocommerce_update_options_wwsd_wholesale_settings', __CLASS__ . '::wwsd_wholesale_update_settings');
		add_filter('woocommerce_product_data_tabs', __CLASS__ . '::wwsd_wholesale_discount_tab_tab' , 99 , 1);
		add_action('woocommerce_product_data_panels', __CLASS__ . '::wwsd_wholesale_discount_tab_fields');
		add_action('woocommerce_process_product_meta', __CLASS__ . '::wwsd_process_product_meta_fields_save'); 
		add_filter('woocommerce_cart_item_subtotal', __CLASS__ . '::wwsd_filter_cart_item_subtotal', 10, 2);
		add_filter('woocommerce_cart_product_subtotal', __CLASS__ . '::wwsd_cart_product_subtotal', 10, 4);
		add_action('woocommerce_before_calculate_totals', __CLASS__ . '::wwsd_before_calculate_totals', 10, 1); 
		add_action('woocommerce_calculate_totals', __CLASS__ .'::wwsd_modifiy_cart_subtotal_price', 10, 1);
		add_filter('woocommerce_checkout_item_subtotal', __CLASS__ . '::wwsd_filter_cart_item_subtotal', 10, 2);
		add_filter('woocommerce_cart_item_price', __CLASS__ . '::wwsd_filter_item_price' , 10, 2);
		add_filter('woocommerce_order_formatted_line_subtotal', __CLASS__ . '::wwsd_formatted_subtotal_order_price', 10, 3);
		add_action('woocommerce_checkout_update_order_meta', __CLASS__ . '::wwsd_update_order_meta_post_order');
		add_action('admin_menu', __CLASS__ . '::wwsd_wholesale_discount_submenu');
		add_action( 'woocommerce_single_product_summary', __CLASS__ . '::wwsd_woocommerce_wholesale_discount_message', 15 );
		
		//add_filter('woocommerce_get_price_html', __CLASS__ . '::change_displayed_sale_price_html', 10, 2);
		
		//--> Define default  discount type.
		if(get_option('wwsd_discount_type') == '')
		{
			update_option("wwsd_discount_type", "flat");
		}
	}
	
	public static function wwsd_woocommerce_wholesale_discount_message() 
	{
		global $wwsd_arr_wholesale_discount_values, $woocommerce;
		$product_id = wc_get_product()->get_id();
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		$arr_discount_calculated_values = $_WwsdWholesaleDiscountClass->wwsd_wholesale_discount_calculation_for_single_product_page($product_id);	
		
		$wwsd_is_discount_available = $arr_discount_calculated_values[$product_id]['wwsd_is_discount_available'];
		$wwsd_product_qty_lowest = $arr_discount_calculated_values[$product_id]['wwsd_product_qty_lowest'];
		$cat_discount_applied = $arr_discount_calculated_values[$product_id]['cat_discount_applied'];
		
		//print_r($arr_discount_calculated_values);die;
		
		if(($wwsd_is_discount_available != '' && $wwsd_is_discount_available > 0) && ($wwsd_product_qty_lowest > 0 || $cat_discount_applied > 0))
		{
			echo '<span class="wwsd-discount-available"><span class="wwsd-discount-available-span"><strong>*</strong> Wholesale Discount is available for this product if you purchase '.$wwsd_product_qty_lowest.' or more products.</span><span>';
		}
		
	}
	

	
	public static function change_displayed_sale_price_html($price, $prodObj) 
	{
		global $wwsd_arr_wholesale_discount_values, $woocommerce;
		
		if( ! is_admin() && ! $prodObj->is_type('variable'))
		{
			
			$product_id	   = $prodObj->get_id();
			$product_price = intval($prodObj->get_price());
			
			//--> Individual product Discount 
			$wwsd_saved_discount_data = get_post_meta($product_id, 'wwsd_wholesale_discount_data', true);

			$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);
			
			
			$wwsd_from_date = get_post_meta($product_id, 'wwsd_discount_from_date', true);
			if($wwsd_from_date != '') $wwsd_from_date = strtotime($wwsd_from_date);
			
			$wwsd_to_date = get_post_meta($product_id, 'wwsd_discount_to_date', true);
			if($wwsd_to_date != '') $wwsd_to_date = strtotime($wwsd_to_date);
			
			$wwsd_all_time_discount = get_post_meta($product_id, 'wwsd_all_time_discount', true);
			
			if($wwsd_enable_woo_discount == "Yes") //--> If product based discount enabled.
			{
				if(!empty($wwsd_saved_discount_data))
				{
					$allow_cat_discount = false;
					$Today_date = strtotime(date("d-m-Y"));
					
					for($i=0; $i<count($wwsd_saved_discount_data); $i++)
					{
						$woo_product_price = $product_price;
						$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
						
						if($Today_date >= $wwsd_from_date  &&  $Today_date <= $wwsd_to_date || $wwsd_all_time_discount == 'Yes')
						{
							if(get_option('wwsd_discount_type') == 'flat') 
							{
								$wwsd_prod_discount = max(0, $wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate']);
							}
							else if(get_option('wwsd_discount_type') == 'percent')
							{
								$wwsd_prod_discount = $wwsd_saved_discount_data[$i]['wwsd_percent_discount'];
							}
							
							if($wwsd_cart_product_qty >= $wwsd_product_qty && $wwsd_product_qty > $wwsd_product_qty_settings)
							{
								$wwsd_product_qty_settings = $wwsd_product_qty;
								$wwsd_prod_amount_settings = $wwsd_prod_discount; 
							}
						}
					}
				}
				
				//--> Find Discounted amount
				if(get_option('wwsd_discount_type') == 'flat')
				{
					$wwsd_prod_amount_settings = max(0, $wwsd_prod_amount_settings);
					$woo_discounted_amount = max(0, $wwsd_cart_product_price - ($wwsd_prod_amount_settings / $wwsd_cart_product_qty));
				}
				else if(get_option('wwsd_discount_type') == 'percent')
				{
					$wwsd_prod_amount_settings = min(1.0, max(0, (100.0 - round($wwsd_prod_amount_settings, 2)) / 100.0));
					$woo_discounted_amount = $wwsd_cart_product_price * $wwsd_prod_amount_settings;
				}
			
			}
			
			return $woo_discounted_amount;
		}
	}

	
	public function wwsd_activate_plugin()
	{
		if($GLOBALS['WOOCOMMERCE_WHOLESALE_DISCOUNT_VER'] != get_option('wwsd_woocommerce_wholesale_discount_curr_ver'))
		{
			$ver = get_option('wwsd_woocommerce_wholesale_discount_curr_ver');
			update_option('wwsd_woocommerce_wholesale_discount_prev_ver',$ver);
			update_option('wwsd_woocommerce_wholesale_discount_curr_ver', $GLOBALS['WOOCOMMERCE_WHOLESALE_DISCOUNT_VER']);
		}
	}
	
	public static function wwsd_wholesale_discount_submenu() 
	{
    	add_submenu_page('woocommerce', 'Wholesale Discount', 'Wholesale Discount', 'manage_options', 'wwsd-wholesale-discount',  __CLASS__ . '::wwsd_wholesale_discount_submenu_callback'); 
	}
	
	
	public static function wwsd_filter_item_price($cart_price, $objCart)
	{
		global $wwsd_arr_wholesale_discount_values, $wwsd_is_calculation_complete;
		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		
		$prodObj = $objCart['data'];
		
		if(!$objCart || !$objCart['data']) 
		{
			return $cart_price;
		}
		
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{ 
			$wwsd_arr_wholesale_discount_values = array();
			return $cart_price;
		}
		
		//print_r($prodObj);die;
		
		$product_id = $prodObj->get_id();
		
		$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);
		
		$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$product_id]['cat_discount_applied'];	//--> get Category discount status


		if($get_parent_id)
			$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
		else
			$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);
		
		if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false) //Product is disable and cat disc not applied
		{
			return $cart_price;
		}
					

		if(get_option('wwsd_discount_type') == 'flat') 
		{
			return $cart_price;
		}
		
		$quantity 			= $wwsd_arr_wholesale_discount_values[$product_id]['quantity'];
		$product_price 		= wc_price($wwsd_arr_wholesale_discount_values[$product_id]['product_price']);
		$wwsd_discounted_amount 	= $wwsd_arr_wholesale_discount_values[$product_id]['wwsd_discounted_amount'];
		
		if(get_option('wwsd_discount_type') == 'percent' && $wwsd_discounted_amount == 1.0) 
		{
			return $cart_price;
		}

		if(!$wwsd_is_calculation_complete) 
		{
			if(get_option('wwsd_discount_type') == 'flat') 
			{
				$discounted_price = wc_price($prodObj->get_price() - $wwsd_discounted_amount);
			} 
			else 
			{
				$discounted_price = wc_price($prodObj->get_price() * $wwsd_discounted_amount);
			}
		} 
		else 
		{
			$discounted_price = wc_price($prodObj->get_price());
		}
		
		return "<span>"."<span style='margin-right:5px; text-decoration:line-through;'>$product_price</span>" ."<span style='color:#3eb903; font-weight:bold;'>$discounted_price</span></span>";
		
	}
	
	
	public static function wwsd_cart_product_subtotal($product_subtotal, $prodObj, $qty, $cart_item) 
	{
		global $wwsd_arr_wholesale_discount_values, $woocommerce;
		
		if(!$prodObj || !$qty) 
		{
			return $product_subtotal;
		}
		
		
		
		//--> Check Coupon will applied or not		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{
			$wwsd_arr_wholesale_discount_values = array();
			return $product_subtotal;
		}
		
		$product_id = $prodObj->get_id();

		
		$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);
		
		$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$product_id]['cat_discount_applied'];	//--> get Category discount status


		if($get_parent_id)
			$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
		else
			$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);

		
		if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false) //Product is disable and cat disc not applied
		{
			return $product_subtotal;
		}
		
		
		$wwsd_discounted_amount = $wwsd_arr_wholesale_discount_values[$product_id]['wwsd_discounted_amount'];
		
		if(get_option('wwsd_discount_type') == 'flat')
		{
			$filtered_product_subtotal = wc_price(max(0, ($prodObj->get_price() * $qty) - $wwsd_discounted_amount));
		} 
		else
		{
			$filtered_product_subtotal = wc_price($prodObj->get_price() * $qty * $wwsd_discounted_amount);
		}
		
		return $filtered_product_subtotal;
	}


	public static function wwsd_filter_cart_item_subtotal($item_price, $cart_item) 
	{
		global $wwsd_arr_wholesale_discount_values, $woocommerce;
		
		//--> Check Coupon will applied or not		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		
		if(!$cart_item || !$cart_item['data']) 
		{
			return $item_price;
		}
		
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{
			$wwsd_arr_wholesale_discount_values = array();
			return $item_price;
		}
		
		
		$prodObj = $cart_item['data'];
		$product_id = $prodObj->get_id();
		
		$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$product_id]['cat_discount_applied'];	//--> get Category discount status
		$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);

		if($get_parent_id)
			$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
		else
			$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);

		
		if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false) //Product is disable and cat disc not applied
		{
			return $item_price;
		}
		
		$cart_original_price = $prodObj->get_price();
		$cart_quantity = $cart_item['quantity'];
		
		//print_r($wwsd_arr_wholesale_discount_values);
		
		$wwsd_discounted_amount = $wwsd_arr_wholesale_discount_values[$product_id]['wwsd_discounted_amount'];
		
		//echo "prod id => ".$product_id;
		//print_r($arr_discount_calculated_values);die;
		
		//echo "===>".$wwsd_discounted_amount;
		//echo "===>".get_option('wwsd_discount_type');
		if((get_option('wwsd_discount_type') == 'flat' && $wwsd_discounted_amount == 0 ) || (get_option('wwsd_discount_type') == 'percent' && $wwsd_discounted_amount == 1.0)) 
		{
			return $item_price;
		}
		
		if(get_option('wwsd_discount_type') == 'flat')
		{
			$txt_discounted_amount = get_woocommerce_currency_symbol() . $wwsd_discounted_amount;
		}
		else if(get_option('wwsd_discount_type') == 'percent')
		{
			$txt_discounted_amount = round((1 - $wwsd_discounted_amount) * 100, 2)."%";
			//echo "==--=>".$wwsd_discounted_amount;
		}
		
		
		return "<span>".$item_price."</span> <span class='txt-discounted-amount'><del>". get_woocommerce_currency_symbol() .$cart_original_price * $cart_quantity. "</del> <span style=\"color:#3eb903\">". $txt_discounted_amount ." off</span></span>";

	}
	
	public static function wwsd_formatted_subtotal_order_price($subtotal, $item_val, $orderObj)
	{
		global $woocommerce;
		
		if (!$subtotal || !$orderObj) 
		{
			return $subtotal;
		}
		

		//--> Check Coupon will applied or not		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{
			return $subtotal;
		}
		
		
		$prodObj = wc_get_product($item_val['product_id']);
		$order_id = $orderObj->get_id();
		$product_id = $item_val['product_id'];
		
		$wholesale_discount_values = get_post_meta($order_id, '_wwsd_order_wholesale_discount_values', true); //-->Fetch Saved discounted info before checkout
		if(!$wholesale_discount_values){return;}
		

		$wwsd_arr_wholesale_discount_values = json_decode($wholesale_discount_values, true);
		
		
		$wwsd_saved_discount_data = get_post_meta($product_id, 'wwsd_wholesale_discount_data', true);
		$get_saved_data_for_cat = get_option( 'wwsd_wholesale_discount_data_for_cat', true );

		if(!empty($wwsd_saved_discount_data) || !empty($get_saved_data_for_cat))
		{
			
			if($prodObj && $prodObj instanceof WC_Product_Variable && $item_val['variation_id']) 
			{
				$product_id = $item_val['variation_id'];
			}
			
		$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$product_id]['cat_discount_applied'];	//--> get Category discount status

		$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);

		if($get_parent_id)
			$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
		else
			$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);

		
		if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false) //Product is disable and cat disc not applied
		{
			return $subtotal;
		}
			
			$wwsd_discounted_amount = $wwsd_arr_wholesale_discount_values[$product_id]['wwsd_discounted_amount'];


			if((count($wwsd_arr_wholesale_discount_values) <= 0 || !$wwsd_discounted_amount))
			{
				return $subtotal;
			}

			$wwsd_discount_type = get_post_meta($order_id, '_wwsd_order_discount_type', true);
	
			if(($wwsd_discount_type == 'flat' && $wwsd_discounted_amount == 0 ) || ($wwsd_discount_type == 'percent' && $wwsd_discounted_amount == 1.0)) 
			{
				return $subtotal;
			}

			if($wwsd_discount_type == "flat")
			{
				$discount_msg = get_woocommerce_currency_symbol().$wwsd_discounted_amount." off";
				$discount_msg = "<span style=\"color:#3eb903\"> [".$discount_msg."]</span>";
			}
			else if($wwsd_discount_type == 'percent')
			{
				$discount_msg =  round((1 - $wwsd_discounted_amount) * 100, 2) . "% off";
				$discount_msg = "<span style=\"color:#3eb903\"> [".$discount_msg."]</span>";
			}
		}
		return "<span><span>$subtotal</span>".$discount_msg."</span>";
	}
	
	
	public static function wwsd_modifiy_cart_subtotal_price($cartObj) 
	{
		global $wwsd_arr_wholesale_discount_values, $wwsd_is_calculation_complete;
		
		if(!$wwsd_is_calculation_complete)
		{
			return;
		}
		
		//--> Check Coupon will applied or not		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{
			$wwsd_arr_wholesale_discount_values = array();
			return;
		}
		
		
		if(sizeof($cartObj->cart_contents) > 0 ) 
		{
			foreach($cartObj->cart_contents as $key => $cart_item_values) 
			{
				$prodObj = $cart_item_values['data'];
				$cart_prd_id = $prodObj->get_id();
				
				$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$cart_prd_id]['cat_discount_applied'];	//--> get Category discount status
				
				$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);
				if($get_parent_id)
					$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
				else
					$wwsd_enable_woo_discount = get_post_meta($cart_prd_id, 'wwsd_enable_woo_discount', true);
		
				if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false) 
				{
					continue;
				}
				
				$product_price = $wwsd_arr_wholesale_discount_values[$cart_prd_id]['product_price'];
				
				$cart_item_values['data']->set_price($product_price);
			}
			$wwsd_is_calculation_complete = false;
		}
	}
	
	public static function wwsd_before_calculate_totals($cartObj)
	{
		global $wwsd_arr_wholesale_discount_values, $wwsd_is_calculation_complete;
		
		//--> Check Coupon will applied or not		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		$arr_discount_calculated_values = $_WwsdWholesaleDiscountClass->wwsd_wholesale_discount_calculation();	
		
		//--> If discount calculation already done then it return.
		if($wwsd_is_calculation_complete)
		{
			return;
		}
		
		//--> Check Coupon Settings.
		if($_WwsdWholesaleDiscountClass->wwsd_check_global_coupon_status())
		{
			$wwsd_arr_wholesale_discount_values = array();
			return;
		}
		
		if(sizeof($cartObj->cart_contents) > 0 ) 
	 	{
			foreach($cartObj->cart_contents as $key => $cart_item_values) 
			{
				//$val = get_post_meta($value['product_id'], 'wwsd_wholesale_discount_data', true);
				
				$prodObj = $cart_item_values['data'];
				
				$cart_prd_id = $prodObj->get_id();
				
				$cat_discount_applied = $wwsd_arr_wholesale_discount_values[$cart_prd_id]['cat_discount_applied'];	//--> get Category discount status

				$get_parent_id = $_WwsdWholesaleDiscountClass->wwsd_get_variant_product_id($prodObj);
				if($get_parent_id)
					$wwsd_enable_woo_discount = get_post_meta($get_parent_id, 'wwsd_enable_woo_discount', true);
				else
					$wwsd_enable_woo_discount = get_post_meta($cart_prd_id, 'wwsd_enable_woo_discount', true);
		
				
				if(($wwsd_enable_woo_discount != '' && $wwsd_enable_woo_discount !== "Yes") && $cat_discount_applied == false)
				{
					continue;
				}
				
				$wwsd_discounted_amount = $wwsd_arr_wholesale_discount_values[$cart_prd_id]['wwsd_discounted_amount'];
				
				if((get_option('wwsd_discount_type') == 'flat')) 
				{
					$set_prod_cart_price = max(0, $prodObj->get_price() - ($wwsd_discounted_amount / $cart_item_values['quantity']));
				}
				else 
				{
					$set_prod_cart_price = $prodObj->get_price() * $wwsd_discounted_amount;
				}
				
				$cart_item_values['data']->set_price($set_prod_cart_price);
			}
			
			$wwsd_is_calculation_complete = true;
		}
	}
	
	
	/**
	 * Save order info for post payment processing
	*/
	public static function wwsd_update_order_meta_post_order($order_id) 
	{
		global $wwsd_arr_wholesale_discount_values;
		$wwsd_discount_type = get_option('wwsd_discount_type');

		update_post_meta($order_id, "_wwsd_order_discount_type", $wwsd_discount_type); //--> Save discount type to escape wrong calculation when futher discount type changed.
		update_post_meta($order_id, "_wwsd_order_wholesale_discount_values", json_encode($wwsd_arr_wholesale_discount_values)); //--> Save entire discount calculation
	}
	
	/**
     * alter the WooCommerce cart discount total to apply a new discount 
     *
	 * through the woocommerce_calculate_totals hook.
	 * 
     * @return void 
	 *
	 * @since 0.1
    */
	/*public function woo_calculate_cart_totals($cartObj)
	{
		global $woocommerce, $wwsd_arr_wholesale_discount_values, $wwsd_is_calculation_complete;
		
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		$_WwsdWholesaleDiscountClass->wwsd_wholesale_discount_calculation();
		
		
		if(!$wwsd_is_calculation_complete) 
		{
			return;
		}
		
		if(sizeof($cartObj->cart_contents) > 0) 
		{
			foreach($cartObj->cart_contents as $key => $cart_item_values) 
			{
				$prodObj = $cart_item_values['data'];
				$cart_prd_id = $prodObj->get_id();
				
				$original_product_price = $wwsd_arr_wholesale_discount_values[$cart_prd_id]['product_price'];
				
				$cart_item_values['data']->set_price($original_product_price);
			}
			$wwsd_is_calculation_complete = false;
		}
	}*/
	
	
	public static function wwsd_wholesale_discount_tab_fields() 
	{
   		global $woocommerce, $post;
		 
		$get_saved_data = get_post_meta($post->ID, 'wwsd_wholesale_discount_data', true);
		
		$enable_wwsd_discount = get_post_meta($post->ID, 'wwsd_enable_woo_discount', true);
		$wwsd_all_time_discount = get_post_meta($post->ID, 'wwsd_all_time_discount', true);
		$wwsd_from_date = get_post_meta($post->ID, 'wwsd_discount_from_date', true);
		$wwsd_to_date = get_post_meta($post->ID, 'wwsd_discount_to_date', true);

?>
        <!-- id below must match target registered in above wwsd_wholesale_discount_tab_tab function -->
        <div id="wwsd_wholesale_discount_tab" class="panel woocommerce_options_panel">
        <div class="wwsd_date_range_discount">
        	<input type="hidden" id="wwsd_discount_type" value="<?php echo get_option('wwsd_discount_type');?>" />
            
            <strong><?php _e('Enable Wholesale Discount', 'wwsd-wholesale-discount'); ?></strong>: <input type="checkbox" name="enable_wwsd_discount" id="enable_wwsd_discount" <?php if(isset($enable_wwsd_discount) && $enable_wwsd_discount == "Yes") echo "checked";?> />
            
        	<p style="font-weight:700; font-size:15px;"><?php _e('Validity of discount', 'wwsd-wholesale-discount'); ?></p>
            
            <p style="font-weight:700;"><label for="wwsd_all_time_discount"> <?php _e('All Time:', 'wwsd-wholesale-discount'); ?> </label><input type="checkbox" class="wwsd_all_time_discount" name="wwsd_all_time_discount" <?php if(isset($wwsd_all_time_discount) && $wwsd_all_time_discount == "Yes") echo "checked";?> /></p>
           
            <p class="wwsd_discount_date_range" id="wwsd_discount_date_range" <?php if(isset($wwsd_all_time_discount) && $wwsd_all_time_discount == "Yes") echo 'style=display:none;';?>>
        	
			<?php _e('From Date', 'wwsd-wholesale-discount'); ?>: <input type="date" class="wwsd_from_date" name="wwsd_from_date" id="wwsd_from_date" placeholder="From Date" value="<?php echo $wwsd_from_date;?>" /> 
			
			<?php _e('To Date', 'wwsd-wholesale-discount'); ?>: <input type="date" class="wwsd_to_date" name="wwsd_to_date" id="wwsd_to_date" placeholder="To Date" value="<?php echo $wwsd_to_date;?>" /></p>
        </div>

        <div id="wwsd-wholesale-append-js-row" style="display:none;">
            <div class="wwsd-wholesale-row-inner">
    
                <label>Quantity</label>
                <input type="number" name="wwsd_minimum_discount_qrt[]" min=0  value="">
<?php
                if(get_option('wwsd_discount_type') == 'flat') 
                {
                    $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                    <input type="number" name="wwsd_flat_discount_rate[]" min="1" step="any">
<?php
                }
                else if((get_option('wwsd_discount_type') == 'percent'))
                {
                    $woo_discount_symbol = "%";
?>	
                    <input type="number" name="wwsd_percent_discount[]" min="0" max="100" step="any" class="discount" value="">						
<?php						
                }
?>
                    <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
            </div>
       </div>
            
            <div class="wwsd-wholesale-cont-area">
                <div class="wwsd-wholesale-field-title">
                    <label style="font-weight:700"><?php _e('Quantity', 'wwsd-wholesale-discount'); ?></label> 
                    <label style="font-weight:700"><?php _e('Discount', 'wwsd-wholesale-discount'); ?> (<?php echo $woo_discount_symbol;?>)</label>
                    <label style="font-weight:700"><?php _e('Action', 'wwsd-wholesale-discount'); ?></label> 
                </div>
                
                <div class="wwsd-wholesale-field-settings" id="wwsd-wholesale-field-settings">
                	
<?php
					if(isset($get_saved_data) && is_array($get_saved_data))
					{
						for($i=0; $i<count($get_saved_data); $i++)
						{	
							$wwsd_percent_discount 	  = isset($get_saved_data[$i]['wwsd_percent_discount']) ? $get_saved_data[$i]['wwsd_percent_discount'] : '';
							$wwsd_flat_discount_rate  = isset($get_saved_data[$i]['wwsd_flat_discount_rate']) ? $get_saved_data[$i]['wwsd_flat_discount_rate'] : '';
							$wwsd_minimum_discount_qrt = isset($get_saved_data[$i]['wwsd_minimum_discount_qrt']) ? $get_saved_data[$i]['wwsd_minimum_discount_qrt'] : '';
?>
							<div class="wwsd-wholesale-row-inner">
                    
                                <input type="number" min="0" name="wwsd_minimum_discount_qrt[]" value="<?php echo $wwsd_minimum_discount_qrt;?>" />
<?php
                                if(get_option('wwsd_discount_type') == 'flat') 
                                {
                                    $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                    <input type="number" step="any" min="1"  name="wwsd_flat_discount_rate[]" value="<?php echo $wwsd_flat_discount_rate;?>" />
<?php
                                }
                                else if((get_option('wwsd_discount_type') == 'percent'))
                                {
                                    $woo_discount_symbol = "%";
?>                                    <input type="number" min="0" max="100" step="any" name="wwsd_percent_discount[]" value="<?php echo $wwsd_percent_discount;?>" />						
<?php						
                                }
?>
                                <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                            </div>
<?php							
						}
					}
					else
					{
?>
                        <div class="wwsd-wholesale-row-inner">

                            <input type="number" step="1" min="1" name="wwsd_minimum_discount_qrt[]" />
<?php
                            if(get_option('wwsd_discount_type') == 'flat') 
                            {
                                $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                <input type="number" step="any" min="1" name="wwsd_flat_discount_rate[]" />
<?php
                            }
                            else if((get_option('wwsd_discount_type') == 'percent'))
                            {
                                $woo_discount_symbol = "%";
?>
                                <input type="number" min="0" max="100" step="any" name="wwsd_percent_discount[]" />						
<?php
                            }
?>
                            <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                        </div>
<?php
						}
?>
                </div>
            </div>
            <a href="javascript:void(0);" class="button" id="wwsd-add-discount-slab"> <?php _e('Add Discount Slab', 'wwsd-wholesale-discount'); ?> </a>
        </div>
<?php
	}
	
	/**
	* Filter function to add a custom tab to the Products Data metabox
	*
	* @param array $settings_tabs Array
	* @return array $settings_tabs
	*
	* @since 0.1
	*/
	public static function wwsd_wholesale_discount_tab_tab($product_data_tabs) 
	{
		$product_data_tabs['wwsd-wholesale-tab'] = array(
			'label' => __('Wholesale Discount', 'wwsd-wholesale-discount'),
			'target' => 'wwsd_wholesale_discount_tab',
		);
		return $product_data_tabs;
	}
	
	
	/**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array
     * @return array $settings_tabs
	 *
	 * @since 0.1
     */
    public static function wwsd_wholesale_add_settings_tab($settings_tabs)
	{
        $settings_tabs['wwsd_wholesale_settings'] = __('Wholesale Discount', 'wwsd-wholesale-discount');
        return $settings_tabs;
    }
	
	
	/**
     * To save wholesale product data.
     *
     * @param array $post_id int
     * @return array $settings_tabs
 	 *
	 * @since 0.1
    */
	public static function wwsd_process_product_meta_fields_save($product_id)
	{
		//print_r($_POST);die;
		$wwsd_from_date 		  = sanitize_text_field($_POST['wwsd_from_date']);
		$wwsd_to_date 			  = sanitize_text_field($_POST['wwsd_to_date']);
		$wwsd_all_time_discount	  = sanitize_text_field($_POST['wwsd_all_time_discount']);
		$enable_wwsd_discount	  = sanitize_text_field($_POST['enable_wwsd_discount']);
		
		$wwsd_percent_discount    = isset($_POST['wwsd_percent_discount']) ? $_POST['wwsd_percent_discount'] : '';
		$wwsd_flat_discount_rate  = isset($_POST['wwsd_flat_discount_rate']) ? $_POST['wwsd_flat_discount_rate'] : '';
		$wwsd_minimum_discount_qrt = isset($_POST['wwsd_minimum_discount_qrt']) ? $_POST['wwsd_minimum_discount_qrt'] : '';
		
		
		$validationCheck = 0;
		
		if((get_option('wwsd_discount_type') == 'flat'))
		{
			if(is_array($wwsd_flat_discount_rate))
			{
				$wwsd_flat_discount_rate  = array_map('sanitize_text_field', wp_unslash($wwsd_flat_discount_rate));
			}
			else
			{
				$wwsd_flat_discount_rate = '';
				$validationCheck = 1;
			}
		}
		else if((get_option('wwsd_discount_type') == 'percent'))
		{
			if(is_array($wwsd_percent_discount))
			{
				$wwsd_percent_discount = array_map('sanitize_text_field', wp_unslash($wwsd_percent_discount));
			}
			else
			{
				$wwsd_percent_discount = '';
				$validationCheck = 1;
			}
		}
		
		
		$wwsd_minimum_discount_qrt = array_map( 'sanitize_text_field', wp_unslash($wwsd_minimum_discount_qrt));

		
		$enable_wwsd_discount = stripslashes($enable_wwsd_discount);
		if($enable_wwsd_discount == 'on')
		{
			$enable_wwsd_discount = 'Yes';
		}
		else
		{
			$enable_wwsd_discount = 'No';
		}
		
		//--> Discount Periods
		if($wwsd_all_time_discount == '' && ($wwsd_from_date == '' && $wwsd_to_date == ''))
		{
			$wwsd_all_time_discount = 'Yes';
		}
		elseif($wwsd_all_time_discount == 'on')
		{
			$wwsd_all_time_discount = 'Yes';
		}
		else
		{
			$wwsd_all_time_discount = 'No';
		}
		
			
	
		$woo_arr_discount = array();
		//print_r(count($wwsd_minimum_discount_qrt));
		//echo "=>".count($wwsd_minimum_discount_qrt);die;
		
		if(is_array($wwsd_minimum_discount_qrt) && $validationCheck == 0)
		{
			
			for($i=1; $i<count($wwsd_minimum_discount_qrt); $i++) // Counter set to 1 to avoid hidden discount slabs.
			{
				if($wwsd_minimum_discount_qrt[$i] != '' && (get_option('wwsd_discount_type') == 'flat' ? $wwsd_flat_discount_rate[$i] : $wwsd_percent_discount[$i]) != '')
				{
					if((get_option('wwsd_discount_type') == 'flat'))
					{
						if(isset($_POST["wwsd_flat_discount_rate"])) 
						$woo_arr_discount[] = array(
							"wwsd_flat_discount_rate" => stripslashes($wwsd_flat_discount_rate[$i]),
							"wwsd_minimum_discount_qrt" => stripslashes($wwsd_minimum_discount_qrt[$i]),
						);
					} 
					else if((get_option('wwsd_discount_type') == 'percent'))
					{
						if(isset($_POST["wwsd_percent_discount"]))
						$woo_arr_discount[] = array(
							"wwsd_percent_discount" 	=> stripslashes($wwsd_percent_discount[$i]),
							"wwsd_minimum_discount_qrt" => stripslashes($wwsd_minimum_discount_qrt[$i]),
						);
					}
				}
			}
			
			if(count($wwsd_minimum_discount_qrt) <= 1 && (get_option('wwsd_discount_type') == 'flat' ? count($wwsd_flat_discount_rate) : count($wwsd_percent_discount) <= 1))
			{
				//--> If slab is not set for a product then it will become disable.
				$enable_wwsd_discount = "No";
			}
			
			if($validationCheck == 0)
			{
				update_post_meta($product_id, 'wwsd_all_time_discount', $wwsd_all_time_discount);
				update_post_meta($product_id, 'wwsd_discount_from_date', $wwsd_from_date);
				update_post_meta($product_id, 'wwsd_discount_to_date', $wwsd_to_date);
				update_post_meta($product_id, 'wwsd_enable_woo_discount', $enable_wwsd_discount);
				update_post_meta($product_id, 'wwsd_wholesale_discount_data', $woo_arr_discount);
			}
			else
			{
				echo $ProductValidationError = 'Please set discount slabs properly.';die;
				return $ProductValidationError;
			}
		}
		
	}
	
	
	
	
	public static function wwsd_wholesale_discount_submenu_callback() 
	{
		
		if(!empty($_POST))
		{ 
			$CategoryValidationError = '';
			$validationCheck = 0;
			$arr_cat_discounted_val = array();
			$hdnTotalSlabs	= sanitize_text_field($_POST['hdnTotalSlabs']);
			$hdnTotalSlabsCounter	= sanitize_text_field($_POST['hdnTotalSlabsCounter']);
			
			if($hdnTotalSlabsCounter != '')
			{
				$hdnTotalSlabsCounterArr = explode("," ,$hdnTotalSlabsCounter);
			
				foreach($hdnTotalSlabsCounterArr as $i)
				{
					$wwsd_from_date = isset($_POST['wwsd_from_date'.$i]) ? $_POST['wwsd_from_date'.$i] : '';
					$wwsd_to_date =  isset($_POST['wwsd_to_date'.$i]) ? $_POST['wwsd_to_date'.$i] : '';
					$wwsd_all_time_discount = isset($_POST['wwsd_all_time_discount'.$i]) ? $_POST['wwsd_all_time_discount'.$i] : '';
					$enable_wwsd_discount = isset($_POST['enable_wwsd_discount'.$i]) ? $_POST['enable_wwsd_discount'.$i] : '';
					
					$wwsd_discount_categories = isset($_POST['wwsd_discount_categories'.$i]) ? $_POST['wwsd_discount_categories'.$i] : '';
					$wwsd_percent_discount = isset($_POST['wwsd_percent_discount'.$i]) ? $_POST['wwsd_percent_discount'.$i] : '';
					$wwsd_flat_discount_rate = isset($_POST['wwsd_flat_discount_rate'.$i]) ? $_POST['wwsd_flat_discount_rate'.$i] : '';
					$wwsd_minimum_discount_qrt = isset($_POST['wwsd_minimum_discount_qrt'.$i]) ? $_POST['wwsd_minimum_discount_qrt'.$i] : '';
					
					
					if(is_array($wwsd_discount_categories))
					{
						$wwsd_discount_categories = array_map('sanitize_text_field', wp_unslash($wwsd_discount_categories));
					}
					else
					{
						$wwsd_discount_categories = '';
						$validationCheck = 1;
					}
					
					
					if(get_option('wwsd_discount_type') == 'flat') 
					{
						if(is_array($wwsd_flat_discount_rate) && count($wwsd_flat_discount_rate) > 0)
						{
							$wwsd_flat_discount_rate  = array_map('sanitize_text_field', wp_unslash($wwsd_flat_discount_rate));
						}
						else
						{
							$wwsd_flat_discount_rate = '';
							$validationCheck = 1;
						}
					}
					else if((get_option('wwsd_discount_type') == 'percent'))
					{
						if(is_array($wwsd_percent_discount) && count($wwsd_percent_discount) > 0)
						{
							$wwsd_percent_discount = array_map('sanitize_text_field', wp_unslash($wwsd_percent_discount));
						}
						else
						{
							$wwsd_percent_discount = '';
							$validationCheck = 1;
						}
					}
					
					
					if(is_array($wwsd_minimum_discount_qrt))
					{
						$wwsd_minimum_discount_qrt = array_map( 'sanitize_text_field', wp_unslash($wwsd_minimum_discount_qrt));
					}
					else
					{
						$wwsd_minimum_discount_qrt = '';
						$validationCheck = 1;
					}
			
					
					if($enable_wwsd_discount == 'on')
					{
						$enable_wwsd_discount = 'Yes';
					}
					else
					{
						$enable_wwsd_discount = 'No';
					}
					
	
					//--> Discount Validity
					if($wwsd_all_time_discount == '' && ($wwsd_from_date == '' && $wwsd_to_date == ''))
					{
						$wwsd_all_time_discount = 'Yes';
					}
					elseif($wwsd_all_time_discount == 'on')
					{
						$wwsd_all_time_discount = 'Yes';
					}
					else
					{
						$wwsd_all_time_discount = 'No';
					}
					
					
					$woo_arr_discount = array();
				
					if(is_array($wwsd_minimum_discount_qrt))
					{
						for($j=0; $j<count($wwsd_minimum_discount_qrt); $j++)
						{
							if($wwsd_minimum_discount_qrt[$j] != ''  && (get_option('wwsd_discount_type') == 'flat' ? $wwsd_flat_discount_rate[$j] : $wwsd_percent_discount[$j]) != '')
							{
								if((get_option('wwsd_discount_type') == 'flat'))
								{
									if(isset($_POST["wwsd_flat_discount_rate".$i])) 
									$woo_arr_discount[] = array(
										"wwsd_flat_discount_rate" => stripslashes($wwsd_flat_discount_rate[$j]),
										"wwsd_minimum_discount_qrt" => stripslashes($wwsd_minimum_discount_qrt[$j]),
									);
								} 
								else if((get_option('wwsd_discount_type') == 'percent'))
								{
									if(isset($_POST["wwsd_percent_discount".$i]))
									$woo_arr_discount[] = array(
										"wwsd_percent_discount" 	=> stripslashes($wwsd_percent_discount[$j]),
										"wwsd_minimum_discount_qrt" => stripslashes($wwsd_minimum_discount_qrt[$j]),
									);
								}
							}
							else
							{
								$validationCheck = 1;
							}
						}
						
						$arr_cat_discounted_val[] = array("wwsd_discount_categories" => $wwsd_discount_categories, "wwsd_all_time_discount" => $wwsd_all_time_discount, "wwsd_discount_from_date" => $wwsd_from_date, "wwsd_discount_to_date" => $wwsd_to_date, "wwsd_enable_woo_discount" => $enable_wwsd_discount, "wwsd_wholesale_discount_data" => $woo_arr_discount);
					
					}
				}
			}
			
			if($validationCheck == 0 || $hdnTotalSlabs == 0)
			{
				update_option('wwsd_wholesale_discount_data_for_cat', $arr_cat_discounted_val);
			}
			else
			{
				$CategoryValidationError = "Please select category or provide data to all the discount slabs properly.";
			}
		}
		
		
		 //--> Get Category list
		
		  $taxonomy     = 'product_cat';
		  $orderby      = 'name';  
		  $show_count   = 0; 
		  $pad_counts   = 0; 
		  $hierarchical = 1; 
		  $title        = '';  
		  $empty        = false;
		
		  $args = array(
				 'taxonomy'     => $taxonomy,
				 'orderby'      => $orderby,
				 'show_count'   => $show_count,
				 'pad_counts'   => $pad_counts,
				 'hierarchical' => $hierarchical,
				 'title_li'     => $title,
				 'hide_empty'   => $empty
		  );
		  
		 $all_categories = get_categories( $args );
		 
		 foreach ($all_categories as $cat) 
		 {
			if($cat->category_parent == 0) 
			{
				$category_id = $cat->term_id;  
				$cat_name = $cat->name;    
			}       
		}
		
		//--> Get Saved Values
		$get_saved_data_for_cat = get_option('wwsd_wholesale_discount_data_for_cat', true);
		//print_r($get_saved_data_for_cat);die;

		
		$woo_discount_symbol = get_woocommerce_currency_symbol();
		
		if(is_array($get_saved_data_for_cat) && count($get_saved_data_for_cat) > 0)
		{
			$hdnTotalSlabs = count($get_saved_data_for_cat);
		}
		else
		{
			$hdnTotalSlabs = 0;
		}
		
		if(isset($hdnTotalSlabsCounter) && $hdnTotalSlabsCounter != '')
		{
			$hdnTotalSlabsCounterArr = explode(",", $hdnTotalSlabsCounter) ;
		}
		else
		{
			$hdnTotalSlabsCounter = '';
		}
		
		if(get_option('wwsd_discount_type') == 'flat') 
		{
			$woo_discount_symbol = get_woocommerce_currency_symbol();
		}
		else if((get_option('wwsd_discount_type') == 'percent'))
		{
			$woo_discount_symbol = "%";
		}
		
		if($hdnTotalSlabs > 0)
		{ 
			$hdnTotalSlabsCounter = $sep = '';
			
			for($jk = 1; $jk <= $hdnTotalSlabs; $jk++)
			{
				$hdnTotalSlabsCounter .= $sep.$jk;
				$sep = ",";
			}
		}
		else
		{
			$hdnTotalSlabsCounter = '';
		}
?>


		<h3><?php _e('Set Discount by Category', 'wwsd-wholesale-discount'); ?></h3>
        
       <?php
       	if(isset($CategoryValidationError) && $CategoryValidationError != '')
		{
	   ?>
        <div class="error fade"><?php echo $CategoryValidationError;?></div>
        <?php
		}
		?>

		<div class="tab_wrapper viv_property_tab">

        <ul class="tab_list">
            <li class="active">Discount by Category</li>
        </ul>
    
        <div class="content_wrapper">
            <div class="tab_content active animated">
               	<form name="frmCatDiscount" id="frmCatDiscount" method="post">
                	<input type="hidden" name="hdnTotalSlabs" id="hdnTotalSlabs" value="<?php echo $hdnTotalSlabs;?>" />
                	<input type="hidden" name="hdnTotalSlabsCounter" id="hdnTotalSlabsCounter" value="<?php echo $hdnTotalSlabsCounter;?>" />
                    <div id="wwsd_wholesale_discount_tab" class="panel woocommerce_options_panel">
               		<div class="panel-group" id="accordion-cat-discount">
                    
 <?php 
					
					if(isset($get_saved_data_for_cat) && is_array($get_saved_data_for_cat))
					{
						for($i=0; $i<count($get_saved_data_for_cat); $i++)
						{
							$wwsd_percent_discount = isset($get_saved_data_for_cat[$i]['wwsd_percent_discount']) ? $get_saved_data_for_cat[$i]['wwsd_percent_discount'] : '';
							$wwsd_flat_discount_rate  = isset($get_saved_data_for_cat[$i]['wwsd_flat_discount_rate']) ? $get_saved_data_for_cat[$i]['wwsd_flat_discount_rate'] : '';
							$wwsd_minimum_discount_qrt = isset($get_saved_data_for_cat[$i]['wwsd_minimum_discount_qrt']) ? $get_saved_data_for_cat[$i]['wwsd_minimum_discount_qrt'] : '';
							
							$enable_wwsd_discount = isset($get_saved_data_for_cat[$i]['wwsd_enable_woo_discount']) ? $get_saved_data_for_cat[$i]['wwsd_enable_woo_discount'] : '';
							$wwsd_all_time_discount =  isset($get_saved_data_for_cat[$i]['wwsd_all_time_discount'])?$get_saved_data_for_cat[$i]['wwsd_all_time_discount']: "";
							$wwsd_from_date = isset($get_saved_data_for_cat[$i]['wwsd_discount_from_date']) ? $get_saved_data_for_cat[$i]['wwsd_discount_from_date'] : '';;
							$wwsd_to_date = isset($get_saved_data_for_cat[$i]['wwsd_discount_to_date']) ? $get_saved_data_for_cat[$i]['wwsd_discount_to_date'] : '';
							
							$wwsd_discount_categories = isset($get_saved_data_for_cat[$i]['wwsd_discount_categories']) ? $get_saved_data_for_cat[$i]['wwsd_discount_categories'] : '';
							$get_saved_data = isset($get_saved_data_for_cat[$i]['wwsd_wholesale_discount_data']) ? $get_saved_data_for_cat[$i]['wwsd_wholesale_discount_data'] : '';

							//print_r($get_saved_data_for_cat[$i]['wwsd_discount_categories']);die;
							$j = $i + 1;


?>
							<div class="panel panel-default">
								<div class="panel-heading">
								  <h4 class="panel-title">
									<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion-cat-discount" href="#<?php echo $j;?>">
									  Category Discount Rule #<?php echo $j;?>
									</a>
								  </h4>
								</div>
								<div id="<?php echo $j;?>" class="panel-collapse collapse <?php if($j == 1){?> in <?php } ?>">
								  
								  <div class="panel-body">
									<div class="wwsd_date_range_discount">
<?php
									//print_r($wwsd_discount_categories);die;
									if(is_array($all_categories) && count($all_categories) > 0)
									{
?>
										
										<select class="form-control wwsd_discount_categories" name="wwsd_discount_categories<?php echo $j;?>[]" placeholder="Choose Category" multiple>
<?php
											foreach($all_categories as $cat) 
											{  
												$selected_cat  = '';
												$category_id   = $cat->term_id;  
												$category_name = $cat->name;    
											  
												if(is_array($wwsd_discount_categories))
												{
													if(in_array($category_id, $wwsd_discount_categories))
													{
														$selected_cat = 'selected="selected"';
													}
												}
?>
												<option value="<?php echo $category_id;?>" <?php echo $selected_cat; ?>><?php echo $category_name;?></option>
<?php
											}
?>                    
										</select>
<?php
									}
?>
									</div>
                                    
									<div class="wwsd_date_range_discount">
									   <input type="hidden" id="wwsd_discount_type<?php echo $j;?>" value="<?php echo get_option('wwsd_discount_type');?>" />
										
									   <label for="enable_wwsd_discount"> <strong><?php _e('Enable Wholesale Discount', 'wwsd-wholesale-discount'); ?></strong>:</label> <input type="checkbox" name="enable_wwsd_discount<?php echo $j;?>" class="enable_wwsd_discount" id="enable_wwsd_discount" <?php if(isset($enable_wwsd_discount) && $enable_wwsd_discount == "Yes") echo "checked";?> />
										
										<p style="font-weight:700; font-size:15px;"><?php _e('Validity of discount', 'wwsd-wholesale-discount'); ?></p>
										
										<p style="font-weight:700;">
                                            <label for="wwsd_all_time_discount1"><?php _e('All Time:', 'wwsd-wholesale-discount'); ?></label> 
                                            <input type="checkbox" class="wwsd_all_time_discount" name="wwsd_all_time_discount<?php echo $j;?>" <?php if(isset($wwsd_all_time_discount) && $wwsd_all_time_discount == "Yes") echo "checked";?> />
                                        </p>
									   
										<p class="wwsd_discount_date_range" <?php if(isset($wwsd_all_time_discount) && $wwsd_all_time_discount == "Yes") echo 'style=display:none;';?>>
										
											<?php _e('From Date', 'wwsd-wholesale-discount'); ?>: <input type="date" name="wwsd_from_date<?php echo $j;?>" class="wwsd_from_date" placeholder="From Date" value="<?php echo $wwsd_from_date;?>" /> 
                                            
                                            <?php _e('To Date', 'wwsd-wholesale-discount'); ?>: <input type="date" name="wwsd_to_date<?php echo $j;?>" class="wwsd_to_date" placeholder="To Date" value="<?php echo $wwsd_to_date;?>" />
                                        </p>
									</div>
									
									
									<div class="wwsd-wholesale-append-js-row-cont" rel="<?php echo $j;?>">
									
										<div class="wwsd-wholesale-append-js-row">
										
											<div class="wwsd-wholesale-row-inner">
												<div class="wwsd-wholesale-field-title">
                                                    <label style="max-width:200px;font-weight:700"><?php _e('Quantity', 'wwsd-wholesale-discount'); ?></label> 
                                                    <label style="max-width:200px;font-weight:700"><?php _e('Discount', 'wwsd-wholesale-discount'); ?> (<?php echo $woo_discount_symbol;?>)</label>
                                                    <label style="font-weight:700"><?php _e('Action', 'wwsd-wholesale-discount'); ?></label> 
												</div>
                                                
<?php 
                                                if(isset($get_saved_data) && is_array($get_saved_data))
                                                {
                                                    for($k=0; $k<count($get_saved_data); $k++)
                                                    {	
                                                        $wwsd_percent_discount = isset($get_saved_data[$k]['wwsd_percent_discount']) ? $get_saved_data[$k]['wwsd_percent_discount'] : '';
                                                        $wwsd_flat_discount_rate  = isset($get_saved_data[$k]['wwsd_flat_discount_rate']) ? $get_saved_data[$k]['wwsd_flat_discount_rate'] : '';
                                                        $wwsd_minimum_discount_qrt = isset($get_saved_data[$k]['wwsd_minimum_discount_qrt']) ? $get_saved_data[$k]['wwsd_minimum_discount_qrt'] : '';
?>
                                                        <div class="wwsd-wholesale-row-inner">
                                                
                                                            <input style="max-width:200px" type="number" min="0" name="wwsd_minimum_discount_qrt<?php echo $j;?>[]" value="<?php echo $wwsd_minimum_discount_qrt;?>" />
<?php
                                                            if(get_option('wwsd_discount_type') == 'flat') 
                                                            {
                                                                $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                                                <input style="max-width:200px" type="number" step="any" min="1"  name="wwsd_flat_discount_rate<?php echo $j;?>[]" value="<?php echo $wwsd_flat_discount_rate;?>" />
<?php
                                                            }
                                                            else if((get_option('wwsd_discount_type') == 'percent'))
                                                            {
                                                                $woo_discount_symbol = "%";
?>                                  <input style="max-width:200px" type="number" min="0" max="100" step="any" name="wwsd_percent_discount<?php echo $j;?>[]" value="<?php echo $wwsd_percent_discount;?>" />						
<?php						
                                                            }
?>
                                                            <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                                                        </div>
<?php							
                                                    }
                                                }
?>                                                
											</div>
										</div>
									</div>
									
									<a href="javascript:void(0);" class="button wwsd-add-discount-slab"> <?php _e('Add Discount Slab', 'wwsd-wholesale-discount'); ?> </a> <a href="javascript:void(0);" class="button wwsd-remove-discount-rule"><?php _e('Remove Discount Rule', 'wwsd-wholesale-discount'); ?></a>	
								  </div>
								</div>
						  </div>
									
			<?php 
						}
					}
			
				/*if(isset($get_saved_data) && is_array($get_saved_data))
                 {
?>
               		<div class="panel panel-default template" style="display: block;">
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#<?php echo count($get_saved_data)?>">Dynamic panel <?php echo count($get_saved_data)?></a>
                      </h4>
                    </div>
                    <div id="3" class="panel-collapse collapse">
                      <div class="panel-body">
                        <div class="wwsd-wholesale-cont-area"> 
                    
                         <div class="wwsd-wholesale-field-title">
                            <label style="max-width:200px;font-weight:700"><?php _e('Quantity', 'wwsd-wholesale-discount'); ?></label> 
                            <label style="max-width:200px;font-weight:700"><?php _e('Discount', 'wwsd-wholesale-discount'); ?> (<?php echo $woo_discount_symbol;?>)</label>
                            <label style="font-weight:700"><?php _e('Action', 'wwsd-wholesale-discount'); ?></label> 
                        </div>
                    
                        <div class="wwsd-wholesale-field-settings" id="wwsd-wholesale-field-settings">
<?php
                            if(isset($get_saved_data) && is_array($get_saved_data))
                            {
                                for($i=0; $i<count($get_saved_data); $i++)
                                {	
                                    $wwsd_percent_discount = isset($get_saved_data[$i]['wwsd_percent_discount']) ? $get_saved_data[$i]['wwsd_percent_discount'] : '';
                                    $wwsd_flat_discount_rate  = isset($get_saved_data[$i]['wwsd_flat_discount_rate']) ? $get_saved_data[$i]['wwsd_flat_discount_rate'] : '';
                                    $wwsd_minimum_discount_qrt = isset($get_saved_data[$i]['wwsd_minimum_discount_qrt']) ? $get_saved_data[$i]['wwsd_minimum_discount_qrt'] : '';
?>
                                    <div class="wwsd-wholesale-row-inner">
                            
                                        <input style="max-width:200px" type="number" min="0" name="wwsd_minimum_discount_qrt[]" value="<?php echo $wwsd_minimum_discount_qrt;?>" />
<?php
                                        if(get_option('wwsd_discount_type') == 'flat') 
                                        {
                                            $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                            <input style="max-width:200px" type="number" step="1" min="1"  name="wwsd_flat_discount_rate[]" value="<?php echo $wwsd_flat_discount_rate;?>" />
<?php
                                        }
                                        else if((get_option('wwsd_discount_type') == 'percent'))
                                        {
                                            $woo_discount_symbol = "%";
?>                                  <input style="max-width:200px" type="number" step="1" min="1" name="wwsd_percent_discount[]" value="<?php echo $wwsd_percent_discount;?>" />						
<?php						
                                        }
?>
                                        <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                                    </div>
<?php							
                                }
                            }
                            else
                            {
?>
                                <div class="wwsd-wholesale-row-inner">
        
                                    <input style="max-width:200px" type="number" step="1" min="1" name="wwsd_minimum_discount_qrt[]" />
<?php
                                    if(get_option('wwsd_discount_type') == 'flat') 
                                    {
                                        $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                        <input style="max-width:200px" type="number" step="1" min="1" name="wwsd_flat_discount_rate[]" />
<?php
                                    }
                                    else if((get_option('wwsd_discount_type') == 'percent'))
                                    {
                                        $woo_discount_symbol = "%";
?>
                                        <input style="max-width:200px" type="number" step="1" min="1" name="wwsd_percent_discount[]" />						
<?php
                                    }
?>
                                    <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                                </div>
<?php
                                }
?>
                        </div>
                        </div>
                      </div>
                    </div>
                  </div>
                 
<?php
				 }*/
?>
                  
                    </div>
               </div>
                     
                     <a class="button btn-lg btn-primary add-new-cat-discount"> <i class="glyphicon glyphicon-plus"></i> Add New Discount Rule</a>
                     <input class="button button-primary" type="submit" name="btnCatDiscount" id="submit" value="Save Discount Settings" />

                </form>
                
                
                <!--Additional New Row-->
                <div class="panel panel-default new-category-block-clone" style="display:none;">
                    <input type="hidden" name="wwsd_hdn_cat_count" />
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion-cat-discount" href="#">Category Discount Rule #1</a>
                      </h4>
                    </div>
                    <div id="" class="panel-collapse collapse">
                      <div class="panel-body">
                            <div class="wwsd_date_range_discount">
                                <select class="form-control cat-track" name="wwsd_discount_categories[]" id="wwsd_discount_categories wwsd_discount_categories" placeholder="Choose Category" multiple>
<?php
                                foreach($all_categories as $cat) 
                                {  
                                    $category_id   = $cat->term_id;  
                                    $category_name = $cat->name;    
?>
                                	<option value="<?php echo $category_id;?>"><?php echo $category_name;?></option>
<?php
                                }
?>                    
                            </select>
                            </div>
                            
                            
                            
                            <div class="wwsd_date_range_discount">
                                
                               <label for="enable_wwsd_discount"> <strong><?php _e('Enable Wholesale Discount', 'wwsd-wholesale-discount'); ?></strong>:</label> <input type="checkbox" name="enable_wwsd_discount[]" class="enable_wwsd_discount" />                                    
                                <p style="font-weight:700; font-size:15px;"><?php _e('Validity of discount', 'wwsd-wholesale-discount'); ?></p>
                                
                                <p style="font-weight:700;"><label for="wwsd_all_time_discount"><?php _e('All Time:', 'wwsd-wholesale-discount'); ?></label> <input type="checkbox" class="wwsd_all_time_discount" name="wwsd_all_time_discount[]" /></p>
                               
                                <p class="wwsd_discount_date_range">
                                
                                <?php _e('From Date', 'wwsd-wholesale-discount'); ?>: <input type="date" name="wwsd_from_date[]" class="wwsd_from_date" placeholder="From Date" /> 
                                
                                <?php _e('To Date', 'wwsd-wholesale-discount'); ?>: <input type="date" name="wwsd_to_date[]" class="wwsd_to_date" placeholder="To Date" /></p>
                            </div>
                            
                            
                            <div class="wwsd-wholesale-field-title">
                                <label style="max-width:200px;font-weight:700"><?php _e('Quantity', 'wwsd-wholesale-discount'); ?></label> 
                                <label style="max-width:200px;font-weight:700"><?php _e('Discount', 'wwsd-wholesale-discount'); ?> (<?php echo $woo_discount_symbol;?>)</label>
                                <label style="font-weight:700"><?php _e('Action', 'wwsd-wholesale-discount'); ?></label> 
                            </div>
                            
                            
                            <div class="wwsd-wholesale-append-js-row-cont" rel="">
                                <div class="wwsd-wholesale-append-js-row">
                                    <div class="wwsd-wholesale-row-inner">
                                       
                                       
                                       
                                        <input type="number" name="wwsd_minimum_discount_qrt[]" min=0  value="" class="wwsd-cat-min-dis" style="max-width:200px">
<?php
                                        if(get_option('wwsd_discount_type') == 'flat') 
                                        {
                                            $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                                            <input type="number" name="wwsd_flat_discount_rate[]" class="wwsd-cat-flat-dis" step="any" min="1" style="max-width:200px">
<?php
                                        }
                                        else if((get_option('wwsd_discount_type') == 'percent'))
                                        {
                                            $woo_discount_symbol = "%";
?>	
                                            <input type="number" name="wwsd_percent_discount[]" min="0" max="100" step="any" class="discount wwsd-cat-percent-dis" value="" style="max-width:200px">						
<?php						
                                        }
?>
                                        <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                                    </div>
                                </div>
                            </div>
                            
                            <a href="javascript:void(0);" class="button wwsd-add-discount-slab"> <?php _e('Add Discount Slab', 'wwsd-wholesale-discount'); ?> </a> <a href="javascript:void(0);" class="button wwsd-remove-discount-rule"><?php _e('Remove Discount Rule', 'wwsd-wholesale-discount'); ?></a>	

                      </div>
                    </div>
                  </div>

                <div id="wwsd-wholesale-append-js-row-hide" style="display:none;">
                <div class="wwsd-wholesale-append-js-row">
                    <div class="wwsd-wholesale-row-inner">
                        <input type="number" class="wwsd-cat-min-dis" name="wwsd_minimum_discount_qrt[]" min=0  value="">
<?php
                        if(get_option('wwsd_discount_type') == 'flat') 
                        {
                            $woo_discount_symbol = get_woocommerce_currency_symbol();
?>
                            <input type="number" name="wwsd_flat_discount_rate[]" class="wwsd-cat-flat-dis" step="any" min="1">
<?php
                        }
                        else if((get_option('wwsd_discount_type') == 'percent'))
                        {
                            $woo_discount_symbol = "%";
?>	
                            <input type="number" name="wwsd_percent_discount[]" min="0" max="100" step="any" class="discount wwsd-cat-percent-dis" value="">						
<?php						
                        }
?>
                            <a href="javascript:void(0);" class="button remove-discount-slab"><?php _e('Remove', 'wwsd-wholesale-discount'); ?></a>	
                    </div>
               </div>
               </div>

            </div><!--End Tab Content 1-->
    
            <!--End Tab Content 2-->
    
        </div>
    </div>
  
<?php	
	}
	

    /**
     * Output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
 	 *
	 * @since 0.1
    */
    public static function wwsd_wholesale_settings_tab() {
        woocommerce_admin_fields(self::get_settings());
    }
	
	
	 /**
     * WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
 	 *
	 * @since 0.1
    */
    public static function wwsd_wholesale_update_settings() {
        woocommerce_update_options(self::get_settings());
    }
	
	/**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
	 *
	 * @since 0.1
     */
    public static function get_settings() {
        $settings = array(
           
		    'section_title' => array(
                'name'     => __('Wholesale Discount Settings for Woocommerce', 'wwsd-wholesale-discount'),
                'type'     => 'title',
                'desc'     => '<span style="color:#F00">Note: While changing "<strong>Discount Type</strong>", you have to provide discount values to all the discount rules (categories and products).</span>',
                'id'       => 'wwsd_wholesale_settings_section_title'
           ),
           
		   'discount_type' => array(
				'title' => __('Discount Type', 'wwsd-wholesale-discount'),
				'id' => 'wwsd_discount_type',
				'std' => 'yes',
				'type' => 'select',
				'options' => array(
					'flat' => __('Flat', 'wwsd-wholesale-discount'),
					'percent' => __('Percentage', 'wwsd-wholesale-discount'),
					
				)
           ),
			
            'discount_on_coupon' => array(
                'title' => __('Apply Wholesale Discount along with discount coupon?', 'wwsd-wholesale-discount'),
				'css' => 'max-width: 150px;',
				'id' => 'wwsd_discount_on_coupon',
				'std' => 'yes',
				'type' => 'select',
				'options' => array(
					'Yes' => __('Yes', 'wwsd-wholesale-discount'),
					'No' => __('No', 'wwsd-wholesale-discount'),
				)
           ),
		   
		   
		    'calculate_variation_separately' => array(
                'title' => __('Combined product variations while applying Wholesale Discount?', 'wwsd-wholesale-discount'),
				'css' => 'max-width: 150px;',
				'id' => 'wwsd_calculate_variation_separately',
				'std' => 'yes',
				'type' => 'select',
				'options' => array(
					'Yes' => __('Yes', 'wwsd-wholesale-discount'),
					'No' => __('No', 'wwsd-wholesale-discount'),
				)
           ),
			
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wwsd_wholesale_settings_section_end'
           )
       );
       
	   return apply_filters('wwsd_wholesale_settings_settings', $settings);
    }
	
	/**
     * Register scripts for backend
     *
	 *
	 * @since 0.1
    */
	public static function wwsd_wholesale_wp_admin_style()
	{
		wp_register_script('wwsd-custom-script', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/js/woo-javascript.js');
		wp_enqueue_script('wwsd-custom-script');

		wp_register_script('wwsd-tab-setting', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/js/woo-tab-settings.js');
		wp_enqueue_script('wwsd-tab-setting');

		wp_register_script('wwsd-cat-choices', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/js/woo-cat-choices.js');
		wp_enqueue_script('wwsd-cat-choices');
		
		wp_register_script('wwsd-choosecat-js', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/js/woo-choose.cat.min.js');
		wp_enqueue_script('wwsd-choosecat-js');

		wp_enqueue_style('custom_cat_choices_css', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/css/woo-cat-choices.css');
		wp_enqueue_script('custom_cat_choices_css');
		
		wp_enqueue_style('custom_tab_setting_css', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/css/woo-tab-style.css');
		wp_enqueue_script('custom_tab_setting_css');
		
		wp_enqueue_style('wwsd_choosecat_css', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/css/woo-choose.cat.min.css');
		wp_enqueue_script('wwsd_choosecat_css');
		
		wp_enqueue_style('custom_wp_admin_css', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/css/woo-admin.css');
		wp_enqueue_script('jquery');
	}

	
	/**
     * Register scripts for front end
     *
	 *
	 * @since 0.1
    */
	public static function wwsd_wholesale_front_style() 
	{
		wp_register_style('custom_wp_front_css', WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH.'assets/css/woo-front.css');
		wp_enqueue_style('custom_wp_front_css');
	}
	
	public static function wwsd_check_global_coupon_status()
	{
		global $woocommerce;
		if(get_option( 'wwsd_discount_on_coupon') == 'Yes') return false;
		return !(empty($woocommerce->cart->applied_coupons));
	}
	
	public static function wwsd_get_variant_product_id($prodObj)
	{
		return $prodObj->get_parent_id();
	}
	
	
	/*
		Main Function for Discount (Product/Category)
	*/
	public static function wwsd_wholesale_discount_calculation()
	{
		global $woocommerce, $wwsd_arr_wholesale_discount_values, $wwsd_is_calculation_complete, $allow_cat_discount;
		$wwsd_is_calculation_complete = false;
		$_WwsdWholesaleDiscountClass = new WwsdWholesaleDiscountClass;
		
		
		foreach(WC()->cart->get_cart() as $key => $cart_item_values) 
		{
		    $allow_cat_discount = 0;
			$wwsd_cart_product_qty = 0;
			$wwsd_product_qty_settings = 0;
			$wwsd_prod_amount_settings = 0.00;

			$wwsdProductObj = $cart_item_values['data'];

			$wwsd_cart_product_id 		= $wwsdProductObj->get_id();
			$wwsd_discount_cal_prod_id 	= $wwsdProductObj->get_id();
			$wwsd_cart_product_price 	= $wwsdProductObj->get_price();
			
			
			
			//$wwsd_saved_discount_data = get_post_meta($cart_item_values['product_id'], 'wwsd_wholesale_discount_data', true);
			
			//--> Check Product has Variation or Not.
			$wwsd_cart_product_id = $wwsdProductObj->get_parent_id();

			if(get_option('wwsd_calculate_variation_separately') == 'Yes' && isset($cart_item_values['variation']) && count($cart_item_values['variation']) > 0 && wc_get_product($wwsdProductObj->get_parent_id()))
			 { 
			 	//$wwsd_discount_cal_prod_id 	= $wwsdProductObj->get_parent_id();
				$wwsd_cart_product_id	= $wwsdProductObj->get_parent_id();
				$wwsd_parent_product	= wc_get_product($wwsdProductObj->get_parent_id());
				
				foreach(WC()->cart->get_cart() as $variation_cart_val) 
				{	
					$varProductObj = $variation_cart_val['data'];
					$objParProduct = wc_get_product($varProductObj->get_parent_id());

					if(isset($variation_cart_val['variation']) && count($variation_cart_val['variation']) > 0 && 
						wc_get_product($varProductObj->get_parent_id()) && 
						$objParProduct->get_id() == $wwsd_parent_product->get_id()) 
						{
							$wwsd_cart_product_qty += $variation_cart_val['quantity'];	
						}
				}
			 }
			 else 
			 {	
			 	$wwsd_cart_product_qty = $cart_item_values['quantity'];
			 }
			
			//$wwsd_discounted_amount = 0;
			//echo $wwsd_discount_cal_prod_id;
			
			//--> Individual product Discount 
			$wwsd_saved_discount_data = get_post_meta($cart_item_values['product_id'], 'wwsd_wholesale_discount_data', true);

			$wwsd_enable_woo_discount = get_post_meta($cart_item_values['product_id'], 'wwsd_enable_woo_discount', true);
			
			
			$wwsd_from_date = get_post_meta($cart_item_values['product_id'], 'wwsd_discount_from_date', true);
			if($wwsd_from_date != '') $wwsd_from_date = strtotime($wwsd_from_date);
			
			$wwsd_to_date = get_post_meta($cart_item_values['product_id'], 'wwsd_discount_to_date', true);
			if($wwsd_to_date != '') $wwsd_to_date = strtotime($wwsd_to_date);
			
			$wwsd_all_time_discount = get_post_meta($cart_item_values['product_id'], 'wwsd_all_time_discount', true);
			
			if($wwsd_enable_woo_discount == "Yes") //--> If product based discount enabled.
			{
				if(!empty($wwsd_saved_discount_data))
				{
					$allow_cat_discount = false;
					$Today_date = strtotime(date("d-m-Y"));

					for($i=0; $i<count($wwsd_saved_discount_data); $i++)
					{
						$woo_product_price = intval($cart_item_values['data']->get_price());
						$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
						
						if(($Today_date >= $wwsd_from_date  &&  $Today_date <= $wwsd_to_date) || $wwsd_all_time_discount == 'Yes')
						{
							if(get_option('wwsd_discount_type') == 'flat') 
							{
								$wwsd_flat_discount_rate = isset($wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate']) ? $wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate'] : '';
								$wwsd_prod_discount = max(0, $wwsd_flat_discount_rate);
							}
							else if(get_option('wwsd_discount_type') == 'percent')
							{
								$wwsd_percent_discount = isset($wwsd_saved_discount_data[$i]['wwsd_percent_discount']) ? $wwsd_saved_discount_data[$i]['wwsd_percent_discount'] : '';
								$wwsd_prod_discount = $wwsd_percent_discount;
							}
							
							if($wwsd_cart_product_qty >= $wwsd_product_qty && $wwsd_product_qty > $wwsd_product_qty_settings)
							{
								$wwsd_product_qty_settings = $wwsd_product_qty;
								$wwsd_prod_amount_settings = $wwsd_prod_discount; 
							}
							
						}
						else
						{
							$wwsd_enable_woo_discount = "No";
						}
						
					}
				}
				
				//--> Find Discounted amount
				if(get_option('wwsd_discount_type') == 'flat')
				{
					$wwsd_prod_amount_settings = max(0, $wwsd_prod_amount_settings);
					$woo_discounted_amount = max(0, $wwsd_cart_product_price - ($wwsd_prod_amount_settings / $wwsd_cart_product_qty));
				}
				else if(get_option('wwsd_discount_type') == 'percent')
				{
					$wwsd_prod_amount_settings = min(1.0, max(0, (100.0 - round($wwsd_prod_amount_settings, 2)) / 100.0));
					$woo_discounted_amount = $wwsd_cart_product_price * $wwsd_prod_amount_settings;
				}
			}
			
			
			
			/** Discount By Category Start **/
			
			$get_saved_data_for_cat = get_option('wwsd_wholesale_discount_data_for_cat', true);
			
			//print_r($get_saved_data_for_cat);die;
			
			if($wwsd_enable_woo_discount == "No") //--> If product based discount not taken place then apply category discount.
			{
				if(isset($get_saved_data_for_cat) && is_array($get_saved_data_for_cat))
				{
					
					$effected_amount_settings = 0;
					//$check_already_applied = 0;
					$wwsd_prod_discount = 0;
					for($kk=0; $kk<count($get_saved_data_for_cat); $kk++)
					{
						//$wwsd_prod_amount_settings = 0.00;
						$enable_wwsd_discount = $get_saved_data_for_cat[$kk]['wwsd_enable_woo_discount'];
						
						
						if($enable_wwsd_discount == "Yes") //--> Category Based Discount is enable.
						{
							$wwsd_saved_discount_data = $get_saved_data_for_cat[$kk]['wwsd_wholesale_discount_data'];
							$wwsd_all_time_discount =  $get_saved_data_for_cat[$kk]['wwsd_all_time_discount'];
							
							$wwsd_from_date = $get_saved_data_for_cat[$kk]['wwsd_discount_from_date'];
							if($wwsd_from_date != '') $wwsd_from_date = strtotime($wwsd_from_date);
				
							$wwsd_to_date = $get_saved_data_for_cat[$kk]['wwsd_discount_to_date'];
							if($wwsd_to_date != '') $wwsd_to_date = strtotime($wwsd_to_date);
							
							$wwsd_discount_categories = $get_saved_data_for_cat[$kk]['wwsd_discount_categories'];
							
							if(!empty($wwsd_saved_discount_data))
							{
								$Today_date = strtotime(date("d-m-Y"));
								
								for($i=0; $i<count($wwsd_saved_discount_data); $i++)
								{
									//$get_matched_cat_vals = array();
									$get_cat_ids = array();
									$woo_product_price = intval($cart_item_values['data']->get_price());
									$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
									
									//--> Check Product variation for discount
									if(($cart_item_values['variation']) && count($cart_item_values['variation']) > 0 && wc_get_product($wwsdProductObj->get_parent_id()))
									{ 
										$wwsd_cart_product_id	= $wwsdProductObj->get_parent_id();
										$get_terms_list = get_the_terms($wwsd_cart_product_id, 'product_cat');
									}
									else
									{
										$get_terms_list = get_the_terms($wwsd_discount_cal_prod_id, 'product_cat');
									}
									
									
									if(is_array($get_terms_list))
									{
										foreach($get_terms_list as $term)
										{
											$get_cat_ids[] = $term->term_id;
										}
									}
									
									
									if(count($wwsd_discount_categories) > 0)
									{
										if(is_array($wwsd_discount_categories) && is_array($get_cat_ids))
										{ 
											$get_matched_cat_vals = array_intersect($wwsd_discount_categories, $get_cat_ids); 
										}
									}
									
									if((($Today_date >= $wwsd_from_date && $Today_date <= $wwsd_to_date) || $wwsd_all_time_discount == 'Yes') && sizeof($get_matched_cat_vals) > 0)
									{
										if(get_option('wwsd_discount_type') == 'flat' && isset($wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate'])) 
										{
											$wwsd_flat_discount_rate = isset($wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate']) ? $wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate'] : '';
											$wwsd_prod_discount = max(0, $wwsd_flat_discount_rate);
										}
										else if(get_option('wwsd_discount_type') == 'percent' && isset($wwsd_saved_discount_data[$i]['wwsd_percent_discount']))
										{
											$wwsd_percent_discount = isset($wwsd_saved_discount_data[$i]['wwsd_percent_discount']) ? $wwsd_saved_discount_data[$i]['wwsd_percent_discount'] : '';
											$wwsd_prod_discount = $wwsd_percent_discount;
										}
										
										
										if($wwsd_cart_product_qty >= $wwsd_product_qty && $wwsd_product_qty >= $wwsd_product_qty_settings)
										{
											$wwsd_product_qty_settings = $wwsd_product_qty;
											$wwsd_prod_amount_settings = $wwsd_prod_discount; 
										}
									}
								}
							}
							
							if(sizeof($get_matched_cat_vals) > 0)
							{
								
								if($wwsd_prod_amount_settings > $effected_amount_settings) $effected_amount_settings = $wwsd_prod_amount_settings;

								
								//--> Find Discounted amount
								if(get_option('wwsd_discount_type') == 'flat')
								{
									if($wwsd_prod_amount_settings > 0)
									{
										$wwsd_prod_amount_settings = max(0, $effected_amount_settings);
										$woo_discounted_amount = max(0, $wwsd_cart_product_price - ($effected_amount_settings / $wwsd_cart_product_qty));
										$allow_cat_discount = true;
									}
								}
								else if(get_option('wwsd_discount_type') == 'percent')
								{
									if($wwsd_prod_amount_settings > 0)
									{
										$wwsd_prod_amount_settings = min(1.0, max(0, (100.0 - round($effected_amount_settings, 2)) / 100.0));
										$woo_discounted_amount = $wwsd_cart_product_price * $effected_amount_settings;
										$allow_cat_discount = true;
									}
								}
							}
						
						}  //--> Category Based Discount is end.
					} //--> get_saved_data_for_cat For loop Closed
					
				}
			}
			//--> Discount By Category End
			
			if(get_option('wwsd_discount_type') == 'percent' && $wwsd_prod_amount_settings <= 0)
			{
				$wwsd_prod_amount_settings = 1;
			}
			
			//--> Keep Discounted values into a global array to access throughout the plugin.
			$wwsd_arr_wholesale_discount_values[$wwsd_discount_cal_prod_id]['cat_discount_applied'] = $allow_cat_discount;
			$wwsd_arr_wholesale_discount_values[$wwsd_discount_cal_prod_id]['product_price'] = $wwsd_cart_product_price;
			$wwsd_arr_wholesale_discount_values[$wwsd_discount_cal_prod_id]['quantity'] = $wwsd_cart_product_qty;
			$wwsd_arr_wholesale_discount_values[$wwsd_discount_cal_prod_id]['wwsd_discounted_amount'] = $wwsd_prod_amount_settings;
		}
		
		//print_r($wwsd_arr_wholesale_discount_values);die;
		return $wwsd_arr_wholesale_discount_values;
	}
	
	
	public static function wwsd_wholesale_discount_calculation_for_single_product_page($product_id)
	{
		global $woocommerce, $wwsd_arr_wholesale_discount_available;
		
		$wwsd_is_discount_available = $allow_cat_discount = false;
		$wwsd_product_qty_lowest = 0;
		
		
		//--> Individual product Discount 
		$wwsd_saved_discount_data = get_post_meta($product_id, 'wwsd_wholesale_discount_data', true);
		$wwsd_enable_woo_discount = get_post_meta($product_id, 'wwsd_enable_woo_discount', true);
		
		
		//$numbers = array_column($array, 'weight')
		//print_r(min($wwsd_saved_discount_data));die;
		
		$wwsd_from_date = get_post_meta($product_id, 'wwsd_discount_from_date', true);
		if($wwsd_from_date != '') $wwsd_from_date = strtotime($wwsd_from_date);
		
		$wwsd_to_date = get_post_meta($product_id, 'wwsd_discount_to_date', true);
		if($wwsd_to_date != '') $wwsd_to_date = strtotime($wwsd_to_date);
		
		$wwsd_all_time_discount = get_post_meta($product_id, 'wwsd_all_time_discount', true);
		
		

		if($wwsd_enable_woo_discount == "Yes") //--> If product based discount enabled.
		{
			if(!empty($wwsd_saved_discount_data))
			{
				$wwsd_product_qty_lowest = $wwsd_saved_discount_data[0]['wwsd_minimum_discount_qrt'];

				$Today_date = strtotime(date("d-m-Y"));
				
				for($i=0; $i<count($wwsd_saved_discount_data); $i++)
				{
					if($Today_date >= $wwsd_from_date  &&  $Today_date <= $wwsd_to_date || $wwsd_all_time_discount == 'Yes')
					{
						$wwsd_is_discount_available = true;
						$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
						
						//$wwsd_product_qty_lowest = $wwsd_product_qty;
						if(get_option('wwsd_discount_type') == 'flat')
						{
							$wwsd_flat_discount_rate = isset($wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate']) ? $wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate'] : '';
							$wwsd_prod_discount  = intval($wwsd_flat_discount_rate);
						}
						else if(get_option('wwsd_discount_type') == 'percent')
						{
							$wwsd_percent_discount = isset($wwsd_saved_discount_data[$i]['wwsd_percent_discount']) ? $wwsd_saved_discount_data[$i]['wwsd_percent_discount'] : '';
							$wwsd_prod_discount  = intval($wwsd_percent_discount);
						}
						
						if($wwsd_prod_discount > 0)
						{
							if($wwsd_product_qty < $wwsd_product_qty_lowest)
							{
								$wwsd_product_qty_lowest = $wwsd_product_qty;
							}
						}
						else
						{
							$wwsd_product_qty_lowest = 0;
						}
					}
				}
			}
		}
		
		
		/** Discount By Category Start **/
		
		$get_saved_data_for_cat = get_option('wwsd_wholesale_discount_data_for_cat', true);
		
		if($wwsd_enable_woo_discount == "No") //--> If product based discount not taken place then apply category discount.
		{
			if(isset($get_saved_data_for_cat) && is_array($get_saved_data_for_cat))
			{
				for($kk=0; $kk<count($get_saved_data_for_cat); $kk++)
				{
					$enable_wwsd_discount = $get_saved_data_for_cat[$kk]['wwsd_enable_woo_discount'];
					
					if($enable_wwsd_discount == "Yes") //--> Category Based Discount is enable.
					{
						$wwsd_saved_discount_data = $get_saved_data_for_cat[$kk]['wwsd_wholesale_discount_data'];
						$wwsd_all_time_discount =  $get_saved_data_for_cat[$kk]['wwsd_all_time_discount'];
						
						$wwsd_from_date = $get_saved_data_for_cat[$kk]['wwsd_discount_from_date'];
						if($wwsd_from_date != '') $wwsd_from_date = strtotime($wwsd_from_date);
			
						$wwsd_to_date = $get_saved_data_for_cat[$kk]['wwsd_discount_to_date'];
						if($wwsd_to_date != '') $wwsd_to_date = strtotime($wwsd_to_date);
						
						$wwsd_discount_categories = $get_saved_data_for_cat[$kk]['wwsd_discount_categories'];
						
						if(!empty($wwsd_saved_discount_data))
						{
							$wwsd_product_qty_lowest = $wwsd_saved_discount_data[0]['wwsd_minimum_discount_qrt'];

							$Today_date = strtotime(date("d-m-Y"));
							
							for($i=0; $i<count($wwsd_saved_discount_data); $i++)
							{
								$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
								
								$get_terms_list = get_the_terms($product_id, 'product_cat');
								
								if(is_array($get_terms_list))
								{
									foreach($get_terms_list as $term)
									{
										$get_cat_ids[] = $term->term_id;
									}
								}
								
								if(count($wwsd_discount_categories) > 0)
								{
									if(is_array($wwsd_discount_categories) && is_array($get_cat_ids))
									{
										$get_matched_cat_vals = array_intersect($wwsd_discount_categories, $get_cat_ids); 
									}
								}
								
								
								if((($Today_date >= $wwsd_from_date && $Today_date <= $wwsd_to_date) || $wwsd_all_time_discount == 'Yes') && sizeof($get_matched_cat_vals) > 0)
								{
									$wwsd_is_discount_available = true;
									$wwsd_product_qty  = intval($wwsd_saved_discount_data[$i]['wwsd_minimum_discount_qrt']);
									if(get_option('wwsd_discount_type') == 'flat')
									{
										$wwsd_prod_discount  = intval($wwsd_saved_discount_data[$i]['wwsd_flat_discount_rate']);
									}
									else if(get_option('wwsd_discount_type') == 'percent')
									{
										$wwsd_prod_discount  = intval($wwsd_saved_discount_data[$i]['wwsd_percent_discount']);
									}

									if($wwsd_prod_discount > 0)
									{
										if($wwsd_product_qty < $wwsd_product_qty_lowest)
										{
											$wwsd_product_qty_lowest = $wwsd_product_qty;
										}
										$allow_cat_discount = true;
									}
									else
									{
										$wwsd_product_qty_lowest = 0;
										
									}
								}
							}
						}
					}//--> Category Based Discount is end.
				
				} //--> get_saved_data_for_cat For loop Closed
			}
		}
		//--> Discount By Category End
		
		
		//--> Keep Discounted values into a global array to access throughout the plugin.
		$wwsd_arr_wholesale_discount_available[$product_id]['cat_discount_applied'] = $allow_cat_discount;
		$wwsd_arr_wholesale_discount_available[$product_id]['wwsd_is_discount_available'] = $wwsd_is_discount_available;
		$wwsd_arr_wholesale_discount_available[$product_id]['wwsd_product_qty_lowest'] = $wwsd_product_qty_lowest;
		//print_r($wwsd_arr_wholesale_discount_available);
		return $wwsd_arr_wholesale_discount_available;
	}	
	
}