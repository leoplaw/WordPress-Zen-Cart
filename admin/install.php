<?php 

// TODO: Why default values don't install to options.

function WordPressZenCart_install($wpzc) {
	// add database tables if necessary
	update_option($wpzc->adminOptionsName, array("seourls"=>$wpzc->seourls,"id"=>$wpzc->id,"class"=>$wpzc->class));
}

function WordPressZenCart_uninstall($wpzc) {
	// remove database tables if necessary
}


?>