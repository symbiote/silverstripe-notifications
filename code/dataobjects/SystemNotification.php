<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class SystemNotification extends DataObject {
	public static $db = array(
		'Identifier'		=> 'Varchar',		// used to reference this notification from code
		'Title'				=> 'Varchar(255)',
		'Description'		=> 'Text',
		'NotificationText'	=> 'Text',
		'NotifyOnClass'		=> 'Varchar(32)',
		'Template'			=> 'Varchar()'
//		'SendDate'			=> 'SS_Datetime',
//		'SendDifference'	=> 'Int',	// should we offset from a particular date?
//		'Repeat'			=> 'Int',	// how many seconds after SendDate we should do another notification? Only has effect
//										// if TriggerOn is Schedule
//		'FieldsToNotifyOn'	=> 'MultiValueField', // A list of object field names that should trigger notification
//
//		'LastSentOn'		=> 'SS_Datetime',
		
//		'TriggerOn'			=> "Enum('Changed,Date')", // when to trigger this notification
	);
	
	public static $many_many = array(
		'Users'			=> 'Member',
		'Groups'		=> 'Group',
	);
	
	/**
	 * A list of globally available keywords
	 *
	 * @var array
	 */
	protected static $global_keywords = array();
	
	/**
	 * Adds a globally available keyword value
	 *
	 * @param string $k
	 * @param string $v 
	 */
	public static function add_keyword($k, $v) {
		self::$global_keywords[$k] = $v;
	}

	/**
	 * @return FieldList 
	 */
	public function getCMSFields() {
		$types = ClassInfo::implementorsOf('NotifiedOn');
		$types = array_combine($types, $types);
		unset($types['NotifyOnThis']);

		if (!$types) {
			$types = array();
		}
		array_unshift($types, '');
		
		$keywords = array();
		
		foreach (self::$global_keywords as $k => $v) {
			$keywords[] = '<strong>'.$k.'</strong>';
		}
		
		$availableKeywords = new LiteralField('AvailableKeywords', "Available keywords will be shown if you select a NotifyOnClass");
		if ($this->NotifyOnClass) {
			$dummy = singleton($this->NotifyOnClass);
			if ($dummy instanceof NotifiedOn) {
				foreach ($dummy->getAvailableKeywords() as $keyword => $desc) {
					$keywords[] = '<strong>'.$keyword.'</strong> - '.$desc;
				}
			}
		}

		$availableKeywords->setContent('<div class="field"><div class="middleColumn"><p><u>Available Keywords:</u> </p><ul><li>$'.implode('</li><li>$', $keywords).'</li></ul></div></div>');

		$identifiers = Config::inst()->get('NotificationService', 'identifiers');
		if (count($identifiers)) {
			$identifiers = array_combine($identifiers, $identifiers);
		}
		
		$fields = new FieldList();

		$relevantMsg = 'Relevant for (note: this notification will only be sent if the 
			context of raising the notification is of this type)';
		$fields->push(new TabSet('Root', 
			new Tab('Main', 
				new DropdownField('Identifier', _t('SystemNotification.IDENTIFIER', 'Identifier'), $identifiers),
				new TextField('Title', _t('SystemNotification.TITLE', 'Title')),
				new TextField('Description', _t('SystemNotification.DESCRIPTION', 'Description')),
				new DropdownField('NotifyOnClass', _t('SystemNotification.NOTIFY_ON_CLASS', $relevantMsg), $types),
				new TextField('Template', _t('SystemNotification.TEMPLATE', 'Template (Optional)')),
				new TextareaField('NotificationText', _t('SystemNotification.TEXT', 'Text')),
				$availableKeywords
			)
		));

		return $fields;
	}

	/**
	 * Get a list of recipients from the notification with the given context
	 * 
	 * @param DataObject $context
	 *				The context object this notification is attached to. 
	 */
	public function getRecipients($context = null) {
		$recipients = new ArrayList();

		// if we have a context, use that for returning the recipients
		if ($context && $context instanceof NotifiedOn) {
			$recipients->merge($context->getRecipients($this->Identifier));
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
	 * Handle calls to format* methods to format content keywords appropriately
	 */
	public function __call($method, $arguments) {
		if (strpos($method, 'format') === 0) {
			$property = substr($method, 6);
			$text = $this->$property;
			return $this->format($text, $arguments[0], $arguments[1], $arguments[2]);
		}

		return parent::__call($method, $arguments);
	}

	/**
	 * Format text with given keywords etc
	 *
	 * @param DataObject $context
	 * @param Member $user
	 * @param array $extraKeywords 
	 */
	protected function format($text, $context, $user=null, $extraKeywords=array()) {
		$memberKeywords = Config::inst()->get('Member', 'db');

		// extract certain keywords
		if (preg_match_all('|\$([a-z]+)|i', $text, $matches)) {
			foreach ($matches[1] as $keyword) {
				$rep = null;
				
				if ($context instanceof NotifiedOn) {
					$rep = $context->getKeyword($keyword);
				}
				
				if (!$rep && $user && isset($memberKeywords[$keyword])) {
					$rep = $user->$keyword;
				}

				if (!$rep && isset($extraKeywords[$keyword])) {
					$rep = $extraKeywords[$keyword];
				}
				
				if (!$rep && isset(self::$global_keywords[$keyword])) {
					$rep = self::$global_keywords[$keyword];
				}

				$text = str_replace('$'.$keyword, $rep, $text);
			}
		}

		return $text;
	}

	public function forTemplate(){
		return self::$render_with_template ? $this->renderWith(self::$render_with_template) : $this->NotificationText;
	}
}