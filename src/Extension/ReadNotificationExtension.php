<?php

namespace Symbiote\Notifications\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use Symbiote\Notifications\Model\InternalNotification;

class ReadNotificationExtension  extends Extension
{
    public function onBeforeInit()
    {
        $member = Member::currentUser();
        if ($member && $this->owner->getRequest()->getVar('notification')) {
            $id = $this->owner->getRequest()->getVar('notification');
            $note = InternalNotification::get()->byID($id);
            if ($note && $note->ToID == $member->ID) {
                $note->IsRead = 1;
                $note->write();
            }
        }
    }
}
