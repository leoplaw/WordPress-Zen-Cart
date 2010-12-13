<?php 
/**
 * WPZC_adminPanel - Admin Section for WordPress Zen Cart
 *
 * @package WordPress Zen Cart
 * @author Leo Plaw
 * @copyright 2010 - 
 * @since 1.0.0
 */

/* Short Code functions */			

class WPZC_shortCodes {

	var $WPZC = "";
	var $ZCconfig = "";
	var $top="<ul id=\"%id%\" class=\"%class%\">\n";
	var $tail="</ul>\n";
	var $cnt=0;

	
		/**
		 * Constructor
		 */
		function WPZC_shortCodes($WPZC){
			
			// check if plugin is configured properly, paths ect. If not report fail.
			$this->WPZC = $WPZC;
			$query = "
				SELECT configuration_key, configuration_value FROM `".DB_PREFIX."configuration`
				WHERE ".DB_PREFIX."configuration.configuration_key IN 
				('SMALL_IMAGE_WIDTH','SMALL_IMAGE_HEIGHT','MEDIUM_IMAGE_WIDTH','MEDIUM_IMAGE_HEIGHT',
				'IMAGE_SUFFIX_MEDIUM','IMAGE_SUFFIX_LARGE',
				'PROPORTIONAL_IMAGES_STATUS','CONFIG_CALCULATE_IMAGE_SIZE','PRODUCTS_IMAGE_NO_IMAGE',
				'PRODUCTS_PRICE_IS_CALL_IMAGE_ON','OTHER_IMAGE_PRICE_IS_FREE_ON',
				'CUSTOMERS_APPROVAL','CUSTOMERS_APPROVAL_AUTHORIZATION','STORE_COUNTRY','STORE_ZONE',
				'DEFAULT_LANGUAGE','LANGUAGE_DEFAULT_SELECTOR','USE_DEFAULT_LANGUAGE_CURRENCY',
				'DEFAULT_CURRENCY','STORE_PRODUCT_TAX_BASIS','DISPLAY_PRICE_WITH_TAX');
			";
			$ZCconfig = $this->WPZC->zcdbcon->get_results($query);
			foreach ($ZCconfig as $option) {
				if (!defined($option->configuration_key)) define ($option->configuration_key,$option->configuration_value);
			}
        	add_shortcode( "zc_product", array(&$this, "zc_product_shortcode_callback" ) );
//			add_shortcode( "zc_product", "WordPressZenCart::WPZC_shortCodes::zc_product_shortcode_callback");
			return $true;
		}

		
		
		/** 
		 * Return a selection of products from Zen Cart
		 * @param array|integer|string $items 
		 * @param booleen $random randomly select n products as specified in $items
		 * @return string HTML formatted list of Zen Cart products
		 */
		function get_zc_products($items=3, $random=false, $image=true, $id='', $class=''){

			// check if plugin is configured properly, paths ect. If not report fail.
			if (!$this->WPZC->validate_Options($this->WPZC->adminOptions)) {
				$zc_products = false;
			} else {		
				$this->WPZC->id = ($id) ? $id : $this->WPZC->adminOptions["WPZC_id"];
				$this->WPZC->class = ($class) ? $class : $this->WPZC->adminOptions["WPZC_class"];
				$query = "
					SELECT *
					FROM `".DB_PREFIX."products`
					INNER JOIN ".DB_PREFIX."products_to_categories ON ".DB_PREFIX."products.products_id = ".DB_PREFIX."products_to_categories.products_id
					INNER JOIN ".DB_PREFIX."products_description ON ".DB_PREFIX."products.products_id = ".DB_PREFIX."products_description.products_id
					INNER JOIN ".DB_PREFIX."categories_description ON ".DB_PREFIX."products_to_categories.categories_id = ".DB_PREFIX."categories_description.categories_id
					WHERE ".DB_PREFIX."products.products_status = 1
				";
	
				if ($random) $query .= "ORDER BY RAND() LIMIT 0 , ".(int)$items.";";
				else {
					if (!is_array($items)) $items = explode(",",$items);
					// sanitize values passed to function
					$items = array_filter($items,array($this,"sanitizeID"));
					$items = implode(",",$items);
					$query .= "WHERE ".DB_PREFIX."products.products_id IN ($items);";
				}
				$products = $this->WPZC->zcdbcon->get_results($query);
	
				$query = "
					SELECT title,code,symbol_left,symbol_right,decimal_point,thousands_point,decimal_places,value
					FROM `".DB_PREFIX."currencies`
					WHERE code = '".DEFAULT_CURRENCY."';
				";
				$currency = $this->WPZC->zcdbcon->get_row($query, ARRAY_A);
				$_SESSION['currency'] = $currency['code'];
				if ($products) {
					$zc_products = $this->shopItems($products, $currency, $image);
				} else {
					$zc_products = null;
				}
			}
			return $zc_products;
		}

		function the_zc_products($items='', $random='', $image='', $id='', $class='') {
			echo $this->get_zc_products($items, $random, $image, $id, $class);
		}


		/**
		 *  Return a selection of products from a Zen Cart category
		 */
		function get_zc_categories($items) {

			// check if plugin is configured properly, paths ect. If not report fail.
			if (!$this->WPZC->validate_Options($this->WPZC->adminOptions)) {
				$zc_cats = false;
			} else {		
			
				if (is_array($items)) {
					foreach ($items as $item) {
						$zc_cats = "<p>Zen Cart category: ".$item."</p>";
					}
				} else {
					$item = $items;
					$zc_cats = "<p>Zen Cart category: ".$item."</p>";
				}
			}
			return $zc_cats;
		}

		function the_zc_categories($cats) {
			echo $this->get_zc_categories($cats);
		}





		function zc_product_shortcode_callback( $atts ) {

			$productID = 'No product id supplied.';

			// Get the attributes and filter out
			// unwanted attributes
			$a = shortcode_atts( array(
				'id' => $productID,
				'random' => false,
				'class' => ''
			), $atts );

			// if random, then $productID specifies the number of random items.
			if ($a['id'] == $productID) $ret = "<p>".$productID."</p>";
			else $ret = $this->get_zc_products($a['id'],$a['random'],$a['class']);
			
			// Do NOT echo in short codes, return value instead:
			return $ret;
		}


		private function shopItems($qresults, $currency, $image=true, $width = "", $height = "",$top="", $tail="") {

			$search = array("%id%","%class%");
			$replace = array($this->WPZC->id.++$this->cnt,$this->WPZC->class);
			$top = ($top) ? $top : str_replace($search,$replace,$this->top);
			$tail = ($tail) ? $tail : $this->tail;
			
			global $ihConf, $bmzConf;
			$width = ($width)? $width : SMALL_IMAGE_WIDTH;
			$height = ($height)? $height : SMALL_IMAGE_HEIGHT;
			$lastid = "";
			$out = $top;
			$row = 0;
			$shopURL = $this->WPZC->adminOptions["WPZC_ZenCartURL"];
			$zc_path = str_replace("includes/configure.php","",$this->WPZC->adminOptions['WPZC_ZenCartPath']);
			set_include_path(get_include_path() . PATH_SEPARATOR . $zc_path);
			
			include_once ("get-redirect-url.php");

			// if Image Handler exists, use it.
			if (file_exists($zc_path."includes/extra_configures/bmz_io_conf.php")) {
				include_once ("includes/extra_configures/bmz_io_conf.php");
				include_once ("includes/extra_configures/bmz_image_handler_conf.php");
				include_once ("includes/functions/extra_functions/functions_bmz_image_handler.php");
				include_once ("includes/functions/extra_functions/functions_bmz_io.php");
				$ihConf['resize'] = true;
				$ihConf['dir']['docroot'] = "";
				$ihConf['dir']['images'] = $zc_path."images/";

			}
			define('IS_ADMIN_FLAG', false);
			$dirwsfunc = DIR_WS_FUNCTIONS;

			require_once ("includes/filenames.php");
			require_once ("includes/init_includes/init_general_funcs.php");
			require_once ('includes/classes/class.base.php');
			require_once ('includes/classes/db/' .DB_TYPE . '/query_factory.php');
			require_once ('includes/database_tables.php');
			global $db,$currencies;
			$db = new queryFactory();
			$db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, USE_PCONNECT, false);
//			require_once ('includes/functions/sessions.php');
			require_once ('includes/init_includes/init_sessions.php'); 
			require_once ('includes/classes/language.php');
			require_once ("includes/init_includes/init_languages.php");
			require_once ('includes/classes/template_func.php');
			$template = new template_func();
			require_once ('init_templates.php'); // has to be replaced because it sends header()
			require_once ('includes/modules/require_languages.php');
			require_once ('includes/classes/currencies.php');
			$currencies = new currencies();
			require_once ("includes/functions/functions_prices.php");
			require_once ("includes/functions/functions_taxes.php");
			require_once ('includes/init_includes/init_currencies.php');
			
			foreach ($qresults as $result) {
				if ($result->products_id == $lastid) continue;
				$products_image = ($result->products_image) ? $result->products_image : PRODUCTS_IMAGE_NO_IMAGE;
				$lastid = $result->products_id;
				$href = $shopURL.'index.php?main_page=product_info&products_id='.$result->products_id;
				if ($this->WPZC->adminOptions['WPZC_seourls'] === true) get_final_url($href);
				if ($image) $img  = zen_image($zc_path."images/".$products_image, $alt = $result->products_name, $width, $height);
				$price = zen_get_products_display_price($result->products_id);

				$oddEven = (++$row & 1) ? "odd" : "even";
		
				$out .= "\t".'<li class="'.$oddEven.' item'.$row.'">'."\n";
				$out .= "\t\t".'<a href="'.$href.'">'."\n";
				if ($image) $out .= "\t\t\t".str_replace($zc_path,$shopURL,$img);
				
				$out .= "\t\t\t".'<span class="itemName">'.$result->products_name."</span>\n";
				$out .= "\t\t".'</a><br />'."\n";
				$out .= "\t\t".'<span class="itemPrice">'.$price.'</span><br />'."\n";
				$out .= "\t".'</li>'."\n";
			}
			$out .= $tail;
			$out = "\n<!-- Zen Cart Items -->\n".$out."<!-- Zen Cart Items end -->\n";
			return $out;
		}


		private function sanitizeID($ID) {
			return (int)$ID;
		}

		

} // end of class WPZC_shortCodes 
?>