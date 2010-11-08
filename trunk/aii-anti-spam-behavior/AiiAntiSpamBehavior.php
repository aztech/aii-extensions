<?php
/**
 * This behavior is designed to use as anti-spam solution
 * e.g. for comments model. The basis of this solution 
 * is to use hidden fields in form and check if they are empty
 * during validation. This is because most of spambots fill
 * all fields in form. Real users doesn't :)
 * 
 * Second anti-spam solution is checking how long form is filled
 * by submitter. Robots fills form really quick, while user
 * need for that at least 1-2 seconds. Measuring time between
 * creating model and saving it gives us answer if we are dealing
 * with form filled by reals user of spambot.
 * 
 * @author Tomasz Suchanek <tomasz.suchanek@gmail.com>
 * @copyright Copyright &copy; 2010 Tomasz "Aztech" Suchanek
 * @license http://www.yiiframework.com/license/
 * @package aii.extensions
 * @version 0.1.0
 */

class AiiAntiSpamBehavior extends CActiveRecordBehavior {

    /**
     * Session variable key, where to store start time.
     * Set different keys for different instances of models,
     * where this bevaiour is used to distinguish them.
     * @var string
     */
    public $sessionKey='AiiAntiSpamBehavior';

    /**
     * Comma separated scenario names, where behaviour should be used.
     * Default set to 'insert'
     * @var string
     */
    public $scenario='insert';

    /**
     * Array of array consisting information about fields, their default value applied after validation
     * and error message displayed to spammers.
     * Note, that you can setting defualt value is not needed and you can leave it empty/null.
     * Fields declared in this array should be empty in model during validation
     * becuase they were declared in form as hidden fields.
     * This is because, spam robots are filling all fields, also those hidden.
     * This helps to distinguish robots from real people.
     * If you would like to skip this feature, please set empty array.
     * @var array
     */
    public $emptyFieldsConfig=array(array('field'=>'email','default'=>'no-spam@yiiframework.com','errorMessage'=>'Go away spammer!'));

    /**
     * Spam robots, usually are filling form fields really quick when people
     * needs at least 1 second to fill values and push save button.
     * Set 'minTime' to value you think user is able to fill form in seconds, e.g. "2.5".
     * 'field' is form field where 'errorMessage' will be displayed
     * If you would like to skip this feature, please set empty array
     * @var array
     */
    public $submitTimeConfig=array('min'=>2,'field'=>'email','errorMessage'=>'Go away spammer!');

    /**
     * Stores creation time in sesssion
     * @param CEvent $event
     */
    public function afterConstruct($event) {
        parent::afterConstruct($event);
        $this->setEnabled($this->isInScenario());
        if(Yii::app()->getSession()->get($this->sessionKey,false)===false)
            Yii::app()->getSession()->add($this->sessionKey, microtime(true));
    }

    /**

     * @param CEvent $event
     * @return boolean
     */
    public function  beforeSave($event)
    {

        return parent::beforeSave($event)&&$return;
    }

    /**
     * Checks if fields from $this->emptyFields are empty.
     * Sets errors and default values.
     * Checks if is less that $this->minFilltime
     * @param CEvent $event
     * @return boolean
     */
    public function  beforeValidate($event)
    {
        $return=true;
        #check empty fields
        foreach($this->emptyFieldsConfig as $config)
        {
            $field=$this->getOwner()->getAttribute($config['field']);
            if(!empty($field)&&$field<>$config['default'])
                $this->getOwner()->addError($config['field'],$config['errorMessage']);
            elseif(isset($config['default']))
                $this->getOwner()->{$config['field']}=$config['default'];
        }
        #check min submition time for formular
        if(!empty($this->submitTimeConfig))
        {
            $startAt=Yii::app()->getSession()->get($this->sessionKey,  microtime(true));
            $endAt=microtime(true);
            if(($endAt-$startAt)<$this->submitTimeConfig['min'])
                $this->getOwner()->addError($this->submitTimeConfig['field'],$this->submitTimeConfig['errorMessage']);
        }
        return parent::beforeValidate($event);
    }

    public function  afterSave($event)
    {
        Yii::app()->getSession()->remove($this->sessionKey);
        return parent::afterSave($event);
    }

    /**
     * Returns true when calling for particular scenario
     * @return boolean
     */
    protected function isInScenario()
    {
        $scenarios=preg_split('/[\s,]+/',$this->scenario,-1,PREG_SPLIT_NO_EMPTY);
        return array_search($this->getOwner()->getScenario(),$scenarios,true) !== false;
    }


}

?>
