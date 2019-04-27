<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;
use Symbiote\MultiValueField\Fields\KeyValueField;
use SilverStripe\Security\Permission;

class InternalNotification extends DataObject {
    private static $table_name = 'InternalNotification';
    
    private static $db = [
        'Title' => 'Varchar(255)',
        'Message'   => 'Text',
        'SentOn' => 'Datetime',
        'IsRead'    => 'Boolean',
        'IsSeen'    => 'Boolean',
        'Context' => MultiValueField::class,
    ];

    private static $has_one = [
        'To'        => Member::class,
        'From'      => Member::class,
    ];

    private static $summary_fields = [
        'Title', 'To.Name', 'SentOn'
    ];

    private static $default_sort = 'ID DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Context', KeyValueField::create('Context'));
        return $fields;
    }

    public function canView($member = null) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        if (Permission::check('ADMIN')) {
            return true;
        }
        return $member && (!$this->ID || $this->ToID == $member->ID || $this->FromID == $member->ID);
    }

    public function canEdit($member = null) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        if (Permission::check('ADMIN')) {
            return true;
        }
        return $member && (!$this->ID || $this->ToID == $member->ID);
    }

}