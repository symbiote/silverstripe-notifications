<?php

namespace Symbiote\Notifications\Report;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use Symbiote\Notifications\Model\BroadcastNotification;
use Symbiote\Notifications\Model\InternalNotification;

class NotificationReport extends Report
{

    private static $notification_types = [
        InternalNotification::class => 'Internal message',
    ];

    public function title()
    {
        return _t(__CLASS__ . '.NOTIFICATION_REPORT', 'Notifications');
    }

    public function group()
    {
        return _t(__CLASS__ . '.NOTIFICATION_REPORT_TITLE', "Notification reports");
    }

    public function sourceRecords($params, $sort, $limit)
    {
        $type = $this->getReportType($params);

        $list = DataList::create($type);

        $fromDate = $params['From'] ?? date('Y-m-d', strtotime('-1 month'));
        $toDate = $params['To'] ?? date('Y-m-d');

        $list = $list->filter([
            'Created:GreaterThan' => $fromDate,
            'Created:LessThanOrEqual' => $toDate
        ]);
        return $list;
    }

    public function columns()
    {
        $ctrl = Controller::curr();
        $params = $ctrl ? $ctrl->getRequest()->getVar('filter') : [];

        $type = $this->getReportType($params);
        return $type::config()->summary_fields;
    }

    protected function getReportType($params)
    {
        $type = $params['Type'] ?? InternalNotification::class;
        if (!isset(self::config()->notification_types[$type])) {
            throw new Exception("Invalid type");
        }
        return $type;
    }

    public function parameterFields()
    {
        $ctrl = Controller::curr();
        $params = $ctrl ? $ctrl->getRequest()->getVar('filter') : [];

        $fields = FieldList::create(
            DropdownField::create('Type', 'Notification type', self::config()->notification_types),
            $from = DateField::create('From'),
            $to = DateField::create('To')
        );

        if (!isset($params['From'])) {
            $from->setValue(date('Y-m-d', strtotime('-1 month')));
        }

        return $fields;
    }
}
