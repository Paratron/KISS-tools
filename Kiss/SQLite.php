<?php
/**
 * SQLite
 * ==========
 * DESCRIPTION
 *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 4 17.09.14
 */
namespace Kiss;

/** @noinspection PhpInconsistentReturnPointsInspection */
class SQLite extends \SQLite3 {

    var $prefix = '';

    var $multiError = NULL;

    var $lastQuery = '';

    function __construct($filename, $prefix = '') {
        $this->prefix = $prefix;
        $this->open($filename);
    }

    /**
     * Replaces " $$" with the table prefix.
     * @param $string
     * @return string
     */
    private function putPrefix($string) {
        return str_replace(' $$', ' ' . $this->prefix, $string);
    }

    public function escape($string) {
        return '\'' . $this->escapeString($string) . '\'';
    }

    /**
     * Processes a regular MySQL query.
     * You can pass an array with multiple queries here. They will be processed and an array of results will be returned.
     * Notice: If you pass an array in here, check $this->multi_error for errors of each query.
     * @param string|array $sqlQuery
     * @return \SQLite3Result|Array
     */
    function query($sqlQuery) {
        $sqlQuery = $this->putPrefix($sqlQuery);
        if (is_array($sqlQuery)) {
            $errors = array();
            $results = array();
            foreach ($sqlQuery as $sql) {
                if (!($results[] = $this->query($sql))) {
                    $errors[] = $this->lastErrorMsg();
                }
                else {
                    $errors[] = '';
                }
            }
            if (count($errors)) {
                $this->multiError = $errors;
            }
            else {
                $this->multiError = NULL;
            }
            return $results;
        }
        $this->lastQuery = $sqlQuery;
        try {
            $result = parent::query($sqlQuery);
        } catch (\ErrorException $e) {
            $result = NULL;
        }
        if ($result) {
            $this->error = NULL;
            return $result;
        }
        $this->error = $this->lastErrorMsg();

        if ($this->lastErrorMsg()) {
            throw new \ErrorException($this->lastErrorMsg() . ' QUERY: ' . $sqlQuery, $this->lastErrorCode());
        }

        return $result;
    }

    /**
     * Prepares a sqlQuery with prefixed table names.
     * @param string $sqlQuery
     * @return \SQLite3Stmt
     */
    function prepare($sqlQuery, $values = NULL) {
        $sqlQuery = $this->putPrefix($sqlQuery);
        $sqlQuery = parent::prepare($sqlQuery);
        if (is_array($values)) {
            foreach ($values as $k => $v) {
                $sqlQuery->bindValue($k, $v);
            }
        }
        return $sqlQuery;
    }

    /**
     * Makes a database query and returns the insertID - only for insert operations.
     * @param string $sqlQuery
     * @return integer|false
     */
    function queryInsert($sqlQuery) {
        $this->query($sqlQuery);
        if (!$this->error) {
            return $this->lastInsertRowID();
        }
        return FALSE;
    }

    /**
     * Makes a database query and returns all rows with all selected columns.
     * @param string $sqlQuery
     * @param array $preparedValues
     * @return array
     */
    function queryAll($sqlQuery, $preparedValues = NULL) {
        $sqlQuery = $this->prepare($sqlQuery);
        $sqlQuery->readOnly();
        $result = $sqlQuery->execute();
        if ($result) {
            $ausgabe = array();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ausgabe[] = $row;
            }
            return $ausgabe;
        }
        return array();
    }

    /**
     * Makes a database query and returns one row with all selected columns.
     * @param string $sqlQuery
     * @param array $preparedValues
     * @return array|FALSE
     */
    public function queryRow($sqlQuery, $preparedValues = NULL) {
        $sqlQuery = $this->prepare($sqlQuery);
        $sqlQuery->readOnly();
        $result = $sqlQuery->execute();
        if ($result) {
            return $result->fetchArray(SQLITE3_ASSOC);
        }
        return FALSE;
    }

    /**
     * Makes a database query and returns a single value directly.
     * @param string $sqlQuery
     * @param array $preparedValues
     * @return mixed|false
     */
    public function queryValue($sqlQuery, $preparedValues = NULL) {
        $sqlQuery = $this->prepare($sqlQuery);
        $sqlQuery->readOnly();
        $result = $sqlQuery->execute();
        if ($result) {
            $row = $result->fetchArray(SQLITE3_NUM);
            return $row[0];
        }
        else {
            return FALSE;
        }
    }

    /**
     * Makes a database query, and returns the first value of each row in a new array.
     * @param string $sqlQuery
     * @param array $preparedValues
     * @return array|false
     */
    public function queryAllValue($sqlQuery, $preparedValues = NULL) {
        $sqlQuery = $this->prepare($sqlQuery);
        $sqlQuery->readOnly();
        $result = $sqlQuery->execute();
        if ($result) {
            $out = array();
            while ($row = $result->fetchArray(SQLITE3_NUM)) {
                $out[] = $row[0];
            }
            return $out;
        }
        return FALSE;
    }

    public function makeSqlValueString($array) {
        $ausgabe = '';
        $keys = array();
        $objects = array();
        $multi_mode = FALSE;

        //Fetch all keys.
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $multi_mode = TRUE;
                foreach ($value as $k => $v) {
                    if (!in_array($k, $keys)) {
                        $keys[] = $k;
                    }
                }
            }
            else {
                $multi_mode = FALSE;
                $keys[] = $key;
            }
        }

        //Now collect (and interpolate if necessary) all objects
        if ($multi_mode) {
            foreach ($array as $v) {
                $obj = array();
                foreach ($keys as $k) {
                    $obj[] = (isset($v[$k])) ? (is_int($v[$k])) ? $v[$k] + 0 : $this->escape($v[$k]) : $this->escape('');
                }
                $objects[] = implode(', ', $obj);
            }
        }
        else {
            $obj = array();
            foreach ($keys as $k) {
                $obj[] = (is_int($array[$k])) ? $array[$k] + 0 : $this->escape($array[$k]);
            }
            $objects[] = implode(', ', $obj);
        }

        $ausgabe = '(`' . implode('`,`', $keys) .
                '`) VALUES (' . implode('), (', $objects) . ')';


        return $ausgabe;
    }

    /**
     * Creates a string with SET values for mysql.
     * Strings will be escaped automatically.
     * @param array $array Associative Array. Keys will be mapped to database columns.
     * @return string
     */
    function makeSqlSetString($array) {
        $ausgabe = '';

        if (is_array($array)) {
            foreach ($array as $key => $wert) {
                if ($ausgabe != '') {
                    $ausgabe .= ', ';
                }
                if (is_int($wert)) {
                    $ausgabe .= '`' . $key . '`=' . $wert;
                }
                else {
                    $ausgabe .= '`' . $key . '`=' . $this->escape($wert);
                }
            }
        }

        return $ausgabe;
    }

    public function createTable($name, $fields, $primaryKeys = NULL, $uniqueKeys = NULL) {
        $sql = 'DROP TABLE IF EXISTS `' . $name . '`;';
        $this->query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `' . $name . '` (' . "\n";
        $prepFields = array();
        foreach ($fields as $k => $v) {
            $prepFields[] = '`' . $k . '` ' . $v . "\n";
        }
        $sql .= implode(",\n", $prepFields);

        if ($primaryKeys) {
            $prepFields = array();
            foreach ($primaryKeys as $k) {
                $prepFields[] = 'PRIMARY KEY (`' . $k . '`)';
            }
            $sql .= ",\n" . implode(",\n", $prepFields);
        }

        if ($uniqueKeys) {
            $uniqueKeys = array();
            foreach ($uniqueKeys as $k) {
                $prepFields[] = 'UNIQUE KEY `' . $k . '` (`' . $k . '`)';
            }
            $sql .= ",\n" . implode(",\n", $prepFields);
        }

        $sql .= ')';

        return $this->query($sql);
    }
}
