<?php

// Get the requested URI

// Remove query string for routing purposes
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Decode URI
$uri = urldecode($uri);

// Map URI to file system path
$file = __DIR__ . $uri;

// .htaccess equivalent routing
/*
########################
# index or action request
RewriteCond %{REQUEST_URI} /(index|action)$
RewriteRule (.*) php/dirlist/dirlist.php [L,QSA]
########################
*/

if (str_ends_with($uri, '/index') || str_ends_with($uri, '/action')) {
    require __DIR__ . '/php/dirlist/dirlist.php';
    exit;
}

// .htaccess equivalent routing
/*
########################
# a directory
RewriteCond %{REQUEST_FILENAME} -d

# without index files
RewriteCond %{REQUEST_FILENAME}/index.php !-f
RewriteCond %{REQUEST_FILENAME}/index.html !-f

# exception case: explicitly use Apache dirlist
RewriteCond %{REQUEST_URI} !(^|/)_
# no path segment that starts with '_'

RewriteRule (.*) php/dirlist/dirlist.php [L,QSA]
########################
*/

// Check if it's a directory
if (is_dir($file)) {
    // Ensure the URI ends with a slash
    if (!str_ends_with($uri, '/')) {
        header("Location: {$uri}/");
        exit;
    }

    // Serve index.php if it exists in the directory
    $indexFile = rtrim($file, '/') . '/index.php';
    if (is_file($indexFile)) {
        return false; // Let PHP's built-in server handle it
    }

    // Serve index.html if it exists in the directory
    $indexHtmlFile = rtrim($file, '/') . '/index.html';
    if (is_file($indexHtmlFile)) {
        return false; // Let PHP's built-in server handle it
    }

    // If no index file, show directory listing
    require __DIR__ . '/php/dirlist/dirlist.php';
    exit;
}

// default: serve the requested file if it exists

// Check if it's a file
if (is_file($file)) {
    return false; // Let PHP's built-in server handle static files
}

// 404 for other routes
http_response_code(404);
echo "<h1>404 - Not Found</h1>";
echo "<p>The requested route '{$uri}' was not found.</p>";
echo "<a href='/'>← Back to home</a>";
