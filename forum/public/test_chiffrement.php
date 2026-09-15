<?php
echo '<pre>';
var_dump(in_array('aes-256-cgm', openssl_get_cipher_methods()));
echo '<pre>';
?>