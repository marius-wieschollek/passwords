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

Scenario('Reset the Account', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/settings');
    I.waitForElement('#danger-purge', 10);
    I.click('#danger-purge');

    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.fillField('#pw-field-password', 'admin');
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');

    I.waitForInvisible('.passwords-form', 5);
    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.wait(15);
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
    I.waitUrlEquals('/index.php/apps/passwords/#/', 20);
});

Scenario('Import the sample database', async (I) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/SamplePasswords.json', 'tests/codecept/data/');

    I.amOnPage('/index.php/apps/passwords/#/backup/import');
    I.refreshPage();
    I.waitForElement('div.import-container', 10);
    I.click('#app-settings li.nav-icon-more');
    I.attachFile('#passwords-import-file', 'tests/codecept/data/SamplePasswords.json');
    I.waitForElement('#passwords-import-execute');
    I.captureWholePage('import-section');

    I.click('#passwords-import-execute');
    I.waitForElement('progress.success', 60);
});

Scenario('Show Password Details', (I) => {
    I.amOnPage('/index.php/apps/passwords/');
    I.waitForElement('div.row', 10);
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    I.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(1)');
    I.waitForElement('div.item-details', 10);
    I.waitForInvisible('.image-container .image.loading-hidden', 10);

    I.captureWholePage('password-details', 4);
});

Scenario('Show Create Password Dialog', (I) => {
    I.amOnPage('/index.php/apps/passwords/');

    I.waitForElement('#controls > div.breadcrumb > div.actions.creatable > span', 10);
    I.click('#controls > div.breadcrumb > div.actions.creatable > span');
    I.waitForVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(3)', 20);
    I.click('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(3)');
    I.waitForElement('#passwords-create-new', 10);

    I.captureWholePage('password-create', 4);
});

Scenario('Show Main Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/');
    I.waitForElement('div.row', 10);
    I.captureWholePage('main-section', 3);
});

Scenario('Show Folder Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.waitForElement('div.title[title=Work]', 10);
    I.click('div.title[title=Work]');
    I.waitForElement('div.title[title=Development]', 10);
    I.captureWholePage('folder-section');
});

Scenario('Show Recent Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/recent');
    I.waitForElement('div.row', 10);
    I.captureWholePage('recent-section');
});

Scenario('Show Favourites Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/favourites');
    I.waitForElement('div.row', 10);
    I.captureWholePage('favourites-section');
});

Scenario('Show Tags Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/tags');
    I.waitForElement('div.row', 10);
    I.captureWholePage('tags-section', 0);
});

Scenario('Show Shared Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/shared');
    I.waitForElement('div.row', 10);
    I.captureWholePage('shared-section', 0);
});

Scenario('Show Security Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/security');
    I.waitForElement('div.row', 10);
    I.captureWholePage('security-section', 0);
});

Scenario('Show Settings Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/settings');
    I.refreshPage();
    I.waitForElement('section.security', 10);
    I.click('#app-settings li.nav-icon-more');
    I.captureWholePage('settings-section', .25);
});

Scenario('Show Export Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/backup/export');
    I.refreshPage();
    I.waitForElement('#passwords-export-execute');
    I.click('#app-settings li.nav-icon-more');
    I.captureWholePage('export-section', .25);

    I.selectOption('#passwords-export-target', 'customCsv');
    I.waitForElement('.csv-mapping');
    I.selectOption('#passwords-mapping-1', 'label');
    I.selectOption('#passwords-mapping-2', 'username');
    I.selectOption('#passwords-mapping-3', 'password');
    I.waitForElement('.csv-mapping div:nth-child(2)');
    await I.captureElement('export-custom-csv', '.step-2', 0, 825);
});

Scenario('Show Import Section', async (I) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/PasswordList.csv', 'tests/codecept/data/');

    I.amOnPage('/index.php/apps/passwords/#/backup/import');
    I.refreshPage();
    I.waitForElement('div.import-container', 10);
    I.click('#app-settings li.nav-icon-more');

    I.selectOption('#passwords-import-source', 'csv');
    I.waitForElement('#passwords-import-csv-delimiter', 10);
    I.attachFile('#passwords-import-file', 'tests/codecept/data/PasswordList.csv');
    I.waitForElement('#passwords-import-csv-skip', 10);
    I.selectOption('#passwords-mapping-0', 'label');
    I.selectOption('#passwords-mapping-1', 'username');
    I.selectOption('#passwords-mapping-2', 'password');
    I.selectOption('#passwords-mapping-3', 'tagLabels');
    I.selectOption('#passwords-mapping-4', 'url');
    I.selectOption('#passwords-mapping-5', 'notes');
    await I.captureElement('import-custom-csv-options', '.step-2', 0, 420);
    await I.captureElement('import-custom-csv-mapping', '.step-3');

});

Scenario('Show Handbook Section', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/help');
    I.refreshPage();
    I.waitForElement('h1#help-top', 10);
    I.click('#app-settings li.nav-icon-more');
    I.captureWholePage('handbook-section', .25);
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
    I.captureWholePage('trash-section', 0);

    I.click('#controls > div.breadcrumb > div.actions.creatable > span');
    I.waitForVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)', 20);
    I.click('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)');
    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
});
