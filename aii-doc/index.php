<?php

// change the following paths if necessary
$yii=dirname(__FILE__).'/../../../yii.svn/yii.php';
$config=dirname(__FILE__).'/console.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();
