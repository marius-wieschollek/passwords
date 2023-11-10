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
        <nc-note-card type="error" :heading="errorLabel" v-if="error">
            <p>{{ error }}</p>
        </nc-note-card>
        <nc-password-field
                :value.sync="password"
                :minlength="12"
                :disabled="loggingIn"
                :placeholder="passwordPlaceholder"
        />
        <nc-button alignment="center-reverse" type="primary" nativeType="submit" :disabled="loggingIn" wide>
            <template #icon>
                <arrow-right :size="20" v-if="!loggingIn"/>
                <nc-loading-icon :size="20" v-else/>
            </template>
            {{ loginLabel }}
        </nc-button>
    </form>
</template>

<script>
    import NcButton from '@nc/NcButton.js';
    import ArrowRight from '@icon/ArrowRight';
    import NcNoteCard from '@nc/NcNoteCard.js';
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';
    import NcPasswordField from '@nc/NcPasswordField.js';
    import LocalisationService from '@js/Services/LocalisationService';
    import WebAuthnDisableAction from '@js/Actions/WebAuthn/WebAuthnDisableAction';
    import WebAuthnAuthorizeAction from '@js/Actions/WebAuthn/WebAuthnAuthorizeAction';

    export default {
        components: {NcLoadingIcon, NcButton, NcPasswordField, ArrowRight, NcNoteCard},
        inject    : ['api'],
        data() {
            let webAuthnAction = new WebAuthnAuthorizeAction();
            return {
                password : '',
                salts    : [],
                loggingIn: false,
                error    : null,
                webAuthnAction
            };
        },

        created() {
            this.requestSession();
        },

        computed: {
            loginLabel() {
                return LocalisationService.translate('Login');
            },
            passwordPlaceholder() {
                return LocalisationService.translate('Password');
            },
            errorLabel() {
                return LocalisationService.translate('DashboardLoginError');
            }
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
                        this.tryLogin({password, salts: this.salts});
                    }
                }
            },
            submitLogin() {
                this.tryLogin({password: this.password, salts: this.salts});
            },
            tryLogin(loginData, isWebAuthn = false) {
                if(this.loggingIn) return;
                this.loggingIn = true;
                this.error = null;

                this.api.openSession(loginData)
                    .catch((d) => { this.loginError(d, isWebAuthn); });
            },
            loginError(e, isWebAuthn = false) {
                let message = 'Unknown Error';
                if(e.response && e.response.status === 403) {
                    message = 'Password invalid. Session revoked for too many failed login attempts.';
                } else if(e.message) {
                    message = e.message;
                }

                this.error = LocalisationService.translate(message);

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