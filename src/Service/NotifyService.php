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
            'see' => 'POST'
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
     * Mark a Notification as read, accepts a notification ID and returns a
     * boolean for success or failure.
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
            $notification = InternalNotification::get()
                ->filter([
                    'ID' => $ID,
                    'ToID' => $member->ID,
                    'IsRead' => false
                ])->first();
            if ($notification) {
                $notification->IsRead = true;
                $notification->write();
                return true;
            }
        }
        return false;
    }

    /**
     * Mark a Notification as seen, accepts a notification ID and returns a
     * boolean for success or failure.
     *
     * @param string|int $ID The ID of an InternalNotification for the current
     * logged in Member
     * @return boolean true when marked seen otherwise false
     */
    public function see($ID)
    {
        $member = Member::currentUser();
        if (!$member) {
            return false;
        }

        if ($ID) {
            $notification = InternalNotification::get()
                ->filter([
                    'ID' => $ID,
                    'ToID' => $member->ID
                ])->first();
            if ($notification) {
                if (!$notification->IsSeen) {
                    $notification->IsSeen = true;
                    $notification->write();
                }
                return true;
            }
        }
        return false;
    }
}
