<?php
// show-source: if you want to see the source code of this file, add ?show-source to the URL
if (isset($_REQUEST["show-source"])) {
    header('Content-Type: text/plain');
    die(file_get_contents($_SERVER['SCRIPT_FILENAME']));
}
?>
<?php

// @marker-show-source@
// https://arkenidar.com/php/dirlist/?show_source=dirlist.php

// -- header part --

// security : measures mentioned by Claude Sonnet 4 :
// prevent directory traversal attacks
// by sanitizing the URI
// and ensuring the path is within the document root
// prevent reading of files outside the directory
// prevent reading of files with disallowed extensions
// prevent too many requests (simple rate limiting)
// set security-related HTTP headers
// start session for rate limiting
// prevent XSS by escaping output where necessary
// prevent clickjacking by setting X-Frame-Options header
// prevent MIME type sniffing by setting X-Content-Type-Options header
// prevent execution of inline scripts by setting Content-Security-Policy header

// security : Enhanced path sanitization
function sanitizePath($uri)
{
    // Remove null bytes
    $uri = str_replace("\0", '', $uri);

    // Remove multiple types of traversal patterns
    $patterns = [
        '/\.\.+[\/\\\\]/',  // ../, ..\, .../, etc.
        '/[\/\\\\]\.\.+/',  // /.. \.. etc.
        '/\.\.+$/',         // trailing ..
    ];

    foreach ($patterns as $pattern) {
        $uri = preg_replace($pattern, '', $uri);
    }

    return $uri;
}


// Get and sanitize URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri = sanitizePath($uri);

if (str_ends_with($uri, '/index')) {
    // remove the "index" part from the URI
    $uri = substr($uri, 0, -strlen('index'));
    $current_is_index_request = true;
} else {
    $current_is_index_request = false;
}

if (str_ends_with($uri, '/action')) {
    // remove the "action" part from the URI
    $uri = substr($uri, 0, -strlen('action'));
    $current_is_action_request = true;
} else {
    $current_is_action_request = false;
}

// get the URI from the request
// unsafe, sanitize it first
//$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); // unsafe, sanitize it first
// sanitize the URI to prevent directory traversal attacks
///// $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
///// $uri = preg_replace('/\.\.\//', '', $uri); // remove ../

// determine the directory to list (absolute path)
$path_relative = $uri; // relative to document root
$docroot = $_SERVER["DOCUMENT_ROOT"];
$directory_to_list = $docroot . $path_relative; // full path to the directory

// security : resolve to absolute path
$directory_to_list = realpath($directory_to_list);

// security check : ensure path exists and is within docroot
if ($directory_to_list === false || str_starts_with($directory_to_list, $docroot) === false) {
    die('Access denied');
}

// security check : check if it's actually a directory
if (is_dir($directory_to_list) === false) {
    die('Directory not found');
}

// security : index.allow file check
// if the directory contains an "index.allow" file, we will allow directory listing
// otherwise, we will check for index.php or index.html files
// if either of those files exist, we will not allow directory listing
// this is to prevent directory listing of directories that have index files
// that would otherwise prevent directory listing
// also for safety, to prevent accidental exposure of directory contents
// if (file_exists("$directory_to_list/index.allow")) { is handled above via .htaccess
if ($current_is_index_request && !file_exists("$directory_to_list/index.allow")) {
    if (file_exists("$directory_to_list/index.php") || file_exists("$directory_to_list/index.html")) {
        //die('Directory listing not allowed');
    }
}

// ensure the path ends with a slash
if (str_ends_with($directory_to_list, '/') === false) {
    $directory_to_list .= '/';
}

// --------------------------------------

// "dirlist-header.php" consumes : $uri, $directory_to_list (other variables are local)

// --------------------------------------

require_once("dirlist-header.php");

?>

<!--------------------------------------------------->

<!-- custom php dirlist (directory_listing) part -->

<?php require_once("dirlist-functions.php"); ?>

<?php
// special situation: if the user wants to read a file,
// we don't want to list the directory
$show_directory = true;

// if the user wants to read a file, we will not show the directory listing
if (isset($_GET['read_file']))
    $show_directory = false;

// if the user wants to read a markdown file, we will not show the directory listing
if (isset($_GET['read_markdown']))
    $show_directory = false;

// exception: if the user wants to read a file and also list the directory
if (isset($_GET['show_dirlist']))
    $show_directory = true;

if ($show_directory) {

    /*
    RewriteEngine on

    # a directory
    RewriteCond %{REQUEST_FILENAME} -d

    # without index files
    RewriteCond %{REQUEST_FILENAME}/index.php !-f
    RewriteCond %{REQUEST_FILENAME}/index.html !-f

    # exception case: explicitly use Apache dirlist
    RewriteCond %{REQUEST_FILENAME} !(.*\/_.*)

    RewriteRule (.*) php/dirlist/dirlist.php
    */

    //########################

    $uri_html = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
    echo "<h1 style='word-break: break-all;'>Directory: $uri_html .</h1>\n\n";

    // actual directory listing
    directory_listing($uri);
}
?>

<!--------------------------------------------------->

<!-- footer part -->

</body>

</html>