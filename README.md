SilverStripe Notifications Module
========================================

Maintainer Contacts
-------------------
*  Marcus Nyeholt (<marcus@silverstripe.com.au>)
*  Shea Dawson (<shea@silverstripe.com.au>)

Requirements
------------
* SilverStripe 3.1 +

Installation Instructions
-------------------------

1. Place this directory in the root of your SilverStripe installation.
2. Visit yoursite.com/dev/build to rebuild the database.

Usage Overview
--------------

### Creating System Notifications

In your _config yml file, add an identifier for each notification you require. This allows you to lookup Notification objects in the database from your code. 

```
NotificationService:
  identifiers:
    - 'NAME_OF_NOTIFICATION1'
    - 'NAME_OF_NOTIFICATION2'
```

Add the NotifiedOn interface to any dataobjects that are relevant to the notifications you will be sending. This is required so the Notifications module can look up the methods (step 3) on your object to send the notification.

```php
class MyDataObject extends DataObject implements NotifiedOn
```

Run ?flush=all

Define the following interface methods on the Object being notified on. 

```php
/**
 * Return a list of all available keywords in the format 
 * array('keyword' => 'A description')
 * @return array
 */
public function getAvailableKeywords();
```
```php
/**
 * Gets a replacement for a keyword
 * @param string $keyword
 * @return string
 */
public function getKeyword($keyword);
```
```php
/**
 * Gets the list of recipients for a given notification event, based on this object's 
 * state. 
 * $event The identifier of the event that triggered this notification
 * @return array An array of Member objects	
 */
public function getRecipients($event);
```

Send a Notification when required from your code 
```php
singleton('NotificationService')->addNotificationSender('email', new EmailNotificationSender());
singleton('NotificationService')->setChannels(array('email', 'log'));
singleton('NotificationService')->notify('NOTIFICATION_IDENTIFIER', $this);
```



TODO
----

* Finish UserNotifications part of the module
* Render Notification in template