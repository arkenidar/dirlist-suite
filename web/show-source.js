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

<link rel="stylesheet" href="/web/code-decorations.css">
<script src="/web/code-decorations.js"></script>

</div>`

    script.insertAdjacentHTML("afterend", html)

    var text_viewer = script.nextElementSibling.querySelector(".text_viewer")

    fetch(url).then(response => response.text()).
        then(response_text => {
            text_viewer.textContent = response_text

            // code_decorations()

            // inject code-decorations.js code here
            // to color the source code in the text_viewer element
            var scriptElement = document.createElement("script")
            scriptElement.src = "/web/code-decorations.js"
            text_viewer.appendChild(scriptElement)

            // execute the code-decorations.js code to color the source code in the text_viewer element
            // execute the code after the script is loaded
            scriptElement.onload = function () {
                code_decorations()
            }

        })

    function code_decorations() {

        text_coloring(text_viewer, text_viewer.textContent)

    }

    // The end of the IIFE

})()
