<?php
/*
 * PDO CLASS "Q-engine" [singleton]
 * Author: Riza
 * Email : me@riza.us
 * Date Created : 15th, Oct 2016
 * Last Updated : 06th, Aug 2018
 * License : GPL 
 */ 

final class pattern extends PDO{
	
	private	$stmt;
	public	$query;
	private static $instance;
		 
	public static function In($Engine)
	{
		if (!isset(self::$instance)){
			self::$instance = new pattern($Engine);	
		}		
    		return self::$instance;
	}
			
/*
 * open connection ;
 * >>  $db = conn::myDB($Engine);
 */	
	
	public function __construct($Engine)
	{			
		try{	
			parent::__construct( 
				$Engine->type .':dbname='. $Engine->dbn .';host='. $Engine->dbh, $Engine->dbu, $Engine->dbp 
			);
		}
		catch(PDOException $e){	
			$msg = $e->getCode();	
			$msg = $Engine->error->$msg;
			$msg = isset($msg)? $msg : $Engine->error->unknown ;			
			die($msg);
		}
	}

	public function indb($table, $rows=null)
	{

		$command = 'INSERT INTO '.$table;
		$row	 = null; $parameter=null; $multi_rows=array(); $single_rows=array();
		foreach ($rows as $a) { $count_array = count($a); }			
		foreach ((($count_array > 1)? $a : $rows ) as $key => $value)
			{
			  $row[]  .= $key;
				if($count_array == 1)
					$parameter[] .=":".$key;
			}
		
		if ($count_array > 1){
			foreach($rows as $keys)
			{	
				$parameter[] = '(' . implode(',', array_fill(0, count($keys), '?')) . ')';
				foreach($keys as $element)
				{
					$multi_rows[] = $element;
				}
			}
		}			
		
		$command   .= " (".implode(',', $row).") VALUES ";
		$params	    = implode(',',$parameter);
		$command   .= ($count_array > 1)? $params : "(".$params.")" ;		
		$exec_rows  = ($count_array > 1)? $multi_rows : $rows ;
		$this->stmt = parent::prepare($command);	
		$this->stmt->execute($exec_rows); 
		$this->stmt->rowCount();
		return $this->stmt;
	}
	
/*
 * Using insert function "indb" in multi array "not a multi dimension of array" ;
 * Or you may encode an array("multi dimension of array") values into json code, see an example test3 ;
 * foreach($post1 as $rand => $values){		
 * >>  $data_post[] = array("test1" => $post1[$rand], "test2" => $post2[$rand], "test3" => json_encode(array($post3[$rand])));		
 * }
 * OR in single array;
 * >>  $data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
 * then execution using;
 * >>  $db->indb("table", $data_post);
 */ 
  
	public function dldb($table, $where=null)
	{
		$command = 'DELETE FROM '.$table;
	   
		$list = array(); $parameter = array();
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

	public function updb($table, $sets=[], $where=[])
	{
		
		$update	= 'UPDATE '.$table.' SET ';
		$value	= [];				
		
		foreach($sets as $key => $values)
		{
			$sdata[]  = $key."=:".$key; 
			$value1[] = '":'.$key.'":"'.$values.'"';
		}
		if(!empty($where)){
		
			foreach($where as $key => $values)
			{
				$updata[] = $key."=:".$key; 
				$value2[] = '":'.$key.'":"'.$values.'"';
			}
		}		
		
		$value1      = json_decode('{'.implode(",",$value1).'}', true);
		$value2      = json_decode('{'.implode(",",$value2).'}', true);
		$update     .= implode(',',$sdata);			
		$param 	     = array_merge($value1,$value2);
		$update     .= (!empty($where))? ' WHERE '.implode(',',$updata) : null ;
		$this->stmt  = parent::prepare($update);
		$this->stmt->execute($param);		  
		$this->stmt->rowCount();
		return $this->stmt;
	}	
	
/*
 * Using update function "updb" ;
 * >>  $data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
 * >>  $db->updb("table", $data_post, array("id" => "data_id"));
 */	
	
	public function getdb($table, $where=null, $order=null, $limit=null, $rows=null)
	{	
		$command    = 'SELECT ';
		$command   .= ($rows  != null)? $rows.' FROM '.$table : '* FROM '.$table;
		$command   .= ($where != null)? ' WHERE '.$where : null ;
		$command   .= ($order != null)? ' ORDER BY '.$order.' ' : null ; 
		$command   .= ($limit != null)? ' LIMIT '.$limit : null ;			   
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
 * >>  $show = $db->getdb("table", "column1='id_or_data_to_show'", null, 3); // 3 is limit data to show;
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
			$command  = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (';
			foreach($item_rows as $x => $y){
				$items[]	 = '`'.$x.'` '.$y;
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
 * >>     "Postcode"    =>	"VARCHAR(50) NOT NULL",
 * >>     "Country"     =>	"VARCHAR(50) NOT NULL" );
 * >>  	$db->chkdb("tb_foo", $myrow, "InnoDB", "latin1");
 */	
 
  public static function Out()
  {	
	self::$instance = null;
	return self::$instance;
  }

}

/*
 * Then, a class conn configuration for class pattern:
 */ 
 
class conn {
	
		public $type	= null;
		public $dbh	= null;
		public $dbu 	= null;
		public $dbp 	= null;
		public $dbn 	= null;
		public $error 	= null;
		private static $inst;

		public function __construct($type,$dbh,$dbu,$dbp,$dbn){
/*
 * Put selective error messeges here to show your web home trouble 
 */ 			
				$error_messeges =
				json_encode([
					"unknown"		=> "<i style='color:red'>Unknown Error!</i>",
					"0"			=> "<i style='color:red'>Unknown Driver! (Check Your login::pattern again)</i>",
					"1045" 			=> "<i style='color:red'>Access Denied! (Check Your login::pattern again)</i>",
					"2002" 			=> "<i style='color:red'>Not Connect!</i>",
					"23000"	 		=> "<i style='color:red'>Duplicate keys</i>",
					"23001" 		=> "<i style='color:red'>Some other error</i>",
					"42000" 		=> "<i style='color:red'>Syntax error or access violation</i>",
					"08007" 		=> "<i style='color:red'>Connection failure during transaction</i>"
				]);
				
				$this->type 	= $type;
				$this->dbh	= $dbh;
				$this->dbu	= $dbu;
				$this->dbp	= $dbp;
				$this->dbn	= $dbn;
				$this->error	= json_decode($error_messeges);
				$pattern 	= [$this->type,$this->dbh,$this->dbu,$this->dbp,$this->dbn];
		}
		
		public static function myDB($pattern){
			if (!isset(self::$inst)){
				self::$inst = new conn($pattern[0],$pattern[1],$pattern[2],$pattern[3],$pattern[4]);	
			}		
			return pattern::In(self::$inst);
		}

		public static function noDB(){		
			self::$inst = null;
			return pattern::Out();
		}
} 
?>
