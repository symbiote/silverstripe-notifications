<?php

namespace Symbiote\Notifications\Helper;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

/**
 * A helper for retrieving keywords etc
 *
 * @author  marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class NotificationHelper
{
    /**
     * @var \SilverStripe\ORM\DataObject
     */
    private $owner;

    /**
     * @var array
     */
    protected $availableKeywords;

    public function __construct(DataObject $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Return a list of all available keywords in the format
     * eg. array(
     *    'keyword' => 'A description'
     * )
     *
     * @return array
     */
    public function getAvailableKeywords()
    {
        if (!$this->availableKeywords) {
            $objectFields = DataObject::getSchema()->databaseFields(get_class($this->owner));

            $objectFields['Created'] = 'Created';
            $objectFields['LastEdited'] = 'LastEdited';
            $objectFields['Link'] = 'Link';

            $this->availableKeywords = [];

            foreach ($objectFields as $key => $value) {
                $this->availableKeywords[$key] = $key;
            }
        }

        return $this->availableKeywords;
    }

    /**
     * Gets a replacement for a keyword
     *
     * @param  $keyword
     * @return string
     */
    public function getKeyword($keyword)
    {
        $k = $this->getAvailableKeywords();

        if ($keyword == 'Link') {
            $link = Director::makeRelative($this->owner->Link());

            return Controller::join_links(Director::absoluteBaseURL(), $link);
        }

        if (isset($k[$keyword])) {
            return $this->owner->$keyword;
        }

        return;
    }
}
