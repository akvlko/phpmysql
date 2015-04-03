<?php
/**
 * MySQL
 *
 * Core database related properties and methods
 *
 * PHP versions 5.x
 *
 * Ashok Vishwakarma
 * Copyright 2014 Ashok Vishwakarma (http://ashokvishwakarma.in )
 *
 * Redistributions of files is strictly prohibited.
 *
 * @copyright     Copyright 2014 Ashok Vishwakarma
 * @link          http://ashokvishwakarma.in 
 * @since         v 1.0
 * @license       Copyright 2014 Ashok Vishwakarma (http://ashokvishwakarma.in )
 */
class mysql{
	
	/**
	 * MySQL databse host details
	 *
	 * @var string
	 * @access private
	 */
	private static $_host;
	
	/**
	 * MySQL databse port details
	 *
	 * @var int
	 * @access private
	 */
	private static $_port;
	
	/**
	 * MySQL databse user details
	 *
	 * @var string
	 * @access private
	 */
	private static $_user;
	
	/**
	 * MySQL databse password details
	 *
	 * @var string
	 * @access private
	 */
	private static $_password;
	
	/**
	 * MySQL databse datbase details
	 *
	 * @var string
	 * @access private
	 */
	private static $_database;
	
	/**
	 * MySQL databse link details
	 *
	 * @var string
	 * @access private
	 */
	private static $_link;
	
	/**
	 * Has One Resource from other table
	 *
	 * @var array
	 * @access public
	 */
	public static $hasOne = false;
	
	/**
	 * Has Many Resource from other table
	 *
	 * @var array
	 * @access public
	 */
	public static $hasMany = false;
	
	/**
	 * Has Many Resource from other table
	 *
	 * @var array
	 * @access public
	 */
	public static $table = false;
	
	/**
	 * Constructor
	 *
	 * protected costructor
	 *
	 */
	protected function __construct(){
		
	}
	
	/*
	 * init
	*
	* Initialize all the required variables
	* Connect to the database
	*
	*/
	
	public static function init(){
		try {
			self::$_host = HOST;
			self::$_user = USER;
			self::$_password = PASSWORD;
			self::$_database = DATABASE;
			if(empty(self::$_link)){
				self::connect();
			}
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * Connect to the database
	 *
	 * @param null
	 * @return null
	 * @access public
	 */
	public static function  connect(){
		try{
			self::$_link = @mysql_pconnect(self::$_host, self::$_user, self::$_password);
			@mysql_select_db(self::$_database, self::$_link);
		}catch(Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * Disconnect to the database
	 *
	 * @param string (database link resource variable)
	 * @return null
	 * @access public
	 */
	public static function disconnect($link){
		@mysql_close($link);
	}
	
	
	/**
	 * find
	 * Find data into the datbase
	 *
	 * @param string $method, array $params
	 * @return array
	 * @access public
	 * @uses $obj->find(array('fields'=>array('id','name', ....), 'conditions'=>'id = 1', 'order'=>'id ASC', 'limit'=>10));
	 */
	public static function find($params = array()){
		try{
			$param_data = self::_parse_query_param($params);
			$query = self::_build_query('select', $param_data);
			$res = self::query($query);
			$result = self::fetch_result($res);
			return $result;
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	/**
	 * query 
	 * Run database query
	 *
	 * @param string $query
	 * @return mysql resource id for current query
	 * @access public
	 * @example $obj->query("SELECT columns FROM table");
	 */
	public static function query($query){
		try{
			if(empty(self::$_link)){
				self::connect();
			}
			return mysql_query($query, self::$_link);
		}catch	(Exception $e){
			self::connect();
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * fetch_result 
	 * Fetch result from database and return in associative array
	 *
	 * @param string $res (mysql database resource id)
	 * @return array of match result with the passed mysql resource id
	 * @access public
	 * @example $obj->fetch_result($res);
	 */
	public static function fetch_result($res){
		try{
			$data= array();
			while($row = mysql_fetch_assoc($res)){
				if(self::$hasOne){
					$hasData = array();
					foreach (self::$hasOne as $model){
						$key = Inflector::singularize($model);
						$r = self::query("SELECT * FROM  " . $model . " WHERE " . $model . ".id = " . $row[$key . "_id"] . " LIMIT 1");
						$hasData[$key] = self::fetch_assoc($r);
					}
				}else if(self::$hasMany){
					$hasData = array();
					foreach(self::$hasMany as $model){
						$key = Inflector::singularize($model);
						$r = self::query("SELECT * FROM  " . $model . " WHERE " . $model . "." . $key . "_id" . " = " . $row['id'] . " LIMIT 1");
						$hasData[$model] = array();
						while($rw = mysql_fetch_assoc($r)){
							$hasData[$model][] = $rw;
						}
					}
				}
				if(!empty($hasData)){
					$d = $hasData;
				}else{
					$d = array();
				}
				$d[self::$table] = $row;
				$data[] = $d;
			}
			return $data;
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	/**
	 * fetch_assoc 
	 * Fetch result from database and return in associative array
	 *
	 * @param string $res (mysql database resource id)
	 * @return array of match result with the passed mysql resource id
	 * @access public
	 * @example $obj->fetch_result($res);
	 */
	public static function fetch_assoc($res){
		try{
			return  mysql_fetch_assoc($res);
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * save 
	 * Saves the post data into the table method called from
	 *
	 * @param array $data (post data from the form)
	 * @return array of match result with the passed mysql resource id
	 * @access public
	 * @example $obj->save($data);
	 * Please make sure your feilds name as your columns name
	 */
	public static function save($data){
		try{
			$query = self::_build_query('insert', $data);
			$res = self::query($query);
			return self::get_insert_id();
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * update 
	 * Update the table data on the given conditions
	 *
	 * @param array $data (post data from the form), string $conditions
	 * @return array of match result with the passed mysql resource id
	 * @access public
	 * @example $obj->save($data);
	 * Please make sure your feilds name as your columns name
	 */
	public static function update($data, $conditions){
		try{
			$query = self::_build_query('update', $data, $conditions);
			$res = self::query($query);
			if($res){
				return true;
			}
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	/**
	 * delete 
	 * Update the table data on the given conditions
	 *
	 * @param array $data (post data from the form), string $conditions
	 * @return array of match result with the passed mysql resource id
	 * @access public
	 * @example $obj->save($data);
	 * Please make sure your feilds name as your columns name
	 */
	public static function delete($conditions){
		try{
			$query = "DELETE FROM self::$table WHERE $conditions";
			$res = self::query($query);
			if($res){
				return true;
			}
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * get_insert_id 
	 * Returns the insert id for the table when a new row inserted'
	 * Should be called after save method
	 *
	 * @param null
	 * @return int id
	 * @access public
	 * @example $obj->get_insert_id();
	 */
	public static function get_insert_id(){
		return mysql_insert_id(self::$_link);
	}
	
	
	/**
	 * Fetch columns from tables 
	 *
	 * @param null
	 * @return array of columns
	 * @access private
	 */
	private static function _fetch_columns(){
		try{
			$columns = array();
			$res = self::query("SHOW COLUMNS FROM " . self::$table);
			while($row = mysql_fetch_array($res)){
				$columns[] = $row[0];
			}
			
			return $columns;
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * Prase query parameter 
	 *
	 * @param array $p (parameter)
	 * @return array
	 * @access public
	 */
	private static function _parse_query_param($p = array()){
		try{
			$query_parameter = array();
			if(!empty($p)){
				if(isset($p['fields'])){
					$query_parameter['fields'] = implode(',', $p['fields']);
				}else{
					$query_parameter['fields'] = '*';
				}
				
				if(isset($p['conditions'])){
					$query_parameter['conditions'] = $p['conditions'];
				}else{
					$query_parameter['conditions'] = '1=1';
				}
				
				if(isset($p['order'])){
					$query_parameter['order'] = $p['order'];
				}else{
					$query_parameter['order'] = 'id ASC';
				}
				
				if(isset($p['limit'])){
					$query_parameter['limit'] = $p['limit'];
				}else{
					$query_parameter['limit'] = '';
				}
			}else{
				$query_parameter['fields'] = '*';
				$query_parameter['order'] = 'id ASC';
				$query_parameter['limit'] = '';
				$query_parameter['conditions'] = '1=1';
			}
			
			return $query_parameter;
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}	
	
	/**
	 * Prase query parameter 
	 *
	 * @param string $type,  array $param (parameter or post data)
	 * @return string
	 * @access private
	 * While using save method please make sure your fields name should be as columns name
	 */
	private static function _build_query($type, $param, $conditions = null){
		try{
			$query = "";
			if($type == 'select'){
				$query = "
					SELECT " . $param['fields'] . "
					FROM ". self::$table ."
					WHERE ". $param['conditions'] ." 
					ORDER BY ". $param['order'] ."
				";
				if($param['limit'] != ''){
					$query .="LIMIT ". $param['limit'];
				}
			}else if($type == 'insert'){
				$columns = self::_fetch_columns();
				$query_cols = '';
				$query_values = '';
				foreach ($param as $col=>$val){
					if(in_array($col, $columns)){
						$query_cols .= " `$col`,";
						$query_values .= "'" .mysql_real_escape_string(trim($val))."',";
					}
				}
				$query_cols = rtrim($query_cols, ',');
				$query_values = rtrim($query_values, ',');
				$query = "INSERT INTO " . self::$table . " (" . $query_cols . ") VALUES (" . $query_values . ")"; 
			}else if($type == 'update'){
				$columns = self::_fetch_columns();
				$query_data = '';
				foreach($param as $col=>$val){
					if(in_array($col, $columns)){
						$query_data .= "`$col` = '" . mysql_real_escape_string(trim($val)) . "',";
					}
				}
				$query_data = rtrim($query_data, ',');
				$query = "UPDATE ". self::$table . " SET " .$query_data ." ";
				if($conditions){
					$query .= "WHERE " . $conditions;
				}
			}
			return $query;
		}catch (Exception $e){
			echo 'Error: ',  $e->getMessage(), "\n";
		}
	}
	
	
	/**
	 * String Escape 
	 *
	 * @param string $string
	 * @return string
	 * @access public
	 */
	public static function escape($string){
		return mysql_real_escape_string($string);
	}
	
	/**
	 * Array Escape 
	 *
	 * @param array $array
	 * @return array
	 * @access public
	 */
	public static function array_escape($arr){
		return array_map(self::escape, $arr);
	}
}
?>