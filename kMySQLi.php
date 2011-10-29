<?php
/**
 * Erweitert die MySQLi-Klasse um zusätzliche Funktionen.
 * @author Christian Engel <christian.engel@wearekiss.com>
 * @version 1.31
 */
class kMySQLi extends mysqli
{
    var $connected = false;
    
	/**
	 * @var Tabellen-Prefix, der vor Tabellennamen gesetzt werden soll, um schnell auch auf anderen Servern zu arbeiten.
	 */
	public $prefix = 'tbl_';
	
	/**
	 * Konstruktor
	 * 
	 * @param string $host Hostname des MySQL Servers
	 * @param string $user Username des MySQL Servers
	 * @param string $pass Passwort des MySQL Servers
	 * @param string $db Name der zu verwendenden Datenbank
	 */
	public function __construct($host, $user, $pass, $db)
	{
		parent::__construct($host, $user, $pass, $db);
		if ( ! $this->connect_error)
		{
            $this->connected = TRUE;
        }
	}
	
	/**
	 * Maskiert die Sonderzeichen eines Strings für die MySQL-Übergabe.
	 * @param string $string
	 * @return string
	 */
	public function mask($string)
	{
		if(get_magic_quotes_gpc()) $string = stripslashes($string);
		return '\''.$this->real_escape_string($string).'\'';
	}
	
	public function query($sqlQuery)
	{
		$result = parent::query($sqlQuery);
		echo $this->sql_error;
		return $result;
	}

    function insert($sqlQuery){

    }
	
	/**
	 * Fährt eine Anfrage an die Datenbank und gibt die Insert-ID zurück. (Nicht zur Datenabfrage gedacht).
	 * @param string $sqlQuery
	 * @return integer|false
	 */
	public function queryInsert($sqlQuery)
	{
		$this->query($sqlQuery);
		if(!$this->error) return $this->insert_id; else return false;
	}
	
	/**
	 * Fährt eine Anfrage an die Datenbank und gibt alle Zeilen zurück.
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
	 * Fährt eine Anfrage an die Datenbank und gibt eine Zeile zurück.
	 * @param string $sqlQuery
	 * @return array|false Assoziatives Array
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
	 * Fährt eine Anfrage an die Datenbank und gibt einen Wert zurück.
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
	 * Erzeugt einen String mit SET-Werten für MySQL.
	 * Strings werden automatisch maskiert.
	 * @param array $array Assoziatives Array. Keys werden als Datenbank-Felder genutzt.
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
					$ausgabe .= $key.'='.$this->mask($wert);
				}
			}
		}
		
		return $ausgabe;
	}
	
	/**
	 * Erzeugt einen String für die Dateneingabe in die Datenbank.
	 * Strings werden automatisch maskiert.
	 * Hat die Fähigkeit fehlende Werte zu interpolieren, wenn Werte im übergebenen Array wiederum Arrays sind. 
	 * @param array $array Assoziatives Array
	 * @return string (SPALTEN) VALUES (WERTE)
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
					$work[] = $this->mask($wert);
				}
			}
			$right[] = implode(', ', $work);
		}
		
		$ausgabe = '('.implode(',', $left).') VALUES ('.implode('), (', $right).')';
		
		
		return $ausgabe;
	}
	
	/**
	 * Erzeugt einen Value-Block aus einem Array.
	 * Strings werden automatisch Maskiert.
	 * Arrays oder Objekte werden ignoriert.
	 * @param array $array Assoziatives Array. Keys werden als Datenbank-Felder genutzt.
	 * @return string
	 */
	function makeSqlValueString_old($array)
	{
		$ausgabe = '';
		$part1 = '';
		$part2 = '';
		
		if(is_array($array))
		{
			foreach($array as $key=>$wert)
			{
				if($part1 != '')
				{
					$part1 .= ', ';
					$part2 .= ', ';
				}
				$part1 .= $key;
				
				
				if(is_int($wert))
				{
					$part2 .= $wert;
				}
				else if(is_string($wert))
				{
					$part2 .= $this->mask($wert);
				}
			}
			
			$ausgabe = '($part1) VALUES ($part2)';
		}
		
		return $ausgabe;
	}

    /**
     * Leert eine Tabelle.
     * @param string $tablename
     * @return void
     */
    function clearTable($tablename){
        $sql = 'TRUNCATE TABLE '.$tablename;
        $this->query($sql);
    }
}
?>