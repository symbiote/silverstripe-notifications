<?php
/**
 * NotificationService
 * @author marcus@silverstripe.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class NotificationService extends Object{

	/**
	 * The default notification send mechanisms to init with
	 * @var array
	 */
	private static $default_senders = array(
    	'email' => 'EmailNotificationSender'
	);

	/**
	 * The list of channels to send to by default
	 * @var array
	 */
	private static $default_channels = array(
		'email'
	);

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


	public function __construct() {
		if (!ClassInfo::exists('QueuedJobService')) {
			$this->config()->use_queues = false;
		}

		$this->setSenders($this->config()->get('default_senders'));
		$this->setChannels($this->config()->get('default_channels'));
	}
	

	/**
	 * Add a channel that this notification service should use when sending notifications
	 * @param array $channels 
	 * 				The channels to send to
	 */
	public function addChannel($channel) {
		$this->channels[] = $channel;
		return $this;
	}


	/**
	 * Set the list of channels this notification service should use when sending notifications
	 * @param array $channels 
	 * 				The channels to send to
	 */
	public function setChannels($channels) {
		$this->channels = $channels;
		return $this;
	}


	/**
	 * Add a notification sender
	 * @param String $channel 
	 * 				The channel to send through
	 * @param NotificationSender | string $sender 
	 * 				The notification channel
	 */
	public function addSender($channel, $sender) {
		$sender = is_string($sender) ? singleton($sender) : $sender;
		$this->senders[$channel] = $sender;
		return $this;
	}


	/**
	 * Add a notification sender to a channel
	 * @param Array $senders
	 */
	public function setSenders($senders) {
		$this->senders = array();
		if(count($senders)){
			foreach ($senders as $channel => $sender) {
				$this->addSender($channel, $sender);
			}
		}
		return $this;
	}


	/**
	 * Get a sender for a particular channel
	 * @param String $channel
	 */
	public function getSender($channel){
		return isset($this->senders[$channel]) ? $this->senders[$channel] : null;
	}

	/**
	 * Trigger a notification event
	 *
	 * @param String $identifier
	 *				The Identifier of the notification event
	 * @param DataObject $context 
	 *				The context (if relevant) of the object to notify on
	 * @param array $data
	 *				Extra data to be sent along with the notification
	 */
	public function notify($identifier, $context, $data = array(), $channel=null) {
		// okay, lets find any notification set up with this identifier
		if ($notifications = SystemNotification::get()->filter('Identifier', $identifier)) {
			foreach ($notifications as $notification) {
				if ($notification->NotifyOnClass && $notification->NotifyOnClass != get_class($context)) {
					continue;
				} else {
					$this->sendNotification($notification, $context, $data, $channel);
				}
			}
		}
	}


	/**
	 * Send out a notification
	 *
	 * @param SystemNotification $notification
	 *				The configured notification object
	 * @param DataObject $context
	 *				The context of the notification to send
	 * @param array $extraData
	 *				Any extra data to add into the notification text
	 * @param string $channel
	 *				A specific channel to send through. If not set, just sends to the default configured
	 */
	public function sendNotification(SystemNotification $notification, DataObject $context, $extraData=array(), $channel=null) {
		// check to make sure that there are users to send it to. If not, we don't bother with it at all 
		$recipients = $notification->getRecipients($context);
		if (!count($recipients)) return;

		// if we've got queues and a large number of recipients, lets send via a queued job instead
		if ($this->config()->get('use_queues') && count($recipients) > 5) {
			$extraData['SEND_CHANNEL'] = $channel;
			singleton('QueuedJobService')->queueJob(new SendNotificationJob($notification, $context, $extraData));
		} else {
			$channels = $channel ? array($channel) : $this->channels;
			foreach ($channels as $channel) {
				if ($sender = $this->getSender($channel)) {
					$sender->sendNotification($notification, $context, $extraData);
				}
			}
		}
	}


	/**
	 * Sends a notification directly to a user
	 * 
	 * @param SystemNotification $notification
	 * @param DataObject $context
	 * @param DataObject $user
	 * @param array $extraData
	 */
	public function sendToUser(SystemNotification $notification, DataObject $context, $user, $extraData) {
		$channel = $extraData && isset($extraData['SEND_CHANNEL']) ? $extraData['SEND_CHANNEL'] : null;
		$channels = $channel ? array($channel) : $this->channels;

		foreach ($channels as $channel) {
			if ($sender = $this->getSender($channel)) {
				$sender->sendToUser($notification, $context, $user, $extraData);
			}
		}
	}
}