<?php
/**
 * Extends the MySQLi class with useful functions.
 * @author Christian Engel <hello@wearekiss.com>
 * @version 1.41
 */
class kMySQLi extends mysqli
{
    var $connected = false;

    /**
     * @var array Will be filled, if you pass multiple queries to query()
     */
    var $multi_error = array();

    /**
     * @var Prefix to put in front of table names. Useful to make one project re-using the same database for multiple instances.
     */
    public $prefix = 'tbl_';

    /**
     * Constructor
     *
     * @param string $host Hostname of the MySQL server
     * @param string $user Username of the  MySQL server
     * @param string $password Password of the  MySQL server
     * @param string $database Name of the database to work with
     * @param string $prefix (optional) Prefix for table names. Default = 'tbl_'
     */
    public function __construct($host, $user=NULL, $password=NULL, $database=NULL, $prefix = '')
    {
        if(is_array($host)){
            $user = $host['user'];
            $password = $host['password'];
            $database = $host['database'];
            $prefix = @$host['prefix'] || '';
            $host = $host['host'];
        }

        parent::__construct($host, $user, $password, $database);
        if ( ! $this->connect_error)
        {
            $this->connected = TRUE;
        }
        $this->prefix = $prefix;
    }

    /**
     * Escapes a string for save sql queries.
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        if(get_magic_quotes_gpc()) $string = stripslashes($string);
        return '\''.$this->real_escape_string($string).'\'';
    }

    /**
     * Replaces " $$" with the table prefix.
     * @param $string
     * @return void
     */
    private function put_prefix($string){
        return str_replace(' $$', ' '.$this->prefix, $string);
    }

    /**
     * Processes a regular MySQL query.
     * You can pass an array with multiple queries here. They will be processed and an array of results will be returned.
     * Notice: If you pass an array in here, check $this->multi_error for errors of each query.
     * @param string|array $sqlQuery
     * @return array|mixed
     */
    public function query($sqlQuery)
    {
        $sqlQuery = $this->put_prefix($sqlQuery);
        if(is_array($sqlQuery)){
            $errors = array();
            $results = array();
            foreach($sqlQuery as $sql){
                $results[] = parent::query($sql);
                $errors[] = $this->error;
            }
            $this->multi_error = $errors;
            return $results;
        }
        $result = parent::query($sqlQuery);
        if($this->error) echo $this->error;
        return $result;
    }

    function insert($sqlQuery){

    }

    /**
     * Makes a database query and returns the insertID - only for insert operations.
     * @param string $sqlQuery
     * @return integer|false
     */
    public function queryInsert($sqlQuery)
    {
        $sqlQuery = $this->put_prefix($sqlQuery);
        $this->query($sqlQuery);
        if(!$this->error) return $this->insert_id; else return false;
    }

    /**
     * Makes a database query and returns all rows with all selected columns.
     * @param string $sqlQuery
     * @return array|false
     */
    public function queryAll($sqlQuery)
    {
        $result = $this->query($sqlQuery);
        if($result)
        {
            $ausgabe = array();
            while($row = $result->fetch_assoc())
            {
                $ausgabe[] = $row;
            }
            return $ausgabe;
        }
        else return array();
    }

    /**
     * Makes a database query and returns one row with all selected columns.
     * @param string $sqlQuery
     * @return array|false Associative Array
     */
    public function queryRow($sqlQuery)
    {
        $result = $this->query($sqlQuery);
        if($result)
        {
            return $result->fetch_assoc();
        }
        else return false;
    }

    /**
     * Makes a database query and returns a single value directly.
     * @param string $sqlQuery
     * @return mixed|false
     */
    public function queryValue($sqlQuery)
    {
        $result = $this->query($sqlQuery);
        if($result)
        {
            $row = $result->fetch_array();
            return $row[0];
        }
        else return false;
    }

    /**
     * Creates a string with SET values for mysql.
     * Strings will be escaped automatically.
     * @param array $array Associative Array. Keys will be mapped to database columns.
     * @return string
     */
    function makeSqlSetString($array)
    {
        $ausgabe = '';

        if(is_array($array))
        {
            foreach($array as $key=>$wert)
            {
                if($ausgabe != '') $ausgabe .= ', ';
                if(is_int($wert))
                {
                    $ausgabe .= $key.'='.$wert;
                }
                else
                {
                    $ausgabe .= $key.'='.$this->escape($wert);
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
    function makeSqlValueString($array)
    {
        $ausgabe = '';
        $left = array();
        $right = array();
        $maxLength = 0;

        //Erstmal vorbereiten.
        foreach($array as $key => $value)
        {
            $left[] = $key;
            if(is_array($value))
            {
                if(count($value)-1 > $maxLength) $maxLength = count($value)-1;
            }
            else
            {
                $array[$key] = array($value);
            }
        }

        //Jetzt die Wertepaare basteln.
        for($i = 0;$i <= $maxLength;$i++)
        {
            $work = array();
            foreach($array as $key => $value)
            {
                if(count($array[$key])-1 < $i)
                {
                    $wert = $array[$key][count($array[$key])-1];
                }
                else
                {
                    $wert = $array[$key][$i];
                }

                if(is_int($wert))
                {
                    $work[] = $wert;
                }
                else
                {
                    $work[] = $this->escape($wert);
                }
            }
            $right[] = implode(', ', $work);
        }

        $ausgabe = '('.implode(',', $left).') VALUES ('.implode('), (', $right).')';


        return $ausgabe;
    }

    /**
     * Truncates a table (makes it empty).
     * @param string $tablename
     * @return void
     */
    function truncate($tablename){
        $sql = 'TRUNCATE TABLE '.$tablename;
        $sql = $this->put_prefix($sql);
        $this->query($sql);
    }
}
?>