<?php if (isset($_REQUEST["show-source"])) {
    header('Content-Type: text/plain');
    die(file_get_contents($_SERVER['SCRIPT_FILENAME']));
} ?>

<!-- @marker-show-source@ -->

<a href="/"> start . </a>
<button type="button" onclick="menu_navigation.style.display=menu_navigation.style.display==''?'none':''">
    page viewer … </button>

<div id="menu_navigation" style="display: none;">

    <!-- https://arkenidar.com/php/modular/search.html -->
    <?php require(__DIR__ . "/search.html"); ?>

    <!-- https://arkenidar.com/web/dhtml/color-theme/colors.html -->
    <?php require(__DIR__ . "/../../web/dhtml/color-theme/colors.html"); ?>
</div>

<script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
        highlightCurrentLink();
    });

    function highlightCurrentLink() {
        // Remove fbclid from the URL if it exists
        const urlObj = new URL(location.href);
        urlObj.searchParams.delete('fbclid');
        const current_url = urlObj.toString();

        // Highlight the current link in the navigation
        document.querySelectorAll('a').forEach(
            a_element => {

                // normalize %20 and + in URLs
                var url1 = decodeURI(a_element.href).replace(/\+/g, ' ');
                var url2 = decodeURI(current_url).replace(/\+/g, ' ');

                if (url1 === url2) {
                    a_element.classList.add('active-link');
                }
            }
        );
    }
</script>
<style>
    a.active-link {
        font-weight: bolder;
    }
</style>

<hr>