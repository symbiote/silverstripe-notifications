<?php

/**
 * A helper for retrieving keywords etc
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class NotificationHelper {
	private $owner;
	
	protected $availableKeywords;
	
	public function __construct($owner) {
		$this->owner = $owner;
	}
	
	
	/*
	 * Return a list of all available keywords in the format 
	 * 
	 * array(
	 *	'keyword' => 'A description'
	 * )
	 */
	public function getAvailableKeywords() {
		if (!$this->availableKeywords) {
			$objectFields = $this->owner->db(); // Config::inst()->get($this->owner->class, 'db');

			// $objectFields = array_combine(array_keys($objectFields), array_keys($objectFields));
			$objectFields['Created'] = 'Created';
			$objectFields['LastEdited'] = 'LastEdited';
			$objectFields['Link'] = 'Link';

			$this->availableKeywords = array();

			foreach ($objectFields as $key => $value) {
				$this->availableKeywords[$key] = $key;
			}
			
		}
		return $this->availableKeywords;
	}

	/*
	 * Gets a replacement for a keyword
	 */
	public function getKeyword($keyword) {
		$k = $this->getAvailableKeywords();
		
		if ($keyword == 'Link') {
			$link = Director::makeRelative($this->owner->Link());
			return Controller::join_links(Director::absoluteBaseURL(), $link);
		}
		
		if (isset($k[$keyword])) {
			return $this->owner->$keyword;
		}
	}
}
