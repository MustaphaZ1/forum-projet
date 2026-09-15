<?php
try {
$pdo=new PDO("mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=if0_42814985_forum1;charset=utf8mb4"
echo "Connexion réussie";
} catch (PDOException $e) {
echo "Erreur : " . $e->getMessage();
}
