<?php
// @todo check database works without ERRMODE_EXCEPTION
// @todo check database works without utf8
// @todo check database works with case sensitive encoding
$pdo = new PDO('mysql:host=localhost;dbname=rhino_data_table_examples', 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;',
]);

return $pdo;
