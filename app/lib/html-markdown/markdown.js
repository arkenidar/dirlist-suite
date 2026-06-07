var markedScript = document.createElement("script");

// When the script is loaded, set up the markdown viewers
if (typeof markdownContentLoaded === "function") {
  markedScript.onload = markdownContentLoaded;
} else {
  markedScript.onload = processMarkdownElements;
}

markedScript.src = "https://cdn.jsdelivr.net/npm/marked/marked.min.js";

// Insert the script after the current script tag
document.currentScript.insertAdjacentElement("afterend", markedScript);

async function processMarkdownElements() {
  function viewContent(content, node) {
    node.insertAdjacentElement("afterend", document.createElement("div"));
    node.style.display = "none";
    var newNode = node.nextSibling;
    var markdown = content;
    var html = marked.parse(markdown);
    newNode.innerHTML = html;
    newNode.className = "markdown-to-html";
  }

  for (var node of document.querySelectorAll(".markdown-inline")) {
    node.style.whiteSpace = "pre";
    var markdown = node.innerText;
    node.style.whiteSpace = "normal";
    viewContent(markdown, node);
  }

  async function markdownContentFromURL(url, node) {
    const response = await fetch(url);
    const text = await response.text();
    viewContent(text, node);
  }

  for (var node of document.querySelectorAll(".markdown-url"))
    await markdownContentFromURL(node.dataset.url, node);
}
