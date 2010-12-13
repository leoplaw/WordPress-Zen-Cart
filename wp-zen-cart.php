<?php
/*
 Plugin Name: WordPress Zen Cart
 Plugin URI: http://guildmedia.net
 Description: Various options for integrating Zen Cart into WordPress
 Version: 0.4.1
 Author: Leo Plaw
 Author URI: http://guildmedia.net
 Text Domain: wp-zen-cart

 Copyright 2010  Leo Plaw  (email : leo [a t ] guild media DOT net)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 */
?>
<?php

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die(__('You are not allowed to call this page directly.')); }

// ini_set('display_errors', '1');
// ini_set('error_reporting', E_ALL);
if (!class_exists("WordPressZenCart")) {
	class WordPressZenCart {

		var $version = '0.3.4';
		var $requiredWPVersion = "2.8";
		var $adminOptionsName = "adminOptionsWordPressZenCart";
		var $adminOptions = '';
		var $zcdbcon = "";
		var $adminView = "";
		var $shortCodes = "";
		var $seourls = false;
		var $id="WPZC";
		var $class="";
			
		function WordPressZenCart() { //constructor

			$this->adminOptions = $this->getAdminOptions();
			if (file_exists($this->adminOptions['WPZC_ZenCartPath'])) {
				include_once ($this->adminOptions['WPZC_ZenCartPath']);
				if (DB_TYPE != 'mysql') {
					$this->adminMessage(__('Only mySQL databases are currently supported.'));
				} else {
					// Can not assume Zen Cart is installed to the same database, so open a new connection
					if (class_exists("wpdb")) {
						$this->zcdbcon = new wpdb( DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, DB_SERVER );
					}
				}
			}

			// Load the language file
			$this->load_textdomain();

			$this->plugin_name = plugin_basename(__FILE__);

			// Init options & tables during activation & deregister init option
			register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
			register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );

			// Register a uninstall hook to remove all tables & option automatic
			register_uninstall_hook( $this->plugin_name, array('WordPressZenCart', 'uninstall') );

			// Start this plugin once all other plugins are fully loaded
			$this->init();
		}

		function init() {
			// Start the plugin

			if ( is_admin() ) {	// Load the admin panel

				include_once ("admin/admin.php");
				$this->adminView = new WPZC_adminPanel($this);
			
			} elseif (!is_admin()) { // or the frontend functions

//				if (DB_TYPE != 'mysql') {
//					$this->adminMessage(__('Only mySQL databases are currently supported.'));
//				} else {
					if (!empty($this->zcdbcon)) {
						// Filters

						// Short Codes
						if (file_exists($this->adminOptions['WPZC_ZenCartPath'])) {
							include_once ("lib/short-codes.php");
							$this->shortCodes = new WPZC_shortCodes($this);
						}
						else $this->adminMessage(__('WordPress Zen Cart is not configured properly. Check your settings.'));
					}
//				}
			}
		}

		function activate() {

			global $wp_version;
			// Check WP version
			if (version_compare($wp_version,$this->requiredWPVersion,"<")) {
				$this->adminMessage(__('WordPress '.$this->requiredWPVersion.' or greater is required'));
			} else {
					
				if (file_exists($options['WPZC_ZenCartPath'])) include ($this->adminOptions['WPZC_ZenCartPath']);

				if (DB_TYPE != 'mysql') {

					// TODO: check why adminMessage is not printing
					$this->adminMessage(__('Connections to '.DB_TYPE.'are not currently supported.'));
				} else {
					include_once (dirname (__FILE__) . '/admin/install.php');

					// check for tables and install if necessary
					WordPressZenCart_install($this);
				}
			}
			return;
		}

		function deactivate() {

			// Filters

			// Short Codes
			remove_shortcode( 'zc_product_shortcode_callback' );

		}

		function uninstall() {

			$this->deactivate($this);

			include_once (dirname (__FILE__) . '/admin/install.php');
			WordPressZenCart_uninstall();

		}


		function adminPermission() {

			if ( !current_user_can('manage_options') ) wp_die(__('You are not permitted to manage options.'));
			//			if (! user_can_access_admin_page()) wp_die( __('You do not have sufficient permissions to access this page.') );
		}


		function adminMessage($msg) {
			add_action( 'admin_notices', create_function('', 'echo \'<div id="message" class="error"><p><strong>' . $msg . '</strong></p></div>\';') );
		}


		function getAdminOptions() {

			// assign default option values
			$WordPressZenCartOptions = array();
			// fetch existing stored option values, if any
			$options = get_option($this->adminOptionsName);
			if (!empty($options)) {
				foreach ($options as $key => $option) {
					$WordPressZenCartOptions[$key] = $option;
				}
			}
			if (isset($_POST["update_WordPressZenCart"])) {
				$WordPressZenCartOptions = array_merge((array)$WordPressZenCartOptions, (array)$_POST);
				foreach ($WordPressZenCartOptions as $key => $option) {
					// remove any elements that are not valid options
					if (!preg_match("/WPZC_/", $key)) unset($WordPressZenCartOptions[$key]);
				}
				$this->validate_Options($WordPressZenCartOptions);
				update_option($this->adminOptionsName, $WordPressZenCartOptions);
			}
			return $WordPressZenCartOptions;
		}


		function load_textdomain() {

			load_plugin_textdomain('wp-zen-cart', false, dirname( plugin_basename(__FILE__) ) . '/lang');

		}



		// Form validation

		function validate_Options($options) {

			/*
			 // $urlregex = "^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
			 $urlregex = "^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?configure.php";
			 if (eregi($urlregex, $url)) {
				echo "good";
				if (function_exists('esc_url_raw')) esc_url_raw($url); // WP 2.8 >
				else sanitize_url($url);
				}
				else {echo "bad";}
				*/

			// Valid Zen Cart configure.php ?
			if (!file_exists($options['WPZC_ZenCartPath'])) {
				$errormsg = __('Zen Cart configure.php does not exist. ').$options['WPZC_ZenCartPath'];
				//				$this->adminMessage($errormsg);
				$validation .= "<li>".$errormsg."</li>";
			} else {
				if (!is_readable($options['WPZC_ZenCartPath'])) {
					$errormsg = __('Zen Cart configure.php is not readable. ').$options['WPZC_ZenCartPath'];
					//				$this->adminMessage($errormsg);
					$validation .= "<li>".$errormsg."</li>";
				} else {
					include_once ($options['WPZC_ZenCartPath']);
					if (DB_TYPE != 'mysql') {
						$errormsg = __('Connections to '.DB_TYPE.' are not currently supported. The plugin will be disabled.');
						//				$this->adminMessage($errormsg);
						$validation .= "<li>".$errormsg."</li>";
					} else {
						if (empty($this->zcdbcon)) {
							$errormsg = __('Can not connect to Database: '.DB_DATABASE);
							//				$this->adminMessage($errormsg);
							$validation .= "<li>".$errormsg."</li>";
						}
					}
				}
			}
			// Valid Zen Cart URL?
			if (isset($options['WPZC_ZenCartURL'])) {
// TODO: Test for trainling slash
			} else {
				$errormsg = __('Zen Cart URL not set.');
				//				$this->adminMessage($errormsg);
				$validation .= "<li>".$errormsg."</li>";
			}

			if ($validation) {
				$this->adminMessage($validation);
				$valid = false;
			} else $valid = true;
			return $valid;
		}


	} // End Class WordPressZenCart

}


if (class_exists("WordPressZenCart")) {
	$WordPressZenCart = new WordPressZenCart();
}
?>