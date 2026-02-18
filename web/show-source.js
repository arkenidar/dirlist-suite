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

</div>`

    script.insertAdjacentHTML("afterend", html)

    var text_viewer = script.nextElementSibling.querySelector(".text_viewer")

    fetch(url).then(response => response.text()).
        then(response_text => {
            text_viewer.textContent = response_text

            console.info(script.src)
            const base = new URL(script.src)

            const url_js = new URL("code-decorations.js", base)
            console.info(url_js.href)

            const url_css = new URL("code-decorations.css", base)
            console.info(url_css.href)

            // code_decorations()

            // inject code-decorations.js code here
            // to color the source code in the text_viewer element
            var scriptElement = document.createElement("script")
            scriptElement.src = url_js.href // "/web/code-decorations.js"
            text_viewer.appendChild(scriptElement)

            // execute the code-decorations.js code to color the source code in the text_viewer element
            // execute the code after the script is loaded
            scriptElement.onload = function () {
                code_decorations()
            }

            // also inject the code-decorations.css file to style the colored source code
            var linkElement = document.createElement("link")
            linkElement.rel = "stylesheet"
            linkElement.href = url_css.href // "/web/code-decorations.css"
            document.head.appendChild(linkElement)

        })

    function code_decorations() {

        text_coloring(text_viewer, text_viewer.textContent)

    }

    // The end of the IIFE

})()
