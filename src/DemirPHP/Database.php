<?php

namespace DemirPHP;

/**
 * Veritabanı Sınıfı
 * Veritabanı işlemlerinizi kolaylaştırır
 * @author Yılmaz Demir <demiriy@gmail.com>
 * @link http://demirphp.com
 * @package DemirPHP\Database
 * @version 0.9
 */

class Database
{
	/**
	 * Veritabanı nesnesini tutar
	 * @var PDO
	 */
	public $db;

	/**
	 * Sorgu değerlerini tutar
	 * @var array
	 */
	public $query = [
		'select' => null,
		'from' => null,
		'where' => [],
		'where_string' => null,
		'join' => [],
		'join_string' => null,
		'orderBy' => null,
		'groupBy' => null,
		'having' => [],
		'having_string' => null,
		'table' => null,
		'query' => null,
		'old_query' => null,
		'limit' => null,
		'params' => [],
		'data' => [],
		'type' => 'SELECT'
	];

	/**
	 * Database başlatıcı
	 * @param PDO $db
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * SELECT ifadesi hazırlar
	 * @return $this
	 */
	public function select()
	{
		$cols = func_get_args();
		$cols = implode(', ', $cols);
		$cols = empty($cols) ? '*' : $cols;
		$this->query['type'] = 'SELECT';
		$this->query['select'] = "SELECT {$cols}";
		return $this;
	}

	/**
	 * SELECT FROM ifadesi hazırlar
	 * @return $this
	 */
	public function selectFrom($table)
	{
		$this->query['select'] = 'SELECT *';
		$this->query['from'] = "FROM {$table}";
		$this->query['table'] = $table;
		return $this;
	}

	/**
	 * SELECT FROM ifadesi hazırlar
	 * @return $this
	 */
	public function table($table)
	{
		return $this->selectFrom($table);
	}

	/**
	 * FROM ifadesi hazırlar
	 * @param $table
	 * @return $this
	 */
	public function from($table)
	{
		$this->query['from'] = "FROM {$table}";
		$this->query['table'] = $table;
		return $this;
	}

	/**
	 * Diziye WHERE ifadesi ekler
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return $this
	 */
	private function addWhere($col, $operator, $value)
	{
		$this->query['where'][] = [$col, $operator, $value];
		$this->buildWhere();
		return $this;
	}

	/**
	 * WHERE ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return Database
	 */
	public function where($col, $operator, $value)
	{
		return $this->addWhere($col, $operator,$value);
	}

	/**
	 * OR WHERE ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return Database
	 */
	public function orWhere($col, $operator, $value)
	{
		return $this->addWhere("OR {$col}", $operator, $value);
	}

	/**
	 * AND WHERE ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return Database
	 */
	public function andWhere($col, $operator, $value)
	{
		return $this->addWhere("AND {$col}", $operator, $value);
	}

	/**
	 * WHERE IN() ifadesi hazırlar
	 * @param $col
	 * @param $data
	 * @return Database
	 */
	public function whereIn($col, $data)
	{
		$in = '(';
		foreach ($data as $key => $value) {
			if (is_numeric($value) || $this->isPrepared($value)) {
				$in .= "{$value}, ";
			} else {
				$in .= "'{$value}', ";
			}
		}
		$in = rtrim($in, ', ');
		$in .= ')';
		return $this->addWhere($col, 'IN', $in);
	}

	/**
	 * AND WHERE IN() ifadesi hazırlar
	 * @param $col
	 * @param $data
	 * @return Database
	 */
	public function andWhereIn($col, $data)
	{
		return $this->whereIn("AND {$col}", $data);
	}

	/**
	 * OR WHERE IN() ifadesi hazırlar
	 * @param $col
	 * @param $data
	 * @return Database
	 */
	public function orWhereIn($col, $data)
	{
		return $this->whereIn("OR {$col}", $data);
	}

	/**
	 * WHERE column BETWEEN val1 AND val2 ifadesi hazırlar
	 * @param $col string
	 * @param $val1 string
	 * @param $val2 string
	 * @return Database
	 */
	public function whereBetween($col, $val1, $val2)
	{
		$val1 = is_numeric($val1) ||$this->isPrepared($val1) ? $val1 : "'{$val1}'";
		$val2 = is_numeric($val2) ||$this->isPrepared($val2) ? $val2 : "'{$val2}'";
		return $this->addWhere($col, 'BETWEEN', "{$val1} AND {$val2}");
	}

	/**
	 * AND WHERE column BETWEEN val1 AND val2 ifadesi hazırlar
	 * @param $col string
	 * @param $val1 string
	 * @param $val2 string
	 * @return Database
	 */
	public function andWhereBetween($col, $val1, $val2)
	{
		return $this->whereBetween("AND {$col}", $val1, $val2);
	}

	/**
	 * OR WHERE column BETWEEN val1 AND val2 ifadesi hazırlar
	 * @param $col string
	 * @param $val1 string
	 * @param $val2 string
	 * @return Database
	 */
	public function orWhereBetween($col, $val1, $val2)
	{
		return $this->whereBetween("OR {$col}", $val1, $val2);
	}

	/**
	 * Alınan WHERE ifadelerini dizgeye dönüştürür
	 */
	private function buildWhere()
	{
		$str = 'WHERE ';
		foreach ($this->query['where'] as $key => $where) {
			if (is_numeric($where[2]) ||$this->isPrepared($where[2])) {
				$str .= "{$where[0]} {$where[1]} {$where[2]} ";
			} else {
				if ($where[1] == 'IN') {
					$str .= "{$where[0]} {$where[1]} {$where[2]} ";
				} else {
					$str .= "{$where[0]} {$where[1]} '{$where[2]}' ";
				}
			}
		}
		$this->query['where_string'] = $str;
	}

	/**
	 * Dizeye JOIN ifadesi ekler
	 * @param $type
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return $this
	 */
	private function addJoin($type, $table, $col1, $operator, $col2)
	{
		$this->query['join'][] = [$type, $table, $col1, $operator, $col2];
		$this->buildJoin();
		return $this;
	}

	/**
	 * JOIN ifadesi hazırlar
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return Database
	 */
	public function join($table, $col1, $operator, $col2)
	{
		return $this->addJoin('INNER', $table, $col1, $operator, $col2);
	}

	/**
	 * INNER JOIN ifadesi hazırlar
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return Database
	 */
	public function innerJoin($table, $col1, $operator, $col2)
	{
		return $this->addJoin('INNER', $table, $col1, $operator, $col2);
	}

	/**
	 * LEFT JOIN ifadesi hazırlar
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return Database
	 */
	public function leftJoin($table, $col1, $operator, $col2)
	{
		return $this->addJoin('LEFT', $table, $col1, $operator, $col2);
	}

	/**
	 * RIGHT JOIN ifadesi hazırlar
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return Database
	 */
	public function rightJoin($table, $col1, $operator, $col2)
	{
		return $this->addJoin('RIGHT', $table, $col1, $operator, $col2);
	}

	/**
	 * FULL JOIN ifadesi hazırlar
	 * @param $table
	 * @param $col1
	 * @param $operator
	 * @param $col2
	 * @return Database
	 */
	public function fullJoin($table, $col1, $operator, $col2)
	{
		return $this->addJoin('FULL', $table, $col1, $operator, $col2);
	}

	/**
	 * Alınan JOIN ifadelerini dizgeye dönüştürülür
	 */
	private function buildJoin()
	{
		$str = null;
		foreach ($this->query['join'] as $key => $join) {
			$str .= "{$join[0]} JOIN {$join[1]} ON ({$join[2]} {$join[3]} {$join[4]}) ";
		}
		$this->query['join_string'] = $str;
	}

	/**
	 * ORDER BY ifadesi hazırlar
	 * @param $col
	 * @param string $type
	 * @return $this
	 */
	public function orderBy($col, $type = 'DESC')
	{
		$this->query['orderBy'] = "ORDER BY {$col} {$type}";
		return $this;
	}

	/**
	 * GROUP BY ifadesi hazırlar
	 * @param $col
	 * @return $this
	 */
	public function groupBy($col)
	{
		$this->query['groupBy'] = "GROUP BY {$col}";
		return $this;
	}

	/**
	 * Dizeye HAVING ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return $this
	 */
	private function addHaving($col, $operator, $value)
	{
		$this->query['having'][] = [$col, $operator, $value];
		$this->buildHaving();
		return $this;
	}

	/**
	 * HAVING ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return $this|Database
	 */
	public function having($col, $operator, $value)
	{
		return $this->addHaving($col, $operator, $value);
		return $this;
	}

	/**
	 * OR HAVING ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return Database
	 */
	public function orHaving($col, $operator, $value)
	{
		return $this->addHaving("OR {$col}", $operator, $value);
	}

	/**
	 * AND HAVING ifadesi hazırlar
	 * @param $col
	 * @param $operator
	 * @param $value
	 * @return Database
	 */
	public function andHaving($col, $operator, $value)
	{
		return $this->addHaving("AND {$col}", $operator, $value);
	}

	/**
	 * Dizedeki HAVING ifadelerini dizgeye dönüştürür
	 */
	private function buildHaving()
	{
		$str = 'HAVING ';
		foreach ($this->query['having'] as $key => $having) {
			if (is_numeric($having[2]) ||$this->isPrepared($having[2])) {
				$str .= "{$having[0]} {$having[1]} {$having[2]} ";
			} else {
				$str .= "{$having[0]} {$having[1]} '{$having[2]}' ";
			}
		}
		$this->query['having_string'] = $str;
	}

	/**
	 * LIMIT ifadesi hazırlar
	 * @param $limit
	 * @param null $offset
	 * @return $this
	 */
	public function limit($limit, $offset = null)
	{
		$this->query['limit'] = is_null($offset) ?
			"LIMIT {$limit}" : "LIMIT {$limit} OFFSET {$offset}";
		return $this;
	}

	/**
	 * INSERT INTO ifadesi hazırlar
	 * @param $table
	 * @param array $data
	 * @return $this
	 */
	public function insertInto($table, array $data = [])
	{
		$this->query['type'] = 'INSERT';
		$this->query['table'] = $table;
		$this->query['data'] = $data;
		return $this;
	}

	/**
	 * INSERT INTO ifadesi hazırlar
	 * @param $table
	 * @param array $data
	 * @return Database
	 */
	public function insert($table, array $data = [])
	{
		return $this->insertInto($table, $data);
	}

	/**
	 * UPDATE ifadesi hazırlar
	 * @param $table
	 * @param array $data
	 * @return $this
	 */
	public function update($table, array $data = [])
	{
		$this->query['type'] = 'UPDATE';
		$this->query['table'] = $table;
		$this->query['data'] = $data;
		return $this;
	}

	/**
	 * DELETE FROM ifadesi hazırlar
	 * @param $table
	 * @return $this
	 */
	public function deleteFrom($table)
	{
		$this->query['type'] = 'DELETE';
		$this->query['table'] = $table;
		return $this;
	}

	/**
	 * DELETE FROM ifadesi hazırlar
	 * @param $table
	 * @return Database
	 */
	public function delete($table)
	{
		return $this->deleteFrom($table);
	}

	/**
	 * INSERT ve UPDATE için Anahtar/değer tanımlar
	 * @param $key
	 * @param null $value
	 * @return $this
	 */
	public function set($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $key => $value) {
				$this->query['data'][$key] = $value;
			}
		} else {
			$this->query['data'][$key] = $value;
		}
		return $this;
	}

	/**
	 * PDO ile veri döndürür
	 * @return mixed|void
	 */
	public function fetch($type = null)
	{
		$params = $this->query['params'];
		$query = $this->build();

		if (!is_null($query)) {
			$sth = $this->db->prepare($query);
			$sth->execute($params);
			return $sth->fetch($type);
		} else {
			return;
		}
	}

	/**
	 * PDO ile sütun döndürür
	 * @return mixed|void
	 */
	public function fetchColumn($type = null)
	{
		$params = $this->query['params'];
		$query = $this->build();

		if (!is_null($query)) {
			$sth = $this->db->prepare($query);
			$sth->execute($params);
			return $sth->fetchColumn($type);
		} else {
			return;
		}
	}

	/**
	 * PDO ile veriler döndürür
	 * @return array|void
	 */
	public function fetchAll($type = null)
	{
		$params = $this->query['params'];
		$query = $this->build();

		if (!is_null($query)) {
			$sth = $this->db->prepare($query);
			$sth->execute($params);
			return $sth->fetchAll($type);
		} else {
			return;
		}
	}

	/**
	 * ID'ye göre veri döndürür
	 * @param int $id
	 * @return array
	 */
	public function find($id, $col = 'id')
	{
		$this->where($col, '=', '?');
		$this->bindParam([$id]);
		return $this->fetch();
	}

	/**
	 * Şarta göre veri döndürür
	 * @param string $col
	 * @param mixed $val
	 * @return array
	 */
	public function findAll($col = null, $val = null)
	{
		if (is_array($col)) {
			$arr = [];

			$keys = array_keys($col);
			$vals = array_values($col);

			foreach ($vals as $key => $value) {
				if ($key === 0) {
					$this->where($keys[$key], '=', '?');
				} else {
					$this->andWhere($keys[$key], '=', '?');
				}
			}
			$this->bindParam($vals);
		} else {
			if (!is_null($col)) {
				$this->where($col, '=', '?');
				$this->bindParam([$val]);
			}
		}
		return $this->fetchAll();
	}

	/**
	 * Veritabanında son eklenen satır ID'sini döndürür
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->db->lastInsertId();
	}

	/**
	 * PDO ile satır sayısını verir
	 * @return int|void
	 */
	public function rowCount()
	{
		$params = $this->query['params'];
		$query = $this->build();

		if (!is_null($query)) {
			$sth = $this->db->prepare($query);
			$sth->execute($params);
			return $sth->rowCount();
		} else {
			return;
		}
	}

	/**
	 * Değerin hazırlanmış sorgu öğesi olup olmadığını sorar
	 * @param $sth
	 * @return bool
	 */
	private function isPrepared($sth)
	{
		if (substr($sth, 0, 1) == ':') {
			return true;
		} elseif ($sth == '?') {
			return true;
		} elseif (substr($sth, -1) == ')' && strpos($sth, '(') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Oluşturulan sorguyu temizler
	 */
	private function clear()
	{
		$this->query = [
			'select' => null,
			'from' => null,
			'where' => [],
			'where_string' => null,
			'join' => [],
			'join_string' => null,
			'orderBy' => null,
			'groupBy' => null,
			'having' => [],
			'having_string' => null,
			'table' => null,
			'query' => null,
			'old_query' => null,
			'limit' => null,
			'params' => [],
			'data' => [],
			'type' => 'SELECT'
		];

	}

	/**
	 * Parametre oluşturur
	 * @param $key
	 * @param null $value
	 * @return $this
	 */
	public function bindParam($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->query['params'] = array_merge($this->query['params'], [$k => $v]);
			}
		} else {
			$this->query['params'] = array_merge($this->query['params'], [$key => $value]);
		}
		return $this;
	}

	/**
	 * Sorguyu çalıştırır
	 * @return bool|void
	 */
	public function execute()
	{
		$params = $this->query['params'];
		$query = $this->build();

		if (!is_null($query)) {
			$sth = $this->db->prepare($query);
			return $sth->execute($params);
		} else {
			return;
		}
	}

	/**
	 * Oluşturulmuş sorguyu döndürür
	 * @return string
	 */
	public function getQuery()
	{
		return trim($this->query['old_query']);
	}

	/**
	 * Hazırlanmış ifadeleri sorgu haline getirir
	 * @return string
	 */
	public function build()
	{
		$query = null;

		switch ($this->query['type']) {
			/**
			 * Select sorgusu hazırlar
			 */
			case 'SELECT':
				$query = sprintf('%s %s %s %s %s %s %s %s',
					$this->query['select'],
					$this->query['from'],
					$this->query['join_string'],
					$this->query['where_string'],
					$this->query['having_string'],
					$this->query['groupBy'],
					$this->query['orderBy'],
					$this->query['limit']
				);
				break;

			/**
			 * Insert sorgusu hazırlar
			 */
			case 'INSERT':
				$keys = implode(', ', array_keys($this->query['data']));
				$vals = null;

				foreach (array_values($this->query['data']) as $val) {
					if (is_numeric($val) ||$val == '?' ||substr($val, 0,1) == ':') {
						$vals .= "$val, ";
					} else {
						$vals .= "'$val', ";
					}
				}

				$vals = trim($vals);
				$vals = substr($vals, 0, -1);

				$query = sprintf('INSERT INTO %s (%s) VALUES (%s)',
					$this->query['table'],
					$keys,
					$vals
				);
				break;

			/**
			 * Update sorgusu hazırlar
			 */
			case 'UPDATE':
				$kvStr = null;

				foreach ($this->query['data'] as $k => $v) {
					if (is_numeric($v) ||$v == '?' ||substr($v, 0, 1) == ':') {
						$kvStr .= "{$k} = {$v}, ";
					} else {
						$kvStr .= "{$k} = '{$v}', ";
					}
				}

				$kvStr = trim($kvStr);
				$kvStr = substr($kvStr, 0, -1);

				$query = sprintf('UPDATE %s SET %s %s',
					$this->query['table'],
					$kvStr,
					$this->query['where_string']
				);
				break;

			/**
			 * Delete sorgusu hazırlar
			 */
			case 'DELETE':
				$query = sprintf('DELETE FROM %s %s',
					$this->query['table'],
					$this->query['where_string']
				);
				break;
		}

		$this->query['query'] = $query;
		$this->clear();
		$this->query['old_query'] = $query;
		return trim($query);
	}
}
