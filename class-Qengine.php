<?php
 /*
 * PDO CLASS "Q-engine" [singleton]
 * Author: Riza
 * Email : me@riza.us
 * Date Created : 15th, Oct 2016
 * License : GPL 
 */ 
 
class conn extends PDO{
	private $Engine;
	private $DBhost;
	private $DBname;
	private $DBuser;
	private $DBpass;
	private $query;
	private $stmt;
	public static $instance;
		 
	public static function myDB()
	{
		if (!isset(self::$instance)){
			self::$instance = new conn();
		}
    		return self::$instance;
	}
			
/*
 * open connection ;
 * >>  $db = conn::myDB();
 */	

	public function __construct()
	{		
		$this->Engine = 'mysql';
		$this->DBhost = 'localhost';		
		$this->DBuser = 'root';
		$this->DBpass = '123';
		$this->DBname = 'test';
		$this->Engine.= ':dbname='.$this->DBname .';host='.$this->DBhost;
		parent::__construct( $this->Engine, $this->DBuser, $this->DBpass );
	}

	public function indb($table, $rows=null)
	{
		function placeholders($text, $count=0, $separator=",")
		{
			$result = array();
			if($count > 0)
				for($x=0; $x<$count; $x++){
				$result[] = $text;
				}
			return implode($separator, $result);
		}
		
		$command = 'INSERT INTO '.$table;
		$row = null; $parameter=null;
		foreach ($rows as $assoc)
		{ 
			$count_array = count($assoc);
		}
		
		$varr = ($count_array == 1)? $rows : $assoc ;
		foreach ($varr as $key => $value)
		{
			$row[]  .= $key;
			$parameter[] .=":".$key;
		}
		
		$command .= " (".implode(', ', $row).") VALUES ";
		
		if ($count_array !== 1)
		{			 
			$multi_rows = array();
			foreach($rows as $key)
			{				
			  $multi_rows = array_merge($multi_rows, array_values($key));
			  $parameter[] = '(' . placeholders('?', sizeof($key)) . ')';
			}
		}
		$command  .= ($count_array == 1)? "(".implode(',',$parameter).")" : implode(',', $parameter);
		$exec_rows = ($count_array == 1)? $rows : $multi_rows ;
		$this->stmt = parent::prepare($command);	
		$this->stmt->execute($exec_rows); 
		$this->stmt->rowCount();
		
		return $this->stmt;		
	}
	
/*
 * Using insert function "indb" in multi array "not a multi dimension of array" ;
 * Or you may encode an array("multi dimension of array") values into json, see an example test3 ;
 * foreach($post1 as $rand => $values){		
 * >>  $data_post[] = array("test1" => $post1[$rand], "test2" => $post2[$rand], "test3" => json_encode(array($post3[$rand])));		
 * }
 * OR in single array;
 * >>  $data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
 * then execution using;
 * >>  $db->indb("table", $data_post);
 */ 
 
	public function dldb($tabel, $where=null)
	{
		$command = 'DELETE FROM '.$tabel;
	   
		$list = array(); $parameter = null;
		foreach ($where as $key => $value)
		{
		  $list[] = "$key = :$key";
		  $parameter[] .= '":'.$key.'":"'.$value.'"';
		}
		$command .= ' WHERE '.implode(' AND ',$list);
   
		$json = "{".implode(',',$parameter)."}";
		$param = json_decode($json,true);

		$this->stmt = parent::prepare($command);
		$this->stmt->execute($param);
		$this->stmt->rowCount();		
		return $this->stmt;
	}
	
/*
 * Using delete function "dldb"  ;
 * >>  $db->dldb("table", array("id" => "data_id"));
 */	

	public function updb($tabel, $sets = null, $where = null)
	{
		
		$update = 'UPDATE '.$tabel.' SET ';
		$set=null; $value=null;			 
		foreach($sets as $key => $values)
		{
			$set[] .= $key." = '".$values."'"; 
			$value[] .= ':"'.$key.'" : "'.$values.'"';
		}
		$update .= implode(',',$set);
		$json 	 = '{'.implode(',',$value).'}';			
		$param 	 = json_decode($json,true);
		$update .= ($where != null)? ' WHERE '.$where : null ;
		$this->stmt = parent::prepare($update);
		$this->stmt->execute($param);		  
		$this->stmt->rowCount();
			 
		return $this->stmt;
	}	
	
/*
 * Using update function "updb" ;
 * >>  $data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
 * >>  $db->updb("table", $data_post, "id ='data_id'");
 */	
	
	public function getdb($table, $where=null, $order=null, $limit=null, $rows=null)
	{	
		$command  = 'SELECT ';
		$command .= ($rows  != null)? $rows.' FROM '.$table : '* FROM '.$table;
		$command .= ($where != null)? ' WHERE '.$where : null ;
		$command .= ($order != null)? ' ORDER BY '.$order.' ' : null ; 
		$command .= ($limit != null)? ' LIMIT '.$limit : null ;			   
		$this->stmt = parent::prepare($command);
		$this->stmt->execute();
		$this->query = array();
		while($result = $this->stmt->fetch(PDO::FETCH_ASSOC)){
			$this->query[] = $result;
		}
		return $this->query;
	}

/*
 * Using select function "getdb" ;
 * >>  $show = $db->getdb("tabel", "column1='id_or_data_to_show'", null, 3); // 3 is limit data to show;
 * >>  if(!$show){
 * >>       echo 'Not Found!';
 * >>  }else{
 * >>     foreach ($result as $data){  
 * >>       echo $data['row'];
 * >>     }
 * >>  }
 */		
 
 public function chkdb($table, $item_rows=array(), $engine="MyISAM", $charset="utf8"){
		$chktable 	= 'SHOW TABLE '.$table; 
		$this->stmt = parent::exec($chktable);
		if(!$this->stmt){
			$command = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (';
			foreach($item_rows as $x => $y){
				$items[] = '`'.$x.'` '.$y;
			}
			$command .= implode(",", $items);
			$command .= ') ENGINE='.$engine.' DEFAULT CHARSET='.$charset.';';
			$this->stmt  = parent::exec($command);
		}
		return $this->stmt;
 }
 
 /*
 * Using create table  function "chkdb" (Default engine MyISAM & Charset utf8) ;
 * >>  	$myrow = array( 
 * >>  	  "ID"		=> 	"INT(11) AUTO_INCREMENT PRIMARY KEY", 
 * >>     "Prename" 	=> 	"VARCHAR(50) NOT NULL", 
 * >>     "Name"	=> 	"VARCHAR(250) NOT NULL",
 * >>     "Postcode"  	=>	"VARCHAR(50) NOT NULL",
 * >>     "Country"   	=>	"VARCHAR(50) NOT NULL" );
 * >>  	$db->chkdb("tb_foo", $myrow, "InnoDB", "latin1");
 */	
 
 public static function noDB()
 {		
	self::$instance = null;		
    	return self::$instance;
 }
 
 /*
 * close connection ;
 * >>  $db = conn::noDB();
 */	
 
}
?>
