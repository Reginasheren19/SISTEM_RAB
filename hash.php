<?php
$password = "admin1_merdeka";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
