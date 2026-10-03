<?php
declare(strict_types=1);

/**
 * Log the user out and return to the public homepage.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

logout();
redirect('index.php');
