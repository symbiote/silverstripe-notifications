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
		$notification->NotificationText = 'Send to $Email with the $Title';
		$notification->Identifier = 'NOTIFY_ON_EVENT';
		$notification->NotifyOnClass = 'NotifyOnThis';
		$notification->write();

		// okay, add it to our page
		$page = $this->objFromFixture('NotifyOnThis', 'not1');
		
		$ns = new NotificationService();
		$ds = new DummyNotificationSender;
		
		NotificationService::$use_queues = false;
		
		$ns->addNotificationSender('dummy', $ds);
		$ns->setChannels(array('dummy'));
		$ns->notify('NOTIFY_ON_EVENT', $page);
		
		// now check that there was an actual notification added into our DS
		$this->assertEquals(1, count($ds->notifications));
		
		$msg = $ds->notifications[0];
		$this->assertEquals("Send to dummy@nowhere.com with the Some Data Object", $msg->text);
	}

	public function testSpecificChannels() {
		$notification = new SystemNotification();
		$notification->Title = "Notify on event";
		$notification->Description = 'Notifies on an event occurring';
		$notification->NotificationText = 'Send to $Email with the $Title';
		$notification->Identifier = 'NOTIFY_ON_EVENT';
		$notification->NotifyOnClass = 'NotifyOnThis';
		$notification->write();

		// okay, add it to our page
		$page = $this->objFromFixture('NotifyOnThis', 'not1');
		
		$ns = new NotificationService();
		$ds = new DummyNotificationSender;
		
		NotificationService::$use_queues = false;

		$ns->addNotificationSender('dummy', $ds);
		$ns->notify('NOTIFY_ON_EVENT', $page, array(), 'dummy');
		
		// now check that there was an actual notification added into our DS
		$this->assertEquals(1, count($ds->notifications));
		
		$msg = $ds->notifications[0];
		$this->assertEquals("Send to dummy@nowhere.com with the Some Data Object", $msg->text);
	}
	
//
//	public function testAddNotificationToMultiple() {
//		DataObject::add_extension('Agreement', 'Notifiable');
//		DataObject::add_extension('AgreementMilestone', 'Notifiable');
//
//		// create a new notification and add it to the page
//		$notification = new UserNotification();
//		$notification->Addresses = array('me@localhost.com');
//		$notification->NotificationText = "This is your notification";
//		$notification->SendDate = date('Y-m-d 00:00:00');
//		$notification->write();
//
//		// okay, add it to our page
//		$agreement = $this->objFromFixture('Agreement', 'agreement');
//		singleton('NotificationService')->addNotification($agreement, $notification);
//
//		$agreement = DataObject::get_by_id('Agreement', $agreement->ID);
//		$this->assertNotNull($agreement->getNotifications());
//
//		$note = $agreement->getNotifications()->First();
//
//		$this->assertEquals(array('me@localhost.com'), $note->Addresses->getValues());
//
//
//		$nextnotification = new UserNotification();
//		$nextnotification->Addresses = array('other@localhost.com');
//		$nextnotification->NotificationText = "This is your notification";
//		$nextnotification->SendDate = date('Y-m-d 00:00:00');
//		$nextnotification->write();
//
//		$milestone = $this->objFromFixture('AgreementMilestone', 'milestone');
//		singleton('NotificationService')->addNotification($milestone, $nextnotification);
//
//		$milestone = DataObject::get_by_id('AgreementMilestone', $milestone->ID);
//		$this->assertNotNull($milestone->getNotifications());
//
//		$note = $milestone->getNotifications()->First();
//
//		$this->assertEquals(array('other@localhost.com'), $note->Addresses->getValues());
//		
//	}
//
//	public function testSendNotificationJob() {
//		DataObject::add_extension('Agreement', 'Notifiable');
//		$page = $this->objFromFixture('Agreement', 'agreement');
//
//		$notification = new UserNotification();
//		$notification->TriggerOn = 'Changed';
//		$notification->Addresses = array('me@localhost.com');
//		$notification->NotificationText = "This is your notification";
//		$notification->write();
//
//		$page->addNotification($notification);
//
//		// now, lets change the page and make sure we're triggered
//		$sendService = $this->getMock('EmailNotificationSender', array('sendToUser'));
//		$sendService->expects($this->once())->method('sendToUser');
//		singleton('NotificationService')->setNotificationSender($sendService);
//
//		$page->AgreementType = 'National Partnership';
//		$page->write();
//
//		// now lets make sure that the job is queued
//		$service = singleton('QueuedJobService');
//		$jobs = $service->getJobList(2);
//
//		$this->assertEquals(1, $jobs->Count());
//		$service->runJob($jobs->First()->ID);
//
//		$jobs = $service->getJobList(2);
//		$this->assertNull($jobs);
//
//	}
//
//	public function testChangeNotifications() {
//		NotificationService::$use_queues = false;
//		DataObject::add_extension('Agreement', 'Notifiable');
//		$page = $this->objFromFixture('Agreement', 'agreement');
//
//		$notification = new UserNotification();
//		$notification->TriggerOn = 'Changed';
//		$notification->Addresses = array('me@localhost.com');
//		$notification->NotificationText = "This is your notification";
//		$notification->write();
//
//		$page->addNotification($notification);
//
//		
//		// now, lets change the page and make sure we're triggered
//		$sendService = $this->getMock('EmailNotificationSender', array('sendNotification'));
//		$sendService->expects($this->once())->method('sendNotification');
//		singleton('NotificationService')->setNotificationSender($sendService);
//
//		$page->AgreementType = 'National Partnership';
//		$page->write();
//	}
//
//	public function testChangeNotificationsWithProperties() {
//		NotificationService::$use_queues = false;
//
//		DataObject::add_extension('Agreement', 'Notifiable');
//		$page = $this->objFromFixture('Agreement', 'agreement');
//
//		$notification = new UserNotification();
//		$notification->TriggerOn = 'Changed';
//		$notification->FieldsToNotifyOn = array('AgreementType', 'SignedBy');
//		$notification->Addresses = array('me@localhost.com');
//		$notification->NotificationText = "This is your notification";
//		$notification->write();
//
//		$page->addNotification($notification);
//
//
//		// now, lets change the page and make sure we're triggered
//		$sendService = $this->getMock('EmailNotificationSender', array('sendNotification'));
//		$sendService->expects($this->once())->method('sendNotification');
//		singleton('NotificationService')->setNotificationSender($sendService);
//
//		$page->AgreementType = 'National Partnership';
//		$page->write();
//
//		// now, lets change 2 properties and make sure it's still only sent once
//		$sendService = $this->getMock('EmailNotificationSender', array('sendNotification'));
//		$sendService->expects($this->once())->method('sendNotification');
//		singleton('NotificationService')->setNotificationSender($sendService);
//
//		$page->AgreementType = 'National Partnership';
//		$page->SignedBy = 'Someone';
//		$page->write();
//		
//		$sendService = $this->getMock('EmailNotificationSender', array('sendNotification'));
//		$sendService->expects($this->never())->method('sendNotification');
//		singleton('NotificationService')->setNotificationSender($sendService);
//		
//		$page->AgreementStatus = 'Anticipated';
//		$page->write();
//	}
}


class DummyNotificationSender {
	
	public $notifications = array();
	
	public function __construct() {}

	/**
	 * Send a notification via email to the selected users
	 *
	 * @param UserNotification $notification
	 * @param array $data
	 */
    public function sendNotification(SystemNotification $notification, $context, $data) {
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
	public function sendToUser(SystemNotification $notification, $context, $user, $data) {
		$cls = new stdClass();
		$cls->notification = $notification;
		$cls->text = $notification->formatNotificationText($context, $user, $data);
		$cls->context = $context;
		$cls->user = $user;
		$cls->data = $data;
		
		$this->notifications[] = $cls;
	}
}