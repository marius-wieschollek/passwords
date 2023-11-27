<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <breadcrumb :show-add-new="false" :actions-on-right="true">
                <div class="settings-level">
                    <nc-checkbox-radio-switch :checked.sync="advanced">
                        {{ t('ExpertSettingsLabel') }}
                    </nc-checkbox-radio-switch>
                    <nc-button :to="{ name: 'Help', params: { page: 'Settings' }}" :title="t('Open the settings page in the handbook')" :aria-label="t('Handbook')">
                        <help-circle-icon slot="icon"/>
                    </nc-button>
                </div>
            </breadcrumb>

            <div class="settings-container" :class="{advanced: advanced}">
                <section class="security">
                    <translate tag="h1" say="Security"/>

                    <translate tag="h3" say="Password Rules"/>
                    <translate tag="label" for="setting-check-duplicates" say="Mark duplicates" v-if="showDuplicateSetting"/>
                    <input type="checkbox"
                           id="setting-check-duplicates"
                           v-model="settings['user.password.security.duplicates']" v-if="showDuplicateSetting">
                    <settings-help text="Mark passwords as weak if they are being used for multiple accounts" v-if="showDuplicateSetting"/>

                    <translate tag="label" for="setting-check-age" say="Maximum age in days"/>
                    <input type="number"
                           min="0"
                           id="setting-check-age"
                           v-model="settings['user.password.security.age']">
                    <settings-help text="Mark passwords as weak if they surpass the specified amount of days"/>

                    <translate tag="label" for="setting-hash-length" say="Security Check Hash" v-if="advanced"/>
                    <select id="setting-hash-length" v-model.number="settings['user.password.security.hash']" v-if="advanced">
                        <translate tag="option" value="0" say="Don't store hashes"></translate>
                        <translate tag="option" value="20" say="Store 50% of the hash"></translate>
                        <translate tag="option" value="30" say="Store 75% of the hash"></translate>
                        <translate tag="option" value="40" say="Store the full hash"></translate>
                    </select>
                    <settings-help text="The SHA-1 hash is used to check for breached passwords. A partial hash can prevent brute force attacks in case the server is hacked but may also cause safe passwords to be mistakenly reported as breached."
                                   v-if="advanced"/>

                    <translate tag="h3" say="Password Generator"/>
                    <translate tag="label" for="setting-security-level" say="Password strength"/>
                    <select id="setting-security-level" v-model.number="settings['user.password.generator.strength']">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <settings-help text="A higher strength results in longer, more complex passwords"/>

                    <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                    <input type="checkbox"
                           id="setting-include-numbers"
                           v-model="settings['user.password.generator.numbers']">
                    <settings-help text="Add numbers to generated passwords"/>

                    <translate tag="label" for="setting-include-special" say="Include special characters"/>
                    <input type="checkbox"
                           id="setting-include-special"
                           v-model="settings['user.password.generator.special']">
                    <settings-help text="Add special characters to generated passwords"/>


                    <translate tag="h3" say="Login & Session" v-if="hasEncryption"/>
                    <translate tag="label"
                               for="setting-session-keepalive"
                               say="Keep me logged in"
                               v-if="hasEncryption"/>
                    <select id="setting-session-keepalive"
                            v-model.number="settings['client.session.keepalive']"
                            v-if="hasEncryption">
                        <translate tag="option" value="0" say="Always"/>
                        <translate tag="option" value="1" say="When i'm active"/>
                        <translate tag="option" value="2" say="When i'm working"/>
                    </select>
                    <settings-help text="Send keep-alive requests to the server to prevent the session from being cancelled"
                                   v-if="hasEncryption"/>

                    <translate tag="label"
                               for="setting-session-lifetime"
                               say="End session after"
                               v-if="advanced && hasEncryption"/>
                    <select id="setting-session-lifetime"
                            v-model.number="settings['user.session.lifetime']"
                            v-if="advanced && hasEncryption">
                        <translate tag="option" say="One minute" value="60"/>
                        <translate tag="option" say="{minutes} minutes" :variables="{minutes:2}" value="120"/>
                        <translate tag="option" say="{minutes} minutes" :variables="{minutes:5}" value="300"/>
                        <translate tag="option" say="{minutes} minutes" :variables="{minutes:10}" value="600"/>
                        <translate tag="option" say="{minutes} minutes" :variables="{minutes:30}" value="1800"/>
                        <translate tag="option" say="{minutes} minutes" :variables="{minutes:60}" value="3600"/>
                    </select>
                    <settings-help text="Specify the amount of time after a request before the session is cancelled"
                                   v-if="advanced && hasEncryption"/>

                    <translate tag="h3" say="Encryption" v-if="hasEncryption"/>
                    <translate tag="label"
                               for="setting-encryption-sse"
                               say="Server encryption mode"
                               v-if="hasEncryption && advanced"/>
                    <select id="setting-encryption-sse"
                            v-model.number="settings['user.encryption.sse']"
                            v-if="hasEncryption && advanced">
                        <translate tag="option" value="0" say="None if CSE used"/>
                        <translate tag="option" value="1" say="Simple encryption"/>
                        <translate tag="option" value="2" say="Advanced encryption"/>
                    </select>
                    <settings-help text="Choose the type of encryption used to encrypt data on the server"
                                   v-if="hasEncryption && advanced"/>

                    <translate tag="label"
                               for="setting-encryption-cse"
                               say="Client encryption mode"
                               v-if="hasEncryption && advanced"/>
                    <select id="setting-encryption-cse"
                            v-model.number="settings['user.encryption.cse']"
                            v-if="hasEncryption && advanced">
                        <translate tag="option" value="0" say="No encryption"/>
                        <translate tag="option" value="1" say="Libsodium"/>
                    </select>
                    <settings-help text="Choose the type of encryption used to encrypt data on the client before it's sent to the server"
                                   v-if="hasEncryption && advanced"/>

                    <translate tag="label"
                               for="setting-encryption-setup"
                               say="End-to-end Encryption"
                               v-if="!hasEncryption"/>
                    <translate tag="input"
                               type="button"
                               id="setting-encryption-setup"
                               localized-value="Enable"
                               @click="runWizard()"
                               v-if="!hasEncryption"/>
                    <settings-help text="Run the installation wizard to set up encryption for your passwords"
                                   v-if="!hasEncryption"/>

                    <translate tag="label"
                               for="setting-encryption-update"
                               say="End-to-end Encryption"
                               v-if="hasEncryption"/>
                    <translate tag="input"
                               type="button"
                               id="setting-encryption-update"
                               localized-value="Change Password"
                               @click="changeCsePassword()"
                               v-if="hasEncryption"/>
                    <settings-help text="Change the encryption password"
                                   v-if="hasEncryption"/>

                    <translate tag="label" for="encryption-webauthn-enable" say="SettingsWebAuthnEnable" v-if="hasEncryption && hasWebAuthn"/>
                    <translate tag="input"
                               type="button"
                               id="encryption-webauthn-enable"
                               :localized-value="webAuthnButtonLabel"
                               @click="installWebAuthn" v-if="hasEncryption && hasWebAuthn"/>
                    <settings-help text="SettingsWebAuthnEnableHelp" v-if="hasEncryption && hasWebAuthn"/>
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

                    <translate tag="label"
                               for="setting-password-hidden"
                               say="Show hidden custom fields"
                               v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-password-hidden"
                           v-model="settings['client.ui.custom.fields.show.hidden']"
                           v-if="advanced">
                    <settings-help text="Show hidden custom fields in the edit form and detail section of a password"
                                   v-if="advanced"/>

                    <translate tag="h3" say="Passwords List View"/>
                    <translate tag="label" for="setting-password-title" say="Set title from"/>
                    <select id="setting-password-title" v-model="settings['client.ui.password.field.title']">
                        <translate tag="option" value="label" say="Name"/>
                        <translate tag="option" value="website" say="Website"/>
                    </select>
                    <settings-help text="Show the selected property as title in the list view"/>

                    <translate tag="label" for="setting-password-sorting" say="Sort by" v-if="advanced"/>
                    <select id="setting-password-sorting"
                            v-model="settings['client.ui.password.field.sorting']"
                            v-if="advanced">
                        <translate tag="option" value="byTitle" say="Title field"/>
                        <translate tag="option" value="label" say="Name"/>
                        <translate tag="option" value="website" say="Website"/>
                    </select>
                    <settings-help text="Sorts passwords by the selected property when sorting by name is selected"
                                   v-if="advanced"/>

                    <translate tag="label"
                               for="setting-password-click"
                               say="Single click action"/>
                    <select id="setting-password-click"
                            v-model="settings['client.ui.password.click.action']">
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="details" say="Show details"/>
                        <translate tag="option" value="edit" say="Edit password"/>
                        <translate tag="option" value="none" say="Nothing"/>
                    </select>
                    <settings-help text="Action to perform when clicking on a password in the list view"/>

                    <translate tag="label"
                               for="setting-password-dblClick"
                               say="Double click action"/>
                    <select id="setting-password-dblClick"
                            v-model="settings['client.ui.password.dblClick.action']">
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="details" say="Show details"/>
                        <translate tag="option" value="edit" say="Edit password"/>
                        <translate tag="option" value="open-url" say="Open Url"/>
                        <translate tag="option" value="none" say="Nothing"/>
                    </select>
                    <settings-help text="Action to perform when double clicking on a password in the list view"/>

                    <translate tag="label" for="setting-password-wheel" say="SettingsMouseWheelAction" v-if="advanced"/>
                    <select id="setting-password-wheel" v-model="settings['client.ui.password.wheel.action']" v-if="advanced">
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="details" say="Show details"/>
                        <translate tag="option" value="edit" say="Edit password"/>
                        <translate tag="option" value="open-url" say="Open Url"/>
                        <translate tag="option" value="none" say="Nothing"/>
                    </select>
                    <settings-help text="SettingsMouseWheelActionHelp" v-if="advanced"/>

                    <translate tag="label" for="setting-password-custom-action" say="SettingsCustomAction"/>
                    <select id="setting-password-custom-action" v-model="settings['client.ui.password.custom.action']">
                        <translate tag="option" value="details" say="Show details"/>
                        <translate tag="option" value="share" say="SettingsShowShareTab"/>
                        <translate tag="option" value="edit" say="Edit password"/>
                        <translate tag="option" value="password" say="Copy password"/>
                        <translate tag="option" value="username" say="Copy username"/>
                        <translate tag="option" value="url" say="Copy website"/>
                        <translate tag="option" value="open-url" say="Open Url"/>
                        <translate tag="option" value="qrcode" say="PasswordActionQrcode"/>
                        <translate tag="option" value="print" say="PasswordActionPrint" v-if="settings['client.ui.password.print']"/>
                        <translate tag="option" value="none" say="Nothing"/>
                    </select>
                    <settings-help text="SettingsCustomActionHelp"/>

                    <translate tag="label" for="setting-password-menu" say="Add copy options in menu"/>
                    <input type="checkbox"
                           id="setting-password-menu"
                           v-model="settings['client.ui.password.menu.copy']">
                    <settings-help text="Shows options to copy the password and user name in the menu"/>

                    <translate tag="label" for="setting-password-print" say="SettingsPasswordPrint" v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-password-print"
                           v-model="settings['client.ui.password.print']" v-if="advanced">
                    <settings-help text="SettingsPasswordPrintHelp" v-if="advanced"/>

                    <translate tag="label"
                               for="setting-password-username"
                               say="Show username in list view"
                               v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-password-username"
                           v-model="settings['client.ui.password.user.show']"
                           v-if="advanced">
                    <settings-help text="Always show the username related to the password in the list view"
                                   v-if="advanced"/>

                    <translate tag="label" for="setting-password-tags" say="Show tags in list view"/>
                    <input type="checkbox" id="setting-password-tags" v-model="settings['client.ui.list.tags.show']">
                    <settings-help text="Show the tags for each password in the list view. Increases loading times"/>

                    <translate tag="h3" say="Passwords Detail View"/>
                    <translate tag="label" for="setting-website-preview" say="Show website preview"/>
                    <input type="checkbox"
                           id="setting-website-preview"
                           v-model="settings['client.ui.password.details.preview']">
                    <settings-help text="Show a preview of the associated website in the details. (Not on mobiles)"/>

                    <translate tag="h3" say="Password Sharing" v-if="hasSharing"/>
                    <translate tag="label" for="setting-sharing-editable" say="Share as editable by default" v-if="hasSharing"/>
                    <input type="checkbox"
                           id="setting-sharing-editable"
                           v-model="settings['user.sharing.editable']" v-if="hasSharing">
                    <settings-help text="Enable the option to let other users edit a shared password by default" v-if="hasSharing"/>
                    <translate tag="label" for="setting-sharing-shareable" say="Allow sharing by default" v-if="hasSharing && hasResharing"/>
                    <input type="checkbox"
                           id="setting-sharing-shareable"
                           v-model="settings['user.sharing.resharing']" v-if="hasSharing && hasResharing">
                    <settings-help text="Enable the option to let other users share a shared password by default" v-if="hasSharing && hasResharing"/>

                    <translate tag="h3" say="Search" v-if="advanced"/>
                    <translate tag="label" for="setting-search-live" say="Search as i type" v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-search-live"
                           v-model="settings['client.search.live']"
                           v-if="advanced">
                    <settings-help text="Start search when a key is pressed anywhere on the page"
                                   v-if="advanced"/>

                    <translate tag="label"
                               for="setting-search-global"
                               say="Search everywhere with Enter"
                               v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-search-global"
                           v-model="settings['client.search.global']"
                           v-if="advanced">
                    <settings-help text="Search everywhere when the enter key is pressed in the search box"
                                   v-if="advanced"/>

                    <translate tag="label"
                               for="setting-search-show"
                               say="Always show search section"
                               v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-search-show"
                           v-model="settings['client.search.show']"
                           v-if="advanced">
                    <settings-help text="Always show the section for global search in the navigation"
                                   v-if="advanced"/>
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
                    <input type="checkbox"
                           id="setting-notification-security"
                           v-model="settings['user.notification.security']">
                    <settings-help text="Notifies you about compromised passwords and other security issues"/>

                    <translate tag="label" for="setting-notification-sharing" say="Passwords shared with me"/>
                    <input type="checkbox"
                           id="setting-notification-sharing"
                           v-model="settings['user.notification.shares']">
                    <settings-help text="Notifies you when other people share passwords with you"/>


                    <translate tag="label" for="setting-notification-admin" say="Administration Issues" v-if="isAdmin"/>
                    <input type="checkbox"
                           id="setting-notification-admin"
                           v-model="settings['user.notification.admin']" v-if="isAdmin">
                    <settings-help text="Notifies you of configuration errors and other administrative issues"
                                   v-if="isAdmin"/>

                    <translate tag="label"
                               for="setting-notification-errors"
                               say="Other errors"
                               v-if="advanced"/>
                    <input type="checkbox"
                           id="setting-notification-errors"
                           v-model="settings['user.notification.errors']"
                           v-if="advanced">
                    <settings-help text="Notifies you when a background operation fails" v-if="advanced"/>
                </section>
                <section class="danger">
                    <translate tag="h1" say="Danger Zone"/>

                    <translate tag="label" for="danger-reset" say="SettingsRecoverItems"/>
                    <translate tag="input"
                               type="button"
                               id="danger-reset"
                               localized-value="SettingsRecoverItemsButton"
                               @click="recoverItemsAction"/>
                    <settings-help text="SettingsRecoverItemsHelp"/>

                    <translate tag="label" for="danger-reset" say="Reset all settings"/>
                    <translate tag="input"
                               type="button"
                               id="danger-reset"
                               localized-value="Reset"
                               @click="resetSettingsAction"/>
                    <settings-help text="Reset all settings on this page to their defaults"/>

                    <translate tag="label" for="danger-purge" say="Delete everything"/>
                    <translate tag="input"
                               type="button"
                               id="danger-purge"
                               localized-value="Delete"
                               @click="resetUserAccount"/>
                    <settings-help text="Start over and delete all configuration, passwords, folders and tags"/>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import SUM from '@js/Manager/SetupManager';
    import EncryptionManager from '@js/Manager/EncryptionManager';
    import {getCurrentUser} from '@nextcloud/auth';
    import NcButton from '@nc/NcButton.js';
    import HelpCircleIcon from '@icon/HelpCircle';
    import Translate from '@vue/Components/Translate';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import SettingsHelp from '@vue/Components/Settings/SettingsHelp';
    import NcCheckboxRadioSwitch from '@nc/NcCheckboxRadioSwitch.js';
    import MessageService from '@js/Services/MessageService';
    import UtilityService from '@js/Services/UtilityService';
    import LoggingService from "@js/Services/LoggingService";
    import SettingsService from '@js/Services/SettingsService';
    import WebAuthnDisableAction from "@js/Actions/WebAuthn/WebAuthnDisableAction";
    import WebAuthnInitializeAction from "@js/Actions/WebAuthn/WebAuthnInitializeAction";
    import UserAccountResetAction from "@js/Actions/User/UserAccountResetAction";
    import UserSettingsResetAction from "@js/Actions/User/UserSettingsResetAction";
    import RecoverHiddenItemsAction from "@js/Actions/User/RecoverHiddenItemsAction";

    export default {
        components: {
            HelpCircleIcon,
            Breadcrumb,
            SettingsHelp,
            Translate,
            NcButton,
            NcCheckboxRadioSwitch
        },
        data() {
            let settings = SettingsService.getAll(),
                observer = (data) => {
                    if(!settings.hasOwnProperty(data.setting) || settings[data.setting] !== data.value) {
                        settings[data.setting] = data.value;
                    }
                };

            SettingsService.observe(Object.keys(settings), observer);
            return {
                observer,
                settings,
                hasSharing   : SettingsService.get('server.sharing.enabled'),
                hasResharing : SettingsService.get('server.sharing.resharing'),
                advanced     : SettingsService.get('client.settings.advanced') === true,
                hasEncryption: API.hasEncryption === true,
                isAdmin      : getCurrentUser().isAdmin,
                nightly      : APP_NIGHTLY,
                noSave       : false,
                locked       : false,
                hasWebAuthn  : WebAuthnInitializeAction.isWebauthnPasswordAvailable()
            };
        },

        computed: {
            showDuplicateSetting() {
                return this.settings['user.password.security.hash'] > 0;
            },
            webAuthnButtonLabel() {
                return this.settings['client.encryption.webauthn.enabled'] ? 'Disable':'Enable';
            }
        },

        beforeDestroy() {
            SettingsService.unobserve(Object.keys(this.settings), this.observer);
        },

        methods: {
            saveSettings() {
                if(this.noSave) return;
                for(let i in this.settings) {
                    if(!this.settings.hasOwnProperty(i)) continue;
                    let value = this.settings[i];

                    if(SettingsService.get(i) !== value) SettingsService.set(i, value);
                }
            },
            runWizard() {
                if(!this.hasEncryption) {
                    SUM.runEncryptionSetup()
                       .then(() => {
                           this.hasEncryption = API.hasEncryption;
                       });
                }
            },
            changeCsePassword() {
                if(this.hasEncryption) {
                    EncryptionManager.updateGui();
                }
            },
            async resetSettingsAction() {
                if(await this.runAction(new UserSettingsResetAction(), false)) {
                    this.advanced = false;
                    this.settings = SettingsService.getAll();
                }
                this.$nextTick(() => {this.noSave = false;});
            },
            async resetUserAccount() {
                await this.runAction(new UserAccountResetAction());
            },
            async installWebAuthn() {
                if(!this.settings['client.encryption.webauthn.enabled']) {
                    await this.runAction(new WebAuthnInitializeAction());
                } else {
                    await this.runAction(new WebAuthnDisableAction());
                }
            },
            async recoverItemsAction() {
                await this.runAction(new RecoverHiddenItemsAction());
            },
            async runAction(action, resetNoSave = true) {
                this.locked = true;
                this.noSave = true;
                try {
                    return await action.run();
                } catch(e) {
                    LoggingService.error(e);
                    MessageService.alert(e && e.hasOwnProperty('message') ? e.message:'Error');
                } finally {
                    this.locked = false;
                    if(resetNoSave) {
                        this.noSave = false;
                    }
                }
            }
        },
        watch  : {
            settings: {
                handler() {
                    this.saveSettings();
                },
                deep: true
            },
            locked(value) {
                if(value) {
                    UtilityService.lockApp();
                } else {
                    UtilityService.unlockApp();
                }
            },
            advanced(value) {
                if(!this.noSave) {
                    SettingsService.set('client.settings.advanced', value);
                }
            }
        }
    };
</script>

<style lang="scss">
.app-content-left.settings {
    .settings-level {
        display : flex;
        gap     : 1rem;

        button,
        span.material-design-icon {
            cursor : pointer;
        }
    }

    .settings-container {
        padding               : 10px;
        margin-right          : -2em;
        display               : grid;
        grid-template-columns : 1fr 1fr 1fr;
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
                    background-color : var(--color-error);
                    border-color     : var(--color-error);
                    color            : var(--color-primary-text);
                }
            }
        }
    }

    @media(min-width : $width-1920-above) {
        .settings-container {
            grid-template-columns : 1fr 1fr 1fr 1fr;
        }
    }

    @media(max-width : $width-large) {
        padding : 0;

        .settings-container {
            grid-template-columns : 1fr 1fr;
            margin-right          : -3em;
        }
    }

    @media(max-width : $width-medium) {
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
    }

    @media(max-width : $width-small) {
        .settings-container {
            padding : 10px;
        }
    }
}
</style>