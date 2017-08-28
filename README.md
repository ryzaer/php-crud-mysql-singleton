# Q-engine-PHP-PDO-Class (singleton)

HOW to use?

//>> open connection <<
$db = conn::myDB();

//>> Insert function <<
$data_post[] = array("test1" => $post1[$rand], "test2" => $post2[$rand], "test3" => json_encode(array($post3[$rand])));
- or -
$data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
$db->indb("table", $data_post);

//>> Delete function <<
$db->dldb("table", array("id" => "data_id"));

//>> Update function <<
$data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
$db->updb("table", $data_post, "id ='data_id'");

//>> Select function <<
$show = $db->getdb("tabel", "column1='id_or_data_to_show'", null, 3); // 3 is limit data to show;
if(!$show){
   echo 'Not Found!';
}else{
   foreach ($result as $data){  
     echo $data['row'];
   }
}

//>> Create Table function <<
$myrow = array( 
"ID" 				=> 	"INT(11) AUTO_INCREMENT PRIMARY KEY", 
"Prename" 	=> 	"VARCHAR(50) NOT NULL", 
"Name"			=> 	"VARCHAR(250) NOT NULL",
"Postcode"  =>	"VARCHAR(50) NOT NULL",
"Country"   =>	"VARCHAR(50) NOT NULL" );
$db->chkdb("tb_foo", $myrow, "InnoDB", "latin1");

//>>Then close connection <<
$db = conn::noDB();
