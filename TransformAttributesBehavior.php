<?php

/**
 * Class ActiveConvertBehavior
 * @method CActiveRecord getOwner()
 */
class TransformAttributesBehavior extends CBehavior
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


    public function beforeSave()
    {
        $this->_convertAttributesToDB();
        return true;
    }

    public function afterSave()
    {
        if(count($this->_backupAttributes)) {
            foreach($this->_backupAttributes as $name => $value) {
                $this->getOwner()->$name = $value;
            }
            $this->_backupAttributes = [];
        }
        return true;
    }

    public function afterFind()
    {
        $this->_convertAttributesFromDB();
        return true;
    }

    /**
     * Конвертируют атрибуты определенные в attributeConverted() перед сохранением в базу
     *
     * @see attributeConverted()
     */
    private function _convertAttributesToDB()
    {
        $owner = $this->getOwner();
        if(!method_exists($owner, 'attributeConverted')) {
            return;
        }
        if($attributes = $owner->attributeConverted()) {
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
     * Конвертируют атрибуты определенные в attributeConverted() после чтения из базы
     *
     * @see attributeConverted()
     */
    private function _convertAttributesFromDB()
    {
        $owner = $this->getOwner();
        if(!method_exists($owner, 'attributeConverted')) {
            return;
        }
        if($attributes = $owner->attributeConverted()) {
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