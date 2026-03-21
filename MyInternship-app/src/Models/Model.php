<?php
namespace App\Models;

use PDO, PDOException;

/*
 * The Model class is a parent class that serves as the base class for all models in the application.
*/
class Model {
    protected $connection;

    public function __construct() {
        $env = parse_ini_file(".env");
        try {
            $this->connection = new PDO('mysql:host=' . $env['servername'] . ';dbname='.$env['dbname'] . ';charset=utf8', $env['username'], $env['password']);
            // set the PDO error mode to exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected successfully";
        }
        catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }
}
