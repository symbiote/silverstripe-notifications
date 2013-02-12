<?php

/**
 * Indicates that this object gets notified on. 
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
interface NotifiedOn {
	
	/**
	 * Return a list of all available keywords in the format: 
	 * array('keyword' => 'A description')
	 * @return array
	 */
	public function getAvailableKeywords();

	/**
	 * Gets a replacement for a keyword
	 * @param $event The identifier of the event that triggered this notification
	 * @return string
	 */
	public function getKeyword($keyword);
	
	/**
	 * Gets the list of recipients for a given notification event, based on this object's 
	 * state. 
	 * 
	 * @param $event The identifier of the event that triggered this notification
	 * @return array An array of Member objects		
	 */
	public function getRecipients($event);
}