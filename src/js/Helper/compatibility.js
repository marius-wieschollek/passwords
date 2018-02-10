$(window).on('load', checkSystem);

function isCompatibleBrowser() {
    try {
        eval('"use strict"; class PasswordsTestBrowserClassSupport {}');
        eval('"use strict"; var PasswordsTestAsyncBrowserFunctionSupport = async function(){}');
        return window.hasOwnProperty('crypto');
    } catch(e) {
        console.log(e);

        return false;
    }
}

function showBrowserCompatibilityWarning() {
    var imgpath = $('[data-constant=imagePath]').attr('data-value') + 'browser/';
    $('#main').html(
        '<div class="passwords-browser-compatibility">' +
        '<h1 class="title">Your Browser is outdated</h1>' +
        '<div class="message">Your browser is outdated and does not provide the necessary functionality to display this page.' + '<br>' +
        'Please check if an update is available for your browser or choose a modern and compatible browser from the list below.' +
        '</div><div class="browser">' +
        '<a target="_blank" href="https://www.mozilla.org/de/firefox/new/" style="background-image: url(' + imgpath + 'firefox.png)">Firefox</a>' +
        '<a target="_blank" href="https://vivaldi.com/download/" style="background-image: url(' + imgpath + 'vivaldi.png)">Vivaldi</a>' +
        '<a target="_blank" href="https://brave.com/download" style="background-image: url(' + imgpath + 'brave.png)">Brave</a>' +
        '<a target="_blank" href="https://www.opera.com/de/download" style="background-image: url(' + imgpath + 'opera.png)">Opera</a>' +
        '</div></div>'
    );

    throw "Browser does not suport ECMAScript 2017 / ES2017";
}

function checkSystem() {
    var link = document.getElementById('pw-link-https');
    if(link) {
        link.setAttribute('href', 'https://' + location.host + location.pathname);
    } else if(!isCompatibleBrowser()) {
        showBrowserCompatibilityWarning();
    }
}