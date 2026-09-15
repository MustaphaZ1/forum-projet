<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv=Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
function getPDO(): PDO {
    $host=$_ENV['DB_HOST'];
    $dbname=$_ENV['DB_NAME'];
    $user=$_ENV['DB_USER'];
    $password=$_ENV['DB_PASSWORD'];
    $charset='utf8mb4';
    $dsn="mysql:host=$host;dbname=$dbname;charset=$charset;port=3306";
    $options=[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ];
    try{
        return new PDO($dsn,$user,$password,$options);
    }catch (PDOException $e){
        die('Erreur de connexion : '.$e->getMessage());
    }
}