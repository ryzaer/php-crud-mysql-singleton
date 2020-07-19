<?php
/*
 * PDO CLASS CRUD for Mysql [singleton]
 * Source	: https://github.com/ryzaer/php-crud-mysql-singleton
 * Author	: Riza TTNT
 * Desc		: PHP PDO Mysql CRUD Object with Costum LONGBLOB
 * Date Created : 15th, Oct 2016
 * Last Updated : 19th, Jul 2020
 * License 	: MIT
 * 
 * 
 * Costumize your recomanded extension (LONGBLOB) file below
 * upload file to db depend your SQl allow packet setting OR PHP memory limit size 
 */ 

final class pattern extends PDO{

	use conn;

	private	$stmt;	
	public	$format = "tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf";
	public	$query;

	/*
	 * Login pattern  ------> 
	 * connect pattern $login = ['user','password','your_database_name','localhost','port','engine'];
	 * and input to your db engine class  $db = conn::myDB($login);
	 * 
	 * very start use ------>	
	 * $chk = 
	 * [
	 * 	
	 * 	//<<< DB USER <<<// 
	 * 	"root",				
	 * 	
	 * 	//<<< DB PASSWORD <<<// 
	 * 	"123",				
	 * 	
	 * 	//<<< DB NAME <<<//
	 * 	"DBriza",			
	 * 	
	 * 	//<<<	DB HOST  <<<// 
	 * 	"localhost",
	 *
	 * 	//<<<	DB PORT  <<<// 
	 * 	"3307",	
	 * 
	 * 	//<<< TYPE DRIVER mysql(pgsql not test)<<<// 
	 * 	"mysql" 
	 * 		
	 * ];
	 *
	 * $dbs = conn::myDB($chk);    
	 * 
	 * or simple connect to all your db
	 * 
	 * $dbs = conn::myDB(['root','123']);
	 */ 

	public function __construct($Engine)
	{			
		try{	
			parent::__construct( 
				$Engine['type'].':dbname='.(isset($Engine['dbn'])? $Engine['dbn']:null).';port='.$Engine['port'].';host='. $Engine['dbh'], $Engine['dbu'], $Engine['dbp'] 
			);
		}
		catch(PDOException $e){	
			$msg = $e->getCode();	
			$msg = isset($Engine['error'][$msg])? $Engine['error'][$msg] : $Engine['error']['unknown'] ;			
			die($msg);
		}
	}

	/*
	 * ////////////////////////////////////////////////////////////////////////////////////////////////////
	 * Using insert function "indb" in multi array "not a multi dimension of array" ;
	 * Or you may encode an [array("multi dimension of array")] values into json code, see an example test3 ;
	 * ////////////////////////////////////////////////////////////////////////////////////////////////////
	 * foreach($post1 as $rand => $values){		
	 * >>  $data_post[] = array("test1" => $post1[$rand], "test2" => $post2[$rand], "test3" => json_encode(array($post3[$rand])));		
	 * }
	 * OR in single array;
	 * >>  $data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
	 * >>  ----- then execution using ----------->>>
	 * >>  $db->indb("table", $data_post);
	 * >>  
	 * >>  //////////////////// insert with BLOB /////////////////////////////////////////
	 * >>  $data_post = array("name" => "file1" , "size" => 1232212, "galeri" => "/foo.jpg");
	 * >>  $db->indb("table", $data_post, true);
	 * >>  ----- 1st true to active BLOB  ------->>> 
	 * >>  /////////////////////////////////////////////////////////////////////////////////////
	 */ 
	
	public function indb($table, $rows=array(), $BLOB=false)
	{
		$command 	= 'INSERT INTO '.$table;
		$arr_mode 	= false;
		
		foreach ($rows as $keys)
		{ 
			if(is_array($keys)){
				$arr_mode = true;
				$parameter[] = '(' . implode(',', array_fill(0, count($keys), '?')) . ')';
				foreach($keys as $element){ $obj_rows[] = trim($element); }
			}		
		}			
		
		foreach ((($arr_mode)? $keys : $rows ) as $key => $value)
		{
			$sub_rows[] = $key;
			(!$arr_mode)? ( $parameter[] = ":".$key ) 	: null ;
			(!$arr_mode)? ( $obj_rows[]  = trim($value))	: null ;
		}
		
		$command .= " (".implode(',', $sub_rows).") VALUES ";
		$params	  = implode(',',$parameter);
		$command .= ($arr_mode)? $params : "(".$params.")" ;
		$except   = implode('|', explode('|', trim($this->format)));
		
		$this->stmt = parent::prepare($command);		
		
		for($i=0;$i < count($obj_rows);$i++){
			$sum[$i] 	= ($arr_mode)? $i+1 : ":".$sub_rows[$i] ;
			$pdo_sum[$i] 	= is_numeric($obj_rows[$i])? PDO::PARAM_INT : PDO::PARAM_STR ;
			$var[$i] 	= ($BLOB)? ((preg_match('/^.*\.('.$except.')$/i',strtolower($obj_rows[$i]))>0)?  file_get_contents($obj_rows[$i]) : $obj_rows[$i] ) : $obj_rows[$i] ;	
			$pdo_var[$i] 	= ($BLOB)? ((preg_match('/^.*\.('.$except.')$/i',strtolower($obj_rows[$i]))>0)?  PDO::PARAM_LOB : $pdo_sum[$i] ) : $pdo_sum[$i] ;	
			$this->stmt->bindParam($sum[$i],$var[$i],$pdo_var[$i]);			
		}
		
		$this->stmt->execute();		
		return $this->lastInsertId();
	}
	
	/*
	 * Using delete function "dldb"  ;
	 * >>  $db->dldb("table", array("id" => "data_id"));
	 */	
  
	public function dldb($table, $where=null)
	{
		$command = 'DELETE FROM '.$table;
	   
		$list = array(); $param = array();
		foreach ($where as $key => $value)
		{
		  $list[]	 = "$key = :$key";
		  $param[] 	.= '":'.$key.'":"'.$value.'"';
		}
		$command	.= ' WHERE '.implode(' AND ', $list);
   		$param 		 = json_decode('{'.implode(",",$param).'}',true);
		$this->stmt	 = parent::prepare($command);

		return $this->stmt->execute($param);
	}

	/*
 	 * Using update function "updb" ;
 	 * >>  ////////////////////simple update/////////////////////////////////
 	 * >>  $data_post 	= ["str1"=>"7172878939","str2"=>"pic_of_arini","str3"=>"arini.jpg"];
 	 * >>  $id  		= ["id" => 1,"id_tool" => 5];
 	 * >>  $db->updb("table", $data_post, $id);
 	 * >>  
 	 * >>  ////////////////////complete update////////////////////////////////
 	 * >>  ----- by adding 'or' string ($or)------------>>>
 	 * >>  $or  		= ["ssd" => "more fastest", "hdd" => "lil bit fast"];
 	 * >>  $db->updb("table", $data_post, $id, true, true, $or));
 	 * >>  ----- 1st true to active BLOB  ------->>>
 	 * >>  ----- 2nd true to active LIKE  ------->>>
 	 * >>  ----- both false as default  --------->>> 
 	 * >>  ----- output >> UPDATE table SET str1=:str1,str2=:str2,str3=:str3 WHERE id LIKE CONCAT('%', :id, '%') AND id_tool LIKE CONCAT('%', :id_tool, '%') AND ( ssd LIKE CONCAT('%', :ssd, '%') OR hdd LIKE CONCAT('%', :hdd, '%') )
 	 * >>  ////////////////////////////////////////////////////////////////////////////
 	 */	


	public function updb($table, $sets=[], $where=[], $BLOB=false, $LIKE=false, $OR=[])
	{
		$update	 = 'UPDATE '.$table.' SET ';
		$optdata = [];
		foreach($sets as $key => $values)
		{			
			$rdata[]  = ":".$key;
			$vdata[]  = $values;			
			$sdata[]  = $key."=:".$key; 
		}
		
		if(!empty($where)){
			foreach($where as $key => $values)
			{
				$rdata[]  = ":".$key;
				$vdata[]  = $values;			
				$udata[]  = ($LIKE)? $key." LIKE CONCAT('%', :$key, '%')" : $key."=:".$key;
			}
			$optdata[] = implode(" AND ",$udata);
		}		
			
		if(!empty($OR)){
			foreach($OR as $key => $values){
				$rdata[]  = ":".$key;
				$vdata[]  = $values;
				$odata[]  = ($LIKE)? $key." LIKE CONCAT('%', :$key, '%')" : $key."=:".$key; 
			}
			$optdata[] = "( ".implode(" OR ",$odata)." )";
		}
		
		$update 	.= implode(',',$sdata);		
		$update 	.= ' WHERE '.implode(" AND ", $optdata);
		$except 	 = implode('|', explode('|', trim($this->format)));
		
		$this->stmt  = parent::prepare($update);
		for($i=0; $i < count($rdata); $i++){		
			$pdo_sum[$i] = is_numeric($vdata[$i])? PDO::PARAM_INT : PDO::PARAM_STR ;
			$var[$i] 	 = ($BLOB)? ((preg_match('/^.*\.('.$except.')$/i',strtolower($vdata[$i]))>0)?  file_get_contents($vdata[$i]) : $vdata[$i] ) : $vdata[$i] ;	
			$pdo_var[$i] = ($BLOB)? ((preg_match('/^.*\.('.$except.')$/i',strtolower($vdata[$i]))>0)?  PDO::PARAM_LOB : $pdo_sum[$i] ) : $pdo_sum[$i] ;		
			$this->stmt->bindParam($rdata[$i], $var[$i],$pdo_var[$i]);	
		}			
			
		return $this->stmt->execute();
	}	

	/*
 	 * This is simple function "getdb" (can be customize) ;
 	 * 
 	 * >>  $show = $db->getdb("table", "column1='id_or_data_to_show'", null, 3); // 3 is limit data to show;
 	 * >>  if(!$show){
 	 * >>       echo 'Not Found!';
 	 * >>  }else{
 	 * >>     foreach ($result as $data){  
 	 * >>       echo $data['row'];
 	 * >>     }
 	 * >>  }
 	 * >> another custom pattern for grouping
 	 * >> $db->getdb("table", "range_date BETWEEN '2018-07-20' AND '2018-08-20' GROUP BY range_date", "range_date DESC", null, "range_date, count(*) as vals");
 	 */
	
	public function getdb($table, $where=null, $order=null, $limit=null, $rows=null)
	{	
		$command  = 'SELECT ';
		$command .=	($rows)?  $rows.' FROM '.$table		: '* FROM '.$table;
		$command .=	($where)? ' WHERE '.$where 			: null ;
		$command .= ($order)? ' ORDER BY '.$order.' ' 	: null ; 
		$command .= ($limit)? ' LIMIT '.$limit 			: null ;			   
		$this->stmt = parent::prepare($command);
		$this->stmt->execute();
		$this->query = array();
		while($result = $this->stmt->fetch(PDO::FETCH_ASSOC)){
			$this->query[] = $result;
		}
		return $this->query;
	}

	/*
 	 * Using create table  function "chkdb" (Default engine MyISAM & Charset utf8) ;
 	 * >>  	$myrow = array( 
 	 * >>  	  "ID" 		=> "INT(11) AUTO_INCREMENT PRIMARY KEY", 
 	 * >>     "Prename"  	=> "VARCHAR(50) NOT NULL", 
 	 * >>     "Name"	=> "VARCHAR(250) NOT NULL",
 	 * >>     "Postcode" 	=> "VARCHAR(50) NOT NULL",
 	 * >>     "Country" 	=> "VARCHAR(50) NOT NULL" );
 	 * >>  	$db->chkdb("tb_foo", $myrow, "InnoDB", "latin1");
 	 */		

	public function chkdb($table, $item_rows=array(), $engine="MyISAM", $charset="utf8"){
		$chktable = 'SHOW TABLE '.$table; 
		$this->stmt = parent::exec($chktable);
		if(!$this->stmt){
			$command  = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (';
			foreach($item_rows as $x => $y){
				$items[]	 = '`'.$x.'` '.$y;
			}
			$command .= implode(",", $items);
			$command .= ') ENGINE='.$engine.' DEFAULT CHARSET='.$charset.';';
			$this->stmt  	 = parent::exec($command);
		}
		return $this->stmt;
	}

	public function gettotal($table, $where=null)
	{
		$command  = "SELECT count(*) FROM `$table`"; 
		$command .=	($where)? ' WHERE '.$where : null ;
		$this->stmt = parent::prepare($command);
		$this->stmt->execute();
		return $this->stmt->fetchColumn(); 
	}
	
	public function pushdb($table, $where=null, $query=[])
	{	$this->query = null;
		if(!empty($query)){
			$this->indb($table, $query);
			$this->query = $this->getdb($table, $where);	
		}
		return $this->query;
	}

	public function sqlParse($arrs=[],$spr="AND"){
		$arr = null;
		if(!empty($arrs)){
			foreach ($arrs as $key => $value) {
				$arr[] = $key."='".str_replace("'","\'",$value)."'";
			}
			$arr = implode(" $spr ", $arr);
		}
		return $arr;
	}

}

/*
 * Then, a class conn configuration for class pattern:
 */ 
 
trait conn {
	
	private static $inst;
	
	public static function myDB($pattern=[]){		
		$user = isset($pattern[0])? $pattern[0] : false;
		$pass = isset($pattern[1])? $pattern[1] : false;
		$pattern = [
			'dbu' 	=> $user,
			'dbp' 	=> $pass,
			'dbn' 	=> (isset($pattern[2])? $pattern[2] : null),
			'dbh' 	=> (isset($pattern[3])? $pattern[3] : 'localhost'),
			'port' 	=> (isset($pattern[5])? $pattern[5] : '3306'), 
			'type' 	=> (isset($pattern[4])? $pattern[4] : 'mysql'),
			'error' => [
				"unknown"	=> "<i style='color:red'>Unknown Error!</i>",
				"0"		=> "<i style='color:red'>Unknown Driver! (Check Your login pattern again)</i>",
				"1045" 		=> "<i style='color:red'>Access Denied! (Check Your login pattern again)</i>",
				"2002" 		=> "<i style='color:red'>Not Connect!</i>",
				"23000"	 	=> "<i style='color:red'>Duplicate keys</i>",
				"23001" 	=> "<i style='color:red'>Some other error</i>",
				"42000" 	=> "<i style='color:red'>Syntax error or access violation</i>",
				"08007" 	=> "<i style='color:red'>Connection failure during transaction</i>"
			]
		];

		if (!isset(self::$inst)){
			self::$inst = new pattern($pattern);	
		}

		return self::$inst;
	}	
	
	public static function noDB(){		
		self::$inst = null;
		return self::$inst;
	}
}
