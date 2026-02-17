function tag_parsing(html) {

    // begin with escapings
    html = html.replaceAll('&', "&amp;") // HTML entities escaping
    html = html.replaceAll('-tag' + '}', '-tag' + '&#x7d;') // {open-tag} {close-tag} escaping
    html = html.replaceAll("?>", "?&gt;") // for PHP tags escaping

    html = html.replaceAll("<", "{open-tag}")
    html = html.replaceAll(">", "{close-tag}")

    // stricter : only <az> or </az> tags are matched ( also see PHP tags escaping above )
    html = html.replaceAll(/{open-tag}(\/?[a-zA-Z][\s\S]*?){close-tag}/g,
        "<span class='tag'>&lt;</span>"
        + "<span class='tag-inside'>$1</span>"
        + "<span class='tag'>&gt;</span>")

    html = html.replaceAll("{open-tag}", "&lt;") // <
    html = html.replaceAll("{close-tag}", "&gt;") // >

    return html
}

function text_coloring(text_viewer, html) {

    html = tag_parsing(html)
    html = source_coloring(html)

    text_viewer.innerHTML = html

    for (var tagInside of text_viewer.querySelectorAll(".tag-inside")) {
        tagInside.innerHTML = tagInside.innerHTML.replace(/^(\/?\w+)(.*)/, "<span class='tag-name'>$1</span>$2")
    }
}

function source_coloring(html) {

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
    const opening_parenthesis_suffix = " \\("
    function ops(keyword) {
        return keyword + opening_parenthesis_suffix
    }
    var keywords = ["function",
        "var", "let", "const",
        ops("if"), "else", ops("switch"),
        ops("for"), ops("while"),
        "break", "continue",
        "return", "yield"]
    html = html.replaceAll(new RegExp("\\b(" + keywords.join("|") + ")\\b", "g"),
        "<span class='source-coloring source-keyword'>$1</span>")

    // +SECTION: comments
    // match single-line comments starting with // and wrap them in a span with class 'source-coloring'
    // dont match // in http and https links, so ignore // that are preceded by :
    html = html.replaceAll(/(^|[^:])(\/\/[^\n]*)/gm,
        "$1<span class='source-coloring source-comment'>$2</span>")

    // match multi-line comments starting with /* and ending with */ and wrap them in a span with class 'source-coloring'
    html = html.replaceAll(/(\/\*[\s\S]*?\*\/)/g,
        "<span class='source-coloring source-comment'>$1</span>")

    return html
}
