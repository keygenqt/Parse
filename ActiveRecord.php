<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\parse;

use Yii;
use yii\base\InvalidCallException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

class ActiveRecord extends BaseActiveRecord
{
    private $_id;

    public static function getDb()
    {
        return \Yii::$app->get('parse');
    }

    public static function find()
    {
        $query = Yii::createObject(ActiveQuery::className(), [get_called_class()]);
        $query->class = static::tableName();
        return $query;
    }

    /**
     * @inheritdoc
     */
    public static function findOne($condition)
    {
        $query = static::find();
        if (is_array($condition)) {
            return $query->andWhere($condition)->one();
        } else {
            return static::get([]);
        }
    }

    /**
     * @inheritdoc
     */
    public static function findAll($condition)
    {
        $query = static::find();
        if (ArrayHelper::isAssociative($condition)) {
            return $query->andWhere($condition)->all();
        } else {
            return static::mget((array) $condition);
        }
    }

    public function setPrimaryKey($value)
    {
        $pk = static::primaryKey()[0];
        if ($this->getIsNewRecord() || $pk != 'objectId') {
            $this->$pk = $value;
        } else {
            throw new InvalidCallException('Changing the primaryKey of an already saved record is not allowed.');
        }
    }

    public function getPrimaryKey($asArray = false)
    {
        $pk = static::primaryKey()[0];
        if ($asArray) {
            return [$pk => $this->$pk];
        } else {
            return $this->$pk;
        }
    }

    public function getOldPrimaryKey($asArray = false)
    {
        $pk = static::primaryKey()[0];
        if ($this->getIsNewRecord()) {
            $id = null;
        } elseif ($pk == 'objectId') {
            $id = $this->_id;
        } else {
            $id = $this->getOldAttribute($pk);
        }
        if ($asArray) {
            return [$pk => $id];
        } else {
            return $id;
        }
    }

    public static function primaryKey()
    {
        return ['objectId'];
    }

    public function attributes()
    {
        return $this->getColumn();
    }

    public function arrayAttributes()
    {
        return $this->getColumn();
    }
    
    public function safeAttributes()
    {
        return $this->getColumn();
    }
    
    public function getColumn()
    {
        return static::columns();
    }

    public static function populateRecord($record, $row)
    {
        $attributes = [];
        
        if (!empty($row)) {
            $arrayAttributes = $record->arrayAttributes();
            foreach($row as $key => $value) {
                if (!isset($arrayAttributes[$key])) {
                    $row[$key] = $value;
                }
            }
            $attributes = array_merge($attributes, $row);
        }

        parent::populateRecord($record, $attributes);

        $pk = static::primaryKey()[0];
        if ($pk === 'objectId') {
            $record->_id = $row['objectId'];
        }
    }

    public static function instantiate($row)
    {
        return new static;
    }

    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }
        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        
        $response = static::getDb()->createCommand()->insert(
            static::tableName(),
            $values
        );

        $pk = static::primaryKey()[0];
        $this->$pk = $response['objectId'];
        if ($pk != 'objectId') {
            $values[$pk] = $response['objectId'];
        }
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }


    public static function updateAll($attributes, $condition = [])
    {
        $pkName = static::primaryKey()[0];
        
        if (count($condition) == 1 && isset($condition[$pkName])) {
            $primaryKeys = is_array($condition[$pkName]) ? $condition[$pkName] : [$condition[$pkName]];
        } else {
//            $primaryKeys = static::find()->where($condition)->column($pkName); @TODO add function column
        }
        if (empty($primaryKeys)) {
            return 0;
        }
        
        foreach ($primaryKeys as $pk) {
            self::getDb()->createCommand()->update(static::tableName(), $pk, $attributes);
        }

        return true;
    }
}
