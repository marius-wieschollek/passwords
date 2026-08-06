/**
 * Integration test for sharing a password with a group (issue #582).
 *
 * The test drives the real Nextcloud from the docker environment, so it needs
 * `npm run start` and a build. Group membership is changed with occ, the sharing
 * cron is triggered through the same app route the frontend uses.
 *
 * Nextcloud 34 rejects the bundled Electron, so run it with Chrome:
 * npx cypress run --spec cypress/e2e/group-share.cy.js --browser chrome
 */

const OCC       = 'docker exec -u www-data passwords-php ./occ';
const GROUP     = 'pw-cypress-group';
const PASSWORD  = 'Cypress Group Share';
const MEMBER    = 'max';
const LATE_USER = 'erika';

function occ(command) {
    return cy.exec(`${OCC} ${command}`, {failOnNonZeroExit: false});
}

/**
 * The cron route is limited to 3 calls per 10 seconds per user,
 * so the calls have to be spaced out to not run into the rate limit
 */
function runSharingCron() {
    cy.wait(4000);

    return cy.request({
        url    : 'https://localhost/index.php/apps/passwords/cron/sharing',
        auth   : {user: 'admin', pass: 'admin'},
        headers: {'OCS-APIRequest': 'true'}
    }).its('body.success').should('eq', true);
}

/**
 * cy.request sends the session cookie of the logged in account along with the
 * basic auth credentials and Nextcloud then answers as the account of the session.
 * curl has no cookies, so it really answers as the requested account.
 */
function passwordLabels(user) {
    const pass = user === 'admin' ? 'admin':'PasswordsApp';

    return cy.exec(`curl -sk -u ${user}:${pass} -H 'OCS-APIRequest: true' https://localhost/index.php/apps/passwords/api/1.0/password/list`)
             .then((result) => JSON.parse(result.stdout).map((password) => password.label));
}

function api(method, route, body) {
    return cy.request({
        method,
        url    : `https://localhost/index.php/apps/passwords/api/1.0/${route}`,
        auth   : {user: 'admin', pass: 'admin'},
        headers: {'OCS-APIRequest': 'true'},
        body
    });
}

/**
 * The test password is recreated for every run so leftovers can not influence the result
 */
function resetTestPassword() {
    api('GET', 'password/list').then((response) => {
        for(let password of response.body) {
            if(password.label === PASSWORD) api('DELETE', 'password/delete', {id: password.id});
        }
    });

    api('POST', 'password/create', {label: PASSWORD, password: 'cypress-group-secret', username: 'cypress'})
        .its('status').should('eq', 201);
}

function openShareTab() {
    cy.visit('https://localhost/apps/passwords/#/', {retryOnNetworkFailure: true});
    cy.contains('.row.password', PASSWORD, {timeout: 30000}).rightclick();
    cy.contains('[role="menuitem"], li', /^\s*Details\s*$/).click();
    cy.get('#app-sidebar-vue', {timeout: 15000});
    cy.contains('#app-sidebar-vue [role="tab"]', /share/i).click();
    cy.get('.sharing-container', {timeout: 15000});
}

describe('Share a password with a group', () => {

    before(() => {
        occ(`group:delete ${GROUP}`);
        occ(`group:add ${GROUP}`);
        occ(`group:adduser ${GROUP} admin`);
        occ(`group:adduser ${GROUP} ${MEMBER}`);

        resetTestPassword();

        // remove the shares of a previous run from the accounts of the members.
        // the first run removes the shares, the second the passwords created from them
        runSharingCron();
        runSharingCron();
        runSharingCron();
    });

    after(() => {
        occ(`group:delete ${GROUP}`);
        api('GET', 'password/list').then((response) => {
            for(let password of response.body) {
                if(password.label === PASSWORD) api('DELETE', 'password/delete', {id: password.id});
            }
        });
        runSharingCron();
        runSharingCron();
    });

    it('Offers the group as a recipient in the share tab', () => {
        cy.login();
        openShareTab();

        cy.get('.sharing-container input.vs__search').type(GROUP);
        cy.contains('li[role="option"]', GROUP, {timeout: 15000});
        // groups are marked as such so they can not be confused with a user
        cy.contains('li[role="option"]', 'User group');
    });

    it('Creates a single share for the group, not one per member', () => {
        cy.login();
        openShareTab();

        cy.get('.sharing-container input.vs__search').type(GROUP);
        cy.contains('li[role="option"]', GROUP, {timeout: 15000}).click();

        cy.get('.sharing-container .shares .share', {timeout: 30000}).should('have.length', 1);
        cy.get('.sharing-container .shares .share').should('contain', GROUP);
        cy.get('.sharing-container .shares .share .group-avatar').should('exist');
    });

    it('Shares the password with the members of the group', () => {
        runSharingCron();
        passwordLabels(MEMBER).should('include', PASSWORD);
    });

    it('Shares the password with a user who joins the group later', () => {
        passwordLabels(LATE_USER).should('not.include', PASSWORD);

        occ(`group:adduser ${GROUP} ${LATE_USER}`);
        runSharingCron();

        passwordLabels(LATE_USER).should('include', PASSWORD);
    });

    it('Revokes the password from a user who leaves the group', () => {
        occ(`group:removeuser ${GROUP} ${MEMBER}`);

        // one run is enough, the group sync runs before the orphaned passwords are deleted.
        // the second run makes sure that nothing is recreated afterwards
        runSharingCron();
        runSharingCron();

        passwordLabels(MEMBER).should('not.include', PASSWORD);
        passwordLabels(LATE_USER).should('include', PASSWORD);
    });

    it('Revokes the password from everyone when the group share is deleted', () => {
        cy.login();
        openShareTab();

        cy.get('.sharing-container .shares .share', {timeout: 30000}).should('have.length', 1);
        cy.get('.sharing-container .shares .share .options span').last().click();

        runSharingCron();
        runSharingCron();

        passwordLabels(LATE_USER).should('not.include', PASSWORD);
    });
});
