<?php
$servername ="localhost"; 
$username="root";
$password="";
$dbname="login-php";



$conn = new mysqli($servername ,$username,$password,$dbname);
if($conn ->connect_error){
    echo"connecton faild ";
}

?>