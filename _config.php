<?php

if (basename(dirname(__FILE__)) != 'notifications') {
	throw new Exception('The notifications module is not installed in correct directory. The directory should be named "notifications"');
}