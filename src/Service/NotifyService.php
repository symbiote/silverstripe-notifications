<?php

namespace Symbiote\Notifications\Service;

use SilverStripe\Security\Member;
use Symbiote\Notifications\Model\InternalNotification;

class NotifyService
{
    public function webEnabledMethods()
    {
        return array(
            'list' => 'GET',
            'read' => 'POST',
        );
    }

    /**
     * List all the notifications a user has, on a particular item,
     * and/or of a particular type
     *
     * @return DataList|null
     */
    public function list()
    {
        $member = Member::currentUser();
        if (!$member) {
            return false;
        }

        return $member->getNotifications();
    }

    /**
     * Mark a Notifaction as read, accepts a notification ID and returns a
     * boolean for sucess or failure.
     *
     * @param string|int $ID The ID of an InternalNotification for the current
     * logged in Member
     * @return boolean true when marked read otherwise false
     */
    public function read($ID)
    {
        $member = Member::currentUser();
        if (!$member) {
            return false;
        }

        if ($ID) {
            $notifaction = InternalNotification::get()
                ->filter([
                    'ID' => $ID,
                    'ToID' => $member->ID,
                    'IsRead' => false
                ])->first();
            if ($notifaction) {
                $notifaction->IsRead = true;
                $notifaction->write();
                return true;
            }
        }
        return false;
    }
}
