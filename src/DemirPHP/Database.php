<?php

namespace DemirPHP;

class Database
{
	/**
	 * @var array
	 */
	protected $stmt = [
		'select' => null,
		'from' => null,
		'where' => null,
		'having' => null,
		'join' => null,
		'orderBy' => null,
		'groupBy' => null,
		'limit' => null,
	];

	/**
	 * @var array
	 */
	protected $arr = [
		'where' => [],
		'having' => [],
		'join' => [],
		'params' => [],
		'data' => [],
	];

	/**
	 * @var string
	 */
	protected $query = null;

	/**
	 * @var string
	 */
	protected $type = 'SELECT';

	/**
	 * @var string
	 */
	protected $table = null;

	/**
	 * @var PDO
	 */
	protected static $pdo;

	/**
	 * @param PDO $pdo
	 * @return void
	 */
	public static function init($pdo)
	{
		self::$pdo = $pdo;
	}

	/**
	 * @param string $select
	 * @return void
	 */
	public function select($select = '*')
	{
		$this->stmt['select'] = "SELECT {$select}";
		return $this;
	}

	/**
	 * @param string $from
	 * @return void
	 */
	public function from($from)
	{
		$this->stmt['from'] = "FROM {$from}";
		// Birden fazla tablo tanımlandıysa, ilkini tablo olarak belirler
		$tables = explode(',', $from);
		$this->table = $tables[0];
		return $this;
	}

	/**
	 * @param string $from
	 * @param string $select
	 * @return void
	 */
	public function table($from, $select = '*')
	{
		return $this->select($select)->from($from);
	}

	/**
	 * @param string $where
	 * @param mixed $params
	 * @return void
	 */
	public function where($where, $param = NULL)
	{
		$this->arr['where'][] = $where;

		if (!is_null($param)) {
			if (is_array($param)) {
				$this->param($param);
			} else {
				$this->param([$param]);
			}
		}

		$this->buildWhere();
		return $this;
	}

	/**
	 * @param string $has
	 * @return boolean
	 */
	public function hasWhere($has = 'AND')
 	{
 		return !empty($this->arr['where']) ? "{$has} " : null;
 	}

 	/**
	 * @param array $arr
	 * @return string
	 */
	public function in($arr)
	{
		if (is_array($arr)) {
			$string = null;
			foreach ($arr as $key => $data) {
				$string .= $this->hasMark($data) ? "{$data}, " : "'{$data}', ";
			}
			return ' IN ('.trim(trim($string), ',').')';
		} else {
			return " IN ({$arr})";
		}
	}

	/**
	 * @param mixed $val1
	 * @param mixed $val2
	 * @return string
	 */
	public function between($val1, $val2)
	{
		return sprintf(' BETWEEN %s AND %s',
			$this->hasMark($val1) ? "{$val1}" : "'{$val1}'",
			$this->hasMark($val2) ? "{$val2}" : "'{$val2}'"
		);
	}

 	/**
	 * WHERE ifadesi oluşturur
	 */
	private function buildWhere()
	{
		$string = 'WHERE ';
		if (isset($this->arr['where']) && is_array($this->arr['where'])) {
			foreach ($this->arr['where'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		$this->stmt['where'] = trim($string);
	}

	/**
	 * @param string $having
	 * @param mixed $param
	 * @return void
	 */
	public function having($having, $param = NULL)
	{
		$this->arr['having'][] = $having;

		if (!is_null($param)) {
			if (is_array($param)) {
				$this->param($param);
			} else {
				$this->param([$param]);
			}
		}

		$this->buildHaving();
		return $this;
	}

	/**
	 * @param string $has
	 * @return boolean
	 */
	public function hasHaving($has = 'AND')
 	{
 		return !empty($this->arr['having']) ? "{$has} " : null;
 	}

	/**
	 * HAVING ifadesi oluşturur
	 */
	private function buildHaving()
	{
		$string = 'HAVING ';
		if (isset($this->arr['having']) && is_array($this->arr['having'])) {
			foreach ($this->arr['having'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		$this->stmt['having'] = trim($string);
	}

	/**
	 * @param string $join
	 * @param string $type
	 * @return void
	 */
	public function join($join, $type = 'INNER')
	{
		$this->arr['join'][] = $type . ' JOIN ' . $join;
		$this->buildJoin();
		return $this;
	}

	/**
	 * JOIN ifadesi oluşturur
	 */
	private function buildJoin()
	{
		$string = null;
		if (isset($this->arr['join']) && is_array($this->arr['join'])) {
			foreach ($this->arr['join'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		$this->stmt['join'] = trim($string);
	}

	/**
	 * @param string $orderBy
	 * @return void
	 */
	public function orderBy($orderBy)
	{
		$this->stmt['orderBy'] = "ORDER BY {$orderBy}";
		return $this;
	}

	/**
	 * @param string $groupBy
	 * @return void
	 */
	public function groupBy($groupBy)
	{
		$this->stmt['groupBy'] = "GROUP BY {$groupBy}";
		return $this;
	}

	/**
	 * @param mixed $limit
	 * @param mixed $offset
	 * @return void
	 */
	public function limit($limit, $offset = null)
	{
		if (is_null($offset)) {
			$this->stmt['limit'] = "LIMIT $limit";
		} else {
			$this->stmt['limit'] = "LIMIT {$limit}, {$offset}";
		} 
		return $this;
	}

	/**
	 * @param string|array $name
	 * @param string|null $value
	 * @return void
	 */
	public function param($name, $value = null)
	{
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->arr['params'] = array_merge($this->arr['params'], [$k => $v]);
			}
		} else {
			$this->arr['params'] = array_merge($this->arr['params'], [$name => $value]);
		}
		return $this;
	}

	/**
	 * @param string|array $name
	 * @param string|null $value
	 * @return void
	 */
	public function bindParam($name, $value = null)
	{
		return $this->param($name, $value);
	}

	/**
	 * @param string|array $name
	 * @param string|null $value
	 * @return void
	 */
	public function params($name, $value = null)
	{
		return $this->param($name, $value);
	}

	/**
	 * @return void
	 */
	public function insert()
	{
		if (func_num_args() === 1) {
			$data = func_get_arg(0);
			$this->arr['data'] = $data;
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$data = func_get_arg(1);
			$this->table($table);
			$this->arr['data'] = $data;
		}
		
		$this->type = 'INSERT';
		return $this;
	}

	/**
	 * @return void
	 */
	public function update()
	{
		if (func_num_args() === 1) {
			$data = func_get_arg(0);
			$this->arr['data'] = $data;
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$data = func_get_arg(1);
			$this->table($table);
			$this->arr['data'] = $data;
		}
		
		$this->type = 'UPDATE';
		return $this;
	}

	/**
	 * @return void
	 */
	public function delete()
	{
		if (func_num_args() === 1) {
			$table = func_get_arg(0);
			$this->table($table);
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$id = func_get_arg(1);
			$this->table($table)
				->where('id=:id')
				->param(':id', $id);
		}
		
		$this->type = 'DELETE';
		return $this;
	}

	/**
	 * @return void
	 */
	public function build()
	{
		$query = null;
		switch($this->type) {
			// SELECT ifadesi hazırlar
			case 'SELECT':
				$query = sprintf("%s %s %s %s %s %s %s %s",
					$this->stmt['select'],
					$this->stmt['from'],
					$this->stmt['join'],
					$this->stmt['where'],
					$this->stmt['having'],
					$this->stmt['groupBy'],
					$this->stmt['orderBy'],
					$this->stmt['limit']
				);
				break;
			// INSERT ifadesi hazırlar
			case 'INSERT':
				$keys = implode(', ', array_keys($this->arr['data']));
				$vals = null;
				foreach (array_values($this->arr['data']) as $val) $vals .= $this->hasMark($val) ? "$val, " : "'$val', ";
				$vals = trim(trim($vals), ',');
				$query = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->table, $keys, $vals);
				break;
			// UPDATE ifadesi hazırlar
			case 'UPDATE':
				$colVal = null;
				foreach ($this->arr['data'] as $k => $v) $colVal .= $this->hasMark($v) ? "{$k}={$v}, " : "{$k}='{$v}', ";
				$colVal = trim(trim($colVal), ',');
				$query = sprintf('UPDATE %s SET %s %s', $this->table, $colVal, $this->stmt['where']);
				break;
			// DELETE ifadesi hazırlar
			case 'DELETE':
				$query = sprintf('DELETE FROM %s %s', $this->table, $this->stmt['where']);
				break;
		}
		return $this->query = $query;
	}

	/**
	 * @param array $params
	 * @return void
	 */
	public function execute(array $params = [])
	{
		$this->hasPdo();
		$this->param($params);
		$statement = self::pdo()->prepare($this->build());
		$statement->execute($this->arr['params']);
		$this->clear();
		return $statement;
	}

	/**
	 * @param string $query
	 * @return void
	 */
	public function query($query)
	{
		$this->hasPdo();
		$statement = self::pdo()->prepare($query);
		$args = array_slice(func_get_args(), 1);
		if (isset($args[0]) && is_array($args[0])) {
			$statement->execute($args[0]);
		} else {
			$statement->execute($args);
		}
		return $statement;
	}

	/**
	 * @param integer $id
	 * @param string $field
	 * @return void
	 */
	public function find($id = null, $field = 'id')
	{
		if (!is_null($id)) {
			$this->where("{$field}=:{$field}", [":{$field}" => $id]);
		}
		return $this->execute()->fetch();
	}

	/**
	 * @param string $col
	 * @param mixed $val
	 * @return void
	 */
	public function findAll($col = null, $val = null)
	{
		if (is_array($col)) {
			$arr = [];
			$keys = array_keys($col);
			$vals = array_values($col);
			foreach ($vals as $key => $value) {
				if ($key === 0) {
					$this->where($keys[$key] . '=?');
				} else {
					$this->where($keys[$key] . '=?');
				}
			}
			$this->param($vals);
		} else {
			if (!is_null($col)) {
				$this->where($col . '=?');
				$this->param([$val]);
			}
		}
		return $this->execute()->fetchAll();
	}

	/**
	 * @return void
	 */
	public static function pdo()
	{
		return self::$pdo;
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * @return string
	 */
	public function getParams()
	{
		return $this->arr['params'];
	}

	/**
	 * @return void
	 */
	public function clear()
	{
		$this->stmt = [
			'select' => null,
			'from' => null,
			'where' => null,
			'having' => null,
			'join' => null,
			'orderBy' => null,
			'groupBy' => null,
			'limit' => null,
		];

		$this->arr = [
			'where' => [],
			'having' => [],
			'join' => [],
			'params' => [],
			'data' => [],
		];

		$this->query = null;
		$this->type = 'SELECT';
		$this->table = null;
	}

	/**
	 * @return void
	 */
	public function copyQuery()
	{
		return [$this->stmt, $this->arr, $this->query, $this->type, $this->table];
	}

	/**
	 * @return void
	 */
	public function putQuery(array $query)
	{
		if (count($query) === 5) {
			list($this->stmt, $this->arr, $this->query, $this->type, $this->table) = $query;
		} else {
			throw new \Exception('Alınan veri doğrulanamadı');
		}
	}

	/**
	 * PDO bağlantısı hazırlanmış mı sorguılar
	 * @return void
	 */
	private function hasPdo()
	{
		if (!self::pdo() instanceof \PDO) {
			throw new \Exception('PDO bağlantısı yapılmamış');
		}
	}

	/**
	 * @param mixed $val
	 * @return bool
	 */
	private function hasMark($val)
	{
		return (is_numeric($val) ||$val == '?' ||substr($val, 0,1) == ':');
	}
}
