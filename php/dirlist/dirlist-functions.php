<?php if (isset($_REQUEST["show-source"])) {
    header('Content-Type: text/plain');
    die(file_get_contents($_SERVER['SCRIPT_FILENAME']));
} ?><?php

    // https://arkenidar.com/php/dirlist/dirlist-functions.php?show-source

    // @marker-show-source@
    // https://arkenidar.com/php/dirlist/?show_source=dirlist-functions.php

    function is_allowed_to_show($file_path)
    {
        // determine the file extension
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // check if there's an index.allow file in the same directory as the file or directories above
        $current_directory = dirname($file_path);
        $index_allow_file = "";
        while (true) {
            $potential_index_allow_file = $current_directory . '/index.allow';
            if (file_exists($potential_index_allow_file)) {
                $index_allow_file = $potential_index_allow_file;
                break;
            }
            $parent_directory = dirname($current_directory);
            if ($parent_directory === $current_directory) {
                // reached the root directory
                break;
            }
            $current_directory = $parent_directory;
        }

        if (file_exists($index_allow_file)) {
            // read the file content
            $file_content = file_get_contents($index_allow_file);
            if ($file_content === false) {
                return "error reading index.allow file";
            }

            // @allow: *.php;
            // parse the file content to find allowed patterns
            $lines = explode("\n", $file_content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '@allow:')) {
                    $mode = "allow";
                } elseif (str_starts_with($line, '@disallow:')) {
                    $mode = "disallow";
                } else {
                    continue; // skip lines that do not start with @allow: or @disallow:
                }
                if ($mode === "allow" || $mode === "disallow") {
                    $patterns = explode(';', substr($line, 2 + strlen($mode))); // get the part after '@allow:' or '@disallow:'
                    foreach ($patterns as $pattern) {
                        $pattern = trim($pattern);
                        if ($pattern === '') {
                            continue;
                        }
                        // // convert wildcard pattern to regex
                        // $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/i';
                        // if (preg_match($regex, basename($file_path))) {
                        //     return true; // allowed
                        // }

                        // if pattern is *.php, then check if the file ends with .php
                        if ($pattern === '*.' . $ext) {
                            return $mode . "ed"; // allowed or disallowed based on mode
                        }

                        // if pattern is exact match, then check if the file matches exactly
                        if ($pattern === basename($file_path)) {
                            return $mode . "ed"; // allowed or disallowed based on mode
                        }

                        // if pattern is just *, then allow all files
                        if ($pattern === '*') {
                            return $mode . "ed"; // allowed or disallowed based on mode
                        }
                    }
                }
            }
        }

        return "not allowed nor disallowed";
    }

    function is_marked_to_show($file_path)
    {
        // check first if the file is explicitly allowed or disallowed via index.allow
        // if allowed, return true
        // if disallowed, return false
        // if not allowed nor disallowed, then check for marker comments in the file
        // if error reading index.allow, return false (do not allow showing)
        // if no index.allow file, then check for marker comments in the file
        $permission = is_allowed_to_show($file_path);
        if ($permission === "allowed") {
            return true; // explicitly allowed
        }
        if ($permission === "disallowed") {
            return false; // explicitly disallowed
        }
        if ($permission === "error reading index.allow file") {
            return false; // in case of error reading index.allow, do not allow showing
        }
        if ($permission === "not allowed nor disallowed") {
            // continue to check for marker comments in the file
        }

        // if not explicitly allowed, then check for marker comments in the file

        // check if the file exists
        if (file_exists($file_path) === false) {
            return false;
        }

        // check if the file is readable
        if (is_readable($file_path) === false) {
            return false;
        }

        // check if the file is not bigger than 1 MB
        if (filesize($file_path) > 1024 * 1024) {
            return false;   // do not allow showing files bigger than 1 MB
        }

        // read the file content
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            return false;
        }

        // check for the presence of the "marker-show-source" comment
        // and the absence of the "marker-hide-source" comment
        $show_condition = strpos($file_content, '@' . 'marker-show-source' . '@') !== false;
        $hide_condition = strpos($file_content, '@' . 'marker-hide-source' . '@') !== false;
        return $show_condition && !$hide_condition;
    }

    function is_directory_allowed_to_show($directory_path, $index_allow_file)
    {
        // check if the directory is allowed to show based on index.allow file
        ///$index_allow_file = $directory_path . '/index.allow';
        if (file_exists($index_allow_file)) {
            // read the file content
            $file_content = file_get_contents($index_allow_file);
            if ($file_content === false) {
                return "error reading index.allow file";
            }

            // @allow: dir_name/;
            // parse the file content to find allowed patterns
            $lines = explode("\n", $file_content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '@allow:')) {
                    $mode = "allow";
                } elseif (str_starts_with($line, '@disallow:')) {
                    $mode = "disallow";
                } else {
                    continue; // skip lines that do not start with @allow: or @disallow:
                }
                if ($mode === "allow" || $mode === "disallow") {
                    $patterns = explode(';', substr($line, 2 + strlen($mode))); // get the part after '@allow:' or '@disallow:'
                    foreach ($patterns as $pattern) {
                        $pattern = trim($pattern);
                        if ($pattern === '') {
                            continue;
                        }
                        // if pattern matches the directory name
                        if ($pattern === basename($directory_path) . '/') {
                            return $mode . "ed"; // allowed or disallowed based on mode
                        }
                        // if pattern is *.ext/ , check if the directory name ends with .ext
                        if (str_starts_with($pattern, '*.') && str_ends_with($pattern, '/')) {
                            $ext = substr($pattern, 2, -1); // get the extension part
                            if (str_ends_with(basename($directory_path), '.' . $ext)) {
                                return $mode . "ed"; // allowed or disallowed based on mode
                            }
                        }
                        // if pattern is just */, then allow all directories
                        if ($pattern === '*/') {
                            return $mode . "ed"; // allowed or disallowed based on mode
                        }
                    } // end foreach pattern
                } // end if mode
            } // end foreach line
        } // end if file exists

        return "not allowed nor disallowed";
    } // end function is_directory_allowed_to_show

    function directory_listing($path_to_list = "")
    {
        // list the directory contents
        print("<div> <!-- begin of directory listing -->\n\n");

        // determine the directory to list
        $docroot = $_SERVER["DOCUMENT_ROOT"];
        $directory_to_list = $docroot . $path_to_list;

        // security step : resolve to absolute path
        $directory_to_list = realpath($directory_to_list);

        // security check : ensure path exists and is within docroot
        if ($directory_to_list === false || str_starts_with($directory_to_list, $docroot) === false) {
            die('Access denied');
        }

        // security check : check if it's actually a directory
        if (is_dir($directory_to_list) === false) {
            die('Directory not found');
        }

        // read settings from .directory-listing.json if it exists
        // possible settings: sort (ascending, descending, mtime_asc, mtime_desc)
        // default: ascending
        // example content: { "sort": "descending" }
        // example content: { "sort": "mtime_desc" }
        // example content: { "sort": "mtime_asc" }
        // example content: { "sort": "ascending" }
        $settings_file = $directory_to_list . "/" . ".directory-listing.json";
        if (is_file($settings_file)) {
            // Read settings from the file
            $settings_content = json_decode(file_get_contents($settings_file), true);
        } else {
            $settings_content = ["sort" => "ascending"];
        }
        // Support for sorting by modification date (mtime_asc, mtime_desc) via .directory-listing.json
        $entries = array_filter(scandir($directory_to_list), function ($entry) {
            return $entry !== "." && $entry !== ".."; // exclude . and ..
            // optionally, exclude hidden files (those starting with a dot)
            // return $entry[0] !== '.';
            // but for now, do not exclude hidden files (those starting with a dot)
        });

        if (isset($settings_content["sort"]) && ($settings_content["sort"] === "mtime_asc" || $settings_content["sort"] === "mtime_desc")) {
            usort($entries, function ($a, $b) use ($directory_to_list, $settings_content) {
                $mtimeA = filemtime("$directory_to_list/$a");
                $mtimeB = filemtime("$directory_to_list/$b");
                if ($settings_content["sort"] === "mtime_asc") {
                    return $mtimeA <=> $mtimeB;
                } else {
                    return $mtimeB <=> $mtimeA;
                }
            });
        } else {
            // Default: name sort

            // deepseek.com name sort

            // sort by name, but strip deepseek and file type but only for comparing

            $deepseek_naming_strip = function ($entry) {
                if (str_starts_with($entry, 'deepseek_')) {
                    $parts = explode('_', $entry);
                    array_shift($parts); // Remove 'deepseek'
                    array_shift($parts); // Remove file type string part
                    return implode('_', $parts);
                }
                return $entry;
            };

            usort($entries, function ($a, $b) use ($deepseek_naming_strip) {
                $a = $deepseek_naming_strip($a);
                $b = $deepseek_naming_strip($b);
                return strcmp($a, $b);
            });

            $sort = ($settings_content["sort"] == "descending") ? SCANDIR_SORT_DESCENDING : SCANDIR_SORT_ASCENDING;
            if ($sort == SCANDIR_SORT_DESCENDING) {
                $entries = array_reverse($entries);
            }
        }

        // Show sorting criteria
        $sort_label = 'name ascending';
        if (isset($settings_content["sort"])) {
            switch ($settings_content["sort"]) {
                case 'descending':
                    $sort_label = 'name descending';
                    break;
                case 'mtime_asc':
                    $sort_label = 'modification date ascending';
                    break;
                case 'mtime_desc':
                    $sort_label = 'modification date descending';
                    break;
            }
        }
        print("<div>Sorting: <b>" . htmlspecialchars($sort_label) . "</b>.</div>\n\n");

        print("<style>\n");
        print("  .directory-listing li { white-space: pre-wrap; }\n");
        print("</style>\n\n");

        print("<ul class='directory-listing'>\n");

        foreach ($entries as $entry) {

            // start output buffering to capture echo output
            ob_start();

            $file_path = "$directory_to_list/$entry";
            $is_dir = is_dir($file_path);

            // this is not needed anymore since we use output buffering
            // but keep it here as a reference
            // ( it's done later only in case of actual content and not empty buffer!)
            // echo "  <li> "; // start list item but we use output buffering to capture echo output

            $link_content = " "; // space
            $link_content .= $is_dir ? "+" : "■";
            $link_content .= " "; // space
            $link_content .= str_replace("_", " ", $entry);
            $link_content .= $is_dir ? "/" : "";

            // HTML-escape the entry name for safety
            $escaped_entry = htmlspecialchars($entry, ENT_QUOTES, 'UTF-8');

            // is directory or file ?
            if ($is_dir) {

                // if is directory, just link to the directory
                // *HTML* output the link
                echo "<a href='$escaped_entry'>$link_content</a>";

                // if directory, then check if the directory or directories above contain an "index.allow" file

                // $file_path is the directory path, so start checking from there
                $current_directory = $file_path;
                while (true) {
                    $index_allow_file = $current_directory . '/index.allow';
                    if (file_exists($index_allow_file)) {
                        // found an index.allow file in current directory or parent directories

                        // don't show if the directory is disallowed
                        $permission = is_directory_allowed_to_show($file_path, $index_allow_file);
                        if ($permission === "disallowed") {
                            break; // do not show the [list] link
                        }

                        // if found and allowed, show the [list] link
                        if ($permission === "not allowed nor disallowed") {
                            // do not show the [list] link
                            break;
                        }

                        if ($permission === "error reading index.allow file") {
                            // do not show the [list] link
                            break;
                        }

                        if ($permission === "allowed") {
                            // show the [list] link, if allowed
                            // *HTML* output the [list] link
                            echo " <a href='$escaped_entry" . "/index" . "'>[list]</a>";
                        }
                        break;
                    } // end if file exists

                    // move up one directory
                    $next_directory = dirname($current_directory);

                    // check if the next directory is the same as the current directory
                    // to avoid infinite loop
                    if ($next_directory === $current_directory) {
                        // reached the root directory, stop
                        break;
                    }

                    // update current directory for next iteration
                    $current_directory = $next_directory;
                }
            } else {

                // if is file
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

                $index_prefix = "action";

                // determine link behavior based on file extension
                if (array_search($ext, ['html', 'htm', 'svg']) !== false) {
                    // link to the HTML file so it can be viewed in the browser
                    // and a separate link to view the raw text
                    echo "<a href='$escaped_entry'>$link_content</a>";
                    echo "<a href='$index_prefix?read_file=$escaped_entry&show_dirlist='>[text]</a>";
                } elseif (array_search($ext, ['md']) !== false) {
                    echo "<a href='$index_prefix?read_markdown=$escaped_entry&show_dirlist='>$link_content</a>";
                } elseif (array_search($ext, ['txt', 'c', 'lua', 'css', 'js', 'json']) !== false) {
                    // link to the text file so it can be viewed in the browser
                    // and a unified link to view the raw text
                    echo "<a href='$index_prefix?read_file=$escaped_entry&show_dirlist='>$link_content [text]</a>";
                } elseif (array_search($ext, ['php']) !== false) {
                    // link to the PHP file so it can be viewed in the browser
                    // and a separate link to view the source code
                    echo "<a href='$escaped_entry'>$link_content</a>";

                    if (is_marked_to_show($file_path)) {
                        echo "<a href='$index_prefix?read_file=$escaped_entry&show_dirlist='>[text]</a>";
                    }
                } elseif (in_array($ext, ['zip', 'apk', 'love', 'blend', 'wav'])) {
                    // direct download
                    echo "<a download href='$escaped_entry'>$link_content</a>";
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // image viewer
                    echo "<a href='$index_prefix?image_view=$escaped_entry'>$link_content</a>";
                } elseif (in_array($ext, ['htaccess'])) {
                    // .htaccess : the file is allowed to show only if it contains the "marker-show-source" comment
                    if (is_marked_to_show($file_path)) {
                        echo "<a href='$index_prefix?read_file=$escaped_entry&show_dirlist='>$link_content [text]</a>";
                    }
                } else {
                    // default
                    echo "<a href='$escaped_entry'>$link_content</a>";

                    if (is_marked_to_show($file_path)) {
                        echo "<a href='$index_prefix?read_file=$escaped_entry&show_dirlist='>[text]</a>";
                    }
                }
            }

            // capture the output and clean the buffer
            $html_buffer = ob_get_contents();
            ob_end_clean();

            // only print if there is actual content (not empty)
            if (trim($html_buffer) === "") {
                continue; // skip empty entries
            }

            // now print the list item with the captured content
            echo "  <li> ";

            echo $html_buffer;

            echo " </li>\n";
        }
        print("</ul>\n");

        print("\n</div> <!-- end of directory listing -->\n");
    }
