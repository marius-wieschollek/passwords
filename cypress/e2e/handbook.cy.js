describe('Handbook', () => {
    it('Set the language to english', () => {
        cy.login();
        cy.visit('https://localhost/settings/user', {retryOnNetworkFailure: true});
        cy.get('#account-setting-language').select('en');
        cy.get('#account-setting-locale').select('en_US');
    });

    it('Set the theme to white', () => {
        cy.login();
        cy.visit('https://localhost/settings/user/theming', {retryOnNetworkFailure: true});
        cy.get('.theming__preview--light .checkbox-radio-switch__icon').click();
    });

    it('Reset the account', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/settings', {retryOnNetworkFailure: true});
        cy.get('#danger-purge').click();
        cy.get('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary').click();
        cy.get('#body-user > div.oc-dialog > div.oc-dialog-content > p');
        cy.document().then((document) => {
            document.querySelector('#body-user > div.oc-dialog > div.oc-dialog-content > input').value =
                document.querySelector('#body-user > div.oc-dialog > div.oc-dialog-content > p').innerText.match(/"([^"]+)"/)[1];
        });
        cy.get('#body-user > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.primary').click();
        cy.url({timeout: 10000}).should('contain', '/apps/passwords/#/folders');
        /** Wait for reset to finish **/
        cy.wait(1000);
    });

    it('Import the sample database', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/backup/import', {retryOnNetworkFailure: true});
        cy.get('#passwords-import-source').select('json');
        cy.get('#passwords-import-file').selectFile('./cypress/fixtures/SamplePasswords.json');
        cy.get('#passwords-import-execute', {timeout: 1000});
        cy.screenshotWithPreview('import-section');
        cy.raiseRequestLimitRequestsToFinish();
        cy.get('#passwords-import-execute').click();
        cy.get('progress.success', {timeout: 60000});
    });

    it('Capture New Password Dialog', () => {
        cy.viewport(1280, 900);
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/all', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-password-create button').click();
        cy.get('#password-username').type('myuser');
        cy.get('#password-password').type('LongAndStrongPassword');
        cy.get('#password-label').type('Example Password');
        cy.get('#password-url').type('https://www.example.com');
        cy.get('#passwords-edit-dialog .modal-container').screenshotWithPreview('password-create', {padding: 10});
    });

    it('Capture Main Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/all', {retryOnNetworkFailure: true});
        cy.get('div[data-password-title="Nextcloud"]')
          .scrollIntoView({offset: {top: -60}});
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.get('div[data-password-title="Nextcloud"]')
          .screenshotWithPreview('password-single');
        cy.screenshotWithPreview('main-section');
    });

    it('Capture Folder Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('div[data-folder-title="Work"]').screenshotWithPreview('folder-single');
        cy.get('div[data-folder-title="Work"]').click();
        cy.get('div[data-folder-title="Development"]');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('folder-section');
    });

    it('Capture New Folder Dialog', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-folder-create button').click();
        cy.get('div.oc-dialog input').type('Example Folder');
        cy.get('div.oc-dialog').screenshotWithPreview('folder-create', {padding: 10});
    });

    it('Capture Recent Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/recent', {retryOnNetworkFailure: true});
        cy.get('div.row', {timeout: 10000});
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('recent-section');
    });

    it('Capture Favourites Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/favorites', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.openSections('Favorites');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('favorites-section');
    });

    it('Capture Shared Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/shared', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.screenshotWithPreview('shared-section');
    });

    it('Capture Security Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/security', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.screenshotWithPreview('security-section');
    });

    it('Capture Handbook Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/help', {retryOnNetworkFailure: true});
        cy.get('h1#help-top');
        cy.screenshotWithPreview('handbook-section');
    });

    it('Capture Tags Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('div[data-tag-title=Communication]').screenshotWithPreview('tag-single');

        cy.closeSections('Favorites');
        cy.screenshotWithPreview('tag-section');
    });

    it('Capture New Tag Dialog', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-tag-create button').click();
        cy.get('#password-field-label').type('Example Tag');
        cy.get('div.oc-dialog').screenshotWithPreview('tag-create', {padding: 10});
    });

    it('Capture Search Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/search/c2hvcA==', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.get('.passwords-search-box input').type('shop');
        cy.get('[data-folder-title="Shopping"]');
        cy.get('[data-tag-title="Shopping"]');
        cy.get('[data-password-title="Steam"]');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('search-section');
    });

    it('Capture Settings Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/settings', {retryOnNetworkFailure: true});
        cy.get('section.security h1').scrollIntoView({offset: {top: -60}});
        cy.screenshotWithPreview('settings-section');
        /** Wait for screenshot to finish **/
        cy.wait(500);
        cy.get('.settings-level .checkbox-content-checkbox').click();
        cy.get('section.security h1').scrollIntoView({offset: {top: -60}});
        cy.screenshotWithPreview('settings-section-advanced');
    });

    it('Capture Export Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/backup/export', {retryOnNetworkFailure: true});
        cy.get('#passwords-export-execute');
        cy.screenshotWithPreview('export-section');
        cy.get('#passwords-export-target').select('customCsv');
        cy.get('.csv-mapping');
        cy.get('#passwords-mapping-1').select('label');
        cy.get('#passwords-mapping-2').select('username');
        cy.get('#passwords-mapping-3').select('password');
        cy.get('.csv-mapping div:nth-child(2)');
        cy.get('.step-2').screenshotWithPreview('export-custom-csv');
    });

    it('Capture Import Custom CSV', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/backup/import', {retryOnNetworkFailure: true});
        cy.get('#passwords-import-source').select('csv');
        cy.get('#passwords-import-file').selectFile('./cypress/fixtures/PasswordList.csv');
        cy.get('#passwords-mapping-0').select('label');
        cy.get('#passwords-mapping-1').select('username');
        cy.get('#passwords-mapping-2').select('password');
        cy.get('#passwords-mapping-3').select('tagLabels');
        cy.get('#passwords-mapping-4').select('url');
        cy.get('#passwords-mapping-5').select('notes');
        cy.get('.step-1').scrollIntoView();
        cy.get('.step-2').screenshotWithPreview('import-custom-csv-options');
        cy.get('.step-4').scrollIntoView();
        cy.get('.step-3').screenshotWithPreview('import-custom-csv-mapping');
    });

    it('Capture Trash Section', () => {
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('div[data-tag-title="Communication"] > div.more').click();
        cy.get('div[data-tag-title="Communication"] > div.more [data-item-action="delete"]').click();
        cy.get('div[data-tag-title="IT"]').click();
        cy.get('div[data-password-title="Nextcloud"] > div.more').click();
        cy.get('div[data-password-title="Nextcloud"] > div.more [data-item-action="delete"]').click();
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('div[data-folder-title="Work"]').click();
        cy.get('div[data-folder-title="Hosting"] > div.more').click();
        cy.get('div[data-folder-title="Hosting"] > div.more [data-item-action="delete"]').click();
        cy.contains('Folder deleted', {timeout: 30000});
        cy.visit('https://localhost/apps/passwords/#/trash', {retryOnNetworkFailure: true});
        cy.get('#app-content.section-trash');
        cy.get('div[data-folder-title="Hosting"]', {timeout: 10000});
        cy.get('div[data-password-title="Nextcloud"]');
        cy.get('div[data-tag-title="Communication"]');
        cy.closeSections('Favorites', 'Tags');
        cy.screenshotWithPreview('trash-section');
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-trash-restore button').click();
        cy.get('.oc-dialog button.primary').click();
        /** Wait for trash restore requests to finish **/
        cy.wait(1000);
    });

    it('Capture Password Sidebar', () => {
        cy.viewport(1280, 1500);
        cy.login();
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('div[data-folder-title="Work"]').click();
        cy.get('div[data-password-title="Nextcloud"] > div.more').click();
        cy.get('div[data-password-title="Nextcloud"] > div.more [data-item-action="details"]').click();
        cy.get('.preview-container .image-loaded', {timeout: 60000});
        cy.screenshotWithPreview('password-details');
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-details');
        cy.get('.password-details-options .checkbox-content-checkbox').click();
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-details-extended');
        cy.get('#tab-button-notes-tab').click();
        cy.get('#tab-notes-tab .notes');
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-notes');
        cy.get('#tab-button-revisions-tab').click();
        cy.get('#tab-revisions-tab .passwords-revision-list');
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-revisions');
        cy.get('#tab-button-share-tab').click();
        cy.get('#tab-share-tab .share-add-user').type('max');
        cy.get('#tab-share-tab .user-search li').contains('Max Mustermann').click();
        cy.get('#tab-share-tab .share-add-user').clear().type('erika');
        cy.get('#tab-share-tab .user-search li').contains('Erika Mustermann').click();
        cy.get('#tab-share-tab .shares li').contains('Erika Mustermann');
        /** Wait for sharing cronjob to finish **/
        cy.wait(2000);
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-sharing');
        cy.get('#app-sidebar-vue .app-sidebar-header__menu .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-password-qrcode').click();
        cy.get('#app-popup .modal-container .disable-warning').click();
        cy.get('#app-popup .modal-container').screenshotWithPreview('password-qrcode', {padding: 10});
    });
});