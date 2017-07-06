SimpleDB
==========

PHP library for simple working with data in the database

Example
-------

```

require_once('SimpleDB.php');

$driver = 'mysqli';
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'database';
$db = new SimpleDB($driver, $hostname, $username, $password, $database);

$db->query('INSERT INTO `example` (`name`) VALUES ("test")');
$db->query('INSERT INTO `example` (`name`) VALUES ("test2")');

$exampleId = $db->getLastId(); //return last inserted id

$examples = $db->query('SELECT * FROM `example`');

$examples->row; // return first row
$examples->rows; // return all rows
$examples->numRows; // return count of all rows

```


> [Yaroslav Urbanevich](http://exe.kh.ua) 
