Parse Query and ActiveRecord for Yii 2
==============================================

(Beta version. Will have problems, please contact: keygenqt@gmail.com)

Full support parse api is not implemented.

This extension provides the integration for the Yii2 framework.
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in [parse.com](https://parse.com/).

Powered by [rest api](https://www.parse.com/docs/rest);

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'parse' => [
            'class' => 'yii\parse\Connection',
            'appId' => 'appId',
            'restKey' => 'restKey',
            'masterKey' => 'masterKey',
        ],
    ]
];
```

and console.php for migrate (since the parser automatically creates classes screwed yii\parse\Command).

```php
return [
    'controllerMap' => [
        'migrate-parse' => 'yii\parse\controllers\MigrateParseController',
    ],
];
```

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either add

```json
{
    "require": {
        ...
        "keygenqt/yii2-parse": "dev-master" 
    },
    "repositories":[
        {
            "type": "git",
            "url": "https://github.com/keygenqt/yii2-parse.git"
        }
    ]
}
```

to the require section of your composer.json.

Using the ActiveRecord
----------------------

The following is an example model called `Customer`:

```php

namespace app\models;

use \yii\data\ActiveDataProvider;
use \yii\parse\ActiveRecord;

class Customer extends ActiveRecord
{
    public static function tableName() 
    {
        return 'customer';
    }

    ...
    
    /**
    * Be sure to add in the model so as parse can not return fileds class.
    */
    public static function columns()
    {
        return ['objectId', 'username', 'password_hash', 'blocked_at', 'role', 'created_at', 'updated_at'];
    }

	public function attributeLabels()
    {
        return [];
    }
	
    public function scenarios()
    {
        return parent::scenarios();
    }
    
    public function search($params)
    {
        /* @var $query \yii\parse\ActiveQuery */
        
        $calss = get_class();
        $query = $calss::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'username' => SORT_ASC, 
                ]
            ],
            'pagination' => [
                'pageSize' => 5,
            ],
        ]);
        
        $this->load($params);
        
        foreach (array_keys($this->getAttributes()) as $attribute) {
            if (is_numeric($this->$attribute)) {
                $query->andFilterWhere(['=', $attribute, $this->$attribute]);
            } else {
                $query->andFilterWhere(['like', $attribute, $this->$attribute]);
            }
        }
        
        return $dataProvider;
    }
}
```