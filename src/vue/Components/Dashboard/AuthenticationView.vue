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
    <form class="passwords-widget-login" @submit.prevent.stop="submitLogin">
        <nc-note-card type="error" :heading="t('DashboardLoginError')" v-if="error">
            <p>{{ t(error) }}</p>
        </nc-note-card>
        <nc-password-field
                :value.sync="password"
                :minlength="12"
                :disabled="loggingIn"
                :placeholder="t('Password')"
        />
        <nc-button alignment="center-reverse" type="primary" nativeType="submit" :disabled="loggingIn || !ready" wide>
            <template #icon>
                <arrow-right :size="20" v-if="!loggingIn"/>
                <nc-loading-icon :size="20" v-else/>
            </template>
            {{ t('Login') }}
        </nc-button>
    </form>
</template>

<script>
    import NcButton from '@nc/NcButton.js';
    import ArrowRight from '@icon/ArrowRight';
    import NcNoteCard from '@nc/NcNoteCard.js';
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';
    import NcPasswordField from '@nc/NcPasswordField.js';
    import WebAuthnDisableAction from '@js/Actions/WebAuthn/WebAuthnDisableAction';
    import WebAuthnAuthorizeAction from '@js/Actions/WebAuthn/WebAuthnAuthorizeAction';
    import Dashboard from "@js/Init/Dashboard";

    export default {
        components: {NcLoadingIcon, NcButton, NcPasswordField, ArrowRight, NcNoteCard},
        inject    : ['api'],
        data() {
            let webAuthnAction = new WebAuthnAuthorizeAction();
            return {
                password : '',
                salts    : [],
                loggingIn: false,
                ready    : false,
                error    : null,
                webAuthnAction
            };
        },

        created() {
            this.requestSession();
        },

        methods: {
            async requestSession() {
                try {
                    let requirements = await this.api.requestSession();

                    if(requirements.hasOwnProperty('challenge')) {
                        this.salts = requirements.challenge.salts;
                    } else {
                        this.tryLogin([]);
                        return;
                    }
                    this.ready = true;

                    await this.tryWebAuthn();
                } catch(e) {
                    this.loginError(e);
                }
            },
            async tryWebAuthn() {
                if(this.webAuthnAction.isAvailable()) {
                    let password = await this.webAuthnAction.run();

                    if(password) {
                        this.password = password;
                        this.tryLogin({password, salts: this.salts}, true);
                    }
                }
            },
            submitLogin() {
                this.tryLogin({password: this.password, salts: this.salts});
            },
            tryLogin(loginData, isWebAuthn = false) {
                if(this.loggingIn || !this.ready) return;
                this.loggingIn = true;
                this.error = null;

                this.api.openSession(loginData)
                    .then(() => { this.$emit('authorized'); })
                    .catch((d) => { this.loginError(d, isWebAuthn); });
            },
            loginError(e, isWebAuthn = false) {
                if(e.response && e.response.status === 403) {
                    this.error = 'Password invalid. Session revoked for too many failed login attempts.';
                } else if(e.message) {
                    this.error = e.message;
                } else {
                    this.error = 'Unknown Error';
                }

                if(isWebAuthn) {
                    (new WebAuthnDisableAction()).run();
                }

                this.password = '';
                this.loggingIn = false;
            }
        }
    };
</script>

<style lang="scss">
.passwords-widget-login {
    height          : 100%;
    display         : flex;
    flex-direction  : column;
    justify-content : center;
    gap             : 1rem;
    padding-bottom  : 4rem;
}
</style>