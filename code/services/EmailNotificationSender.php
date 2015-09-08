<?php
/**
 * EmailNotificationSender
 * @author marcus@silverstripe.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class EmailNotificationSender extends Object implements NotificationSender {

	/**
	 * Email Address to send email notifications from 
	 * @var string
	 */
	private static $send_notifications_from; 


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
	 * @param SystemNotification $notification
	 * @param string $email
	 * @param array $data
	 */
	public function sendToUser($notification, $context, $user, $data) {
		$subject = $notification->format($notification->Title, $context, $user, $data);

		if(Config::inst()->get('SystemNotification', 'html_notifications')){
			$message = $notification->format($notification->NotificationContent(), $context, $user, $data);
		}else{
			$message = $notification->format(nl2br($notification->NotificationContent()), $context, $user, $data);
		}	

		if($template = $notification->getTemplate()){
			$templateData = $notification->getTemplateData($context, $user, $data);
			$templateData->setField('Body', $message);
			try {
				$body = $templateData->renderWith($template);
			} catch (Exception $e) {
				$body = $message;
			}
		}else{
			$body = $message;
		}
		
		$from = $this->config()->get('send_notifications_from');
		$to = $user->Email;
		if (!$to && method_exists($user, 'getEmailAddress')) {
			$to = $user->getEmailAddress();
		}

		// log 
		$message = "Sending $subject to $to";
		SS_Log::log($message, SS_Log::NOTICE);

		// send
		$email = new Email($from, $to, $subject);
		$email->setBody($body);
		$this->extend('onBeforeSendToUser', $email);
		$email->send();
	}
}