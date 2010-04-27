<?php
return array(
	'sourcePath' => dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'messagePath'=> dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'messages',
	'languages' => array( 'pl' , 'en' ),
	'fileTypes' => array( 'php' ),
	'exclude' => array( '.svn' , 'tags' , 'branches', 'aii-doc', 'aii-audio-player'),
	'translator' => 'Yii::t',
);
?>