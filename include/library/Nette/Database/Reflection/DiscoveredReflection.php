<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Database\Reflection
 */



/**
 * Reflection metadata class with discovery for a database.
 *
 * @author     Jan Skrasek
 * @property-write Connection $connection
 * @package Nette\Database\Reflection
 */
class DiscoveredReflection extends Object implements IReflection
{
	/** @var Cache */
	protected $cache;

	/** @var ICacheStorage */
	protected $cacheStorage;

	/** @var Connection */
	protected $connection;

	/** @var array */
	protected $structure = array();



	/**
	 * Create autodiscovery structure.
	 */
	public function __construct(ICacheStorage $storage = NULL)
	{
		$this->cacheStorage = $storage;
	}



	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
		if ($this->cacheStorage) {
			$this->cache = new Cache($this->cacheStorage, 'Nette.Database.' . md5($connection->getDsn()));
			$this->structure = ($tmp=$this->cache->load('structure')) ? $tmp : $this->structure;
		}
	}



	public function __destruct()
	{
		if ($this->cache) {
			$this->cache->save('structure', $this->structure);
		}
	}



	public function getPrimary($table)
	{
		$primary = & $this->structure['primary'][strtolower($table)];
		if (isset($primary)) {
			return empty($primary) ? NULL : $primary;
		}

		$columns = $this->connection->getSupplementalDriver()->getColumns($table);
		$primaryCount = 0;
		foreach ($columns as $column) {
			if ($column['primary']) {
				$primary = $column['name'];
				$primaryCount++;
			}
		}

		if ($primaryCount !== 1) {
			$primary = '';
			return NULL;
		}

		return $primary;
	}



	public function getHasManyReference($table, $key, $refresh = TRUE)
	{
		$reference = & $this->structure['hasMany'][strtolower($table)];
		if (!empty($reference)) {
			$candidates = $columnCandidates = array();
			foreach ($reference as $targetPair) {
				list($targetColumn, $targetTable) = $targetPair;
				if (stripos($targetTable, $key) === FALSE)
					continue;

				$candidates[] = array($targetTable, $targetColumn);
				if (stripos($targetColumn, $table) !== FALSE) {
					$columnCandidates[] = $candidate = array($targetTable, $targetColumn);
					if (strtolower($targetTable) === strtolower($key))
						return $candidate;
				}
			}

			if (count($columnCandidates) === 1) {
				return reset($columnCandidates);
			} elseif (count($candidates) === 1) {
				return reset($candidates);
			}

			foreach ($candidates as $candidate) {
				list($targetTable, $targetColumn) = $candidate;
				if (strtolower($targetTable) === strtolower($key))
					return $candidate;
			}

			if (!$refresh && !empty($candidates)) {
				throw new PDOException('Ambiguous joining column in related call.');
			}
		}

		if (!$refresh) {
			throw new PDOException("No reference found for \${$table}->related({$key}).");
		}

		$this->reloadAllForeignKeys();
		return $this->getHasManyReference($table, $key, FALSE);
	}



	public function getBelongsToReference($table, $key, $refresh = TRUE)
	{
		$reference = & $this->structure['belongsTo'][strtolower($table)];
		if (!empty($reference)) {
			foreach ($reference as $column => $targetTable) {
				if (stripos($column, $key) !== FALSE) {
					return array(
						$targetTable,
						$column,
					);
				}
			}
		}

		if (!$refresh) {
			throw new PDOException("No reference found for \${$table}->{$key}.");
		}

		$this->reloadForeignKeys($table);
		return $this->getBelongsToReference($table, $key, FALSE);
	}



	protected function reloadAllForeignKeys()
	{
		foreach ($this->connection->getSupplementalDriver()->getTables() as $table) {
			if ($table['view'] == FALSE) {
				$this->reloadForeignKeys($table['name']);
			}
		}

		foreach (array_keys($this->structure['hasMany']) as $table) {
			uksort($this->structure['hasMany'][$table], create_function('$a, $b', '
				return strlen($a) - strlen($b);
			'));
		}
	}



	protected function reloadForeignKeys($table)
	{
		foreach ($this->connection->getSupplementalDriver()->getForeignKeys($table) as $row) {
			$this->structure['belongsTo'][strtolower($table)][$row['local']] = $row['table'];
			$this->structure['hasMany'][strtolower($row['table'])][$row['local'] . $table] = array($row['local'], $table);
		}

		if (isset($this->structure['belongsTo'][$table])) {
			uksort($this->structure['belongsTo'][$table], create_function('$a, $b', '
				return strlen($a) - strlen($b);
			'));
		}
	}

}
