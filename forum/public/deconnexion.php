<?php
session_start();
unset($_SESSION['messagerie_deverrouillee']);
session_destroy();
header('Location: index.php');
exit;