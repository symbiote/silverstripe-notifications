<?php

namespace Symbiote\Notifications\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;

/**
 * EmailNotificationSender
 *
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class EmailNotificationSender implements NotificationSender
{
    use Configurable, Extensible;

    /**
     * Email Address to send email notifications from
     *
     * @var string
     */
    private static $send_notifications_from;

    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
    ];

    /**
     * @var LoggerInterface
     */
    public $logger;

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

        if (Config::inst()->get(SystemNotification::class, 'html_notifications')) {
            $message = $notification->format(
                $notification->NotificationContent(),
                $context,
                $user,
                $data
            );
        } else {
            $message = $notification->format(
                nl2br($notification->NotificationContent()),
                $context,
                $user,
                $data
            );
        }

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

        $from = $this->config()->get('send_notifications_from');
        $to = $user->Email;
        if (!$to && method_exists($user, 'getEmailAddress')) {
            $to = $user->getEmailAddress();
        }

        // log
        $this->logger->notice(sprintf("Sending %s to %s", $subject, $to));

        // send
        try {
            $email = new Email($from, $to, $subject);
            $email->setBody($body);
            $this->extend('onBeforeSendToUser', $email);
            $email->send();
        } catch (\Swift_SwiftException $e) {
            if ($this->logger) {
                if ($to !== 'admin') {
                    $this->logger->warning("Failed sending email to $to");    
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }
}
