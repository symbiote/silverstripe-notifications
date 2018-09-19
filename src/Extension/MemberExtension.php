<?php

namespace Symbiote\Notifications\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use Symbiote\MemberProfiles\Pages\MemberProfilePage;
use Symbiote\Notifications\Model\InternalNotification;

class MemberExtension extends Extension
{
    public function getNotifications($limit = 10, $offset = 0, $filter = [])
    {
        $filter = array_merge(
            $filter,
            ['ToID' => $this->owner->ID]
        );
        return InternalNotification::get()
            ->filter($filter)
            ->limit($limit, $offset);
    }
}
