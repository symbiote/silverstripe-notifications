<?php
/**
 * SystemNotification
 * @author marcus@symbiote.com.au, shea@livesource.co.nz
 * @license http://silverstripe.org/bsd-license/
 */
class SystemNotification extends DataObject implements PermissionProvider{
	
	/**
	 * A list of all the notifications that the system manages. 
	 * @var array
	 */
	private static $identifiers = array();

	/**
	 * A list of globally available keywords for all NotifiedOn implementors
	 * @var array
	 */
	private static $global_keywords = array();

	/**
	 * If true, notification text can contain html and a wysiwyg editor will be
	 * used to create the notification text rather than textarea
	 * @var boolean
	 */
	private static $html_notifications = false;

	/**
	 * Name of a template file to render all notifications with
	 * Note: it's up to the NotificationSender to decide whether or not to use it
	 * @var string
	 */
	private static $default_template;

	private static $db = array(
		'Identifier'		=> 'Varchar',		// used to reference this notification from code
		'Title'			=> 'Varchar(255)',
		'Description'		=> 'Text',
		'NotificationText'	=> 'Text',
		'NotificationHTML'	=> 'HTMLText',
		'NotifyOnClass'		=> 'Varchar(128)',
		'CustomTemplate'	=> 'Varchar'
	);
	
	/**
	 * @return FieldList 
	 */
	public function getCMSFields() {
		// Get NotifiedOn implementors
		$types = ClassInfo::implementorsOf('NotifiedOn');
		$types = array_combine($types, $types);
		unset($types['NotifyOnThis']);
		if (!$types) $types = array();
		array_unshift($types, '');

		// Available keywords
		$keywords = $this->getKeywords();
		if(count($keywords)){
			$availableKeywords = '<div class="field"><div class="middleColumn"><p><u>Available Keywords:</u> </p><ul><li>$'.implode('</li><li>$', $keywords).'</li></ul></div></div>';
		}else{
			$availableKeywords = "Available keywords will be shown if you select a NotifyOnClass";
		}
		
		// Identifiers
		$identifiers = $this->config()->get('identifiers');
		if (count($identifiers)) $identifiers = array_combine($identifiers, $identifiers);
		
		$fields = FieldList::create();

		$relevantMsg = 'Relevant for (note: this notification will only be sent if the context of raising the notification is of this type)';
		$fields->push(TabSet::create('Root', 
			Tab::create('Main', 
				DropdownField::create('Identifier', _t('SystemNotification.IDENTIFIER', 'Identifier'), $identifiers),
				TextField::create('Title', _t('SystemNotification.TITLE', 'Title')),
				TextField::create('Description', _t('SystemNotification.DESCRIPTION', 'Description')),
				DropdownField::create('NotifyOnClass', _t('SystemNotification.NOTIFY_ON_CLASS', $relevantMsg), $types),
				TextField::create('CustomTemplate', _t('SystemNotification.TEMPLATE', 'Template (Optional)'))->setAttribute('placeholder', $this->config()->get('default_template')),
				LiteralField::create('AvailableKeywords', $availableKeywords)
			)
		));

		if($this->config()->html_notifications){
			$fields->insertBefore(HTMLEditorField::create('NotificationHTML', _t('SystemNotification.TEXT', 'Text')), 'AvailableKeywords');
		}else{
			$fields->insertBefore(TextareaField::create('NotificationText', _t('SystemNotification.TEXT', 'Text')), 'AvailableKeywords');
		}
		
		$this->extend('updateCMSFields', $fields);

		return $fields;
	}


	/**
	 * Get a list of available keywords to help the cms user know what's available
	 * @return array
	 **/
	public function getKeywords(){
		$keywords = array();
		
		foreach ($this->config()->get('global_keywords') as $k => $v) {
			$keywords[] = '<strong>'.$k.'</strong>';
		}
		
		if ($this->NotifyOnClass) {
			$dummy = singleton($this->NotifyOnClass);
			if ($dummy instanceof NotifiedOn || method_exists($dummy, 'getAvailableKeywords')) {
				if(is_array($dummy->getAvailableKeywords())){
					foreach ($dummy->getAvailableKeywords() as $keyword => $desc) {
						$keywords[] = '<strong>'.$keyword.'</strong> - '.$desc;
					}	
				}
			}
		}

		return $keywords;
	}


	/**
	 * Get a list of recipients from the notification with the given context
	 * 
	 * @param DataObject $context
	 *				The context object this notification is attached to. 
	 * @return ArrayList
	 */
	public function getRecipients($context = null) {
		$recipients = ArrayList::create();

		// if we have a context, use that for returning the recipients
		if ($context && ($context instanceof NotifiedOn) || method_exists($context, 'getRecipients')) {
			$contextRecipients = $context->getRecipients($this->Identifier);
			if ($contextRecipients) {
				$recipients->merge($contextRecipients);
			}
		}

		if ($context instanceof Member) {
			$recipients->push($context);
		} else if ($context instanceof Group) {
			$recipients = $context->Members();
		}

		// otherwise load with a preconfigured list of recipients
		return $recipients;
	}


	/**
	 * Format text with given keywords etc
	 *
	 * @param sting $text
	 * @param DataObject $context
	 * @param Member $user
	 * @param array $extraData 
	 * @return $string
	 */
	public function format($text, $context, $user=null, $extraData=array()) {
		$data = $this->getTemplateData($context, $user, $extraData);

		// render
		$viewer = new SSViewer_FromString($text);
		try {
			$string = $viewer->process($data);	
		} catch (Exception $e) {
			$string = $text;
		}
		return $string; 
	}


	/**
	 * Get compiled template data to render a string with
	 *
	 * @param DataObject $context
	 * @param Member $user
	 * @param array $extraData 
	 * @return ArrayData
	 */
	public function getTemplateData($context, $user=null, $extraData=array()){
		// useful global data
		$data = array(
			'ThemeDir' => SSViewer::get_theme_folder(),
			'SiteConfig' => SiteConfig::current_site_config()
		);

		// the context object, keyed by it's class name
		$data[$context->ClassName] = $context;

		// data as defined by the context object
		$contextData = $context->getNotificationTemplateData();
		if(is_array($contextData)){
			$data = array_merge($data, $contextData);	
		}

		// the member the notification is being sent to  
		$data['Member'] = $user;

		// extra data
		$data = array_merge($data, $extraData);

		return ArrayData::create($data);
	}


	/**
	 * Get the custom or default template to render this notification with
	 * @return string
	 */
	public function getTemplate(){
		return $this->CustomTemplate ? $this->CustomTemplate : $this->config()->get('default_template');
	}


	/**
	 * Get the notification content, whether that's html or plain text
	 * @return string
	 */
	public function NotificationContent(){
		return $this->config()->html_notifications ? $this->NotificationHTML : $this->NotificationText;
	}

	public function canView($member = null) {
		return Permission::check('ADMIN') || Permission::check('SYSTEMNOTIFICATION_VIEW');
	}

	public function canEdit($member = null) {
		return Permission::check('ADMIN') || Permission::check('SYSTEMNOTIFICATION_EDIT');
	}

	public function canDelete($member = null) {
		return Permission::check('ADMIN') || Permission::check('SYSTEMNOTIFICATION_DELETE');
	}

	public function canCreate($member = null) {
		return Permission::check('ADMIN') || Permission::check('SYSTEMNOTIFICATION_CREATE');
	}

	public function providePermissions() {
		return array(
			'SYSTEMNOTIFICATION_VIEW' => array(
				'name' => 'View System Notifications',
				'category' => 'Notifications',
			),
			'SYSTEMNOTIFICATION_EDIT' => array(
				'name' => 'Edit a System Notification',
				'category' => 'Notifications',
			),
			'SYSTEMNOTIFICATION_DELETE' => array(
				'name' => 'Delete a System Notification',
				'category' => 'Notifications',
			),
			'SYSTEMNOTIFICATION_CREATE' => array(
				'name' => 'Create a System Notification',
				'category' => 'Notifications'
			)
		);
	}

}
