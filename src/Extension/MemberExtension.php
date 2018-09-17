<?php

namespace Symbiote\Notifications\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use Symbiote\MemberProfiles\Pages\MemberProfilePage;
use Symbiote\Notifications\Model\InternalNotification;

class MemberExtension extends Extension
{
    public function getNotifications($read = false, $limit = 10, $offset = 0)
    {
        return InternalNotification::get()
            ->filter([
                'ToID' => $this->owner->ID,
                'IsRead' => $read
            ])->limit($limit, $offset);
    }
}
