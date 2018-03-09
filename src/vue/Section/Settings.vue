<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <section class="security">
                <translate tag="h1" say="Security"/>
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
                <translate tag="label" for="setting-section-default" say="Default section"/>
                <select id="setting-section-default" v-model="settings['client.ui.section.default']">
                    <translate tag="option" value="all" say="All Passwords"/>
                    <translate tag="option" value="favourites" say="Favourites"/>
                    <translate tag="option" value="folders" say="Folders"/>
                    <translate tag="option" value="tags" say="Tags"/>
                    <translate tag="option" value="recent" say="Recent"/>
                </select>
                <settings-help text="The default section to be opened at startup"/>

                <translate tag="h3" say="Passwords List View"/>
                <translate tag="label" for="setting-password-title" say="Set title from"/>
                <select id="setting-password-title" v-model="settings['client.ui.password.field.title']">
                    <translate tag="option" value="label" say="Name"/>
                    <translate tag="option" value="website" say="Website"/>
                    <translate tag="option" value="user" say="Username"/>
                </select>
                <settings-help text="Show the selected property as title in the list view"/>

                <translate tag="label" for="setting-password-sorting" say="Sort by"/>
                <select id="setting-password-sorting" v-model="settings['client.ui.password.field.sorting']">
                    <translate tag="option" value="byTitle" say="Title field"/>
                    <translate tag="option" value="label" say="Name"/>
                    <translate tag="option" value="website" say="Website"/>
                    <translate tag="option" value="user" say="Username"/>
                </select>
                <settings-help text="Sorts passwords by the selected property when sorting by name is selected"/>

                <translate tag="label" for="setting-password-menu" say="Add copy options in menu"/>
                <input type="checkbox" id="setting-password-menu" v-model="settings['client.ui.password.menu.copy']">
                <settings-help text="Shows options to copy the password and user name in the menu"/>

                <translate tag="label" for="setting-password-tags" say="Show tags in list view"/>
                <input type="checkbox" id="setting-password-tags" v-model="settings['client.ui.list.tags.show']">
                <settings-help text="Shows the tags for a password in the main view. Might be slower."/>
            </section>
            <section class="ui">
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
                <settings-help text="Notifies you when your passwords are compromised or other security issues appear"/>

                <translate tag="label" for="setting-notification-sharing" say="Passwords shared with me"/>
                <input type="checkbox" id="setting-notification-sharing" v-model="settings['user.notification.shares']">
                <settings-help text="Notifies you when other people share passwords with you"/>

                <translate tag="label" for="setting-notification-errors" say="Errors happening"/>
                <input type="checkbox" id="setting-notification-errors" v-model="settings['user.notification.errors']">
                <settings-help text="Notifies you, when a background operation failed"/>
            </section>
            <section class="tests" v-if="nightly">
                <translate tag="h1" say="Field tests"/>

                <translate tag="label" for="setting-test-encryption" say="Encryption support"/>
                <input type="button" id="setting-test-encryption" value="Run" @click="runTests($event)">
                <span></span>
            </section>
        </div>
    </div>
</template>

<script>
    import Messages from "@js/Classes/Messages";
    import Translate from "@vue/Components/Translate";
    import SettingsManager from '@js/Manager/SettingsManager';
    import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';
    import SettingsHelp from "@/vue/Components/SettingsHelp";

    export default {
        components: {
            SettingsHelp,
            Translate
        },
        data() {
            return {
                settings: SettingsManager.getAll(),
                nightly : process.env.NIGHTLY_FEATURES
            };
        },
        methods   : {
            saveSettings() {
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
            }
        },
        watch     : {
            settings: {
                handler(value, oldValue) {
                    if(value.hasOwnProperty('user.password.generator.strength') && oldValue.hasOwnProperty('user.password.generator.strength')) {
                        this.saveSettings();
                    }
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    .app-content-left.settings {
        padding : 10px;

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
            max-width             : 400px;
            float                 : left;
            margin                : 0 2em 2em 0;

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

            @media all and (max-width : $mobile-width) {
                margin : 0 0 2em 0;
            }
        }
    }
</style>