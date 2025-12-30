<?php
// Redirigir todo a /public/index.php
if (!file_exists('public/index.php')) {
    die('Error: public/index.php not found');
}

require 'public/index.php';
