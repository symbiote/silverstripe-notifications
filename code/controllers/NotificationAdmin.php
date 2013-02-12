<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationAdmin extends ModelAdmin {
	public static $managed_models = array(
		'SystemNotification'
	);
	
	public static $url_segment = 'notifications';
	
	public static $menu_title = 'Notifications';
}