<?php


$conn = mysqli_connect('localhost', 'root', '', 'ballot_box copy');

if(!$conn){
    die("Connection failed: ". mysqli_connect_error());
}else{
     echo "";
}
?>