function checkBrowserSupport() {
    try {
        if(!window.hasOwnProperty('crypto') || typeof window.crypto.subtle !== "object") {
            return 'crypto';
        }

        if(!window.hasOwnProperty('TextEncoder')) {
            return 'TextEncoder';
        }

        if(!window.hasOwnProperty('WebAssembly') || typeof window.WebAssembly.instantiate !== "function") {
            return 'WebAssembly';
        }
    } catch(e) {
        console.error(e);

        return 'ECMAScript 2017 / ES2017';
    }

    return true;
}

function showBrowserCompatibilityWarning(reason) {
    var imgpath   = OC.filePath('passwords', 'img', 'browser/'),
        container = document.getElementById('main'),
        title     = OC.L10N.translate('passwords', 'Your browser is outdated'),
        message   = OC.L10N.translate('passwords', 'Your browser is outdated and does not have the necessary functionality to run this app.') + '<br>' +
                    OC.L10N.translate('passwords', 'Please check if an update is available for your browser or choose a modern and compatible browser from the list below.');

    if(reason === 'WebAssembly') {
        var handbookLink = null,
            settings     = OCP.InitialState.loadState('passwords', 'settings');

        if(settings && settings['server.handbook.url']) {
            handbookLink = settings['server.handbook.url.web'] + 'Enable-WebAssembly';
        }

        title = OC.L10N.translate('passwords', 'Your browser does not support WebAssembly');
        message = OC.L10N.translate('passwords', 'Your browser does not support WebAssembly (WASM), which is required to run this app.') +
                  '<br>' + OC.L10N.translate('passwords', 'In some browsers, WebAssembly must be enabled in the browser configuration.') + '<br>' +
                  (handbookLink ? '<a target="_blank" rel="noreferrer noopener" href="' + handbookLink + '">':'') +
                  OC.L10N.translate('passwords', 'A guide to enable WebAssembly can be found in the Passwords App handbook.') +
                  (handbookLink ? '</a>':'') +
                  '<br><br>' +
                  OC.L10N.translate('passwords',
                                    'If your browser does not have WebAssembly support, check if an update is available for your browser or choose a modern and compatible browser from the list below.');
    }

    container.innerHTML =
        '<div class="passwords-browser-compatibility"><h1 class="title">' + title + '</h1><div class="message">' + message + '</div><div class="browser">' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.mozilla.org/firefox/new/" style="background-image: url(' + imgpath + 'firefox.png)">Firefox</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://vivaldi.com/download/" style="background-image: url(' + imgpath + 'vivaldi.png)">Vivaldi</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://brave.com/" style="background-image: url(' + imgpath + 'brave.png)">Brave</a>' +
        '<a target="_blank" rel="noreferrer noopener" href="https://www.torproject.org/download/" style="background-image: url(' + imgpath + 'tor.png)">Tor Browser</a>' +
        '</div></div>';
    container.setAttribute('class', '');

    console.error('Browser does not support ' + reason);
    throw new Error('Browser does not support ' + reason);
}

function checkSystem() {
    var reason = checkBrowserSupport();
    if(reason !== true) showBrowserCompatibilityWarning(reason);
}

window.addEventListener('DOMContentLoaded', checkSystem, false);