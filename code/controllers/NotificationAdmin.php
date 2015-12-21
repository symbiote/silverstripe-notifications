<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationAdmin extends ModelAdmin
{
    private static $managed_models = array(
        'SystemNotification'
    );
    
    private static $url_segment = 'notifications';
    
    private static $menu_title = 'Notifications';

    private static $menu_icon = 'notifications/images/notifications-icon.png';
}
