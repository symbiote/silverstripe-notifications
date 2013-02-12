<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * A task that checks to see if there's any email notifications that need sending
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class CheckEmailNotifications extends BuildTask {
    public function run($request) {
		$notifications = singleton('NotificationService')->findNotificationsForDate();

		if ($notifications && $notifications->Count()) {
			foreach ($notifications as $notification) {
				$notification->send();
			}
		}
	}
}