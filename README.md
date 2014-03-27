TransformAttributesBehavior
=======================
Behavior for Yii1.x CActiveRecord.
Transform values of attributes before saving to DB and after reading from DB.
Use the beforeSave/afterSave handlers, so the transform when recording does work only with the methods save(), update(), insert().


Functions for transformations
------------
You can assign functions for both transformations, if the functions is not assigned,
the default setting when recording in database attributes is converted to JSON, when reading the contrary.
You can overlap default functions and use e.g. serialize()/unserialize().
~~~
    public function behaviors(){
        return array(
            'TransformAttributesBehavior' => array(
                'class' => 'application.components.transform-attributes-behavior.TransformAttributesBehavior',
                'callbackToDb' => function ($model, $attributeName) {
                        return is_string($model->$attributeName) ? $model->$attributeName : serialize($model->$attributeName);
                    },
                'callbackFromDb' => function ($model, $attributeName) {
                    return empty($model->$attributeName) ? $model->$attributeName : unserialize($model->$attributeName);
                }
            )
        );
    }
~~~
You can specify a separate transformation function for an attribute, see below.


Attributes for transformations
------------

Attributes for transform defined:

1) In method behaviors() - property 'transformations'
~~~
    public function behaviors(){
        return array(
            'TransformAttributesBehavior' => array(
                'class' => 'application.components.transform-attributes-behavior.TransformAttributesBehavior',
                'transformations' => array('attribute1', 'attribute2')
            )
        );
    }
~~~

2) In method attributeTransformations()
~~~
public function attributeTransformations(){
        return array('attribute1', 'attribute2');
}
~~~

Also, in behaviors() and  attributeTransformations() can specify a separate transformation function for an attribute:
~~~
        // default functions for 'attribute1'
        array('attribute1',
              'attribute2' => array(
                    // function transform 'attribute2' to DB
                    'to' => function ($model, $attributeName) {
                        return is_string($model->$attributeName) ? $model->$attributeName : serialize($model->$attributeName);
                    },
                    // function transform 'attribute2' from DB
                    'from' => function ($model, $attributeName) {
                        return empty($model->$attributeName) ? $model->$attributeName : unserialize($model->$attributeName);
                    }
              )
              );
~~~
