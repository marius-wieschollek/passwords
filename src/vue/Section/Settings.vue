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
    import API from '@js/Helper/api';
    import Messages from "@js/Classes/Messages";
    import Translate from "@vue/Components/Translate";
    import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';

    export default {
        components: {
            Translate
        },
        data() {
            return {
                settings: {}
            };
        },
        created() {
            this.loadSettings();
        },
        computed: {
            testsEnabled() {
                return process.env.NODE_ENV !== 'production';
            }
        },
        methods   : {
            loadSettings() {
                this.settings = {};
                API.listSettings('user')
                   .then((d) => {this.settings = d;});
            },
            saveSettings() {
                API.setSettings(this.settings)
                   .catch(this.loadSettings);
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