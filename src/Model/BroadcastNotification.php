<?php

namespace Symbiote\Notifications\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Core\Injector\Injector;
use Symbiote\Notifications\Service\NotificationService;
use SilverStripe\Forms\ListboxField;
use Symbiote\MultiValueField\Fields\KeyValueField;

class BroadcastNotification extends DataObject implements NotifiedOn
{
    private static $table_name = 'BroadcastNotification';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'SendNow' => 'Boolean',
        'IsPublic'  => 'Boolean',
        'Context' => 'MultiValueField'
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

        if ($this->ID) {
            $fields->dataFieldByName('SendNow')->setRightTitle('If selected, this notification will be broadcast to all users in groups selected below');

            $fields->removeByName('Groups');

            $fields->addFieldToTab('Root.Main', ListboxField::create('Groups', 'Groups', Group::get()));
        } else {
            $fields->removeByName('SendNow');
        }

        $context = KeyValueField::create('Context')->setRightTitle('Add a Link and Title field here to provide context for this message');

        $fields->replaceField('Context', $context);

        return $fields;
    }

    public function getAvailableKeywords()
    {
        $fields = $this->getNotificationTemplateData();
        return array_keys($fields);
    }

    /**
     * Gets an associative array of data that can be accessed in
     * notification fields and templates
     * @return array
     */
    public function getNotificationTemplateData()
    {
        $fields = $this->Context->getValues();
        if (!is_array($fields)) {
            $fields = [];
        }
        $fields['Content'] = $this->Content;
        return $fields;
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

    public function Link()
    {
        $context = $this->Context->getValues();
        return isset($context['Link']) ? $context['Link'] : null;
    }
}
