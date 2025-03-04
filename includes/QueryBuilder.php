<?php
class QueryBuilder {
    private $select = '*';
    private $from = '';
    private $where = [];
    private $params = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $joins = [];
    
    public function select($columns) {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }
    
    public function from($table) {
        $this->from = $table;
        return $this;
    }
    
    public function where($condition, $params = []) {
        $this->where[] = $condition;
        $this->params = array_merge($this->params, (array)$params);
        return $this;
    }
    
    public function join($table, $condition, $type = 'LEFT') {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "$column $direction";
        return $this;
    }
    
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    public function getQuery() {
        $query = "SELECT {$this->select} FROM {$this->from}";
        
        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where);
        }
        
        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $query .= " OFFSET {$this->offset}";
        }
        
        return $query;
    }
    
    public function getParams() {
        return $this->params;
    }
} 