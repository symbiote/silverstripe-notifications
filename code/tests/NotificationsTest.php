<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class NotificationsTest extends SapphireTest {

	static $fixture_file = 'notifications/code/tests/NotificationsTest.yaml';
	
	protected $extraDataObjects = array(
		'NotifyOnThis'
	);

	public function testNotificationTrigger() {
		// create a new notification and add it to the object
		$notification = new SystemNotification();
		$notification->Title = "Notify on event";
		$notification->Description = 'Notifies on an event occurring';
		$notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
		$notification->Identifier = 'NOTIFY_ON_EVENT';
		$notification->NotifyOnClass = 'NotifyOnThis';
		$notification->write();

		// okay, add it to our page
		$page = $this->objFromFixture('NotifyOnThis', 'not1');
		
		$ns = new NotificationService();
		$ds = new DummyNotificationSender();
		
		Config::inst()->update('NotificationService', 'use_queues', false);
		
		$ns->addSender('dummy', $ds);
		$ns->setChannels(array('dummy'));
		$ns->notify('NOTIFY_ON_EVENT', $page);
		
		// check that there was an actual notification added into our DS
		$this->assertEquals(1, count($ds->notifications));

		// check that the message was formatted appropriately
		$msg = $ds->notifications[0];
		$this->assertEquals("This is a notfication to dummy@nowhere.com about Some Data Object", $msg->text);
	}

	public function testSpecificChannels() {
		$notification = new SystemNotification();
		$notification->Title = "Notify on event";
		$notification->Description = 'Notifies on an event occurring';
		$notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
		$notification->Identifier = 'NOTIFY_ON_EVENT';
		$notification->NotifyOnClass = 'NotifyOnThis';
		$notification->write();

		// okay, add it to our page
		$page = $this->objFromFixture('NotifyOnThis', 'not1');
		
		$ns = new NotificationService();
		$ds = new DummyNotificationSender();
		
		Config::inst()->update('NotificationService', 'use_queues', false);

		$ns->addSender('dummy', $ds);
		$ns->notify('NOTIFY_ON_EVENT', $page, array(), 'dummy');
		
		// now check that there was an actual notification added into our DS
		$this->assertEquals(1, count($ds->notifications));
		
		$msg = $ds->notifications[0];

		$this->assertEquals("This is a notfication to dummy@nowhere.com about Some Data Object", $msg->text);
	}

	public function testSendEmailNotification() {
		$notification = new SystemNotification();
		$notification->Title = "Notify on event";
		$notification->Description = 'Notifies on an event occurring';
		$notification->NotificationText = 'This is a notfication to $Member.Email about $NotifyOnThis.Title';
		$notification->Identifier = 'NOTIFY_ON_EVENT';
		$notification->NotifyOnClass = 'NotifyOnThis';
		$notification->write();

		// okay, add it to our page
		$page = $this->objFromFixture('NotifyOnThis', 'not1');
		
		$ns = new NotificationService();

		Config::inst()->update('NotificationService', 'use_queues', false);
		Config::inst()->update('EmailNotificationSender', 'send_notifications_from', 'test@test.com');
		Config::inst()->update('SystemNotification', 'default_template', false);

		$ns->setSenders(array('email' => 'EmailNotificationSender'));
		$ns->setChannels(array('email'));
		$ns->notify('NOTIFY_ON_EVENT', $page);
		
		// now check that there was an email sent
		$users = $page->getRecipients($notification->Identifier);
		$expectedTo = $users[0]->Email;
		$expectedFrom = 'test@test.com';
		$expectedSubject = $notification->Title;
		$expectedBody = "This is a notfication to $expectedTo about $page->Title";
		$expectedBody = $notification->format(nl2br($expectedBody), $page); // TODO

		$this->assertEmailSent($expectedTo, $expectedFrom, $expectedSubject);
	}
}


class DummyNotificationSender extends Object implements NotificationSender{
	
	public $notifications = array();

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
		$cls = new stdClass();
		$cls->notification = $notification;
		$cls->text = $notification->format($notification->NotificationText, $context, $user, $data);
		$cls->context = $context;
		$cls->user = $user;
		$cls->data = $data;
		
		$this->notifications[] = $cls;
	}
}