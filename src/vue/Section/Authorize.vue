<template>
    <div id="app-content">
        <form id="authorize-window" @submit="submitLogin">
            <translate icon="repeat" v-if="retryVisible" title="Request Token again" :iconClass="retryClass" @click="requestToken()"/>
            <div class="passwords">
                <input type="password" placeholder="Password" v-model="password" required v-if="hasPassword">
                <select v-model="providerId" v-if="hasToken && providers.length > 1">
                    <option v-for="(option, id) in providers" :value="id" :title="option.description">{{option.label}}</option>
                </select>
                <input type="text" placeholder="Token" :title="provider.description" v-model="token" required v-if="hasToken && provider.type === 'user-token'">
            </div>
            <input type="submit" value="Login" v-if="hasToken || hasPassword">
            <translate class="login-error" :say="errorMessage" tag="div" v-if="hasError"/>
        </form>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Messages from '@js/Classes/Messages';
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate},
        data() {
            return {
                password    : '',
                token       : '',
                providerId  : -1,
                provider    : null,
                providers   : [],
                hasPassword : false,
                hasToken    : false,
                hasError    : false,
                pwAlgorithm    : '',
                errorMessage: 'Login incorrect',
                retryClass  : ''
            };
        },

        created() {
            document.body.classList.add('pw-authorisation');
            API.requestSession()
               .then((d) => {
                   if(d.hasOwnProperty('password')) {
                       this.hasPassword = true;
                       this.pwAlgorithm = d.password;
                   }
                   if(d.hasOwnProperty('token')) {
                       this.hasToken = true;
                       this.provider = null;
                       this.providerId = -1;
                       this.providers = [];

                       for(let i = 0; i < d.token.length; i++) {
                           if(d.token[i].id === 'twofactor_nextcloud_notification' && d.token.length !== 1 && !process.env.NIGHTLY_FEATURES) {
                               continue;
                           }

                           this.providers.push(d.token[i]);
                           if(this.provider === null && !d.token[i].request) {
                               this.providerId = this.providers.length-1;
                               this.provider = d.token[i];
                           }
                       }

                       if(this.provider === null) {
                           this.providerId = 0;
                           this.provider = this.providers[0];
                       }
                   }
                   if(!this.hasPassword && !this.hasToken) {
                       API.openSession([])
                          .then(() => { this.goToTarget(); })
                          .catch((d) => { this.loginError(d); });
                   }
               });
        },

        computed: {
            retryVisible() {
                return this.provider !== null && this.provider.request;
            }
        },

        methods: {
            async submitLogin() {
                let data = {};

                if(this.hasPassword) {
                    data.password = await API.getHash(this.password, this.pwAlgorithm);
                }
                if(this.hasToken) {
                    data.token = {};
                    data.token[this.provider.id] = this.token;
                }
                this.hasError = false;

                API.openSession(data)
                   .then(() => { this.goToTarget(); })
                   .catch((d) => { this.loginError(d); });
            },
            loginError(e) {
                this.password = '';
                this.token = '';
                this.hasError = true;

                if(e.status === 403) {
                    this.errorMessage = 'Login incorrect';
                } else if(e.message) {
                    this.errorMessage = e.message;
                } else {
                    this.errorMessage = 'Unknown Error';
                }
            },
            goToTarget() {
                let target = this.$route.params.target,
                    route  = {path: '/'};

                if(target) route = JSON.parse(atob(target));
                document.body.classList.add('pw-authorized');

                setTimeout(() => {
                    this.$router.push(route);
                }, 250);

                setTimeout(() => {
                    document.body.classList.remove('pw-authorized');
                    document.body.classList.remove('pw-authorisation');
                }, 500);
            },
            requestToken() {
                this.retryClass = 'fa-spin';
                API.requestToken(this.provider.id)
                   .then((d) => {
                       if(this.provider.type === 'request-token') this.token = d.data.code;
                       setTimeout(() => {this.retryClass = '';}, 1500);
                   })
                   .catch(() => {
                       Messages.alert('You may have requested too many tokens. Please try again later.', 'Token request failed');
                       this.retryClass = '';
                   });
            }
        },

        watch: {
            providerId(value) {
                this.provider = this.providers[value];
                this.token = '';
                if(this.provider.request) {
                    this.requestToken();
                }
            }
        }
    };
</script>

<style lang="scss">
    body#body-user.pw-authorisation {
        #header {
            background-color: rgba(0, 0, 0, 0);
        }

        #appmenu li a::before {
            display : none;
        }

        #content-wrapper {
            padding-top : 0;
        }

        #app-navigation {
            transform  : translateX(-100%);
            z-index    : 1001;
            transition : transform ease-in-out 0.25s 0.25s;
        }

        #app-content {
            position         : fixed;
            top              : 0;
            left             : 0;
            bottom           : 0;
            right            : 0;
            margin-left      : 0 !important;
            display          : flex;
            align-items      : center;
            background-color : var(--color-primary);
            background-image : var(--image-login-background);
            opacity          : 1;
            transition       : opacity ease-in-out 0.25s, margin-left ease-in-out 0.25s 0.25s;
        }

        #authorize-window {
            margin     : 0 auto;
            width      : 300px;
            text-align : center;
            position   : relative;

            .fa-repeat {
                position  : absolute;
                font-size : 1.5rem;
                color     : var(--color-primary-text);
                padding   : 0.5rem;
                cursor    : pointer;
                right     : -2.5rem;
            }

            input {
                width     : 100%;
                padding   : 0.75rem;
                border    : none;
                font-size : 1rem;

                &[type=text],
                &[type=password] {
                    margin        : 0;
                    box-shadow    : 0 1px 0 transparentize($color-black, 0.9) inset !important;
                    border-radius : 0;

                    &:first-child {
                        box-shadow              : none;
                        border-top-left-radius  : 0.25rem;
                        border-top-right-radius : 0.25rem;
                    }

                    &:last-child {
                        border-bottom-left-radius  : 0.25rem;
                        border-bottom-right-radius : 0.25rem;
                    }
                }

                &[type=submit] {
                    margin-top : 1rem;
                    border     : 1px solid var(--color-primary-text);
                    color      : var(--color-primary-text);
                    background : var(--color-primary) url(/core/img/actions/confirm-white.svg?v=2) no-repeat right 16px center;
                }
            }

            select {
                width            : 100%;
                border           : none;
                font-size        : 1rem;
                box-shadow       : 0 1px 0 transparentize($color-black, 0.9) inset !important;
                border-radius    : 0;
                background-color : var(--color-main-background);
                padding          : 0.75rem 0.75rem 0.75rem 0.5rem;
                margin           : 0;
                height           : 2.75rem;

                &:first-child {
                    box-shadow              : none;
                    border-top-left-radius  : 0.25rem;
                    border-top-right-radius : 0.25rem;
                }

                &:last-child {
                    border-bottom-left-radius  : 0.25rem;
                    border-bottom-right-radius : 0.25rem;
                }
            }

            .login-error {
                color            : var(--color-error);
                margin-top       : 1rem;
                background-color : var(--color-box-shadow);
                border-radius    : var(--border-radius);
                padding          : 0.5rem;
                border           : 1px solid var(--color-error);
                position         : absolute;
                width            : 300px;
                animation        : shake 0.1s ease-in-out 0s 5;

                @keyframes shake {
                    0% {
                        margin-left : -5px;
                    }
                    33% {
                        margin-left : 0;
                    }
                    66% {
                        margin-left : 5px;
                    }
                    99% {
                        margin-left : 0;
                    }
                }
            }
        }

        &.pw-authorized {
            #app-navigation {
                transform : translateX(0);
            }
            #app-content {
                margin-left : 0;
                opacity     : 0;
            }
            #header {
                transition : background-color ease-in-out 0.25s 0.25s;
                background-color: var(--color-primary);
            }
        }
    }
</style>