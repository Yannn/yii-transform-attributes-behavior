<?php
/**
 * TransformAttributesBehavior class file.
 *
 * @author Annenkov Yaroslav <ya@annenkov.ru>
 * @link https://github.com/Yannn/transform-attributes-behavior
 */

/**
 * Behavior for Yii1.x CActiveRecord
 * Transform values of attributes before saving to DB and after reading from DB.
 *
 * @method CActiveRecord getOwner()
 * @version 0.1
 */
class TransformAttributesBehavior extends CActiveRecordBehavior
{
    private $_backupAttributes = [];
    public $callbackToDb;
    public $callbackFromDb;

    public function events()
    {
        return array(
            'onBeforeSave' => 'beforeSave',
            'onAfterSave' => 'afterSave',
            'onAfterFind' => 'afterFind',
        );
    }

    function __construct()
    {
        // default callback function for save to db
        $this->callbackToDb = function ($model, $attributeName) {
            return is_string($model->$attributeName) ? $model->$attributeName : CJSON::encode($model->$attributeName);
        };
        // default callback function for read from db
        $this->callbackFromDb = function ($model, $attributeName) {
            return empty($model->$attributeName) ? $model->$attributeName : CJSON::decode($model->$attributeName);
        };
    }

    /**
     * @param CEvent $event
     */
    public function beforeSave($event)
    {
        $this->_convertAttributesToDB();
        parent::beforeSave($event);
    }

    /**
     * @param CEvent $event
     */
    public function afterSave($event)
    {
        // restore values of attributes saved in _convertAttributesToDB()
        if(count($this->_backupAttributes)) {
            foreach($this->_backupAttributes as $name => $value) {
                $this->getOwner()->$name = $value;
            }
            $this->_backupAttributes = [];
        }
        parent::afterSave($event);
    }

    /**
     * @param CEvent $event
     */
    public function afterFind($event)
    {
        $this->_convertAttributesFromDB();
        parent::afterFind($event);
    }

    /**
     * Convert values of attributes before saving to DB
     *
     * @see attributeConverted()
     */
    private function _convertAttributesToDB()
    {
        $owner = $this->getOwner();
        if(!method_exists($owner, 'attributeTransformations')) {
            return;
        }
        if($attributes = $owner->attributeTransformations()) {
            $this->_backupAttributes = array_merge($this->_backupAttributes, $owner->getAttributes(array_keys($attributes)));
            foreach($attributes as $name => $value) {
                if(isset($value['to']) && is_callable($value['to'])) {
                    $callback = $value['to'];
                } else {
                    $callback = $this->callbackToDb;
                }
                $owner->$name = $callback($owner, $name);
            }
        }
    }

    /**
     * Convert values of attributes after reading from DB
     *
     * @see attributeConverted()
     */
    private function _convertAttributesFromDB()
    {
        $owner = $this->getOwner();
        if(!method_exists($owner, 'attributeTransformations')) {
            return;
        }
        if($attributes = $owner->attributeTransformations()) {
            foreach($attributes as $name => $value) {
                if(isset($value['from']) && is_callable($value['from'])) {
                    $callback = $value['from'];
                } else {
                    $callback = $this->callbackFromDb;
                }
                $owner->$name = $callback($owner, $name);
            }
        }
    }
}