<?php
$server = "localhost";
$user = "root";
$password = "";
$nama_database = "siakad_uinbanten_006";
$db = mysqli_connect($server, $user, $password, $nama_database);

if( !$db ){
    die("gagal terhubung dengan database". mysqli_connect_error());
}
?>