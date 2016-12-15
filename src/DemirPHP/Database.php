<?php

namespace DemirPHP;

class Database
{
	/**
	 * @var array
	 * Sorgu ifadelerini tutar
	 */
	private static $stmt = [
		'select' => NULL,
		'from' => NULL,
		'where' => NULL,
		'whereArr' => [],
		'having' => NULL,
		'havingArr' => [],
		'join' => NULL,
		'joinArr' => [],
		'orderBy' => NULL,
		'groupBy' => NULL,
		'limit' => NULL,
		'table' => NULL,
		'type' => 'SELECT',
		'params' => [],
		'query' => NULL,
		'clear' => TRUE
	];

	/**
	 * @var \PDO
	 */
	private static $pdo;

	/**
	 * PDO objesini alır
	 * @param void $pdo
	 * @return void
	 */
	public static function init(\PDO $pdo)
	{
		return self::$pdo = $pdo;
	}

	/**
	 * @param string $select
	 * @return void
	 */
	public static function select($select = '*')
	{
		self::$stmt['select'] = "SELECT {$select}";
		return new self;
	}

	/**
	 * @param string $from
	 * @return void
	 */
	public static function from($from)
	{
		self::$stmt['from'] = "FROM {$from}";
		self::$stmt['table'] = $from;
		return new self;
	}

	/**
	 * @param string $from
	 * @return void
	 */
	public static function table($from, $select = '*')
	{
		return self::select($select)->from($from);
	}

	/**
	 * @param string $where
	 * @return void
	 */
	public static function where($where, $param = NULL)
	{
		self::$stmt['whereArr'][] = $where;
		if (!is_null($param) && is_array($param)) self::param($param);
		self::buildWhere();
		return new self;
	}

	/**
	 * @return boolean
	 */
	public static function hasWhere($has = 'AND ', $empty = NULL)
 	{
 		return !empty(self::$stmt['whereArr']) ? $has : $empty;
 	}

	/**
	 * WHERE ifadesi oluşturur
	 */
	private static function buildWhere()
	{
		$string = 'WHERE ';
		if (isset(self::$stmt['whereArr']) && is_array(self::$stmt['whereArr'])) {
			foreach (self::$stmt['whereArr'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		self::$stmt['where'] = trim($string);
	}

	/**
	 * @param array $arr
	 * @return string
	 */
	public static function in($arr)
	{
		if (is_array($arr)) {
			$string = NULL;
			foreach ($arr as $key => $data) {
				$string .= self::hasMark($data) ? "{$data}, " : "'{$data}', ";
			}
			return ' IN ('.trim(trim($string), ',').')';
		} else {
			return $arr;
		}
	}

	/**
	 * @param mixed $val1
	 * @param mixed $val2
	 * @return string
	 */
	public static function between($val1, $val2)
	{
		return ' BETWEEN ' .
			(self::hasMark($val1) ? "{$val1}" : "'{$val1}'") .
			' AND ' .
			(self::hasMark($val2) ? "{$val2}" : "'{$val2}'");
	}

	/**
	 * @param string $having
	 * @return void
	 */
	public static function having($having, $param = NULL)
	{
		self::$stmt['havingArr'][] = $having;
		if (!is_null($param) && is_array($param)) self::param($param);
		self::buildHaving();
		return new self;
	}

	/**
	 * @return boolean
	 */
	public static function hasHaving($has = 'AND ', $empty = NULL)
 	{
 		return !empty(self::$stmt['havingArr']) ? $has : $empty;
 	}

	/**
	 * HAVING ifadesi oluşturur
	 */
	private static function buildHaving()
	{
		$string = 'HAVING ';
		if (isset(self::$stmt['havingArr']) && is_array(self::$stmt['havingArr'])) {
			foreach (self::$stmt['havingArr'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		self::$stmt['having'] = trim($string);
	}

	/**
	 * @param string $join
	 * @return void
	 */
	public static function join($join, $type = 'INNER')
	{
		self::$stmt['joinArr'][] = $type . ' JOIN ' . $join;
		self::buildJoin();
		return new self;
	}

	/**
	 * JOIN ifadesi oluşturur
	 */
	private static function buildJoin()
	{
		$string = null;
		if (isset(self::$stmt['joinArr']) && is_array(self::$stmt['joinArr'])) {
			foreach (self::$stmt['joinArr'] as $key => $value) {
				$string .= "{$value} ";
			}
		}
		self::$stmt['join'] = trim($string);
	}

	/**
	 * @param string $orderBy
	 * @return void
	 */
	public static function orderBy($orderBy)
	{
		self::$stmt['orderBy'] = "ORDER BY {$orderBy}";
		return new self;
	}

	/**
	 * @param string $groupBy
	 * @return void
	 */
	public static function groupBy($groupBy)
	{
		self::$stmt['groupBy'] = "GROUP BY {$groupBy}";
		return new self;
	}

	/**
	 * @param string $limit
	 * @return void
	 */
	public static function limit($limit)
	{
		self::$stmt['limit'] = "LIMIT $limit";
		return new self;
	}

	/**
	 * @param string|array $name
	 * @param string|null $value
	 * @return void
	 */
	public static function param($name, $value = null)
	{
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				self::$stmt['params'] = array_merge(self::$stmt['params'], [$k => $v]);
			}
		} else {
			self::$stmt['params'] = array_merge(self::$stmt['params'], [$name => $value]);
		}
		return new self;
	}

	/**
	 * @param string|array $name
	 * @param string|null $value
	 * @return void
	 */
	public static function bindParam($name, $value = null)
	{
		return self::param($name, $value);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public static function insert()
	{
		if (func_num_args() === 1) {
			$data = func_get_arg(0);
			self::$stmt['dataArr'] = $data;
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$data = func_get_arg(1);
			self::table($table);
			self::$stmt['dataArr'] = $data;
		}
		
		self::$stmt['type'] = 'INSERT';
		return new self;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public static function update()
	{
		if (func_num_args() === 1) {
			$data = func_get_arg(0);
			self::$stmt['dataArr'] = $data;
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$data = func_get_arg(1);
			self::table($table);
			self::$stmt['dataArr'] = $data;
		}
		
		self::$stmt['type'] = 'UPDATE';
		return new self;
	}

	/**
	 * @return void
	 */
	public static function delete()
	{
		if (func_num_args() === 1) {
			$table = func_get_arg(0);
			self::table($table);
		} elseif (func_num_args() === 2) {
			$table = func_get_arg(0);
			$id = func_get_arg(1);
			self::table($table)
				->where('id=:id')
				->param(':id', $id);
		}
		
		self::$stmt['type'] = 'DELETE';
		return new self;
	}

	/**
	 * @param string|null $query
	 * @return void
	 */
	public static function build()
	{
		$q = null;
		switch(self::$stmt['type']) {
			// SELECT ifadesi hazırlar
			case 'SELECT':
				$q = sprintf("%s %s %s %s %s %s %s %s",
					self::$stmt['select'],
					self::$stmt['from'],
					self::$stmt['join'],
					self::$stmt['where'],
					self::$stmt['having'],
					self::$stmt['groupBy'],
					self::$stmt['orderBy'],
					self::$stmt['limit']
				);
				if (self::$stmt['clear']) self::clear();
				break;

			// INSERT ifadesi hazırlar
			case 'INSERT':
				$keys = implode(', ', array_keys(self::$stmt['dataArr']));
				$vals = null;
				foreach (array_values(self::$stmt['dataArr']) as $val) {
					$vals .= self::hasMark($val) ? "$val, " : "'$val', ";
				}
				$vals = trim(trim($vals), ',');
				$q = sprintf('INSERT INTO %s (%s) VALUES (%s)',
					self::$stmt['table'], $keys, $vals);
				if (self::$stmt['clear']) self::clear();
				break;

			// UPDATE ifadesi hazırlar
			case 'UPDATE':
				$colVal = null;
				foreach (self::$stmt['dataArr'] as $k => $v) {
					$colVal .= self::hasMark($v) ? "{$k}={$v}, " : "{$k}='{$v}', ";
				}
				$colVal = trim(trim($colVal), ',');
				$q = sprintf('UPDATE %s SET %s %s',
					self::$stmt['table'], $colVal, self::$stmt['where']);
				break;

			// DELETE ifadesi hazırlar
			case 'DELETE':
				$q = sprintf('DELETE FROM %s %s',
					self::$stmt['table'], self::$stmt['where']);
				if (self::$stmt['clear']) self::clear();
				break;
		}
		return self::$stmt['query'] = $q;
	}

	/**
	 * @param array $params
	 * @return void
	 */
	public static function execute(array $params = [])
	{
		self::hasPdo();
		self::param($params);
		$params = self::$stmt['params'];
		$statement = self::$pdo->prepare(self::build());
		$statement->execute($params);
		return $statement;
	}

	/**
	 * @param string $query
	 * @return void
	 */
	public static function query($query)
	{
		self::hasPdo();
		$statement = self::$pdo->prepare($query);
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
	public static function find($id = null, $field = 'id')
	{
		if (!is_null($id)) {
			self::where("{$field}=:{$field}");
			self::param(":{$field}", $id);
		}
		return self::execute()->fetch();
	}

	/**
	 * @param string $col
	 * @param mixed $val
	 * @return void
	 */
	public static function findAll($col = null, $val = null)
	{
		if (is_array($col)) {
			$arr = [];
			$keys = array_keys($col);
			$vals = array_values($col);
			foreach ($vals as $key => $value) {
				if ($key === 0) {
					self::where($keys[$key] . '=' . '?');
				} else {
					self::where($keys[$key] . '=' . '?');
				}
			}
			self::param($vals);
		} else {
			if (!is_null($col)) {
				self::where($col . '=' . '?');
				self::param([$val]);
			}
		}
		return self::execute()->fetchAll();
	}

	/**
	 * @return void
	 */
	public static function pdo()
	{
		self::hasPdo();
		return self::$pdo;
	}

	/**
	 * @return string
	 */
	public static function getQuery()
	{
		return self::$stmt['query'];
	}

	/**
	 * @return string
	 */
	public static function getParams()
	{
		return self::$stmt['params'];
	}

	/**
	 * @return string
	 */
	public static function notClear()
	{
		self::$stmt['clear'] = FALSE;
		return new self;
	}

	/**
	 * Sorguyu sıfırlar
	 */
	public static function clear()
	{
		self::$stmt = [
			'select' => NULL,
			'from' => NULL,
			'where' => NULL,
			'whereArr' => [],
			'having' => NULL,
			'havingArr' => [],
			'join' => NULL,
			'joinArr' => [],
			'orderBy' => NULL,
			'groupBy' => NULL,
			'limit' => NULL,
			'table' => NULL,
			'type' => 'SELECT',
			'params' => [],
			'clear' => TRUE
		];
	}

	/**
	 * PDO bağlantısı hazır mı
	 */
	private static function hasPdo()
	{
		if (!self::$pdo instanceof \PDO) {
			throw new \Exception('PDO bağlantısı yapılmamış');
		}
	}

	/**
	 * @param mixed $val
	 * @return bool
	 */
	private static function hasMark($val)
	{
		return (is_numeric($val) ||$val == '?' ||substr($val, 0,1) == ':');
	}
}
