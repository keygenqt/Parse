<?php

namespace yii\parse;

use yii\db\ActiveQueryInterface;
use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;

class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    const EVENT_INIT = 'init';

    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    public function all($db = null)
    {
        if ($this->asArray) {
            return parent::all($db);
        }

        $result = $this->createCommand($db)->search();
        if (empty($result['results'])) {
            return [];
        }
        $models = $this->createModels($result['results']);
        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }
        foreach ($models as $model) {
            $model->afterFind();
        }

        return $models;
    }

    public function one($db = null)
    {
        if (($result = parent::one($db)) === false) {
            return null;
        }
        
        if ($this->asArray) {
            return $result;
        } else {
            /* @var $class \yii\parse\ActiveRecord */
            $class = $this->modelClass;
            $model = $class::instantiate($result);
            $class::populateRecord($model, $result);
            if (!empty($this->with)) {
                $models = [$model];
                $this->findWith($this->with, $models);
                $model = $models[0];
            }
            $model->afterFind();
            return $model;
        }
    }
}
