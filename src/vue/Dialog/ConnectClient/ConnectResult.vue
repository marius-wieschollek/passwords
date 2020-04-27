<template>
    <div class="connect-client-result">
        <translate tag="div" :class="cssClass" :icon="icon" :say="text"/>
        <translate tag="button" :say="closeText" @click="close()"/>
        <translate tag="button" class="primary" :say="restartText" @click="restart()"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';

    export default {
        components: {Translate},
        props     : {
            status: Object
        },

        computed: {
            cssClass() {
                return `message${this.status.success ? '':' error'}`;
            },
            text() {
                if(this.status.success) return 'Connection successful';
                if(this.status.reason === 'reject') return 'You cancelled the connection';
                if(this.status.reason === 'timeout') return 'You did not confirm the connection in time';
            },
            icon() {
                return this.status.success ? 'check-circle':'times-circle';
            },
            closeText() {
                return this.status.success ? 'I\'m done':'Don\'t try again';
            },
            restartText() {
                return this.status.success ? 'Connect something else':'Try again';
            }
        },

        methods: {
            async close() {
                this.$emit('close');
            },
            async restart() {
                this.$emit('restart');
            }
        }
    };
</script>

<style lang="scss">
    #passlink-connect .connect-client-result {
        .message {
            text-align  : center;
            margin      : 0 1rem 2rem;
            font-weight : bold;

            i {
                display   : block;
                font-size : 8rem;
                margin    : 2rem 3rem;
                color     : var(--color-success);
            }

            &.error i {
                color : var(--color-error);
            }
        }

        button {
            display    : block;
            margin     : 1rem;
            text-align : center;
            width      : calc(100% - 2rem);
        }
    }
</style>