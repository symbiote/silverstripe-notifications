<?php
/**
 * NotifiedOn
 * @author marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
interface NotifiedOn {
	/**
	 * Return a list of available keywords in the format 
	 * array('keyword' => 'A description') to help users format notification fields
	 * @return array
	 */
	public function getAvailableKeywords();

	/**
	 * Gets an associative array of data that can be accessed in
	 * notification fields and templates 
	 * @return array
	 */
	public function getNotificationTemplateData();
	
	/**
	 * Gets the list of recipients for a given notification event, based on this object's 
	 * state. 
	 * $event The Identifier of the notification being sent
	 * @return array An array of Member objects	
	 */
	public function getRecipients($event);
}