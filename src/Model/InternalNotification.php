<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;


class InternalNotification extends DataObject {
    private static $db = [
        'Title' => 'Varchar(255)',
        'Message'   => 'Text',
        'SentOn' => 'Datetime',
        'IsRead'    => 'Boolean',
    ];

    private static $has_one = [
        'To'        => Member::class,
        'From'      => Member::class,
    ];

    private static $summary_fields = [
        'Title', 'To.Name', 'SentOn'
    ];

    private static $default_sort = 'ID DESC';

    public function canView($member = null) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        return $member && $this->ToID == $member->ID || $this->FromID == $member->ID;
    }

    public function canEdit($member = null) {
        $member = $member ?: Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        return $member && $this->ToID == $member->ID;
    }
}