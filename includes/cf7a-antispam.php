<?php

$mysql = new mysqli('localhost', 'user', 'pass', 'database');

// Tell b8 to use the above MySQL resource
$config_b8      = [ 'storage'  => 'mysql' ];
$config_storage = [ 'resource' => $mysql,
                    'table'    => 'b8_wordlist' ];