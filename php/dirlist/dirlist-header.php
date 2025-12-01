<?php
if (isset($_REQUEST["show-source"])) {
    header('Content-Type: text/plain');
    die(file_get_contents($_SERVER['SCRIPT_FILENAME']));
}
?>
<?php

// @marker-show-source@
// https://arkenidar.com/php/dirlist/?show_source=dirlist-header.php

// --------------------------------------

// "dirlist-header.php" consumes : $uri, $directory_to_list (other variables are local)

// --------------------------------------

// read_file parameter handling
// This code is used to handle the 'read_file' parameter in the URL.

// The following code is used to display the contents of a markdown file
// or a text file based on the 'read_file' parameter passed in the URL.
// The 'read_file' parameter is expected to be a GET parameter
// and should be passed in the URL as a query string.
// For example: ?read_file=example.md
// The code checks if the 'read_file' parameter is set and if it has a .md or .txt extension.
// If the parameter is set and has a .md extension, it includes the markdown.js script
// and creates a div with the class 'markdown-url' and the data-url attribute set to the file name.
// If the parameter is set and has a .txt extension, it reads the contents of the file
// and displays it in a preformatted block.

// The htmlspecialchars function is used to prevent XSS attacks
// by converting special characters to HTML entities.
// The ENT_QUOTES flag is used to convert both double and single quotes.
// The 'UTF-8' parameter is used to specify the character encoding.
// The pathinfo function is used to get the file extension of the file name.
// The basename function is used to prevent directory traversal attacks
// by stripping any directory information from the filename.

// --------------------------------------

// "dirlist-header.php" consumes : $uri, $directory_to_list (other variables are local)

// --------------------------------------

$ext = "";
if (isset($_GET['read_file'])) {
    $file_name = $_GET['read_file'];
} elseif (isset($_GET['image_view'])) {
    $file_name = $_GET['image_view'];
} elseif (isset($_GET['show_source'])) {
    $file_name = $_GET['show_source'];
} elseif (isset($_GET['read_markdown'])) {
    $file_name = $_GET['read_markdown'];
} else {
    $file_name = "";
}
if ($file_name) {
    $file_name = basename($file_name);
}
if ($file_name) {
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
} else {
    $ext = "";
}
?><?php
    // show the source code of a PHP or HTML file if the 'show_source' parameter is set
    // and if the file contains the comment "marker-show-source"
    if (isset($_GET['show_source'])) {
        header('Content-Type: text/plain');

        // sanitize the input
        $source_path = $_GET['show_source'];
        $file_name = basename($source_path);

        if (!$file_name) {
            die("Invalid file name.");
        }

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        if (!in_array($ext, ['php', 'html', 'htm', 'htaccess'])) {
            die("The requested file ($file_name) is not a PHP or HTML file.");
        }

        $file_name =  $directory_to_list . $file_name;

        if (!is_file($file_name)) {
            die("The requested file ($file_name) does not exist.");
        }

        // requires : is_marked_to_show($file_name)
        require_once "dirlist-functions.php";

        switch ($ext) {
            case 'php':
                // allowed extensions
                // PHP : check if the file is marked to show
                if (is_marked_to_show($file_name)) {
                    // the file is marked to show
                    die(file_get_contents($file_name));
                } else {
                    die("The requested file ($file_name) does not contain the 'marker-show-source' comment. check also for 'index.allow' permissions-file.");
                }
                break;
            case 'html':
            case 'htm':
                // allowed extensions
                // HTML : the file is naturally allowed to show
                die(file_get_contents($file_name));
                break;
            case 'htaccess':
                // allowed extensions
                // .htaccess : check if the file is marked to show
                if (is_marked_to_show($file_name)) {
                    // the file is marked to show
                    die(file_get_contents($file_name));
                } else {
                    die("The requested file ($file_name) does not contain the 'marker-show-source' comment. check also for 'index.allow' permissions-file.");
                }
                break;
            default:
                die("The requested file ($file_name) is not a PHP or HTML file.");
        }
    }
    ?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php require($_SERVER['DOCUMENT_ROOT'] . "/php/modular/common-head.php"); ?>

    <title>
        directory :
        <?= htmlspecialchars($uri, ENT_QUOTES, 'UTF-8') ?>

        : <?= htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8') ?>

    </title>
</head>

<body>

    <?php require __DIR__ . "/../modular/combo-header.php"; ?>

    <!-- START of navigation -->
    <?php

    /* breadcrumb navigation generation */

    // example of $uri : /php/dirlist/
    // example of $uri parts : ["php", "dirlist"]
    // example of $links : ['<a href="/php/"> php </a>', '<a href="/php/dirlist/"> dirlist </a>']
    // example of $breadcrumb : '<a href="/php/"> php </a> , \n<a href="/php/dirlist/"> dirlist </a> .'

    function breadcrumb_navigation_to_html($uri)
    {
        // links to the current directory and navigation

        // security: validate input
        if (!is_string($uri)) {
            return "Invalid path.";
        }

        // trim slashes from the start and end of the URI
        $url_parts = rtrim($uri, "/");
        $url_parts = ltrim($url_parts, "/");

        // split the URI into parts
        $url_parts = explode("/", $url_parts);
        // remove empty parts and validate each part
        $url_parts = array_filter($url_parts, function ($part) {
            // security: filter out dangerous characters and patterns
            if ($part === "" || $part === "." || $part === "..") {
                return false;
            }
            // reject parts with null bytes, control characters, or dangerous patterns
            if (preg_match('/[\x00-\x1f\x7f<>"\'&]/', $part)) {
                return false;
            }
            return true;
        });

        // assemble the breadcrumb navigation
        $links = []; // array of links
        $assembled = "/"; // start with root
        foreach ($url_parts as $part) { // for each part of the URI
            // security: additional validation per part
            if (strlen($part) > 255 || preg_match('/[^\w\-._~]/', $part)) {
                continue; // skip parts that are too long or contain unsafe characters
            }

            $assembled .= $part . "/"; // assemble the link

            // replace underscores with spaces for display
            $link_text = str_replace("_", " ", $part);
            $link_text = htmlspecialchars($link_text, ENT_HTML5, 'UTF-8');

            // security: properly encode the URL path for href attribute
            $safe_assembled = htmlspecialchars($assembled, ENT_QUOTES, 'UTF-8');

            $links[] = "<a href='$safe_assembled'> $link_text </a>";
        }

        if (empty($links)) {
            return "Root directory.";
        }

        $breadcrumb = implode(" , \n", $links);
        $breadcrumb .= " .";

        return $breadcrumb;
    }
    $links_trail = breadcrumb_navigation_to_html($uri);
    ?>
    <a href="/index"> Directory path . </a>
    <?php echo $links_trail; ?>

    <!-- START of media-player -->
    <div id="media_player_container">
        <div id="player_feature" style="display: none;">
            <div>
                <button onclick="player_options.style.display=player_options.style.display==''?'none':''">
                    media player …
                </button>
                <span id="playing"></span> .
            </div>

            <div id="player_options" style="display: none;">
                <hr>
                <div>
                    <div>
                        <span>
                            After this playable media do .
                            <select id="after" oninput="json_link()">
                                <option value="next"> Play *next* media content , after . </option>
                                <option value="repeat"> *Repeat* the current media content . </option>
                                <option value="stop"> *Stop* after the end of the current media content . </option>
                                <option value="playlist"> Repeat this *playlist* of media contents . </option>
                            </select>
                        </span>
                    </div>
                    <hr>
                    <div><a id="link_to_current_playable" href="#" target="_blank">
                            Link to the current playable media content . </a></div>
                    <hr>
                    <div><label class="no_select">
                            Do you want to show download links ?
                            <input type="checkbox" id="player_download_links_shown">
                        </label></div>
                    <hr>
                    <div><label class="no_select">
                            Do you want to intercept into this media-contents player ?
                            <input type="checkbox" id="player_intercept" checked>
                        </label></div>
                </div>

            </div> <!-- player_options -->

            <script src="/web/dhtml/dhtml5_player/player.js"></script>
            <link rel="stylesheet" href="/web/dhtml/dhtml5_player/player.css">

            <audio controls id="player_audio"></audio>
            <video controls id="player_video" style="max-width: 100%; height: auto;"></video>
        </div> <!-- player_feature -->

        <hr>
    </div> <!-- sticky -->

    <?php
    $safe_web_url = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
    ?>
    <?php
    if (isset($_GET['read_markdown']) && $ext === 'md') {
    ?>
        <script src="/app/lib/html-markdown/markdown.js"></script>
        <div class="markdown-url" data-url="<?= $safe_web_url ?>"></div>
        <hr>
        <a href='<?= "action?read_file=$safe_web_url" ?>'> View raw markdown file </a>
        <hr>
    <?php
    } // end if ext === 'md'
    ?>
    <?php
    if (isset($_GET['read_file'])) {
        // for PHP or HTML files, modify the link to show the source code
        if (in_array($ext, ['php', 'htaccess'])) {
            // for PHP files, link to show the source code
            // not needed for HTML files
            $safe_web_url = "action?show_source=$safe_web_url";
        }
    ?>
        <script src="/web/show-source.js" data-href="<?= $safe_web_url ?>"></script>
        <hr>
        <?php if (isset($_GET['read_file']) && in_array($ext, ['md'])) { ?>
            <a href='<?= "action?read_markdown=$safe_web_url" ?>'> View rendered markdown file </a>
            <hr>
        <?php } ?>
    <?php
    } // end if ext !== 'md'
    ?>

    <?php if (isset($_GET['image_view']) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        // Display the image
        $imageFile = htmlspecialchars($_GET['image_view'], ENT_QUOTES, 'UTF-8');
    ?>
        <h2> Image File : <?= $imageFile ?> </h2>
        <a href="<?= $imageFile ?>" target="_blank">View Image</a>
        <hr>
        <a href="<?= $imageFile ?>" target="_blank">
            <img src="<?= $imageFile ?>" alt="Image View" style="max-width: 100%; height: auto;">
        </a>
    <?php
    } // end if ext === 'jpg' || ext === 'jpeg' || ext === 'png' || ext === 'gif'
    ?>

    <!-- END of header -->