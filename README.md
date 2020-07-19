# PHP CRUD Class For Mysql (singleton)
<p>This is a simple CRUD Class with PHP PDO Statement and singleton</p>
<h3>HOW to use?</h3>

<strong>Open connection</strong>
<pre><code>
require_once("class.crud.mysql.php");
// Default driver is mysql (pgsql not test yet)
$chk = array ("your_db_user","your_db_password","your_database_name","your_host","your_port_host","driver");
$db = conn::myDB($chk);

// or simple very start to connect all of your db
$db = conn::myDB(["your_db_user","your_db_password"]);

</code></pre>

<strong>Insert function</strong>
<pre><code>$data_post[] = array("test1" => $post1[$rand], "test2" => $post2[$rand], "test3" => json_encode(array($post3[$rand])));
// or
$data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
$db->indb("table", $data_post);</code></pre>

<strong>Delete function</strong>
<pre><code>$db->dldb("table", array("id" => "data_id"));</code></pre>

<strong>Update function</strong>
<pre><code>$data_post = array("test1" => $post1 , "test2" => $post2, "test3" => $post3);
$db->updb("table", $data_post, array("id => "data_id"));</code></pre>

<strong>Select function</strong>
<pre><code>$show = $db->getdb("tabel", "column1='id_or_data_to_show'", null, 3); // 3 is limit data to show;
if(!$show){
   echo 'Not Found!';
}else{
   foreach ($show as $data){  
     echo $data['row'];
   }
}</code></pre>

<strong>Create Table function</strong>
<pre><code>$myrow = array( 
"ID"        =>  "INT(11) AUTO_INCREMENT PRIMARY KEY", 
"Prename"   => 	"VARCHAR(50) NOT NULL", 
"Name"      => 	"VARCHAR(250) NOT NULL",
"Postcode"  =>	"VARCHAR(50) NOT NULL",
"Country"   =>	"VARCHAR(50) NOT NULL" );
$db->chkdb("tb_foo", $myrow, "InnoDB", "latin1");</code></pre>

<strong>Close connection</strong>
<pre><code>$db = conn::noDB();</code></pre>
