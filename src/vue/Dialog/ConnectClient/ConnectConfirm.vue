<template>
    <div class="connect-client-confirm">
        <translate tag="div"
                   class="code-info"
                   say="Check if the codes below match with the ones provided by the device or app:"/>
        <div v-for="code in client.code" class="code-container">
            <span class="code">{{code}}</span>
        </div>
        <div class="client-name">
            <translate tag="label" for="client-name" say="Choose a name:"/>
            <input id="client-name" maxlength="64" v-model="label"/>
        </div>
        <div class="reject-timer">
            <progress max="100" @click="reject()" :value="progress"/>
            <translate say="The codes don't match" @click="reject()"/>
        </div>
        <translate tag="button" class="primary" say="Looks good" @click="confirm()"/>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';

    export default {
        components: {Translate},
        props     : {
            client: Object
        },
        data() {
            return {
                label   : this.client.label,
                interval: null,
                progress: 0
            };
        },

        mounted() {
            this.interval = setInterval(() => {this.timer();}, 1000);
        },

        beforeDestroy() {
            clearInterval(this.interval);
        },

        methods: {
            async reject() {
                await API.passLinkConnectReject();
                this.$emit('reject', {success: false, reason: 'reject', client: this.label});
                clearInterval(this.interval);
            },
            async confirm() {
                await API.passLinkConnectConfirm(this.label);
                this.$emit('confirm', {success: true, client: this.label});
                clearInterval(this.interval);
            },
            timer() {
                this.progress += 100 / this.client.time;
                if(this.progress >= 100) {
                    this.$emit('reject', {success: false, reason: 'timeout', client: this.label});
                    clearInterval(this.interval);
                }

                console.log(this.progress);
            }
        }
    };
</script>

<style lang="scss">
    #passlink-connect .connect-client-confirm {

        .client-name {
            display               : grid;
            margin                : 1rem;
            grid-template-columns : 1fr 2fr;

            label {
                align-self : center;
            }

            input {
                width : 100%;
            }
        }

        .code-info {
            margin      : 1rem;
            font-weight : bold;
            text-align  : center;
        }

        .code-container {
            margin     : .5rem 0;
            text-align : center;

            .code {
                border        : 1px solid var(--color-border-dark);
                border-radius : var(--border-radius-large);
                font-family   : var(--pw-mono-font-face);
                text-align    : center;
                font-size     : 3rem;
                display       : inline-block;
                line-height   : 3rem;
                padding       : .5rem;
            }
        }

        button {
            display : block;
            width   : calc(100% - 2rem);
            margin  : 0 auto 1rem;
        }

        .reject-timer {
            position : relative;
            width    : calc(100% - 2rem);
            margin   : 1rem auto .5rem;

            span {
                position    : absolute;
                top         : 0;
                width       : 100%;
                line-height : 2rem;
                text-align  : center;
                color       : var(--color-primary-text-dark);
                font-weight : bold;
                cursor      : pointer;
            }

            progress {
                height           : 2rem;
                border-radius    : var(--border-radius-pill);
                background-color : var(--color-error);

                &::-moz-progress-bar {
                    background    : var(--color-box-shadow);
                    border-radius : var(--border-radius-pill);
                    transition    : all 1s;
                }

                &::-webkit-progress-value {
                    background    : var(--color-box-shadow);
                    border-radius : var(--border-radius-pill);
                    transition    : all 1s;
                }
            }
        }
    }
</style>