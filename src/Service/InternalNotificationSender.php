<?php

namespace Symbiote\Notifications\Service;

use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Model\InternalNotification;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;

/**
 * EmailNotificationSender
 *
 * @author  marcus@symbiote.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class InternalNotificationSender implements NotificationSender
{
    /**
     * Send a notification via email to the selected users
     *
     * @param SystemNotification           $notification
     * @param \SilverStripe\ORM\DataObject $context
     * @param array                        $data
     */
    public function sendNotification($notification, $context, $data)
    {
        $users = $notification->getRecipients($context);
        foreach ($users as $user) {
            $this->sendToUser($notification, $context, $user, $data);
        }
    }

    /**
     * Send a notification directly to a single user
     *
     * @param SystemNotification $notification
     * @param $context
     * @param $user
     * @param array              $data
     */
    public function sendToUser($notification, $context, $user, $data)
    {
        $subject = $notification->format($notification->Title, $context, $user, $data);

        $content = $notification->NotificationContent();

        if (!Config::inst()->get(SystemNotification::class, 'html_notifications')) {
            $content = strip_tags($content);
        }

        $message = $notification->format(
            $content,
            $context,
            $user,
            $data
        );

        if ($template = $notification->getTemplate()) {
            $templateData = $notification->getTemplateData($context, $user, $data);
            $templateData->setField('Body', $message);
            try {
                $body = $templateData->renderWith($template);
            } catch (Exception $e) {
                $body = $message;
            }
        } else {
            $body = $message;
        }

        $notice = InternalNotification::create([
            'Title' => $subject,
            'Message' => $body,
            'ToID'      => $user->ID,
            'FromID'    => Member::currentUserID(),
            'SentOn'    => date('Y-m-d H:i:s'),
            'Context' => [
                'ClassName' => get_class($context),
                'ID' => $context->ID,
                'Link' => $context->hasMethod('Link') ? $context->Link() : ''
            ]
        ]);

        $notice->write();
    }
}
