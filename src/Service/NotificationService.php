<?php

namespace Symbiote\Notifications\Service;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;
use Symbiote\Notifications\Job\SendNotificationJob;
use Symbiote\Notifications\Model\NotificationSender;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * NotificationService
 * @author  marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationService
{
    use Configurable;

    /**
     * The default notification send mechanisms to init with
     * @var array
     */
    private static $default_senders = [
        'email' => EmailNotificationSender::class,
        'internal' => InternalNotificationSender::class,
    ];

    /**
     * The list of channels to send to by default
     * @var array
     */
    private static $default_channels = [
        'email',
        'internal',
    ];

    /**
     * Should we use the queued jobs approach to sending notifications?
     * @var Boolean
     */
    private static $use_queues = true;

    /**
     * The objects to use for actually sending a notification, indexed
     * by their channel ID
     * @var array
     */
    protected $senders;

    /**
     * The list of channels to send to
     * @var array
     */
    protected $channels;

    public function __construct()
    {
        if (!ClassInfo::exists(QueuedJobService::class)) {
            $this->config()->use_queues = false;
        }

        $this->setSenders($this->config()->get('default_senders'));
        $this->setChannels($this->config()->get('default_channels'));
    }

    /**
     * Add a channel that this notification service should use when sending notifications
     * @param string $channel The channel to add
     * @return \Symbiote\Notifications\Service\NotificationService
     */
    public function addChannel($channel)
    {
        $this->channels[] = $channel;

        return $this;
    }

    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Set the list of channels this notification service should use when sending notifications
     * @param array $channels The channels to send to
     * @return \Symbiote\Notifications\Service\NotificationService
     */
    public function setChannels($channels)
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Add a notification sender
     * @param string                    $channel The channel to send through
     * @param NotificationSender|string $sender  The notification channel
     * @return \Symbiote\Notifications\Service\NotificationService
     */
    public function addSender($channel, $sender)
    {
        $sender = is_string($sender) ? singleton($sender) : $sender;
        $this->senders[$channel] = $sender;

        return $this;
    }

    /**
     * Add a notification sender to a channel
     * @param array $senders
     * @return \Symbiote\Notifications\Service\NotificationService
     */
    public function setSenders($senders)
    {
        $this->senders = [];
        if (count($senders)) {
            foreach ($senders as $channel => $sender) {
                $this->addSender($channel, $sender);
            }
        }

        return $this;
    }

    /**
     * Get a sender for a particular channel
     * @param string $channel
     * @return mixed|null
     */
    public function getSender($channel)
    {
        return isset($this->senders[$channel]) ? $this->senders[$channel] : null;
    }

    /**
     * Trigger a notification event
     * @param string      $identifier The Identifier of the notification event
     * @param DataObject  $context    The context (if relevant) of the object to notify on
     * @param array       $data       Extra data to be sent along with the notification
     * @param string|null $channel
     */
    public function notify($identifier, $context, $data = [], $channel = null)
    {
        // okay, lets find any notification set up with this identifier
        if ($notifications = SystemNotification::get()->filter('Identifier', $identifier)) {
            foreach ($notifications as $notification) {
                $subclasses = $notification->NotifyOnClass ? ClassInfo::subclassesFor($notification->NotifyOnClass) : [];
                if ($notification->NotifyOnClass && !isset($subclasses[strtolower(get_class($context))])) {
                    continue;
                } else {
                    // figure out the channels to send the notification on
                    $channels = $channel ? [$channel] : [];
                    if ($notification->Channels) {
                        $channels = json_decode($notification->Channels);
                    }

                    $this->sendNotification($notification, $context, $data, $channels);
                }
            }
        }
    }

    /**
     * Send out a notification
     * @param SystemNotification $notification The configured notification object
     * @param DataObject         $context      The context of the notification to send
     * @param array              $extraData    Any extra data to add into the notification text
     * @param string             $channels     The specific channels to send through. If not set, just
     *                                         sends to the default configured
     */
    public function sendNotification(
        SystemNotification $notification,
        DataObject $context,
        $extraData = [],
        $channels = null
    ) {
        // check to make sure that there are users to send it to. If not, we don't bother with it at all
        $recipients = $notification->getRecipients($context);
        if (!count($recipients)) {
            return;
        }

        // if we've got queues and a large number of recipients, lets send via a queued job instead
        if ($this->config()->get('use_queues') && count($recipients) > 5) {
            $extraData['SEND_CHANNELS'] = $channels;
            singleton(QueuedJobService::class)->queueJob(
                new SendNotificationJob(
                    $notification,
                    $context,
                    $extraData
                )
            );
        } else {
            if (!is_array($channels)) {
                $channels = [$channels];
            }
            $channels = count($channels) ? $channels : $this->channels;
            foreach ($channels as $channel) {
                if ($sender = $this->getSender($channel)) {
                    $sender->sendNotification($notification, $context, $extraData);
                }
            }
        }
    }

    /**
     * Sends a notification directly to a user
     * @param SystemNotification $notification
     * @param DataObject         $context
     * @param DataObject         $user
     * @param array              $extraData
     */
    public function sendToUser(
        SystemNotification $notification,
        DataObject $context,
        $user,
        $extraData = []
    ) {
        $channels = $this->channels;
        if ($extraData && isset($extraData['SEND_CHANNELS'])) {
            $channels = $extraData['SEND_CHANNELS'];
            unset($extraData['SEND_CHANNELS']);
        }

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channel) {
            if ($sender = $this->getSender($channel)) {
                $sender->sendToUser($notification, $context, $user, $extraData);
            }
        }
    }
}
