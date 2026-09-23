<?php

/**
 * Restaurant Management System (RMS) - Laravel Root Entrypoint
 * Forwards requests to the public directory for XAMPP Apache environments
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// If request is already for public, or index file
require_once __DIR__ . '/public/index.php';
