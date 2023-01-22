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
    cy.session([username, password], () => {
        cy.visit('https://localhost/apps/passwords');
        cy.get('#user').type(username);
        cy.get('#password').type(password);
        cy.get('form.login-form button[type="submit"]').click();
        cy.url().should('contain', '/apps/passwords');
    });
});

Cypress.Commands.add('closeNotifications', () => {
    cy.document().then((document) => {
        if(document.querySelector('#header .notifications-button .notification__dot') !== null) {
            let dismissAllButton = document.querySelector('#header-menu-notifications .dismiss-all button');
            if(dismissAllButton) dismissAllButton.click();
        }
    });
});

Cypress.Commands.add('closeToasts', () => {
    cy.document().then((document) => {
        document.querySelectorAll('.toastify').forEach(function(el) {
            el.remove();
        });
    });
});

Cypress.Commands.add('closeSections', (...sections) => {
    if(!Array.isArray(sections)) {
        sections = [sections];
    }

    for(let section of sections) {
        cy.document().then((document) => {
            let closeSectionButton = document.querySelector(`.app-navigation-entry-link[title="${section}"]`).parentNode.querySelector('button.icon-collapse--open');
            if(closeSectionButton) closeSectionButton.click();
        });
    }
});
Cypress.Commands.add('openSections', (...sections) => {
    if(!Array.isArray(sections)) {
        sections = [sections];
    }

    for(let section of sections) {
        cy.document().then((document) => {
            let openSectionButton = document.querySelector(`.app-navigation-entry-link[title="${section}"]`).parentNode.querySelector('button.icon-collapse:not(.icon-collapse--open)');
            if(openSectionButton) openSectionButton.click();
        });
    }
});

Cypress.Commands.add(
    'screenshotWithPreview',
    {prevSubject: 'optional'},
    (subject, fileName, options = {}) => {

        if(!options.hasOwnProperty('overwrite')) {
            options.overwrite = true;
        }

        if(!options.hasOwnProperty('closeToasts') || options.closeToasts) {
            cy.closeToasts();
        }

        if(!options.hasOwnProperty('closeNotifications') || options.closeNotifications) {
            cy.closeNotifications();
        }

        let createThumb = () => {
            cy.exec(`convert cypress/screenshots/${fileName}.png -thumbnail '320x200>' cypress/screenshots/_previews/${fileName}.jpg`);
        };

        if(subject) {
            cy.wrap(subject).screenshot(fileName, options).then(createThumb);
        } else {
            cy.screenshot(fileName, options).then(createThumb);
        }
    });