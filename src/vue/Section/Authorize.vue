<template>
    <div id="app-content" :style="getStyle">

        <form id="authorize-window" @submit="submitLogin">
            <div class="passwords">
                <input type="password" placeholder="Password" v-model="password" required>
                <input type="password" placeholder="Token" v-model="token" required>
            </div>
            <input type="submit" value="Login">
        </form>
    </div>
</template>

<script>
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {

        data() {
            return {
                password: '',
                token: ''
            }
        },
        created() {
            document.body.classList.add('pw-authorisation');
        },

        beforeDestroy() {
            document.body.classList.remove('pw-authorisation');
        },

        computed: {
            getStyle() {
                console.log(SettingsManager.get('server.theme.background'));
                return {
                    'background-color': SettingsManager.get('server.theme.color'),
                    'background-image': `url(${SettingsManager.get('server.theme.background')})`
                };
            }
        },

        methods: {
            submitLogin() {
                this.$router.push({path:'/'})
            }
        }
    };
</script>

<style lang="scss">
    body.pw-authorisation {
        #header {
            background : rgba(0, 0, 0, 0) none !important;
        }

        #app-navigation {
            display : none !important;
        }

        #app-content {
            position    : fixed;
            top         : 0;
            left        : 0;
            bottom      : 0;
            right       : 0;
            display     : flex;
            align-items : center;
        }

        #authorize-window {
            margin     : 0 auto;
            width      : 300px;
            text-align : center;

            input {
                width: 100%;
                padding: 0.75rem;
                border: none;
                font-size: 1rem;

                &[type=password] {
                    margin: 0;
                    box-shadow: 0 1px 0 #0000001a inset;

                    &:first-of-type {
                        box-shadow: none;
                        border-radius: 0.25rem 0.25rem 0 0;
                    }

                    &:last-of-type {
                        border-radius: 0 0 0.25rem 0.25rem;
                    }
                }

                &[type=submit] {
                    margin-top: 1rem;
                    border: 1px solid #fff;
                    color: #fff;
                    background-color: #00000080 !important;
                    background-image: url(/core/img/actions/confirm-white.svg?v=2);
                    background-position: right 16px center;
                    background-repeat: no-repeat;
                }
            }
        }
    }
</style>