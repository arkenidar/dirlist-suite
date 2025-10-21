function memorizeBgFg(bg, fg) {
    localStorage.bg = bg
    localStorage.fg = fg
}
function applyBgFg(bg, fg) {
    document.querySelector('html').style.setProperty('--bg', bg)
    document.querySelector('html').style.setProperty('--fg', fg)
}
function setBgFg(bg, fg) {
    memorizeBgFg(bg, fg)
    applyBgFg(bg, fg)
}
function initBgFg() { setBgFg(localStorage.bg || "white", localStorage.fg || "black") }
initBgFg() // document.addEventListener("DOMContentLoaded", initBgFg)

// <link rel="stylesheet" href="/sitewide.css"><script src="/web/dhtml/color-theme/colors.js"></script>

/*
<button onclick="setBgFg('grey', 'yellow')">style 1</button>
<button onclick="setBgFg('black', 'white')">style 2</button>
*/

setInterval( _ => applyBgFg(localStorage.bg, localStorage.fg) , 3000 )
