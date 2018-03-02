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
                <span></span>

                <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                <input type="checkbox" id="setting-include-numbers" v-model="settings['user.password.generator.numbers']">
                <span></span>

                <translate tag="label" for="setting-include-special" say="Include special charaters"/>
                <input type="checkbox" id="setting-include-special" v-model="settings['user.password.generator.special']">
                <span></span>
            </section>
            <section class="ui">
                <translate tag="h1" say="User Interface"/>

                <translate tag="label" for="setting-password-field" say="Sort passwords by"/>
                <select id="setting-password-field" v-model="settings['client.ui.password.sorting.field']">
                    <translate tag="option" value="label" say="Name"></translate>
                    <translate tag="option" value="host" say="Website"></translate>
                    <translate tag="option" value="user" say="Username"></translate>
                </select>
                <span></span>
            </section>
            <section class="tests" v-if="testsEnabled">
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

    export default {
        components: {
            Translate
        },
        data() {
            return {
                settings: SettingsManager.getAll()
            };
        },
        computed  : {
            testsEnabled() {
                return process.env.NODE_ENV !== 'production';
            }
        },
        methods   : {
            saveSettings() {
                for(let i in this.settings) {
                    if(this.settings.hasOwnProperty(i)) {
                        SettingsManager.set(i, this.settings[i]);
                    }
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
            grid-template-columns : 3fr 2fr 1fr;
            max-width             : 400px;
            float                 : left;
            margin-bottom         : 2em;

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
        }
    }
</style>