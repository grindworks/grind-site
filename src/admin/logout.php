<?php
ob_start();

/**
 * logout.php
 *
 * Admin Logout Handler.
 */
define('GRINDS_APP', true);

require_once __DIR__ . '/bootstrap_base.php';
require_once ROOT_PATH . '/lib/auth.php';

// CSRF & POST Method Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf_token(Routing::getString($_POST, 'csrf_token'))) {
    // Redirect to dashboard if accessed via GET or invalid token
    redirect('admin/index.php');
}

// Perform logout
grinds_logout();

// Redirect to login
redirect('admin/login.php');
