/**
 * Converts raw source text into display-safe HTML by detecting and wrapping
 * HTML tags with styled spans, while safely escaping everything else.
 *
 * Processing order:
 *  1. Escape bare `&` as `&amp;`.
 *  2. Tokenize PHP blocks (`<? ... ?>`) so inner `>` (e.g. `->`) cannot
 *     prematurely close an HTML tag.
 *  3. Replace `<` / `>` with sentinels `{open-tag}` / `{close-tag}`.
 *  4. Wrap matched `{open-tag}word...{close-tag}` with `.tag` / `.tag-inside` spans.
 *  5. Convert unmatched sentinels to `&lt;` / `&gt;`.
 *  6. Restore PHP tokens as escaped source text.
 *
 * @param {string} html - Raw source text (may contain HTML or PHP markup).
 * @returns {string} HTML string with tags wrapped in styled spans.
 */
function tag_parsing(html) {

    // begin with escapings
    html = html.replaceAll('&', "&amp;") // HTML entities escaping
    html = html.replaceAll('-tag' + '}', '-tag' + '&#x7d;') // {open-tag} {close-tag} escaping

    // protect PHP blocks (e.g. <?php ... ?>, <?= ... ?>) so inner ">" does not
    // interfere with HTML tag parsing (for example in $obj->prop)
    var phpTokens = []
    html = html.replaceAll(/<\?[\s\S]*?\?>/g,
        function (match) {
            var token = "__PHP_BLOCK_" + phpTokens.length + "__"
            phpTokens.push(match.replaceAll("<", "&lt;").replaceAll(">", "&gt;"))
            return token
        })

    html = html.replaceAll("<", "{open-tag}")
    html = html.replaceAll(">", "{close-tag}")

    // stricter : only <az> or </az> tags are matched ( also see PHP tags escaping above )
    html = html.replaceAll(/{open-tag}(\/?[a-zA-Z][\s\S]*?){close-tag}/g,
        "<span class='tag'>&lt;</span>"
        + "<span class='tag-inside'>$1</span>"
        + "<span class='tag'>&gt;</span>")

    html = html.replaceAll("{open-tag}", "&lt;") // <
    html = html.replaceAll("{close-tag}", "&gt;") // >

    // restore protected PHP blocks as escaped source text
    for (var i = 0; i < phpTokens.length; i++) {
        html = html.replaceAll("__PHP_BLOCK_" + i + "__", () => phpTokens[i])
    }

    return html
}

/**
 * Applies full syntax highlighting to `html` and injects the result into
 * `text_viewer.innerHTML`.
 *
 * Pipeline:
 *  1. `tag_parsing`   — wraps HTML tags with styled spans.
 *  2. `source_coloring` — colorizes strings, comments, keywords, etc.
 *  3. Post-processes `.tag-inside` spans to highlight the tag name separately.
 *
 * @param {Element} text_viewer - DOM element that receives the highlighted HTML.
 * @param {string}  html        - Raw source text to highlight.
 */
function text_coloring(text_viewer, html) {

    html = tag_parsing(html)
    html = source_coloring(html)

    text_viewer.innerHTML = html

    for (var tagInside of text_viewer.querySelectorAll(".tag-inside")) {
        tagInside.innerHTML = tagInside.innerHTML.replace(/^(\/?\w+)(.*)/, "<span class='tag-name'>$1</span>$2")
    }
}

/**
 * Colorizes source-code constructs in an HTML string that has already been
 * processed by `tag_parsing`.
 *
 * To avoid corrupting the markup emitted by `tag_parsing`, existing HTML tags
 * are tokenized first and restored last. Internal passes run in this order:
 *  1. Protect existing HTML tags (spans from `tag_parsing`).
 *  2. Protect string literals (JS/Lua single, double, and template strings).
 *  3. Protect comments (C-style `//` and `/* … * /`, Lua `--` and `--[[ ]]`).
 *  4. Highlight HTTP/HTTPS links.
 *  5. Highlight function names.
 *  6. Highlight `{` and `}` (including the escaped `&#x7d;` form).
 *  7. Highlight language keywords (JavaScript + Lua).
 *  8. Restore comments, strings, HTML tags in reverse-protection order.
 *
 * @param {string} html - HTML string (output of `tag_parsing`).
 * @returns {string} HTML string with source constructs wrapped in styled spans.
 */
function source_coloring(html) {

    // protect real HTML tags (e.g. spans emitted by tag_parsing)
    // so source-level regex passes do not corrupt markup
    var htmlTagTokens = []
    html = html.replaceAll(/(<\/?[^>]+>)/g,
        function (match) {
            var token = "__SRC_HTML_TAG_" + htmlTagTokens.length + "__"
            htmlTagTokens.push(match)
            return token
        })

    // +SECTION: strings ( generic JS and Lua short strings )
    // protect strings before other passes so comments/keywords are not parsed inside them
    var stringTokens = []
    html = html.replaceAll(/("(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*'|`(?:\\.|[^`\\])*`)/g,
        function (match) {
            var token = "__SRC_STRING_" + stringTokens.length + "__"
            stringTokens.push("<span class='source-coloring source-string'>" + match + "</span>")
            return token
        })

    // +SECTION: comments
    // protect comments before other passes so keywords/functions are not parsed inside comments
    var commentTokens = []
    function wrap_comment(commentText) {
        var token = "__SRC_COMMENT_" + commentTokens.length + "__"
        commentTokens.push("<span class='source-coloring source-comment'>" + commentText + "</span>")
        return token
    }

    // ++SECTION: generic comments ( in C/C++/JavaScript/PHP/CSS/etc. )

    // match single-line comments starting with //
    // dont match // in http and https links, so ignore // that are preceded by :
    html = html.replaceAll(/(^|[^:])(\/\/[^\n]*)/gm,
        function (match, prefix, commentBody) {
            return prefix + wrap_comment(commentBody)
        })

    // match multi-line comments starting with /* and ending with */
    html = html.replaceAll(/(\/\*[\s\S]*?\*\/)/g,
        function (match, commentBody) {
            return wrap_comment(commentBody)
        })

    // ++SECTION: Lua comments

    // match Lua multi-line comments in forms --[[...]], --[=[...]=], --[==[...]==], etc.
    // note: this regex does not handle nested Lua multi-line comments
    // note: do not match ---[[ which is considered a single-line comment
    html = html.replaceAll(/(^|[^:-])(--\[(=*)\[[\s\S]*?\]\3\])/gm,
        function (match, prefix, commentBody) {
            return prefix + wrap_comment(commentBody)
        })

    // match single-line comments starting with --, excluding Lua long-comment openings (--[[, --[=[, ...)
    html = html.replaceAll(/(^|[^:])(\-\-(?!\[=*\[)[^\n]*)/gm,
        function (match, prefix, commentBody) {
            return prefix + wrap_comment(commentBody)
        })

    // +SECTION: http and https links
    // match http and https links and wrap them in a span with class 'source-coloring' to color them as well
    html = html.replaceAll(/(https?:\/\/[^\s]+)/g,
        "<span class='source-coloring'>$1</span>")

    // +SECTION: function
    // match function definitions in the form of "function functionName(" and wrap the function name in a span with class 'source-coloring'
    html = html.replaceAll(/function\s+([a-zA-Z_$][\w$]*)\s*\(/g,
        "function <span class='source-coloring source-underline'>$1</span>(")

    // +SECTION: { and }
    // match { and } characters and wrap them in a span with class 'source-coloring' to color them as well
    html = html.replaceAll(/([{}])/g,
        "<span class='source-coloring'>$1</span>")
    // also color &#x7d; which is the escaped version of } character
    html = html.replaceAll(/(&#x7d;)/g,
        "<span class='source-coloring'>$1</span>")

    // +SECTION: keywords
    // match keywords like
    // "function", "var", "let", "const",
    // "if", "else", "switch", "for", "while",
    // "break", "continue", "return", "yield"
    // and wrap them in a span
    // with class 'source-coloring source-keyword'
    var keywords = [
        // JavaScript keywords
        "function",
        "var", "let", "const",
        "if", "else", "switch",
        "for", "while",
        "break", "continue",
        "return", "yield",
        "null",
        "true", "false",
        // Lua keywords
        "nil",
        "and", "or", "not",
        "repeat", "until", "goto",
        "local","do","end",
        "if","then","else","elseif",
        "while",
        "for","in"
    ]
    html = html.replaceAll(new RegExp("\\b(" + keywords.join("|") + ")\\b", "g"),
        "<span class='source-coloring source-keyword'>$1</span>")

    // restore protected comments in reverse order so nested placeholders are resolved
    for (var i = commentTokens.length - 1; i >= 0; i--) {
        html = html.replaceAll("__SRC_COMMENT_" + i + "__", () => commentTokens[i])
    }

    // restore protected strings after all other coloring passes
    for (var i = 0; i < stringTokens.length; i++) {
        html = html.replaceAll("__SRC_STRING_" + i + "__", () => stringTokens[i])
    }

    // restore protected HTML tags at the very end
    for (var i = 0; i < htmlTagTokens.length; i++) {
        html = html.replaceAll("__SRC_HTML_TAG_" + i + "__", () => htmlTagTokens[i])
    }

    return html
}
