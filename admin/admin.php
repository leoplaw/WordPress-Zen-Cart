<?php
/**
 * WPZC_adminPanel - Admin Section for WordPress Zen Cart
 *
 * @package WordPress Zen Cart
 * @author Leo Plaw
 * @copyright 2010 - 
 * @since 1.0.1
 */
class WPZC_adminPanel {

	var $WPZC = "";

	// constructor
	function WPZC_adminPanel($WPZC) {

		$this->WPZC = $WPZC;
		$this->WPZC->validate_Options($this->WPZC->adminOptions);
		add_action( 'admin_init', array(&$this, 'WPZC_init') );
		add_action( 'admin_menu', array(&$this, 'WPZC_menu') );

	}

	function WPZC_init() {

		wp_register_style( 'WPZC_PluginStylesheet', WP_PLUGIN_URL . '/wp-zen-cart/admin/css/stylesheet.css' );

	}

	function WPZC_menu() {

		$page = add_options_page('WP Zen Cart', 'WP Zen Cart', 9, 'wpzc', array(&$this, 'buildAdmin'));
		add_action( 'admin_print_styles-' . $page, array(&$this, 'adminStyles') );

	}

	function adminStyles() {

		wp_enqueue_style( 'WPZC_PluginStylesheet' );

	}

	function buildAdmin() {

		ob_start;
		$this->WPZC->adminPermission();
?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h2>WordPress ZenCart Integration</h2>
<h3><?php _e('Zen Cart Settings') ?></h3>

<ul>
<li><label for="WPZC_ZenCartPath"><?php _e('Zen Cart Config:', 'wp-zen-cart') ?></label> <input
	id="WPZC_ZenCartPath" type="text" maxlength="" size=""
	value="<?php echo $this->WPZC->adminOptions["WPZC_ZenCartPath"]; ?>"
	name="WPZC_ZenCartPath"> <span class="setting-description"><?php _e('Path to Zen Cart configure.php', 'wp-zen-cart') ?></span><br />
</li>
<li><label for="WPZC_ZenCartURL"><?php _e('Zen Cart URL:', 'wp-zen-cart') ?></label> <input
	id="WPZC_ZenCartURL" type="text" maxlength="" size=""
	value="<?php echo $this->WPZC->adminOptions["WPZC_ZenCartURL"]; ?>"
	name="WPZC_ZenCartURL"> <span class="setting-description"><?php _e('URL of Zen Cart', 'wp-zen-cart') ?></span><br />
</li>
<li><label for="WPZC_seourls"><?php _e('Fetch SEO URLs:', 'wp-zen-cart') ?></label> 
	<input id="WPZC_seourls" name="WPZC_seourls" type="checkbox" value="1"<?php if ( 1 == $this->WPZC->adminOptions["WPZC_seourls"] ) echo ' checked="checked"'; ?> />
	<span class="setting-description"><?php _e('Fetch SEO URLs, may slow down the page.', 'wp-zen-cart') ?></span><br />
</li>
<li><label for="WPZC_id"><?php _e('Default id:', 'wp-zen-cart') ?></label> <input
	id="WPZC_id" type="text" maxlength="" size=""
	value="<?php echo $this->WPZC->adminOptions["WPZC_id"]; ?>"
	name="WPZC_id"> <span class="setting-description"><?php _e('Default HTML id', 'wp-zen-cart') ?></span><br />
</li>
<li><label for="WPZC_class"><?php _e('Default class:', 'wp-zen-cart') ?></label> <input
	id="WPZC_class" type="text" maxlength="" size=""
	value="<?php echo $this->WPZC->adminOptions["WPZC_class"]; ?>"
	name="WPZC_class"> <span class="setting-description"><?php _e('Default HTML class', 'wp-zen-cart') ?></span><br />
</li>
<div class="submit"><input class="button-primary" type="submit" name="update_WordPressZenCart"
	value="<?php _e('Update Settings', 'wp-zen-cart') ?>" /></div>
</form>
</div>
			<?php
	    ob_end_flush();
	}



} // end of class WPZC_adminPanel
?>