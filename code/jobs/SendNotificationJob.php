<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * A queued job for sending notifications
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class SendNotificationJob extends AbstractQueuedJob implements QueuedJob {

	public function __construct(SystemNotification $notification=null, DataObject $context=null, $data = array()) {
		if ($notification) {
			$this->notificationID = $notification->ID;
			$this->contextID = $context->ID;
			$this->contextClass = $context->class;
			$this->extraData = $data;
		}
	}
	
	public function getNotification() {
		return DataObject::get_by_id('SystemNotification', $this->notificationID);
	}

	public function getContext() {
		if ($this->contextID) {
			return DataObject::get_by_id($this->contextClass, $this->contextID);
		}
	}

	public function getTitle() {
		$context = $this->getContext();
		$notification = $this->getNotification();
		
		if ($context) {
			$title = '';
			if ($context->hasField('Title')) {
				$title = $context->Title;
			} else if ($context->hasField('Name')) {
				$title = $context->Name;
			} else if ($context->hasField('Description')) {
				$title = $context->Description;
			} else {
				$title = '#'.$context->ID;
			}
		} else {
			$title = $notification->Title;
		}

		return 'Sending notification "'.$notification->Description.'" for '.$title;
	}

	public function getJobType() {
		$notification = $this->getNotification();
		$recipients = $notification->getRecipients($this->getContext());
		$sendTo = array();
		if ($recipients) {
			if ($recipients instanceof MultiValueField) {
				$recipients = $recipients->getValues();
				foreach ($recipients as $id) {
					$sendTo[$id] = 'Member';
				}
			} else if ($recipients instanceof DataObjectSet) {
				foreach ($recipients as $r) {
					$sendTo[$r->ID] = $r->ClassName;
				}
			}

			$this->totalSteps = count($recipients);
			$this->sendTo = $sendTo;
		}

		$this->totalSteps = count($this->sendTo);

		return $this->totalSteps > 5 ? QueuedJob::QUEUED : QueuedJob::IMMEDIATE;
	}

	public function process() {
		$remaining = $this->sendTo;

		// if there's no more, we're done!
		if (!count($remaining)) {
			$this->isComplete = true;
			return;
		}

		$this->currentStep++;

		$keys = array_keys($remaining);
		$toID = array_shift($keys);
		$toClass = $remaining[$toID];
		unset($remaining[$toID]);

		$notification = $this->getNotification();
		$context = $this->getContext();

		$service = singleton('NotificationService');

		$user = DataObject::get_by_id($toClass, (int) $toID);

		$data = array();
		
		// extra data is an array - need to deserialise it!! 
		foreach ($this->extraData as $k => $v) {
			$data[$k] = $v;
		}

		// now send to the single user
		$service->sendToUser($notification, $context, $user, $data);

		// save new data
		$this->sendTo = $remaining;

		if (count($remaining) <= 0) {
			$this->isComplete = true;
			return;
		}
	}
}