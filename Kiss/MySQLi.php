<?php
/**
 * Extends the MySQLi class with useful functions.
 * @author Christian Engel <hello@wearekiss.com>
 * @version 1.7
 */

namespace Kiss;

class MySQLi extends \mysqli {
    var $connected = false;

    var $last_query = '';

    /**
     * @var array Will be filled, if you pass multiple queries to query()
     */
    var $multi_error = array();

    /**
     * @var Prefix to put in front of table names. Useful to make one project re-using the same database for multiple instances.
     */
    public $prefix = 'tbl_';

    /**
     * Set this to TRUE, if the class should throw MySQL errors.
     * @var bool
     */
    public $throwErrors = FALSE;

    private $logging = FALSE;

    /**
     * Constructor
     *
     * @param {String} $host Hostname of the MySQL server, or array containing all parameters.
     * @param {String} $user Username of the  MySQL server
     * @param {String} $password Password of the  MySQL server
     * @param {String} $database Name of the database to work with
     * @param {String} [$prefix] Prefix for table names. Default = 'tbl_'
     * @param {String} [$logging] Set to a filename to log all mySQL queries & errors to it.
     */
    public function __construct($host, $user = NULL, $password = NULL, $database = NULL, $prefix = '', $logging = NULL) {
        if (is_array($host)) {
            $user = $host['user'];
            $password = $host['password'];
            $database = $host['database'];
            $prefix = (isset($host['prefix'])) ? $host['prefix'] : '';
            if (isset($host['logging'])) {
                $this->logging = $host['logging'];
            }
            $host = $host['host'];
        }

        if ($logging) {
            $this->loggig = $logging;
        }

        if (is_string($this->logging)) {
            $this->logging = fopen($this->logging, 'a');
            fwrite($this->logging, 'REQUEST:' . "\n" . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\n\n");
        }

        parent::__construct($host, $user, $password, $database);
        if (!$this->connect_error) {
            $this->connected = TRUE;
        }
        $this->prefix = $prefix;
    }

    function enableLogging($logFile, $clean = FALSE){
        if($clean && file_exists($logFile)){
            unlink($logFile);
        }
        $this->logging = fopen($logFile, 'a');
        if($this->logging){
            fwrite($this->logging, 'REQUEST:' . "\n" . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\n\n");
        }
    }

    private function logSQL($sql) {
        if (!$this->logging) {
            return;
        }
        fwrite($this->logging, 'QUERY:' . "\n" . $sql . "\n");
        if ($this->error) {
            fwrite($this->logging, 'ERROR:' . "\n" . $this->error . "\n");
        }
        fwrite($this->logging, "\n");
    }

    /**
     * Escapes a string for save sql queries.
     * @param string $string
     * @return string
     */
    public function escape($string) {
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
        return '\'' . $this->real_escape_string($string) . '\'';
    }

    /**
     * Replaces " $$" with the table prefix.
     * @param $string
     * @return string
     */
    private function putPrefix($string) {
        return str_replace(' $$', ' ' . $this->prefix, $string);
    }

    /**
     * Processes a regular MySQL query.
     * You can pass an array with multiple queries here. They will be processed and an array of results will be returned.
     * Notice: If you pass an array in here, check $this->multi_error for errors of each query.
     * @param string|array $sqlQuery
     * @return array|mixed
     */
    public function query($sqlQuery) {
        $sqlQuery = $this->putPrefix($sqlQuery);
        if (is_array($sqlQuery)) {
            $errors = array();
            $results = array();
            foreach ($sqlQuery as $sql) {
                $results[] = parent::query($sql);
                $errors[] = $this->error;
                $this->logSQL($sql);
            }
            $this->multi_error = $errors;
            return $results;
        }
        $this->last_query = $sqlQuery;
        $result = parent::query($sqlQuery);
        $this->logSQL($sqlQuery);
        if($this->error && $this->throwErrors){
            throw new \ErrorException('ERROR: ' . $this->error . ' ON QUERY: ' . $sqlQuery, $this->errno);
        }
        return $result;
    }

    /**
     * Performs a MySQL Query and returns the number of affected rows.
     * @param $sqlQuery
     * @return int
     */
    function queryAffect($sqlQuery) {
        $this->query($sqlQuery);
        return $this->affected_rows;
    }

    /**
     * Processes a query to the database and returns TRUE if one or more result rows are returned. Returns FALSE if no result is returned.
     * @param string $sqlQuery
     * @return boolean
     */
    function querySuccess($sqlQuery) {
        $result = $this->query($sqlQuery);
        if ($result) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Makes a database query and returns the insertID - only for insert operations.
     * @param string $sqlQuery
     * @return integer|false
     */
    public function queryInsert($sqlQuery) {
        $sqlQuery = $this->putPrefix($sqlQuery);
        $this->query($sqlQuery);
        if (!$this->error) {
            return $this->insert_id;
        }
        return false;
    }

    /**
     * Makes a database query and returns all rows with all selected columns.
     * @param string $sqlQuery
     * @return array|false
     */
    public function queryAll($sqlQuery) {
        $result = $this->query($sqlQuery);
        if ($result) {
            $ausgabe = array();
            while ($row = $result->fetch_assoc()) {
                $ausgabe[] = $row;
            }
            return $ausgabe;
        }
        else {
            return array();
        }
    }

    /**
     * Makes a database query and returns one row with all selected columns.
     * @param string $sqlQuery
     * @return array|false Associative Array
     */
    public function queryRow($sqlQuery) {
        $result = $this->query($sqlQuery);
        if ($result) {
            return $result->fetch_assoc();
        }
        else {
            return FALSE;
        }
    }

    /**
     * Makes a database query and returns a single value directly.
     * @param string $sqlQuery
     * @return mixed|false
     */
    public function queryValue($sqlQuery) {
        $result = $this->query($sqlQuery);
        if ($result) {
            $row = $result->fetch_array();
            return $row[0];
        }
        else {
            return FALSE;
        }
    }

    /**
     * Makes a database query, and returns the first value of each row in a new array.
     * @param string $sqlQuery
     * @return array|false
     */
    public function queryAllValue($sqlQuery) {
        $result = $this->query($sqlQuery);
        if ($result) {
            $out = array();
            while ($row = $result->fetch_array()) {
                $out[] = $row[0];
            }
            return $out;
        }
        return FALSE;
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

    /**
     * Creates a string for a database insert.
     * Strings will be escaped automatically.
     * Has the ability to interpolate missing values if you submit multidimensional array with differing length.
     * @param array $array Associative Array
     * @return string "([COLUMNS]) VALUES ([VALUES])"
     */
    function makeSqlValueString($array) {
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
     * Truncates a table (makes it empty).
     * @param string $tablename
     * @return void
     */
    function truncate($tablename) {
        $sql = 'TRUNCATE TABLE ' . $tablename;
        $sql = $this->putPrefix($sql);
        $this->query($sql);
    }
}

?>