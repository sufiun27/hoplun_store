<?php
class DbInfo {
    //make change as per hosting
    private $host = "10.3.13.87";
    private $user = "sa";
    private $pass = "sa@123";
    
    public function __construct() {
        // Empty constructor
    }


    public function getHost() {
        return $this->host;
    }
    
    public function getUser() {
        return $this->user;
    }
    
    public function getPass() {
        return $this->pass;
    }
}
$db="ok";
?>
