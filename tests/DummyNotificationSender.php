<?php

namespace Symbiote\Notifications\Tests;

use Symbiote\Notifications\Model\NotificationSender;

class DummyNotificationSender implements NotificationSender
{
    public $notifications = [];

    /**
     * Send a notification via email to the selected users
     * @param UserNotification $notification
     * @param                  $context
     * @param array            $data
     */
    public function sendNotification($notification, $context, $data)
    {
        $users = $notification->getRecipients($context);

        foreach ($users as $user) {
            $this->sendToUser($notification, $context, $user, $data);
        }
    }

    /**
     * Send a notification to a single user at a time
     * @param UserNotification $notification
     * @param                  $context
     * @param                  $user
     * @param array            $data
     */
    public function sendToUser($notification, $context, $user, $data)
    {
        $cls = new \stdClass();
        $cls->notification = $notification;
        $cls->text = $notification->format($notification->NotificationText, $context, $user, $data);
        $cls->context = $context;
        $cls->user = $user;
        $cls->data = $data;

        $this->notifications[] = $cls;
    }
}
