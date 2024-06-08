import Application from '@js/Init/Application';

// noinspection JSUnresolvedVariable
/**
 * Set global webpack path
 *
 * @type {string}
 */
__webpack_public_path__ = `${OC.appswebroots.passwords}/`;

(function() {
    if(location.protocol !== 'https:') {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if(checkBrowserSupport() === true) {
        Application.init();
    }
}());