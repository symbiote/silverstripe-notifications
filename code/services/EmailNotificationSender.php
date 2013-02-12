<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * A notification sender that sends via email
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EmailNotificationSender implements NotificationChannel {
	public function __construct() {}

	/**
	 * Send a notification via email to the selected users
	 *
	 * @param SystemNotification	$notification
	 * @param DataObject			$context
	 * @param array					$data
	 */
    public function sendNotification($notification, $context, $data) {
		$users = $notification->getRecipients($context);
		foreach ($users as $user) {
			$this->sendToUser($notification, $context, $user, $data);
		}
	}

	/**
	 * Send a notification directly to a single user
	 *
	 * @param UserNotification $notification
	 * @param string $email
	 * @param array $data
	 */
	public function sendToUser($notification, $context, $user, $data) {
		$subject = $notification->formatTitle($context, $user, $data);
		$message = $notification->formatNotificationText($context, $user, $data);
		
		$from = SiteConfig::current_site_config()->ReturnEmailAddress;
		
		$to = $user->Email;
		if (!$to && method_exists($user, 'getEmailAddress')) {
			$to = $user->getEmailAddress();
		}
		 
		$email = new Email($from, $to, $subject);
		$email->setBody(nl2br($message));
		$email->send();
	}
}