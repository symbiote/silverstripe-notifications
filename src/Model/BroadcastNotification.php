<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Core\Injector\Injector;
use Symbiote\Notifications\Service\NotificationService;
use SilverStripe\Forms\ListboxField;



class BroadcastNotification extends DataObject implements NotifiedOn
{
    private static $table_name = 'BroadcastNotification';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'Link'  => 'Varchar(255)',
        'SendNow' => 'Boolean',
        'IsPublic'  => 'Boolean',
    ];

    private static $many_many = [
        'Groups' => Group::class
    ];

    public function onBeforeWrite()
    {
        if ($this->SendNow) {
            $this->SendNow = false;
            Injector::inst()->get(NotificationService::class)->notify(
                'BROADCAST',
                $this
            );
        }
        parent::onBeforeWrite();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('IsPublic')->setRightTitle('Indicate whether this can be displayed to public users');
        $fields->dataFieldByName('Link')->setRightTitle('An optional link to attach to this message');

        if ($this->ID) {
            $fields->dataFieldByName('SendNow')->setRightTitle('If selected, this notification will be broadcast to all users in groups selected below');

            $fields->removeByName('Groups');

            $fields->addFieldToTab('Root.Main', ListboxField::create('Groups', 'Groups', Group::get()));
        } else {
            $fields->removeByName('SendNow');
        }



        return $fields;
    }

    public function getAvailableKeywords()
    {
        return [
            'Content',
            'Link'
        ];
    }

    /**
     * Gets an associative array of data that can be accessed in
     * notification fields and templates
     * @return array
     */
    public function getNotificationTemplateData()
    {
        return [
            'Content' => $this->Content,
            'Link' => $this->Link,
        ];
    }

    /**
     * Gets the list of recipients for a given notification event, based on this object's
     * state.
     * @param string $event The Identifier of the notification being sent
     * @return array An array of Member objects
     */
    public function getRecipients($event)
    {
        $groupIds = $this->Groups()->column('ID');
        if (count($groupIds)) {
            $members = Member::get()->filter('Groups.ID', $groupIds);
            return $members;
        }
        return [];
    }
}
