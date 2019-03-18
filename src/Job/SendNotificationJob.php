<?php

namespace Symbiote\Notifications\Job;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Symbiote\Notifications\Model\SystemNotification;
use Symbiote\Notifications\Service\NotificationService;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\MultiValueField\ORM\FieldType\MultiValueField;

/* All code covered by the BSD license located at http://silverstripe.org/bsd-license/ */

if (class_exists('Symbiote\QueuedJobs\Services\AbstractQueuedJob')) {

    /**
     * A queued job for sending notifications
     * @author Marcus Nyeholt <marcus@symbiote.com.au>
     */
    class SendNotificationJob extends AbstractQueuedJob implements QueuedJob
    {
        /**
         * SendNotificationJob constructor.
         * @param \Symbiote\Notifications\Model\SystemNotification|null $notification
         * @param \SilverStripe\ORM\DataObject|null                     $context
         * @param array                                                 $data
         */
        public function __construct(
            SystemNotification $notification = null,
            DataObject $context = null,
            $data = []
        ) {
            if ($notification) {
                $this->notificationID = $notification->ID;
                $this->contextID = $context->ID;
                $this->contextClass = get_class($context);
                $this->extraData = $data;
            }
        }

        /**
         * @return \SilverStripe\ORM\DataObject
         */
        public function getNotification()
        {
            return SystemNotification::get()->byID($this->notificationID);
        }

        /**
         * @return \SilverStripe\ORM\DataObject|null
         */
        public function getContext()
        {
            if ($this->contextID) {
                return DataObject::get_by_id($this->contextClass, $this->contextID);
            }

            return;
        }

        /**
         * @return string
         */
        public function getTitle()
        {
            $context = $this->getContext();
            $notification = $this->getNotification();

            if ($context) {
                $title = '';
                if ($context->hasField('Title')) {
                    $title = $context->Title;
                } else {
                    if ($context->hasField('Name')) {
                        $title = $context->Name;
                    } else {
                        if ($context->hasField('Description')) {
                            $title = $context->Description;
                        } else {
                            $title = '#'.$context->ID;
                        }
                    }
                }
            } else {
                $title = $notification->Title;
            }

            return 'Sending notification "'.$notification->Description.'" for '.$title;
        }

        /**
         * @return string
         */
        public function getJobType()
        {
            $notification = $this->getNotification();
            $recipients = $notification->getRecipients($this->getContext());
            $sendTo = [];
            if ($recipients) {
                if (is_array($recipients) || $recipients instanceof DataList || $recipients instanceof ArrayList) {
                    foreach ($recipients as $r) {
                        $sendTo[$r->ID] = $r->ClassName;
                    }
                } else {
                    if ($recipients instanceof MultiValueField) {
                        $recipients = $recipients->getValues();
                        foreach ($recipients as $id) {
                            $sendTo[$id] = Member::class;
                        }
                    }
                }

                $this->totalSteps = count($recipients);
                $this->sendTo = $sendTo;
            }

            $this->totalSteps = count($this->sendTo);

            return $this->totalSteps > 5 ? QueuedJob::QUEUED : QueuedJob::IMMEDIATE;
        }

        public function process()
        {
            $remaining = $this->sendTo;

            // if there's no more, we're done!
            if (!count($remaining)) {
                $this->isComplete = true;

                return;
            }

            $this->currentStep++;

            $keys = array_keys($remaining);
            $toID = array_shift($keys);
            $toClass = $remaining[$toID];
            unset($remaining[$toID]);

            $notification = $this->getNotification();
            $context = $this->getContext();

            $service = singleton(NotificationService::class);

            $user = DataObject::get_by_id($toClass, (int)$toID);

            $data = [];

            // extra data is an array - need to deserialise it!!
            foreach ($this->extraData as $k => $v) {
                $data[$k] = $v;
            }

            // now send to the single user
            $service->sendToUser($notification, $context, $user, $data);

            // save new data
            $this->sendTo = $remaining;

            if (count($remaining) <= 0) {
                $this->isComplete = true;
            }
        }
    }
}
