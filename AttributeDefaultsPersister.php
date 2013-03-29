<?php
/**
 * AttributeDefaultsPersister behavior 
 *
 * This is a behavior for CModels (CFormModel and CActiveRecord) that provides
 * methods to save and restore default values in user state.
 *
 * To save the current attribute values as defaults call:
 *
 *      $model->saveAsDefaults();
 *
 * Saved default values can be loaded any time with a call to:
 *
 *      $model->loadDefaults();
 *
 * All attributes that should be saved must be configured in the 'attributes'
 * property of this behavior:
 *
 *      public function behaviors() 
 *      {
 *          return array(
 *              'defaults'=>array(
 *                  'class'=>'AttributeDefaultsPersister',
 *                  'attributes'=>array('name','username','projectID'),
 *                  ),
 *              ),
 *          );
 *      }
 *
 * It's also possible to save or load a single attribute or a list of attributes:
 *
 *      $model->saveAsDefaults('name');
 *      $model->saveAsDefaults(array('name','username'));
 *
 *      $model->loadDefaults('name');
 *      $model->loadDefaults(array('name','projectID'));
 *
 * If no default values where saved when 'loadDefaults()' is called and the model has a 
 * method 'attributeDefaults()' its return value (name/value pairs) will be used as default 
 * values. If the model does not have such a method no defaults will be set in this case.
 *
 * To load only attributes that are safe in the current scenario, 'safeOnly' can be configured 
 * to true (default is false). To override this setting you can specify $safeOnly as second 
 * parameter to 'loadDefaults()'.
 * 
 * @version 1.1 $Id: AttributeDefaultsPersister.php 7 2011-01-11 20:40:58Z mike $
 * @author Michael Härtl <haertl.mike@googlemail.com>
 */
class AttributeDefaultsPersister extends CModelBehavior
{
    /**
     * @var array list of attribute names that can be saved / loaded.
     */
    public $attributes=array();

    /**
     * @var string prefix for the state key. Actual key will be prefix + model class name. Defaults to 'default_'.
     */
    public $stateKeyPrefix='default_';

    /**
     * @var bool wether to only load safe attributes. Defaults to false.
     */
    public $safeOnly=false;


    /**
     * Save current attribute value(s) as default to persistent user state.
     * 
     * @param mixed optional attribute name (string) or list of names (array) to save. Defaults to null, meaning all configured attributes.
     * @throws CException if the provided attribute name is not configured.
     */
    public function saveAsDefaults($attribute=null)
    {
        if ($attribute===null)
        {
            $saveDefaults=$this->owner->getAttributes($this->attributes);
        }
        elseif(is_array($attribute))
        {
            // Save the Union of new defaults + persistent defaults (new defaults override persistent).
            $defaults=$this->loadPersistentDefaults();
            $saveDefaults=$defaults + $this->owner->getAttributes(array_intersect($attribute,$this->attributes));
        }
        elseif(is_string($attribute) && in_array($attribute,$this->attributes))
        {
            $saveDefaults=$this->loadPersistentDefaults();
            $saveDefaults[$attribute]=$this->owner->{$attribute};
        }
        else 
            throw new CException(sprintf('Attribute "%s" can not be saved as default.',$attribute));

        $this->savePersistentDefaults($saveDefaults);
    }

    /**
     * Load all or only specific default attribute values from persistent user state.
     *
     * If no defaults where saved, the attributeDefaults() method of the owning model will be called
     * to return the initial default values. If there's no such method, no defaults will get loaded.
     * 
     * @param mixed attribute name (string) or list of names (array) to save. Will load all saved attributes if null or not supplied.
     * @param bool wether to only load safe attributes. Will override $this->safeOnly.
     * @throws CException if the provided attribute name is not configured.
     */
    public function loadDefaults($attribute=null,$safeOnly=null)
    {
        $defaults=$this->loadPersistentDefaults();
        if ($attribute===null)
        {
            $loadDefaults=$defaults;
        }
        elseif(is_array($attribute))
        {
            $loadDefaults=array_intersect_key($defaults,array_combine($attribute,$attribute));
        }
        elseif(is_string($attribute) && in_array($attribute,$this->attributes))
        {
            $loadDefaults=array($attribute => isset($defaults[$attribute]) ? $defaults[$attribute] : null);
        }
        else 
            throw new CException(sprintf('Attribute "%s" can not be loaded from defaults.',$attribute));

        if ($safeOnly)
            $this->owner->setAttributes($loadDefaults,$safeOnly===null ? $this->safeOnly : $safeOnly);
        else
            foreach($loadDefaults as $attr => $val)
                $this->owner->$attr=$val;
    }

    /**
     * Reset (delete) all or only specific default attribute values from persistent user state.
     *
     * @param mixed attribute name (string) or list of names (array) to reset.
     */
    public function resetDefaults($attribute=null)
    {
        if ($attribute===null)
            $this->savePersistentDefaults(null);
        else
        {
            $defaults=$this->loadPersistentDefaults();
            if (!is_array($attribute))
                $attribute=array($attribute);
            foreach($attribute as $attr)
                unset($defaults[$attr]);
            $this->savePersistentDefaults($defaults);
        }
    }

    /**
     * @return array name/value pairs of all default values from user state.
     */
    protected function loadPersistentDefaults()
    {
        $key=$this->getStateKey();
        if (($defaults=Yii::app()->user->getState($key,null))===null)
            $defaults=method_exists($this->owner,'attributeDefaults') ? $this->owner->attributeDefaults() : array();

        YII_DEBUG && Yii::trace(
            sprintf('Loaded default attributes "%s": %s',$key,var_export($defaults,true)),
            'application.behavior.defaultpersister'
        );

        return $defaults;
    }

    /**
     * Save provided attributes in persistent user state
     * 
     * @param array list of default values (name/value pairs) to save.
     */
    protected function savePersistentDefaults($defaults)
    {
        $key=$this->getStateKey();
        Yii::app()->user->setState($key,$defaults);

        YII_DEBUG && Yii::trace(
            sprintf('Saved default attributes "%s": %s',$key,var_export($defaults,true)),
            'application.behavior.defaultpersister'
        );
    }

    /**
     * @return string the state key under which the attributes get saved in user state
     */
    protected function getStateKey()
    {
        return $this->stateKeyPrefix.strtolower(get_class($this->owner));
    }
}
