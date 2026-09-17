// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('login', (username = 'admin', password = 'admin') => {
    cy.session(
        [username, password],
        () => {
            cy.visit('/login');
            cy.get('#user').type(username);
            cy.get('#password').type(password);
            cy.get('form.login-form button[type="submit"]').click();
            cy.url().should('contain', '/apps/passwords');
        },
        {
            validate() {
                cy.request('/settings/user').its('status').should('eq', 200)
            },
        }
    );
});

Cypress.Commands.add('dialogConfirm', (option = 'primary') => {
    cy.get('.nc-generic-dialog .dialog__actions button.button-vue--vue-'+option).click();
});

Cypress.Commands.add('modalType', (field, text) => {
    cy.get('#password-field-'+field).type(text);
});

Cypress.Commands.add('modalConfirm', (option = 'primary') => {
    cy.get('.passwords-form .actions button.button-vue--vue-'+option).click();
});

Cypress.Commands.add('itemAction', (item, action) => {
    let selector = '[data-pw-item="'+item.type+'"]'+(item.hasOwnProperty('label') ? '[data-pw-label="'+item.label+'"]':'')+(item.hasOwnProperty('id') ? '[data-pw-id="'+item.id+'"]':'');

    cy.wait(1000);
    cy.get(selector+' [data-pw-role="context-menu"] button:visible').click();
    cy.wait(1000);
    cy.get('.item-list .v-popper__inner [data-pw-action="'+action+'"]').click();
    cy.wait(1000);
});

Cypress.Commands.add('batchAction', (action, selectAll = false) => {
    if(selectAll) {
        cy.get(' [data-pw-role="batch-actions"] [data-pw-action="select-all"] button').click();

    }
    cy.get(selector+' [data-pw-role="batch-actions"] [data-pw-action="'+action+'"] button').click();
});

Cypress.Commands.add('closeNotifications', () => {
    cy.document().then((document) => {
        if (document.querySelector('#header .notifications-button .notification__dot') !== null) {
            let dismissAllButton = document.querySelector('#header-menu-notifications .dismiss-all button');
            if (dismissAllButton) dismissAllButton.click();
        }
    });
});

Cypress.Commands.add('closeToasts', () => {
    cy.document().then((document) => {
        document.querySelectorAll('.toastify').forEach(function (el) {
            el.remove();
        });
    });
});

Cypress.Commands.add('closeSections', (...sections) => {
    if (!Array.isArray(sections)) {
        sections = [sections];
    }

    for (let section of sections) {
        cy.document().then((document) => {
            let closeSectionButton = document.querySelector(`.app-navigation-entry-link[title="${section}"]`).parentNode.querySelector('button.icon-collapse--open');
            if (closeSectionButton) closeSectionButton.click();
        });
    }
});
Cypress.Commands.add('openSections', (...sections) => {
    if (!Array.isArray(sections)) {
        sections = [sections];
    }

    for (let section of sections) {
        cy.document().then((document) => {
            let openSectionButton = document.querySelector(`.app-navigation-entry-link[title="${section}"]`).parentNode.querySelector('button.icon-collapse:not(.icon-collapse--open)');
            if (openSectionButton) openSectionButton.click();
        });
        cy.get(`.app-navigation-entry-link[title="${section}"] .loading-icon`).should('not.exist');
    }
});

Cypress.Commands.add('waitForRequestsToFinish', () => {
    cy.log('Waiting for requests to finish');
    cy.window()
        .then((window) => {
            window.debugPwRequestLimit();
        });

    cy.wait(2500);
    cy.get('body[data-debug-loading="false"]', {timeout: 60000});
});

Cypress.Commands.add('raiseRequestLimitRequestsToFinish', (limit = 32) => {
    cy.log('Raise request limit to ' + limit);
    cy.window()
        .then((window) => {
            window.debugPwRequestLimit(limit);
        });
});

Cypress.Commands.add(
    'screenshotWithPreview',
    {prevSubject: 'optional'},
    (subject, fileName, options = {}) => {

        if (!options.hasOwnProperty('overwrite')) {
            options.overwrite = true;
        }

        if (!options.hasOwnProperty('closeToasts') || options.closeToasts) {
            cy.closeToasts();
        }

        if (!options.hasOwnProperty('closeNotifications') || options.closeNotifications) {
            cy.closeNotifications();
        }

        let createThumb = () => {
            cy.task('thumbnail', fileName).then(() => null)
        };

        if (subject) {
            cy.wrap(subject).screenshot(fileName, options).then(createThumb);
        } else {
            cy.screenshot(fileName, options).then(createThumb);
        }
    });

Cypress.Commands.add('occ', (...args) => {
    return cy.task('occ', args, {timeout: 120000});
});