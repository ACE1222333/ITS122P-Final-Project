<?php
header('Content-Type: text/html; charset=utf-8');
$conn = mysqli_connect("localhost", "root", "", "carousell_db");
if (!$conn) {
    echo "Connection not established";
}
