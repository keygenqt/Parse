<?php

namespace yii\parse;

use Yii;
use yii\base\Component;
use yii\db\QueryInterface;
use yii\db\QueryTrait;

class Query extends Component implements QueryInterface
{
    use QueryTrait;

    public $fields;

    public $source;
    
    public $class;

    public $query;
    
    public $select = [];

    public function createCommand($db = null)
    {
        if ($db === null) {
            $db = Yii::$app->get('parse');
        }
        
        $commandConfig = $db->getQueryBuilder()->build($this);

        return $db->createCommand($commandConfig);
    }

    public function all($db = null)
    {
        $result = $this->createCommand($db)->search();
        if (empty($result['results'])) {
            return [];
        }
        if (!empty($this->select)) {
            for ($i = 0; $i<count($result['results']); $i++) {
                foreach ($result['results'][$i] as $key => $value) {
                    if (!in_array($key, $this->select)) {
                        unset($result['results'][$i][$key]);
                    }
                }
                foreach ($this->select as $column) {
                    if (empty($result['results'][$i][$column])) {
                        $result['results'][$i][$column] = null;
                    }
                }
            }
        }
        return $result['results'];
    }

    public function one($db = null)
    {
        $result = $this->createCommand($db)->search(['limit' => 1]);
        if (empty($result['results'])) {
            return false;
        }
        $record = reset($result['results']);

        return $record;
    }

    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    public function count($q = [], $db = NULL)
    {
        if (is_string($q) && $q == '*') {
            $q = [];
        }
        if (empty($this->where)) {
            $this->where = $q;
        } else {
            $this->where = array_merge($this->where, $q);
        }
        $count = $this->createCommand($db)->search(['count' => 1, 'limit' => 0]);
        return $count['count'];
    }

    public function exists($db = null)
    {
        return self::one($db) !== false;
    }

    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    public function from($class)
    {
        $this->class = $class;
        return $this;
    }
}
