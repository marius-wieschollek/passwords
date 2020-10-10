function isCompatibleBrowser() {
    try {
        if(!window.hasOwnProperty('crypto') || typeof window.crypto.subtle !== "object") {
            console.error('Web Crypto API not supported');
            return false;
        }

        if(!window.hasOwnProperty('TextEncoder')) {
            console.error('TextEncoder not supported');
            return false;
        }

        if(!window.hasOwnProperty('WebAssembly') || typeof window.WebAssembly.instantiate !== "function") {
            console.error('WebAssembly not supported');
            return false;
        }
    } catch(e) {
        console.error(e);

        return false;
    }

    return true;
}

function showBrowserCompatibilityWarning() {
    var imgpath   = OC.filePath('passwords', 'img', 'browser/'),
        container = document.getElementById('main');
    container.innerHTML =
        '<div class="passwords-browser-compatibility">' +
        '<h1 class="title">Your Browser is outdated</h1>' +
        '<div class="message">Your browser is outdated and does not provide the necessary functionality to display this page.' +
        '<br>Please check if an update is available for your browser or choose a modern and compatible browser from the list below.' +
        '</div><div class="browser">' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.mozilla.org/firefox/new/" style="background-image: url(' + imgpath + 'firefox.png)">Firefox</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://vivaldi.com/download/" style="background-image: url(' + imgpath + 'vivaldi.png)">Vivaldi</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://brave.com/download" style="background-image: url(' + imgpath + 'brave.png)">Brave</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.torproject.org/download/" style="background-image: url(' + imgpath + 'tor.png)">Tor Browser</a>' +
        '</div></div>';
    container.setAttribute('class', '');

    throw new Error('Browser does not suport ECMAScript 2017 / ES2017');
}

function checkSystem() {
    if(!isCompatibleBrowser()) showBrowserCompatibilityWarning();
}

window.addEventListener('DOMContentLoaded', checkSystem, false);