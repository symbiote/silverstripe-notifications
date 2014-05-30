<?php

/**
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class NotifyOnThis extends DataObject implements NotifiedOn, TestOnly {
	public static $db = array(
		'Title' => 'Varchar',
		'NotifyBy' => 'SS_Datetime',
		'Status' => 'Varchar',
	);
	
	protected $availableKeywords;
	
	/**
	 * Return a list of all available keywords in the format 
	 * 
	 * array(
	 *	'keyword' => 'A description'
	 * )
	 *
	 * @return array
	 */
	public function getAvailableKeywords() {
		if (!$this->availableKeywords) {
			$objectFields = Object::combined_static($this->class, 'db');

			// $objectFields = array_combine(array_keys($objectFields), array_keys($objectFields));
			$objectFields['Created'] = 'Created';
			$objectFields['LastEdited'] = 'LastEdited';

			$this->availableKeywords = array();

			foreach ($objectFields as $key => $value) {
				$this->availableKeywords[$key] = array('short'=>$key, 'long'=>$key);
			}
			
		}
		
		return $this->availableKeywords;
	}

	
	/**
	 * Gets an associative array of data that can be accessed in
	 * notification fields and templates 
	 * @return array
	 */
	public function getNotificationTemplateData(){
		return array();
	}
	
	
	/**
	 * Gets the list of recipients for a given notification event, based on this object's 
	 * state. 
	 * 
	 * @param string $event
	 *				The Identifier of the notification being sent
	 */
	public function getRecipients($event) {
		// this should actually be specified on the object directly. If it's hitting here, 
		// then we need to error so the code writer knows to implement!
		$member = new Member;
		$member->Email = 'dummy@nowhere.com';
		$member->FirstName = "First";
		$member->Surname = "Last";

		return array($member);
	}
}