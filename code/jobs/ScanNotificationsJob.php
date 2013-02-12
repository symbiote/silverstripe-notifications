<?php
/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

/**
 * Job that's used to scan for notifications that
 * work on a date range. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class ScanNotificationsJob extends AbstractQueuedJob implements QueuedJob {
    public function __construct() {
		$this->lastRun = date('Y-m-d H:i:s');
	}

	public function getTitle() {
		return 'Notification send scanner';
	}

	public function getJobType() {
		return QueuedJob::QUEUED;
	}

	/**
	 * Make sure to store the list of items we must still send to
	 */
	public function setup() {
		$this->totalSteps = 1;
	}

	public function process() {
		$notifications = singleton('NotificationService')->findNotificationsForDate();

		if ($notifications && $notifications->Count()) {
			foreach ($notifications as $notification) {
				$notification->send();
			}
		}

		// now set up a new one
		$nextSend = new ScanNotificationsJob();
		singleton('QueuedJobService')->queueJob($nextSend, date('Y-m-d H:i:s', time() + 3600));

		$this->currentStep++;
		$this->isComplete = true;
	}
}