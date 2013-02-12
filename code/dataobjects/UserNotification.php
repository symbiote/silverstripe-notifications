<?php

/**
 * WIP. An object that represents a notification that is sent to a user
 * at some point - this could be either time based, date based
 * or on an event
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class UserNotification extends DataObject {
	public static $db = array(
		'Description' => 'Text',
		'Addresses' => 'MultiValueField',
		'TriggerOn' => "Enum('Changed,Published,Date')", // when to trigger this notification
		'SendDate' => 'SS_Datetime',
		'SendDifference' => 'Int', // should we offset from a particular date?
		'Repeat' => 'Int',	// how many seconds after SendDate we should do another notification? Only has effect
							// if TriggerOn is Schedule
		'FieldsToNotifyOn' => 'MultiValueField', // A list of object field names that should trigger notification
		'Subject' => 'Varchar(255)',
		'LastSentOn' => 'SS_Datetime',
		'NotificationText' => 'Text',
		'NotifyOnID' => 'Int',	// need to manually manage these relationships because SS doesn't do top level DataObject relations well
		'NotifyOnClass' => 'Varchar(32)',
	);

	public static $many_many = array(
		'Groups' => 'Group',
		'Users' => 'Member',
	);

	protected $availableKeywords;

	public function getCMSFields() {
		Requirements::javascript('notifications/javascript/notifications.js');
		$fields = new FieldSet();

		$timeOptions = array(
			'' => '',
			-(86400 * 90) => '3 months before',
			-(86400 * 30) => '1 month before',
			-(86400 * 7) => '1 week before',
			-(86400) => '1 day before',
		);

		$scheduleTimes = array(
			'' => '',
			86400 => 'Each day',
			7*86400 => 'Each week',
			30*86400 => 'Each month',
		);

		$header = $this->ID ? 'Edit Notification' : 'New Notification';

		$type = $this->NotifyOnClass;
		$dummy = new $type;
		$keywords = $dummy->getNotificationKeywords();

		$objectFields = array();
		$keywordInfo = new DataObjectSet();
		foreach ($keywords as $fieldName => $details) {
			$objectFields[$fieldName] = $details['short'];

			$tmp = new ViewableData();
			$tmp->Title = $fieldName;
			$tmp->Value = $details['long'];

			$keywordInfo->push($tmp);
		}

		$this->availableKeywords = $keywordInfo;

		$fields->push(new FieldGroup(
			new HeaderField('NotificationHeader', $header),
			new TextField('Description', 'Description'),
			new MultiValueTextField('Addresses', 'Email to'),
			new DropdownField('TriggerOn', _t('UserNotification.TRIGGER_ON', 'Trigger On'), $this->dbObject('TriggerOn')->enumValues()),
			$date = new DropdownDateField('SendDate', 'Date to send'),
			new MultiValueDropdownField('FieldsToNotifyOn', 'Fields to trigger on', $objectFields),
			new DropdownField('Repeat', 'Repeat Schedule', $scheduleTimes),
			new DropdownField('SendDifference', 'Send', $timeOptions),
			new TextField('Subject', 'Message Subject'),
			new TextareaField('NotificationText', 'Message')
		));

		$date->setConfig('dmyfields', true);
		$date->setConfig('dateformat', 'dd/MM/YYYY');

		$fields->push(new HiddenField('NotifyOnClass', 'attachclass', $this->NotifyOnClass));
		$fields->push(new HiddenField('NotifyOnID', 'attachid', $this->NotifyOnID));

		if ($this->ID) {
			$fields->push(new HiddenField('NotificationID', '', $this->ID));
		}

		return $fields;
	}

	/**
	 * Returns the list of available keywords
	 */
	public function availableKeywords() {
		return $this->availableKeywords;
	}

	/**
	 * Return the object this notification is attached to
	 *
	 * @return DataObject
	 */
	public function getContext() {
		return DataObject::get_by_id($this->NotifyOnClass, $this->NotifyOnID);
	}
	
	/**
	 * Returns the list of addresses that this notification will be sent to
	 */
	public function getAddresses() {
		return $this->Addresses;
	}

	/**
	 * Send this notification
	 *
	 * @param DataObject $context
	 * @param array $extraData
	 */
	public function send($context = null, $extraData = array()) {
		if (!$context) {
			$context = $this->getContext();
		}
		singleton('NotificationService')->sendNotification($this, $context, $extraData);

		// now, if this is a onetime date send, then we need to mark it as sent. Otherwise, if scheduled, we
		// need to change our date send
		if ($this->TriggerOn == 'Date' && $this->SendDate) {
			// are we on a schedule of some sort? If so, then we need to update the send date
			if ($this->Repeat) {
				$this->SendDate = strtotime($this->SendDate) + $this->Repeat;
			}
		}

		$this->LastSentOn = date('Y-m-d H:i:s');
		$this->write();
	}

	/**
	 * Sets the date value for this notification based on the object it is attached
	 * to
	 */
	public function updateSendDate() {
		
	}

	/**
	 * Update the send to date based on the context object's date
	 *
	 * @param String $newDate
	 */
	public function updateContextDate($newDate=null) {
		if (!$newDate) {
			$fields = $this->FieldsToNotifyOn->getValues();
			if (!isset($fields[0])) {
				return;
			}

			$newDateField = $fields[0];
			$newDate = $this->getContext()->$newDateField;
		}

		// how much needs to be added or removed?
		$tstamp = strtotime($newDate);
		$newDate = $tstamp + $this->SendDifference;
		// calculate using the tstamp of the relative date - the newDate value might actually be in the past
		if ($tstamp > time()) {
			$this->SendDate = date('Y-m-d H:i:s', $newDate);
		} else {
			$this->SendDate = null;
		}
	}

	/**
	 * When saving, check to see if the date needs to be updated
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		// if we're date based, make sure our dates are all okay
		if ($this->TriggerOn == 'Date') {
			$fields = $this->FieldsToNotifyOn->getValues();
			if ($fields && count($fields)) {
				$this->updateContextDate();
			} else {

			}
		}
	}

	/**
	 * When inited, make sure we create a scheduled job for our date based jobs if needbe
	 *
	 * Otherwise, you'll need to set up the cron details manually
	 */
	public function requireDefaultRecords() {
		if (ClassInfo::exists('QueuedJobService')) {
			$existing = DataObject::get_one('QueuedJobDescriptor', '"JobTitle" = \'Notification send scanner\'');
			if (!$existing || !$existing->ID) {
				// k go create
//				$nextSend = new ScanNotificationsJob();
//				singleton('QueuedJobService')->queueJob($nextSend, date('Y-m-d H:i:s', time() + 300));
			}
		}
	}
}