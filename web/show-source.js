(function () {

    var script = document.currentScript

    var url = script.dataset.href || ""

    var html = /*html*/`
<div>

<b><a href="${url}">Text: ${url}</a></b>

<pre class="text_viewer" contenteditable="true"
style=" overflow-y: scroll; border:1px solid black; padding: 1em; "
spellcheck="false" translate="no">
</pre>

<style>
/* Syntax highlighting colors */

.tag,
.tag-inside {
    color: blue;
}

.tag-name,
.tag {
    font-weight: bold;
}

.tag-name,
.tag {
    color: darkblue;
}
</style>

</div>`

    script.insertAdjacentHTML("afterend", html)

    var text_viewer = script.nextElementSibling.querySelector(".text_viewer")

    fetch(url).then(response => response.text()).
        then(response_text => {
            text_viewer.textContent = response_text
            text_coloring(text_viewer)
        })

    function text_parsing(html) {

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

    function text_coloring(text_viewer) {

        var html = text_viewer.textContent

        html = text_parsing(html)

        text_viewer.innerHTML = html

        for (var tagInside of text_viewer.querySelectorAll(".tag-inside")) {
            tagInside.innerHTML = tagInside.innerHTML.replace(/^(\/?\w+)(.*)/, "<span class='tag-name'>$1</span>$2")
        }
    }

})()
