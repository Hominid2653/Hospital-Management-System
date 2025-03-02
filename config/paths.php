<?php
// Define the application's base URL
define('BASE_URL', '/SianMedical/'); // Adjust this to match your project's root URL

function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}
?> 