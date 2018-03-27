<?php

class HandbookScreenshotsCest {
    public $importFileName = __DIR__.'/../_data/passwords.json';

    public function _before(AcceptanceTester $I) {
        $I->loadSessionSnapshot('handbook');
    }

    public function _after(AcceptanceTester $I) {
        $I->saveSessionSnapshot('handbook');
    }

    /**
     * @param AcceptanceTester $I
     *
     *
     */
    public function loginWorks(AcceptanceTester $I) {
        $I->amOnPage('/');
        $I->amOnPage('/index.php/login');
        $I->see('Nextcloud');

        $I->submitForm(['name' => 'login'], [
            'user'     => 'admin',
            'password' => 'admin'
        ], '#submit');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     */
    public function importDatabase(AcceptanceTester $I) {
        file_put_contents(
            $this->importFileName,
            file_get_contents('https://git.mdns.eu/nextcloud/passwords/wikis/_files/Sample%20Passwords.json')
        );

        $I->amOnPage('/index.php/apps/passwords/#/backup/import');
        $I->clickWithLeftButton('li.nav-icon-more');
        $I->waitForElement('div.import-container', 5);
        $I->attachFile('#passwords-import-file', '/passwords.json');
        $I->waitForElement('#passwords-import-execute', 5);
        $I->wait(0.25);
        $I->makeScreenshot('import-section');


        /*$I->clickWithLeftButton('#passwords-import-execute');
        $I->waitForElement('progress.success', 60);
        unlink($this->importFileName);*/
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showAllPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/');
        $I->waitForElement('div.row', 5);
        $I->wait(2.5);
        $I->makeScreenshot('main-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showFolderPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/folders');
        $I->waitForElement('div.title[title=Work]', 5);
        $I->clickWithLeftButton('div.title[title=Work]');
        $I->waitForElement('div.title[title=Development]', 5);
        $I->wait(1);
        $I->makeScreenshot('folder-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showRecentPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/recent');
        $I->waitForElement('div.row', 5);
        $I->wait(1);
        $I->makeScreenshot('recent-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showFavouritesPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/favourites');
        $I->waitForElement('div.row', 5);
        $I->wait(1);
        $I->makeScreenshot('favourites-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showTagsPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/tags');
        $I->waitForElement('div.row', 5);
        $I->wait(1);
        $I->makeScreenshot('tags-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showSecurityPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/security');
        $I->waitForElement('div.row', 5);
        $I->makeScreenshot('security-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showSettingsPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/settings');
        $I->clickWithLeftButton('li.nav-icon-more');
        $I->waitForElement('section.security', 5);
        $I->wait(0.25);
        $I->makeScreenshot('settings-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     */
    public function showHandbookPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/#/help');
        $I->clickWithLeftButton('li.nav-icon-more');
        $I->waitForElement('h1#help-top', 5);
        $I->wait(0.25);
        $I->makeScreenshot('handbook-section');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @throws Exception
     * @throws \Codeception\Exception\TestRuntimeException
     * @throws \Codeception\Exception\ElementNotFound
     */
    public function showTrashPage(AcceptanceTester $I) {
        $I->amOnPage('/index.php/apps/passwords/');
        $I->waitForElement('div.row.password', 10);
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(5)');

        $I->amOnPage('/index.php/apps/passwords/#/folders');
        $I->waitForElement('div.row.folder', 10);
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more');
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(3) > div.more > div > ul > li:nth-child(2)');

        $I->amOnPage('/index.php/apps/passwords/#/tags');
        $I->waitForElement('div.row.tag', 10);
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more');
        $I->clickWithLeftButton('#app-content > div.app-content-left > div.item-list > div:nth-child(4) > div.more > div > ul > li:nth-child(2)');

        $I->amOnPage('/index.php/apps/passwords/#/trash');
        $I->waitForElementNotVisible('#notification .row', 20);
        $I->makeScreenshot('trash-section');

        $I->clickWithLeftButton('#controls > div.breadcrumb > div.actions.creatable > span');
        $I->waitForElementVisible('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)', 20);
        $I->clickWithLeftButton('#controls > div.breadcrumb > div.actions.creatable > div > ul > li:nth-child(4)');
        $I->waitForElement('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary', 10);
        $I->clickWithLeftButton('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary');
    }
}
