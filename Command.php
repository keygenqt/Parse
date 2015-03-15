<?php

namespace yii\parse;

use yii\base\Component;
use yii\helpers\Json;

class Command extends Component
{
    public $db;
    public $queryParts;
    public $options = [];
    public $class;

    public function search($options = [])
    {
        $query = $this->queryParts;
        
        if (!empty($query)) {
            $query = ['where' => Json::encode($query)];
        }
        $url = [
            'classes',
            $this->class
        ];
        
        return $this->db->get($url, array_merge(array_merge($this->options, $query), $options));
    }

    public function deleteByQuery($options = [])
    {
        $query = $this->queryParts;

        if (!empty($query)) {
            $query = ['where' => Json::encode($query)];
        }
        $url = [
            'classes',
            $this->class
        ];
        return $this->db->delete($url, array_merge(array_merge($this->options, $query), $options));
    }
    
    public function delete($table, $condition = [])
    {
        $query = new Query;
        
        $rows = $query
            ->from($table)
            ->select(array_merge(array_keys($condition), ['objectId']))
            ->where($condition)
            ->all();
        
        foreach ($rows as $data) {
            $url = [ 'classes', $table, $data];
            $this->db->delete($url);
        }
        return !empty($rows);
    }
    
    public function deleteModel($table, $id)
    {
        $url = ['classes', $table, ['objectId' => $id]];
        $this->db->delete($url);
    }

    public function insert($calss, $columns)
    {
        foreach ($columns as $name => $value) {
            if (is_numeric($columns[$name])) {
                $columns[$name] = (int)$columns[$name];
            }
        }
        
        $query = Json::encode($columns);
        $url = [
            'classes',
            $calss
        ];
        
        return $this->db->post($url, $this->options, $query);
    }

    public function get($index, $type, $id, $options = [])
    {
        return $this->db->get([$index, $type, $id], $options);
    }

	public function update($class, $pk, $attributes)
	{
        $url = [
            'classes',
            $class,
            $pk
        ];
        foreach ($attributes as $name => $value) {
            if (is_numeric($attributes[$name])) {
                $attributes[$name] = (int)$value;
            }
        }
		return $this->db->put($url, [], Json::encode($attributes));
	}
}
