DefaultPersister
================

This is a behavior for `CModel` (`CFormModel`, `CActiveRecord`) which allows to save the
set of current attribute values as defaults for the current user and restore them at a later time.

##Requirements

Should work with any 1.1.x version. Not tested with 1.0.x.

##Usage

When this behavior is attached to a model, the current model values can be saved
as default values with a simple command:

```php
$model->saveAsDefaults();
```

This will save all configured attributes in user state (session). To load these defaults back into the model you can use:

```php
$model->loadDefaults();
```

It's also possible to save or load only some attributes:

```php
// Will merge with already saved defaults
$model->saveAsDefaults('name');
$model->saveAsDefaults(array('status','project_id'));

$model->loadDefaults('name');
$model->loadDefaults(array('name','project_id'));
```

To only load attributes that are safe in the current scenario, you can override the
configured value of `safeOnly` (see below):

```php
// true indicates that only safe attributes should be loaded
$model->loadDefaults(null,true);
```

Finally to clear the saved default values use:

```php
$model->resetDefaults();       // Reset all defaults
$model->resetDefaults('name'); // Reset specific attribute
```

##Configuration

Like all behaviors this extension has to be configured in the `behaviors()` method of a model:

```php
public function behaviors()
{
    return array(
        'defaults'=>array(
            'class'=>'ext.defaultpersister.AttributeDefaultsPersister',
            'attributes'=>array('name','status','project_id'),
        ),
    );
}
```

All attributes that should be saved with `saveAsDefaults()` must be listed in the `attributes` property of the behavior.

The complete list of configuration options is:

* `attributes` : list of attribute names that can be saved / loaded 
* `safeOnly` : if true, only attributes that are safe in the current scenario will be loaded with `loadDefaults()`. Default is `false`.
* `stateKeyPrefix` : prefix for the user state key that is used to store defaults. Actual key name will be prefix + model class name. Defaults to `default_`.

If `loadDefaults()` is called before any values where ever saved with `saveAsDefaults()` the model is scanned for a method `attributeDefaults()`. If this method is found the returned values (name/value pairs) will be set as default. If no such method is available, `loadDefaults()` will do nothing in this case.

Since Version 1.1.0 `resetDefaults()` can be used to clear all attributes, one attribute or a list of attributes default values.

If YII_DEBUG is true, this behavior will trace some messages under the category `application.behavior.defaultpersister`.

## Example

One scenario where this behavior can come in handy is e.g. when a model is used as complex filter model for a datagrid. Think of a backend area with pages for users and projects, each showing a filter form and a datagrid. Changes in the filter form trigger a AJAX grid update. Whenever backend personnel accesses such a page the last filter settings should be restored for convenience.

A controller action for this could look like:

```php
public function actionUserList()
{
    $filter=new User('filter');
    $filter->loadDefaults();

    // Set filter attributes on Ajax request and save them as default
    if (($isAjax=isset($_GET['ajax'])) && isset($_GET['User']))
    {
        $filter->attributes=$_GET['User'];
        if (!$filter->validate())   // Invalid filter settings!
            return;
        $filter->saveAsDefaults();
    }

    // Similar to the search() method in Yii's default CRUD models,
    // this method creates a CActiveDataProvider from the current
    // attribute values:
    $data=$filter->getDataProvider();

    if ($isAjax)
        // render only the partial for the data grid:
        $this->renderPartial('_userGrid',array(
            'data'=>$data,
        ));
    else
        // render complete view with filter and data grid
        $this->render('userList',array(
            'filter'=>$filter,
            'data'=>$data,
        ));
}
```

##Changelog

### 1.1.0
 * Fixed: Model defined attributes that are not in DB will also be set in AR
 * Added resetDefaults() method

### 1.0.0 - initial release
