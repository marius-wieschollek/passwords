<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <breadcrumb :show-add-new="false">
                <div class="settings-level">
                    <translate tag="label" for="setting-settings-advanced" say="View"/>
                    <select id="setting-settings-advanced" v-model="advanced">
                        <translate tag="option" value="0" say="Default"/>
                        <translate tag="option" value="1" say="Advanced"/>
                    </select>
                </div>
            </breadcrumb>

            <div class="settings-container" :class="{advanced: advanced==='1'}">
                <section class="security">
                    <translate tag="h1" say="Security"/>

                    <translate tag="h3" say="Password Rules"/>
                    <translate tag="label" for="setting-check-duplicates" say="Mark duplicates"/>
                    <input type="checkbox" id="setting-check-duplicates" v-model="settings['user.password.security.duplicates']">
                    <settings-help text="Mark passwords as weak if they are being used for multiple accounts"/>

                    <translate tag="label" for="setting-check-age" say="Maximum age in days"/>
                    <input type="number" min="0" id="setting-check-age" v-model="settings['user.password.security.age']">
                    <settings-help text="Mark passwords as weak if they surpass the specified amount of days"/>

                    <translate tag="h3" say="Password Generator"/>
                    <translate tag="label" for="setting-security-level" say="Password strength"/>
                    <select id="setting-security-level" v-model="settings['user.password.generator.strength']">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <settings-help text="A higher strength results in longer, more complex passwords"/>

                    <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                    <input type="checkbox" id="setting-include-numbers" v-model="settings['user.password.generator.numbers']">
                    <span></span>

                    <translate tag="label" for="setting-include-special" say="Include special characters"/>
                    <input type="checkbox" id="setting-include-special" v-model="settings['user.password.generator.special']">
                    <span></span>
                </section>
                <section class="ui">
                    <translate tag="h1" say="User Interface"/>

                    <translate tag="h3" say="General"/>
                    <translate tag="label" for="setting-section-default" say="Initial section"/>
                    <select id="setting-section-default" v-model="settings['client.ui.section.default']">
                        <translate tag="option" value="all" say="All Passwords"/>
                        <translate tag="option" value="favorites" say="Favorites"/>
                        <translate tag="option" value="folders" say="Folders"/>
                        <translate tag="option" value="tags" say="Tags"/>
                        <translate tag="option" value="recent" say="Recent"/>
                    </select>
                    <settings-help text="The initial section to be shown when the app is opened"/>

                    <translate tag="label" for="setting-password-hidden" say="Show hidden custom fields" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-password-hidden" v-model="settings['client.ui.custom.fields.show.hidden']" v-if="advancedSettings">
                    <settings-help text="Show hidden custom fields in the edit form and detail section of a password" v-if="advancedSettings"/>

                    <translate tag="h3" say="Passwords List View"/>
                    <translate tag="label" for="setting-password-title" say="Set title from"/>
                    <select id="setting-password-title" v-model="settings['client.ui.password.field.title']">
                        <translate tag="option" value="label" say="Name"/>
                        <translate tag="option" value="website" say="Website"/>
                    </select>
                    <settings-help text="Show the selected property as title in the list view"/>

                    <translate tag="label" for="setting-password-sorting" say="Sort by" v-if="advancedSettings"/>
                    <select id="setting-password-sorting" v-model="settings['client.ui.password.field.sorting']" v-if="advancedSettings">
                        <translate tag="option" value="byTitle" say="Title field"/>
                        <translate tag="option" value="label" say="Name"/>
                        <translate tag="option" value="website" say="Website"/>
                    </select>
                    <settings-help text="Sorts passwords by the selected property when sorting by name is selected" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-password-click" say="Single click action" v-if="advancedSettings"/>
                    <select id="setting-password-click" v-model="settings['client.ui.password.click.action']" v-if="advancedSettings">
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="details" say="Show details"/>
                    </select>
                    <settings-help text="Action to perform when clicking on a password in the list view" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-password-dblClick" say="Double click action" v-if="advancedSettings"/>
                    <select id="setting-password-dblClick" v-model="settings['client.ui.password.dblClick.action']" v-if="advancedSettings">
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="details" say="Show details"/>
                    </select>
                    <settings-help text="Action to perform when double clicking on a password in the list view" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-password-menu" say="Add copy options in menu"/>
                    <input type="checkbox" id="setting-password-menu" v-model="settings['client.ui.password.menu.copy']">
                    <settings-help text="Shows options to copy the password and user name in the menu"/>

                    <translate tag="label" for="setting-password-username" say="Show username in list view" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-password-username" v-model="settings['client.ui.password.user.show']" v-if="advancedSettings">
                    <settings-help text="Always show the username related to the password in the list view" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-password-tags" say="Show tags in list view"/>
                    <input type="checkbox" id="setting-password-tags" v-model="settings['client.ui.list.tags.show']">
                    <settings-help text="Show the tags for each password in the list view. Increases loading times"/>

                    <translate tag="h3" say="Search" v-if="advancedSettings"/>
                    <translate tag="label" for="setting-search-live" say="Search as i type" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-search-live" v-model="settings['client.search.live']" v-if="advancedSettings">
                    <settings-help text="Start search when a key is pressed anywhere on the page" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-search-global" say="Search everywhere with Enter" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-search-global" v-model="settings['client.search.global']" v-if="advancedSettings">
                    <settings-help text="Search everywhere when the enter key is pressed in the search box" v-if="advancedSettings"/>

                    <translate tag="label" for="setting-search-show" say="Always show search section" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-search-show" v-model="settings['client.search.show']" v-if="advancedSettings">
                    <settings-help text="Always show the section for global search in the navigation" v-if="advancedSettings"/>
                </section>
                <section class="notifications">
                    <translate tag="h1" say="Notifications"/>

                    <translate tag="h3" say="Send Emails for"/>
                    <translate tag="label" for="setting-mail-security" say="Security issues"/>
                    <input type="checkbox" id="setting-mail-security" v-model="settings['user.mail.security']">
                    <settings-help text="Sends you e-mails about compromised passwords and other security issues"/>

                    <translate tag="label" for="setting-mail-shares" say="Passwords shared with me"/>
                    <input type="checkbox" id="setting-mail-shares" v-model="settings['user.mail.shares']">
                    <settings-help text="Sends you e-mails when other people share passwords with you"/>

                    <translate tag="h3" say="Show Notifications for"/>
                    <translate tag="label" for="setting-notification-security" say="Security issues"/>
                    <input type="checkbox" id="setting-notification-security" v-model="settings['user.notification.security']">
                    <settings-help text="Notifies you about compromised passwords and other security issues"/>

                    <translate tag="label" for="setting-notification-sharing" say="Passwords shared with me"/>
                    <input type="checkbox" id="setting-notification-sharing" v-model="settings['user.notification.shares']">
                    <settings-help text="Notifies you when other people share passwords with you"/>

                    <translate tag="label" for="setting-notification-errors" say="Other errors" v-if="advancedSettings"/>
                    <input type="checkbox" id="setting-notification-errors" v-model="settings['user.notification.errors']" v-if="advancedSettings">
                    <settings-help text="Notifies you when a background operation fails" v-if="advancedSettings"/>
                </section>
                <section class="danger">
                    <translate tag="h1" say="Danger Zone"/>

                    <translate tag="label" for="danger-reset" say="Reset all settings"/>
                    <translate tag="input" type="button" id="danger-reset" value="Reset" @click="resetSettingsAction"/>
                    <settings-help text="Reset all settings on this page to their defaults"/>

                    <translate tag="label" for="danger-purge" say="Delete everything"/>
                    <translate tag="input" type="button" id="danger-purge" value="Delete" @click="resetUserAccount"/>
                    <settings-help text="Start over and delete all configuration, passwords, folders and tags"/>
                </section>
                <section class="tests" v-if="nightly">
                    <translate tag="h1" say="Field tests"/>

                    <translate tag="label" for="setting-test-encryption" say="Encryption support"/>
                    <input type="button" id="setting-test-encryption" value="Test" @click="runTests($event)">
                    <settings-help text="Checks if your passwords, folders and tags can be encrypted without issues"/>
                </section>
            </div>
        </div>
        <div id="settings-reset" class="loading" v-if="locked"></div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Messages from '@js/Classes/Messages';
    import Translate from '@vue/Components/Translate';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import SettingsHelp from '@vue/Components/SettingsHelp';
    import SettingsManager from '@js/Manager/SettingsManager';
    import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';

    export default {
        components: {
            Breadcrumb,
            SettingsHelp,
            Translate
        },
        data() {
            let advancedSettings = SettingsManager.get('client.settings.advanced');

            return {
                settings: SettingsManager.getAll(),
                advancedSettings,
                advanced: advancedSettings ? '1':'0',
                nightly : process.env.NIGHTLY_FEATURES,
                noSave  : false,
                locked  : false
            };
        },
        methods   : {
            saveSettings() {
                if(this.noSave) return;
                for(let i in this.settings) {
                    if(!this.settings.hasOwnProperty(i)) continue;
                    let value = this.settings[i];

                    if(SettingsManager.get(i) !== value) SettingsManager.set(i, value);
                }
            },
            async runTests($event) {
                $event.target.setAttribute('disabled', 'disabled');
                let result = await EncryptionTestHelper.runTests();
                if(result) Messages.info('The client side encryption test completed successfully on this browser', 'Test successful');
                $event.target.removeAttribute('disabled');
            },
            resetSettingsAction() {
                Messages.confirm('This will reset all settings to their defaults. Do you want to continue?', 'Reset all settings')
                        .then(() => { this.resetSettings(); });
            },
            async resetSettings() {
                this.locked = true;
                this.noSave = true;
                for(let i in this.settings) {
                    if(this.settings.hasOwnProperty(i)) this.settings[i] = await SettingsManager.reset(i);
                }
                this.advancedSettings = false;
                this.advanced = '0';
                this.noSave = false;
                this.locked = false;
            },
            async resetUserAccount() {
                try {
                    let form = await Messages.form(
                        {password: {type: 'password'}},
                        'DELETE EVERYTHING',
                        'Do you want to delete all your settings, passwords, folders and tags?\nIt will NOT be possible to undo this.'
                    );
                    if(form.password) {
                        this.performUserAccountReset(form.password);
                    }
                } catch(e) {

                }
            },
            async performUserAccountReset(password) {
                try {
                    this.locked = true;
                    let response = await API.resetUserAccount(password);

                    if(response.status === 'accepted') {
                        this.locked = false;
                        Messages.confirm(['You have to wait {seconds} seconds before you can reset your account.', {seconds: response.wait}], 'Account reset requested')
                                .then(() => { this.performUserAccountReset(password); });
                    } else if(response.status === 'ok') {
                        window.localStorage.removeItem('passwords.settings');
                        window.localStorage.removeItem('pwFolderIcon');
                        location.href = location.href.replace(location.hash, '');
                    }
                } catch(e) {
                    console.error(e);
                    Messages.alert('Invalid Password');
                }
            }
        },
        watch     : {
            settings: {
                handler() {
                    this.saveSettings();
                },
                deep: true
            },
            advanced(value) {
                this.advancedSettings = this.settings['client.settings.advanced'] = value === '1';
            }
        }
    };
</script>

<style lang="scss">
    .app-content-left.settings {
        .settings-level {
            color    : $color-grey-dark;
            position : absolute;
            right    : 5px;

            label {
                margin-right : 5px
            }
        }

        .settings-container {
            padding               : 10px;
            margin-right          : -2em;
            display               : grid;
            grid-template-columns : 1fr 1fr 1fr 1fr;
            grid-column-gap       : 2em;
            max-width             : 100%;

            &.advanced section.ui {
                grid-row-start    : 1;
                grid-row-end      : 3;
                grid-column-start : 2;
            }
        }

        h1 {
            font-size   : 2.25em;
            font-weight : 200;
            margin      : 0.25em 0 1em;
        }

        h3 {
            margin-bottom : 0;
        }

        section {
            display               : grid;
            grid-template-columns : 3fr 2fr 30px;
            grid-auto-rows        : max-content;
            padding               : 0 0 4em 0;

            h1,
            h3 {
                grid-column-start : 1;
                grid-column-end   : 4;
            }

            label {
                line-height : 40px;
            }

            select {
                justify-self : end;
                width        : 100%;
            }

            input {
                justify-self : end;
                max-height   : 34px;

                &[type=checkbox] {
                    cursor : pointer;
                }
            }

            &.danger {

                input[type=button] {
                    transition : color .2s ease-in-out, border-color .2s ease-in-out, background-color .2s ease-in-out;

                    &:hover {
                        background-color : $color-red;
                        border-color     : $color-red;
                        color            : $color-white;
                    }
                }
            }
        }

        @media all and (max-width : $width-extra-large) {
            .settings-container {
                grid-template-columns : 1fr 1fr 1fr;
            }
        }

        @media all and (max-width : $width-large) {
            padding : 0;

            .settings-container {
                grid-template-columns : 1fr 1fr;
                margin-right          : -3em;
            }
        }

        @media all and (max-width : $width-medium) {
            margin-right : 0;

            section {
                padding : 0 0 4em 0;
            }

            .settings-container {
                grid-template-columns : 1fr;
                margin-right          : -1em;

                &.advanced section.ui {
                    grid-row-start    : initial;
                    grid-row-end      : initial;
                    grid-column-start : initial;
                }
            }

            .settings-level label {
                display : none;
            }
        }

        @media all and (max-width : $width-small) {
            .settings-container {
                padding : 10px;
            }
        }
    }

    #settings-reset {
        position         : fixed;
        top              : 0;
        right            : 0;
        bottom           : 0;
        left             : 0;
        background-color : transparentize($color-black, 0.9);
        cursor           : wait;
        z-index          : 2000;
    }
</style>