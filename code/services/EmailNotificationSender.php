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
	 * @param UserNotification | SystemNotification $notification
	 * @param string $email
	 * @param array $data
	 */
	public function sendToUser($notification, $context, $user, $data) {
		$subject = $notification->formatTitle($context, $user, $data);
		$message = nl2br($notification->formatNotificationText($context, $user, $data));

		if($notification->Template){
			try {
				$body = ArrayData::create(array(
					'Subject' => $subject, 
					'Content' => $message,
					'ThemeDir' => SSViewer::get_theme_folder()
				))->renderWith($notification->Template);	
			} catch (Exception $e) {
				$body = $message;
			}
		}else{
			$body = $message;
		}
		
		$from = SiteConfig::current_site_config()->ReturnEmailAddress;
		
		$to = $user->Email;
		if (!$to && method_exists($user, 'getEmailAddress')) {
			$to = $user->getEmailAddress();
		}
		 
		$email = new Email($from, $to, $subject);
		$email->setBody($body);
		$email->send();
	}
}