<?php

namespace RTF;

use PDO;

class DB {

    public $pdo;

    function __construct($db, $user, $pass, $host = 'localhost', $charset = 'utf8mb4') {
        $dsn = "mysql:host=".$host.";dbname=".$db.";charset=" . $charset;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Some convenience methods.
     * @param string $name Method name.
     * @param array $args Method arguments.
     * @return array|null
     */
    public function __call($name, $args) {

        // getBy{columnName}. Converts columnName to column_name, calls getBy($table, $column, $value)
        if (strlen($name) > 5 && substr($name, 0, 5) == 'getBy' && count($args) == 2) {
            $columnUnderscore = $this->camelCaseToUnderscores(substr($name, 5));
            return $this->getBy($args[0], $columnUnderscore, $args[1]);
        }

        // getAllBy{columnName}. Converts columnName to column_name, calls getAllBy($table, $column, $value)
        if (strlen($name) > 8 && substr($name, 0, 8) == 'getAllBy' && count($args) == 2) {
            $columnUnderscore = $this->camelCaseToUnderscores(substr($name, 8));
            return $this->getAllBy($args[0], $columnUnderscore, $args[1]);
        }
    }

    /**
     * Get a single row by id.
     * @param string $table The table.
     * @param int $id Value of id column.
     * @return array|null The result.
     */
    public function get($table, $id) {
        $sql = "SELECT * FROM `" . $table . "` WHERE id = ?";
        $ret = $this->fetch($sql, [$id]);
        return $ret ? $ret : null;
    }

    /**
     * Get a single row by a certain column.
     * @param string $table The table.
     * @param string $column The column.
     * @param mixed $value The value of the column.
     * @return array|null The result.
     */
    public function getBy($table, $column, $value) {
        $sql = "SELECT * FROM `" . $table . "` WHERE `" . $column . "` = ?";
        $ret = $this->fetch($sql, [$value]);
        return $ret ? $ret : null;
    }

    /**
     * Get all rows by a certain column.
     * @param string $table The table.
     * @param string $column The column.
     * @param mixed $value The value of the column.
     * @return array Array of results.
     */
    public function getAllBy($table, $column, $value) {
        $sql = "SELECT * FROM `" . $table . "` WHERE `" . $column . "` = ?";
        return $this->fetchAll($sql, [$value]);
    }

    /**
     * Select data, return first element.
     * @param string $query Query String with ? placeholders
     * @param array|null $vars Array of values for placeholders
     * @return array The first result.
     */
    public function fetch($query, $vars = null) {
        $st = $this->pdo->prepare($query);
        $st->execute($vars);
        return $st->fetch();
    }

    /**
     * Select data, return array.
     * @param string $query Query String with ? placeholders.
     * @param array|null $vars Array of values for placeholders.
     * @return array Array of results.
     */
    public function fetchAll($query, $vars = null) {
        $st = $this->pdo->prepare($query);
        $st->execute($vars);
        return $st->fetchAll();
    }


    /**
     * Execute a query, return affected rows.
     * @param string $query Query String with ? placeholders
     * @param array|null $vars Array of values for placeholders
     * @return int Number of affected rows.
     */
    public function execute($query, $vars = null) {
        $st = $this->pdo->prepare($query);
        $st->execute($vars);
        return $st->rowCount();
    }

    /**
     * Update data.
     * @param string $table The table.
     * @param array $values key/value pairs of values to set.
     * @param array|null $where key/value pairs of WHERE clause, combined with AND.
     * @return int Number of affected rows.
     */
    public function update($table, $values, $where = null) {
        $sql = "UPDATE `" . $table . "` SET ";

        $sqlValuesStringArr = [];
        $valuesArr = [];
        foreach ($values as $key => $value) {
            $sqlValuesStringArr[] = $key . ' = ?';
            $valuesArr[] = $value;
        }
        $sql .= implode(", ", $sqlValuesStringArr) . " ";

        $whereArr = [];
        if ($where) {

            $sqlWhereStringArr = [];
            foreach ($where as $key => $value) {
                $sqlWhereStringArr[] = $key . ' = ?';
                $whereArr[] = $value;
            }
            $sql .= 'WHERE ' . implode(" AND ", $sqlWhereStringArr) . " ";
        }

        $bindArr = array_merge($valuesArr, $whereArr);
        return $this->execute($sql, $bindArr);
    }

    /**
     * Insert data.
     * @param string $table The table.
     * @param array $values key/value pairs of values to set.
     * @return int The last inserted id.
     */
    public function insert($table, $values) {
        $sql = "INSERT INTO `" . $table . "` SET ";

        $sqlValuesStringArr = [];
        $valuesArr = [];
        foreach ($values as $key => $value) {
            $sqlValuesStringArr[] = $key . ' = ?';
            $valuesArr[] = $value;
        }
        $sql .= implode(", ", $sqlValuesStringArr) . " ";

        $st = $this->pdo->prepare($sql);
        $st->execute($valuesArr);
        return $this->pdo->lastInsertId();
    }

    public function insertMulti($table, $valuesArr) {

        if (empty($valuesArr)) return;

        $columnNames = [];
        foreach ($valuesArr[0] as $key => $value) {
            $columnNames[] = $key;
        }

        $sql = "INSERT INTO `" . $table . "` (" . implode(",", $columnNames) . ") VALUES ";

        $pdoValuesArr = [];
        $valuesPlaceholderStrings = [];
        foreach ($valuesArr as $values) {

            $valuesPlaceHolderArr = [];
            foreach ($values as $key => $value) {
                $pdoValuesArr[] = $value;
                $valuesPlaceHolderArr[] = '?';
            }

            $valuesPlaceholderStrings[] = '(' . implode(",", $valuesPlaceHolderArr) . ')';
        }
        $valuesPlaceholderString = implode(",", $valuesPlaceholderStrings);

        $sql .= $valuesPlaceholderString;

        $st = $this->pdo->prepare($sql);

        return $st->execute($pdoValuesArr);
    }

    /**
     * Delete data.
     * @param string $table The table.
     * @param array $where key/value pairs of WHERE clause, combined with AND.
     * @return int Number of affected rows.
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM `" . $table . "` ";

        $whereArr = [];
        $sqlWhereStringArr = [];
        foreach ($where as $key => $value) {
            $sqlWhereStringArr[] = $key . ' = ?';
            $whereArr[] = $value;
        }
        $sql .= 'WHERE ' . implode(" AND ", $sqlWhereStringArr) . " ";
        return $this->execute($sql, $whereArr);
    }


    /**
     * Convert columnName to column_name
     * @param string $str cameCaseString
     * @return string underscore_string
     */
    public function camelCaseToUnderscores($str) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $str, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}