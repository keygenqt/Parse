<?php

namespace yii\parse;

class QueryBuilder extends \yii\base\Object
{
    public $db;

    public function __construct($connection, $config = [])
    {
        $this->db = $connection;
        parent::__construct($config);
    }

    public function build($query)
    {
        $parts = $options = [];
        
        if (!empty($query->select)) {
            $options['keys'] = implode(',', $query->select);
        }
        
        if (!empty($query->orderBy)) {
            $order = [];
            foreach ($query->orderBy as $key => $type) {
                switch ($type) {
                    case 3:
                        $type = '-';
                    break;
                    case 4:
                        $type = '';
                    break;
                }
                $order[] = $type . $key;
            }
            $options['order'] = implode(',', $order);
        }
        
        if (!empty($query->limit) && $query->limit > 0) {
            $options['limit'] = $query->limit;
        }
        
        if (!empty($query->offset) && $query->offset > 0) {
            $options['skip'] = $query->offset;
        }
        
        if ($query->where && is_array($query->where)) {
            
            if (!empty($query->where[0])) {
                if (is_array($query->where[1])) {
                    $where = [];
                    foreach ($query->where as $data) {
                        if (is_numeric($data[2])) {
                            $data[2] = (int)$data[2];
                        }
                        if (is_array($data)) {
                            if ($data[0] == 'like') {
                                $where[] = [$data[1] => ['$regex' => "^\Q{$data[2]}\E"]];
                            } elseif ($data[0] == '=') {
                                $where[] = [$data[1] => $data[2]];
                            }
                        }
                    }
                    if ($query->where[0] == 'or') {
                        $parts = ['$or' => $where];
                    } else {
                        foreach ($where as $queryCondition) {
                            $parts = array_merge($queryCondition, $parts);
                        }
                    }
                } else {
                    if ($query->where[0] == 'like') {
                        $parts = [$query->where[1] => ['$regex' => "^\Q{$query->where[2]}\E"]];
                    } elseif ($query->where[0] == '=') {
                        if (is_numeric($query->where[2])) {
                            $query->where[2] = (int)$query->where[2];
                        }
                        $parts = [$query->where[1] => $query->where[2]];
                    }
                }
            } else {
                $parts = $query->where;
            }
        }
        
        return [
            'options' => $options,
            'queryParts' => $parts,
            'class' => $query->class,
        ];
    }
}
