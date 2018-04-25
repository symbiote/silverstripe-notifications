<?php

namespace Symbiote\Notifications\Model;

/**
 * NotificationSender
 *
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
interface NotificationSender
{
    /**
     * Send a notification.
     * Automatically determines the list of users to send to based on the notification
     * object and context
     *
     * @param SystemNotification           $notification
     * @param \SilverStripe\ORM\DataObject $context
     * @param array                        $data
     */
    public function sendNotification($notification, $context, $data);

    /**
     * Send a notification to a single user at a time
     *
     * @param SystemNotification            $notification
     * @param \SilverStripe\ORM\DataObject  $context
     * @param \SilverStripe\Security\Member $user
     * @param array                         $data
     */
    public function sendToUser($notification, $context, $user, $data);
}
