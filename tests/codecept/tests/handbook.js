const download = require('download');

Feature('Handbook');

Before((I) => {
    I.amOnPage('/index.php/apps/passwords');
});

Scenario('Log into Nextcloud', (I) => {
    I.amOnPage('/');
    I.amOnPage('/index.php/login');
    I.see('Nextcloud');

    I.fillField('#user', 'admin');
    I.fillField('#password', 'admin');
    I.click('#submit-form');
});

Scenario('Reset the Account', (I) => {
    I.amOnPage('/index.php/apps/passwords/#/settings');
    I.waitForElement('#danger-purge', 10);
    I.click('#danger-purge');

    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.fillField('#password-field-password', 'admin');
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');

    I.waitForInvisible('.passwords-form', 5);
    I.waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
    I.wait(11);
    I.click('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
    I.waitUrlEquals('/index.php/apps/passwords/#/folders', 30);
});

Scenario('Import the sample database', async (I) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/SamplePasswords.json', 'tests/codecept/data/');

    I.amOnPage('/index.php/apps/passwords/#/backup/import');
    I.waitForElement('div.import-container', 10);
    I.attachFile('#passwords-import-file', 'tests/codecept/data/SamplePasswords.json');
    I.waitForElement('#passwords-import-execute');
    await I.openMoreMenu();
    await I.captureWholePage('import-section');

    I.click('#passwords-import-execute');
    I.waitForElement('progress.success', 80);
});

Scenario('Show Create Password Dialog', async (I) => {
    I.amOnPage('/index.php/apps/passwords/');

    I.waitForElement('#controls > div.breadcrumb > div.passwords-more-menu > span', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > span');
    I.waitForVisible('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(3)', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(3)');
    I.waitForElement('#passwords-create-new', 10);
    I.fillField('#password-username', 'myuser');
    I.wait(0.5);
    I.fillField('#password-password', 'LongAndStrongPassword');
    I.fillField('#password-label', 'Example Password');
    I.fillField('#password-url', 'https://www.example.com');
    I.click('#passwords-create-new');
    I.click('div.foldout-container:nth-child(2) > div:nth-child(1)');

    await I.captureWholePage('password-create', 4);
});

Scenario('Show Main Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/all');
    I.waitForElement('div.row', 10);
    await I.captureWholePage('main-section', 20);
    await I.captureElement('password-single', 'div[data-password-title=Amazon]', 0);
});

Scenario('Show Folder Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.wait(10);
    I.waitForElement('div[data-folder-title=Work]', 10);
    await I.captureElement('folder-single', 'div[data-folder-title=Work]', 0);
    I.click('div[data-folder-title=Work]');
    I.waitForElement('div[data-folder-title=Development]', 10);
    await I.captureWholePage('folder-section', 3);
});

Scenario('Show Create Folder Dialog', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#folders');

    I.waitForElement('div[data-folder-title=Work]', 10);
    I.waitForElement('#controls > div.breadcrumb > div.passwords-more-menu > span', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > span');
    I.waitForVisible('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(1)', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(1)');
    I.waitForElement('div.oc-dialog', 10);
    I.fillField('div.oc-dialog input', 'Example Folder');
    await I.captureElement('folder-create', 'div.oc-dialog', 0);
});

Scenario('Show Recent Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/recent');
    I.waitForElement('div.row', 10);
    await I.captureWholePage('recent-section');
});

Scenario('Show Favourites Section', async(I) => {
    I.amOnPage('/index.php/apps/passwords/#/favorites');
    I.waitForElement('div.row', 10);
    await I.captureWholePage('favorites-section');
});

Scenario('Show Tags Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/tags');
    I.waitForElement('div[data-tag-title=Communication]', 10);
    await I.captureWholePage('tag-section', 0);
    await I.captureElement('tag-single', 'div[data-tag-title=Communication]', 0);
});

Scenario('Show Create Tag Dialog', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#tags');

    I.waitForElement('div[data-tag-title=Communication]', 10);
    I.waitForElement('#controls > div.breadcrumb > div.passwords-more-menu > span', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > span');
    I.waitForVisible('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(2)', 10);
    I.click('#controls > div.breadcrumb > div.passwords-more-menu > div > ul > li:nth-child(2)');
    I.waitForElement('div.oc-dialog', 10);
    I.fillField('#password-field-label', 'Example Tag');
    await I.captureElement('tag-create', 'div.oc-dialog', 0);
});

Scenario('Show Shared Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/shared');
    I.waitForElement('div.row', 10);
    await I.captureWholePage('shared-section', 0);
});

Scenario('Show Security Section', async(I) => {
    I.amOnPage('/index.php/apps/passwords/#/security');
    I.waitForElement('div.row', 10);
    await I.captureWholePage('security-section', 0);
});

Scenario('Show Search Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/search/c2hvcA==');
    I.waitForInvisible('#app-content.loading', 10);
    I.executeScript(()=> {document.getElementById('searchbox').value=''});
    I.fillField('#searchbox', 'shop');
    await I.captureWholePage('search-section', 10);
});

Scenario('Show Settings Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/settings');
    I.waitForElement('section.security', 10);
    await I.openMoreMenu();
    await I.captureWholePage('settings-section', .25);
    I.selectOption('#setting-settings-advanced', '1');
    await I.captureWholePage('settings-section-advanced', .1);
});

Scenario('Show Export Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/backup/export');
    I.waitForElement('#passwords-export-execute');
    await I.openMoreMenu();
    await I.captureWholePage('export-section', .25);

    I.selectOption('#passwords-export-target', 'customCsv');
    I.waitForElement('.csv-mapping');
    I.selectOption('#passwords-mapping-1', 'label');
    I.selectOption('#passwords-mapping-2', 'username');
    I.selectOption('#passwords-mapping-3', 'password');
    I.waitForElement('.csv-mapping div:nth-child(2)');
    I.click('.step-2');

    await I.captureElement('export-custom-csv', '.step-2', 0, 825);
});

Scenario('Show Import Custom CSV', async (I) => {
    await download('https://git.mdns.eu/nextcloud/passwords/wikis/Developers/_files/PasswordList.csv', 'tests/codecept/data/');

    I.amOnPage('/index.php/apps/passwords/#/backup/import');
    I.waitForElement('div.import-container', 10);
    await I.openMoreMenu();

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
    I.click('.step-2');

    await I.captureElement('import-custom-csv-options', '.step-2', 0, 420);
    await I.captureElement('import-custom-csv-mapping', '.step-3');
});

Scenario('Show Handbook Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/help');
    I.waitForElement('h1#help-top', 10);
    await I.openMoreMenu();
    await I.captureWholePage('handbook-section', .25);
});

Scenario('Show Trash Section', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/tags');
    I.waitForElement('div[data-tag-title="Communication"]', 20);
    I.click('div[data-tag-title="Communication"] > div.more');
    I.click('div[data-tag-title="Communication"] > div.more > div > ul > li:nth-child(2)');
    I.click('div[data-tag-title="Shopping"]');
    I.waitForElement('div[data-password-title="Amazon"]', 20);
    I.click('div[data-password-title="Amazon"] > div.more');
    I.click('div[data-password-title="Amazon"] > div.more > div > ul > li:nth-child(6)');

    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.waitForElement('div[data-folder-title="Work"]', 20);
    I.click('div[data-folder-title="Work"] > div.more');
    I.click('div[data-folder-title="Work"] > div.more > div > ul > li:nth-child(2)');

    I.amOnPage('/index.php/apps/passwords/#/trash');
    I.waitForElement('div[data-password-title="Amazon"]', 20);
    await I.captureWholePage('trash-section', 1);

    I.click('.passwords-more-menu .button');
    I.waitForVisible('.menu-center > ul:nth-child(1) > li:nth-child(4) > span:nth-child(1)', 20);
    I.click('.menu-center > ul:nth-child(1) > li:nth-child(4) > span:nth-child(1)');
    I.waitForElement('.oc-dialog button.primary', 10);
    I.click('.oc-dialog button.primary');
});

Scenario('Show Password Details', async (I) => {
    I.amOnPage('/index.php/apps/passwords/#/folders');
    I.waitForElement('div[data-folder-title="Private"]', 10);
    I.click('div[data-folder-title="Private"]');
    I.waitForElement('div[data-folder-title="Shopping"]', 10);
    I.click('div[data-folder-title="Shopping"]');
    I.waitForElement('div[data-password-title="Amazon"]', 20);
    I.click('div[data-password-title="Amazon"] > div.more');
    I.click('div[data-password-title="Amazon"] > div.more > div > ul > li:nth-child(1)');
    I.moveCursorTo('#nextcloud', 1, 0);
    I.waitForElement('div.item-details', 10);
    I.waitForInvisible('.image-container .image.loading-hidden', 10);

    await I.captureWholePage('password-details', 4);
    I.setWindowSize(1280, 1500);
    await I.captureElement('password-details-details', '.item-details');
    I.click('.item-details [data-tab=notes]');
    await I.captureElement('password-details-notes', '.item-details');
    I.click('.item-details [data-tab=share]');
    /**
     * This photo is currently useless
     await I.captureElement('password-details-sharing', '.item-details');
     */
    I.click('.item-details [data-tab=qrcode]');
    I.selectOption('#password-details-qrcode', 'url');
    await I.captureElement('password-details-qrcode', '.item-details');
    I.click('.item-details [data-tab=revisions]');
    await I.captureElement('password-details-revisions', '.item-details');
});
