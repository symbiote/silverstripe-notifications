<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * Class to encapsulate common activities around adding and getting notifications
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license http://silverstripe.org/bsd-license/
 * @package Notifications
 */
class NotificationService {

	/**
	 * The default notification send mechanism
	 * @var NotificationChannel
	 */
	public static $sender_service = 'LogNotificationSender';

	/**
	 * Should we use the queued jobs approach to sending notifications?
	 * @var Boolean
	 */
	public static $use_queues = true;

	/**
	 * A list of all the notifications that the system manages. 
	 * 
	 * Simply a list of strings at the moment - it may eventually make sense to be strongly typed,
	 * not sure yet. 
	 *
	 * @var array
	 */
	protected static $identifiers = array();

	/**
	 * The objects to use for actually sending a notification, indexed
	 * by their channel ID
	 *
	 * @var array
	 */
	protected $notificationChannels;
	
	/**
	 * The list of channels that are always sent to
	 *
	 * @var array
	 */
	protected $channels;

	public function __construct() {
		if (!ClassInfo::exists('QueuedJobService')) {
			self::$use_queues = false;
		}
		
		$this->notificationChannels = array('log' => singleton(self::$sender_service));
		$this->channels = array('log');
	}
	
	/**
	 * Add an identifier
	 *
	 * @param string $i 
	 */
	public static function add_identifier($i) {
		self::$identifiers[] = $i;
	}
	
	/**
	 * Get a list of all identifiers
	 *
	 * @param string $i 
	 */
	public static function get_identifiers() {
		return self::$identifiers;
	}

	/**
	 * Set the list of channels this notification service should use for all 
	 * notifications
	 * 
	 * @param array $channels 
	 *				The channels to send to all the time
	 */
	public function setChannels($senders) {
		$this->channels = $senders;
	}

	/**
	 *
	 * @param String $channel
	 *				The channel to send through
	 * @param NotificationChannel $sender 
	 *				The notification channel implementor
	 */
	public function addNotificationSender($channel, $sender) {
		$this->notificationChannels[$channel] = $sender;
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
		$notifications = DataObject::get('SystemNotification', '"Identifier"=\''.Convert::raw2sql($identifier).'\'');
		if ($notifications) {
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
	 * Find notifications that need to be executed given the current date and
	 * where those notifications are time sensitive to a particular property
	 * on a data object type. 
	 *
	 * @return DataObjectSet
	 */
	public function findNotificationsForDate($date=null) {
		return array();
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
		$out = $notification->getRecipients($context); 
		
		if (!count($out)) {
			return;
		}

		// if we've got queues and a large number of recipients, lets send via a queued job instead
		if (self::$use_queues > 5) {
			$extraData['SEND_CHANNEL'] = $channel;
			singleton('QueuedJobService')->queueJob(new SendNotificationJob($notification, $context, $extraData));
		} else {
			$channels = $this->channels;
			if ($channel) {
				$channels = array($channel);
			} 
			foreach ($channels as $channel) {
				if (isset($this->notificationChannels[$channel])) {
					$this->notificationChannels[$channel]->sendNotification($notification, $context, $extraData);
				}
			}
		}
	}

	/**
	 * Sends a notification directly to a user
	 * 
	 * @param String $email
	 */
	public function sendToUser(SystemNotification $notification, DataObject $context, $user, $extraData) {
		$channel = $extraData && isset($extraData['SEND_CHANNEL']) ? $extraData['SEND_CHANNEL'] : null;
		
		$channels = $this->channels;
		if ($channel) {
			$channels = array($channel);
		}

		foreach ($channels as $channel) {
			if (isset($this->notificationChannels[$channel])) {
				$this->notificationChannels[$channel]->sendToUser($notification, $context, $user, $extraData);
			}
		}
	}
}