const download = require('download');

Feature('Handbook');

Scenario('Log into Nextcloud', (I) => {
    I.amOnPage('/');
    I.amOnPage('/index.php/login');
    I.see('Nextcloud');

    I.fillField('#user', 'admin');
    I.fillField('#password', 'admin');
    I.click('#submit');
});

Scenario('Import the sample database', async (I) => {
    let data = await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/Sample%20Passwords.json', 'tests/codecept/data/');

    I.amOnPage('/index.php/apps/passwords/#/backup/import');
    I.click('li.nav-icon-more');
    I.waitForElement('div.import-container', 10);
    I.attachFile('#passwords-import-file', 'tests/codecept/data/Sample Passwords.json');
    I.waitForElement('#passwords-import-execute');
    ImakeScreenShot(I, 'import-section');


    I.click('#passwords-import-execute');
    I.waitForElement('progress.success', 60);
});

Scenario('Show All Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'main-section', 3.5);
});

Scenario('Show Folder Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.waitForElement('div.title[title=Work]', 10);
    I.click('div.title[title=Work]');
    I.waitForElement('div.title[title=Development]', 10);
    ImakeScreenShot(I, 'folder-section');
});

Scenario('Show Recent Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/recent');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'recent-section');
});

Scenario('Show Favourites Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/favourites');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'favourites-section');
});

Scenario('Show Tags Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/tags');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'tags-section', 0);
});

Scenario('Show Shared Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/shared');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'shared-section', 0);
});

Scenario('Show Security Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/security');
    I.waitForElement('div.row', 10);
    ImakeScreenShot(I, 'security-section', 0);
});

Scenario('Show Settings Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/settings');
    I.click('li.nav-icon-more');
    I.waitForElement('section.security', 10);
    ImakeScreenShot(I, 'settings-section', .25);
});

Scenario('Show Handbook Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/help');
    I.click('li.nav-icon-more');
    I.waitForElement('h1#help-top', 10);
    ImakeScreenShot(I, 'handbook-section', .25);
});

Scenario('Show Trash Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/');
    I.waitForElement('div.row.password', 10);
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(5)');

    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.waitForElement('div.row.folder', 10);
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(2)');

    I.amOnPage('/index.php/apps/passwords/#/tags');
    I.waitForElement('div.row.tag', 10);
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more');
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more > div > ul > li:nth-child(2)');

    I.amOnPage('/index.php/apps/passwords/#/trash');
    I.waitForInvisible('#notification .row', 20);
    ImakeScreenShot(I, 'trash-section', 0);

    I.click('#controls > div.breadcrumb > div.actions.creatable > span');
    I.waitForVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)', 20);
    I.click('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)');
    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
});

function ImakeScreenShot(I, name, wait = 1) {
    I.moveCursorTo('#nextcloud');
    I.wait(wait);
    I.saveScreenshot(`${name}.png`);
}
