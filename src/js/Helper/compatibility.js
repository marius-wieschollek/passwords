function isCompatibleBrowser() {
    try {
        return window.crypto.subtle &&
               window.TextEncoder &&
               typeof WebAssembly === "object" && typeof WebAssembly.instantiate === "function";
    } catch(e) {
        console.error(e);

        return false;
    }
}

function showBrowserCompatibilityWarning() {
    var imgpath = OC.filePath('passwords', 'img', 'browser/'),
        $main    = $('#main');
    $main.html(
        '<div class="passwords-browser-compatibility">' +
        '<h1 class="title">Your Browser is outdated</h1>' +
        '<div class="message">Your browser is outdated and does not provide the necessary functionality to display this page.' + '<br>' +
        'Please check if an update is available for your browser or choose a modern and compatible browser from the list below.' +
        '</div><div class="browser">' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.mozilla.org/de/firefox/new/" style="background-image: url(' + imgpath + 'firefox.png)">Firefox</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://vivaldi.com/download/" style="background-image: url(' + imgpath + 'vivaldi.png)">Vivaldi</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://brave.com/download" style="background-image: url(' + imgpath + 'brave.png)">Brave</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.opera.com/de/download" style="background-image: url(' + imgpath + 'opera.png)">Opera</a>' +
        '</div></div>'
    );
    $main.removeClass('loading');

    throw new Error('Browser does not suport ECMAScript 2017 / ES2017');
}

function checkSystem() {
    if(!isCompatibleBrowser()) showBrowserCompatibilityWarning();
}

window.addEventListener('load', checkSystem, false);