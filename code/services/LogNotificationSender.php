<?php

/**
 * A notification sender that just logs the things being sent. This should be used while in dev,
 * and use the email based on in prod
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @licence http://silverstripe.org/bsd-license/
 * @package Notifications
 */
class LogNotificationSender implements NotificationChannel {
	public function __construct() {}

	/**
	 * Send a notification via email to the selected users
	 *
	 * @param UserNotification $notification
	 * @param array $data
	 */
    public function sendNotification($notification, $context, $data) {
		$users = $notification->getRecipients($context);
		foreach ($users as $user) {
			$this->sendToUser($notification, $context, $user, $data);
		}
	}

	/**
	 * Send a notification to a single user at a time
	 *
	 * @param UserNotification $notification
	 * @param string $email
	 * @param array $data
	 */
	public function sendToUser($notification, $context, $user, $data) {
		$message = 'Sending ' . $notification->formatTitle($context, $user, $data) . ' to user ' . $user->Email;
		SS_Log::log($message, SS_Log::NOTICE);
	}
}