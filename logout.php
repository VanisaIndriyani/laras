<?php
require_once __DIR__ . '/db.php';
session_destroy();
$_SESSION = [];
set_flash('success', 'Anda telah logout. Sampai jumpa!');
redirect(base_url('login.php'));
