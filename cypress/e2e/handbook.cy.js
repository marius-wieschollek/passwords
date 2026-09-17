describe('Handbook', () => {
    before(() => {
        cy.occ('user:setting', 'admin', 'core', 'lang', 'en');
        cy.occ('user:setting', 'admin', 'core', 'locale', 'en_US');
        cy.occ('user:setting', 'admin', 'theming', 'enabled-themes', '["light"]');
        cy.occ('passwords:backup:restore', 'SampleData', '--no-interaction');
    });

    beforeEach(() => {
        cy.login('admin', 'admin');
    });

    it('Import the sample database', () => {
        cy.visit('https://localhost/apps/passwords/#/backup/import', {retryOnNetworkFailure: true});
        cy.get('#passwords-import-source').select('json');
        cy.get('#passwords-import-file').selectFile('./cypress/fixtures/SamplePasswords.json');
        cy.get('#passwords-import-execute', {timeout: 1000});
        cy.screenshotWithPreview('import-section');
    });

    it('Capture New Password Dialog', () => {
        cy.viewport(1280, 900);
        cy.visit('https://localhost/apps/passwords/#/all', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-password-create button').click();
        cy.get('#password-username').type('myuser');
        cy.get('#password-password').type('LongAndStrongPassword');
        cy.get('#password-label').type('Example Password');
        cy.get('#password-url').type('https://www.example.com');
        cy.get('#password-folder').should('have.value', 'Home');
        cy.get('#passwords-edit-dialog .modal-container').screenshotWithPreview('password-create', {padding: 10});
    });

    it('Capture Main Section', () => {
        cy.visit('https://localhost/apps/passwords/#/all', {retryOnNetworkFailure: true});
        cy.get('div[data-pw-label="Nextcloud"]')
          .scrollIntoView({offset: {top: -60}});
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.get('div[data-pw-label="Nextcloud"]')
          .screenshotWithPreview('password-single');
        cy.screenshotWithPreview('main-section');
    });

    it('Capture Folder Section', () => {
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.wait(500);
        cy.get('div[data-pw-label="Work"]').screenshotWithPreview('folder-single');
        cy.get('div[data-pw-label="Work"]').click();
        cy.get('div[data-pw-label="Development"]');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('folder-section');
    });

    it('Capture New Folder Dialog', () => {
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-folder-create button').click();
        cy.modalType('input', 'Example Folder');
        cy.get('div.modal-container').screenshotWithPreview('folder-create', {padding: 10});
    });

    it('Capture Recent Section', () => {
        cy.visit('https://localhost/apps/passwords/#/recent', {retryOnNetworkFailure: true});
        cy.get('div.row', {timeout: 10000});
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('recent-section');
    });

    it('Capture Favourites Section', () => {
        cy.visit('https://localhost/apps/passwords/#/favorites', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.openSections('Favorites');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('favorites-section');
    });

    it('Capture Shared Section', () => {
        cy.visit('https://localhost/apps/passwords/#/shared', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.screenshotWithPreview('shared-section');
    });

    it('Capture Security Section', () => {
        cy.visit('https://localhost/apps/passwords/#/security', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.screenshotWithPreview('security-section');
    });

    it('Capture Handbook Section', () => {
        cy.visit('https://localhost/apps/passwords/#/help', {retryOnNetworkFailure: true});
        cy.get('h1#help-top');
        cy.screenshotWithPreview('handbook-section');
    });

    it('Capture Tags Section', () => {
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('div[data-pw-label=Communication]').screenshotWithPreview('tag-single');

        cy.closeSections('Favorites');
        cy.screenshotWithPreview('tag-section');
    });

    it('Capture New Tag Dialog', () => {
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('.passwords-breadcrumbs .breadcrumb__actions .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-tag-create button').click();
        cy.get('#password-field-label').type('Example Tag');
        cy.get('div.modal-container').screenshotWithPreview('tag-create', {padding: 10});
    });

    it('Capture Search Section', () => {
        cy.visit('https://localhost/apps/passwords/#/search/c2hvcA==', {retryOnNetworkFailure: true});
        cy.get('div.row');
        cy.get('.passwords-search-box input').type('shop');
        cy.get('[data-pw-item="folder"]');
        cy.get('[data-pw-item="tag"]');
        cy.get('[data-pw-item="password"]');
        /** Wait for Favicons to load **/
        cy.waitForRequestsToFinish();
        cy.screenshotWithPreview('search-section');
    });

    it('Capture Settings Section', () => {
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
        cy.visit('https://localhost/apps/passwords/#/tags', {retryOnNetworkFailure: true});
        cy.get('[data-pw-role="content"] [data-pw-item="tag"]', {timeout: 10000});
        cy.itemAction({type: 'tag', label: 'Communication'}, 'delete');
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('[data-pw-role="content"] [data-pw-item="folder"]', {timeout: 10000});
        cy.get('[data-pw-role="content"] [data-pw-label="Work"]').click();
        cy.itemAction({type: 'folder', label: 'Hosting'}, 'delete');
        cy.itemAction({type: 'password', label: 'Nextcloud'}, 'delete');
        cy.contains('Folder deleted', {timeout: 10000});
        cy.visit('https://localhost/apps/passwords/#/trash', {retryOnNetworkFailure: true});
        cy.get('#app-content.section-trash');
        cy.get('[data-pw-role="content"] [data-pw-label="Hosting"]', {timeout: 10000});
        cy.get('[data-pw-role="content"] [data-pw-label="Nextcloud"]');
        cy.get('[data-pw-role="content"] [data-pw-label="Communication"]');
        cy.closeSections('Favorites', 'Tags');
        cy.screenshotWithPreview('trash-section');
        cy.batchAction('restore', true);
        cy.dialogConfirm();
        /** Wait for trash restore requests to finish **/
        cy.wait(1000);
    });

    it('Capture Password Sidebar', () => {
        cy.viewport(1280, 1500);
        cy.visit('https://localhost/apps/passwords/#/folders', {retryOnNetworkFailure: true});
        cy.get('div[data-pw-label="Work"]').click();
        cy.itemAction({type: 'password', label: 'Nextcloud'}, 'details');
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
        cy.get('#tab-share-tab .share-edit-form input.vs__search').type('max');
        cy.get('#max.option').click();
        cy.get('#tab-share-tab .share-edit-form input.vs__search').type('erika');
        cy.get('#erika.option').click();
        cy.get('#tab-share-tab .share-edit-actions .button-vue--vue-primary').click();
        cy.get('#tab-share-tab .share-list .list-item-content__name').contains('Erika Mustermann');
        /** Wait for sharing cronjob to finish **/
        cy.wait(2000);
        cy.get('#app-sidebar-vue').screenshotWithPreview('password-details-sharing');
        cy.get('#app-sidebar-vue .app-sidebar-header__menu .action-item__menutoggle').click();
        cy.get('.action-item__popper .passwords-password-qrcode').click();
        cy.get('#app-popup .modal-container .disable-warning').click();
        cy.get('#app-popup .modal-container').screenshotWithPreview('password-qrcode', {padding: 10});
    });
});