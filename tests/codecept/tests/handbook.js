const download = require('download');

Feature('Handbook');

Scenario('Log into Nextcloud', (i) => {
    i.amOnPage('/');
    i.amOnPage('/index.php/login');
    i.see('Nextcloud');

    i.fillField('#user', 'admin');
    i.fillField('#password', 'admin');
    i.click('#submit');
});

Scenario('Reset the Account', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/settings');
    i.waitForElement('#danger-purge', 10);
    i.click('#danger-purge');

    i.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    i.fillField('#pw-field-password', 'admin');
    i.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');

    i.waitForInvisible('.passwords-form', 5);
    i.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    i.wait(15);
    i.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
    i.waitUrlEquals('/index.php/apps/passwords/#/', 20);
});

Scenario('Import the sample database', async (i) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/SamplePasswords.json', 'tests/codecept/data/');

    i.amOnPage('/index.php/apps/passwords/#/backup/import');
    i.refreshPage();
    i.waitForElement('div.import-container', 10);
    i.click('#app-settings li.nav-icon-more');
    i.attachFile('#passwords-import-file', 'tests/codecept/data/SamplePasswords.json');
    i.waitForElement('#passwords-import-execute');
    i.captureWholePage('import-section');

    i.click('#passwords-import-execute');
    i.waitForElement('progress.success', 60);
});

Scenario('Show Password Details', (i) => {
    i.amOnPage('/index.php/apps/passwords/');
    i.waitForElement('div.row', 10);
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(1)');
    i.waitForElement('div.item-details', 10);
    i.waitForInvisible('.image-container .image.loading-hidden', 10);

    i.captureWholePage('password-details', 4);
});

Scenario('Show Create Password Dialog', (i) => {
    i.amOnPage('/index.php/apps/passwords/');

    i.waitForElement('#controls > div.breadcrumb > div.actions.creatable > span', 10);
    i.click('#controls > div.breadcrumb > div.actions.creatable > span');
    i.waitForVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(3)', 20);
    i.click('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(3)');
    i.waitForElement('#passwords-create-new', 10);

    i.captureWholePage('password-create', 4);
});

Scenario('Show Main Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/');
    i.waitForElement('div.row', 10);
    i.captureWholePage('main-section', 3);
});

Scenario('Show Folder Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/folders');
    i.waitForElement('div.title[title=Work]', 10);
    i.click('div.title[title=Work]');
    i.waitForElement('div.title[title=Development]', 10);
    i.captureWholePage('folder-section');
});

Scenario('Show Recent Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/recent');
    i.waitForElement('div.row', 10);
    i.captureWholePage('recent-section');
});

Scenario('Show Favourites Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/favourites');
    i.waitForElement('div.row', 10);
    i.captureWholePage('favourites-section');
});

Scenario('Show Tags Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/tags');
    i.waitForElement('div.row', 10);
    i.captureWholePage('tags-section', 0);
});

Scenario('Show Shared Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/shared');
    i.waitForElement('div.row', 10);
    i.captureWholePage('shared-section', 0);
});

Scenario('Show Security Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/security');
    i.waitForElement('div.row', 10);
    i.captureWholePage('security-section', 0);
});

Scenario('Show Settings Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/settings');
    i.refreshPage();
    i.waitForElement('section.security', 10);
    i.click('#app-settings li.nav-icon-more');
    i.captureWholePage('settings-section', .25);
});

Scenario('Show Export Section', async (i) => {
    i.amOnPage('/index.php/apps/passwords/#/backup/export');
    i.refreshPage();
    i.waitForElement('#passwords-export-execute');
    i.click('#app-settings li.nav-icon-more');
    i.captureWholePage('export-section', .25);

    i.selectOption('#passwords-export-target', 'customCsv');
    i.waitForElement('.csv-mapping');
    i.selectOption('#passwords-mapping-1', 'label');
    i.selectOption('#passwords-mapping-2', 'username');
    i.selectOption('#passwords-mapping-3', 'password');
    i.waitForElement('.csv-mapping div:nth-child(2)');
    await i.captureElement('export-custom-csv', '.step-2', 0, 825);
});

Scenario('Show Import Section', async (i) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/PasswordList.csv', 'tests/codecept/data/');

    i.amOnPage('/index.php/apps/passwords/#/backup/import');
    i.refreshPage();
    i.waitForElement('div.import-container', 10);
    i.click('#app-settings li.nav-icon-more');

    i.selectOption('#passwords-import-source', 'csv');
    i.waitForElement('#passwords-import-csv-delimiter', 10);
    i.attachFile('#passwords-import-file', 'tests/codecept/data/PasswordList.csv');
    i.waitForElement('#passwords-import-csv-skip', 10);
    i.selectOption('#passwords-mapping-0', 'label');
    i.selectOption('#passwords-mapping-1', 'username');
    i.selectOption('#passwords-mapping-2', 'password');
    i.selectOption('#passwords-mapping-3', 'tagLabels');
    i.selectOption('#passwords-mapping-4', 'url');
    i.selectOption('#passwords-mapping-5', 'notes');
    await i.captureElement('import-custom-csv-options', '.step-2', 0, 420);
    await i.captureElement('import-custom-csv-mapping', '.step-3');

});

Scenario('Show Handbook Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/#/help');
    i.refreshPage();
    i.waitForElement('h1#help-top', 10);
    i.click('#app-settings li.nav-icon-more');
    i.captureWholePage('handbook-section', .25);
});

Scenario('Show Trash Section', (i) => {
    i.amOnPage('/index.php/apps/passwords/');
    i.waitForElement('div.row.password', 10);
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(5)');

    i.amOnPage('/index.php/apps/passwords/#/folders');
    i.waitForElement('div.row.folder', 10);
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(2)');

    i.amOnPage('/index.php/apps/passwords/#/tags');
    i.waitForElement('div.row.tag', 10);
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more');
    i.click('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more > div > ul > li:nth-child(2)');

    i.amOnPage('/index.php/apps/passwords/#/trash');
    i.waitForInvisible('#notification .row', 20);
    i.captureWholePage('trash-section', 0);

    i.click('#controls > div.breadcrumb > div.actions.creatable > span');
    i.waitForVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)', 20);
    i.click('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)');
    i.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    i.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
});
