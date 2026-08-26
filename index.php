<?php
require_once __DIR__ . '/config.php';
if (is_logged_in()) {
    redirect(base_url('dashboard.php'));
} else {
    redirect(base_url('login.php'));
}
