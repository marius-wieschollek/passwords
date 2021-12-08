<template>
    <dialog-window ref="window" id="passlink-connect" title="Connect a new device" :has-controls="false">
        <div class="content">
            <connect-link :has-link="hasLink" :has-code="hasCode" :protocol="protocol" v-on:connect="connect($event)" v-if="step === 1"/>
            <connect-confirm :client="client"
                             v-on:reject="reject($event)"
                             v-on:confirm="confirm($event)"
                             v-if="step === 2"/>
            <connect-result :status="status" v-on:restart="restart" v-on:close="close" v-if="step === 3"/>
        </div>
    </dialog-window>
</template>

<script>
    import Translate from '@vc/Translate';
    import ConnectLink from '@vue/Dialog/ConnectClient/ConnectLink';
    import ConnectConfirm from '@vue/Dialog/ConnectClient/ConnectConfirm';
    import ConnectResult from '@vue/Dialog/ConnectClient/ConnectResult';
    import DialogWindow from "./DialogWindow";

    export default {
        components: {DialogWindow, ConnectLink, ConnectConfirm, ConnectResult, Translate},

        props: {
            hasLink : Boolean,
            hasCode : Boolean,
            protocol: String
        },

        data() {
            return {
                step  : 1,
                client: {},
                status: {}
            };
        },

        methods: {
            close() {
                this.$refs.window.closeWindow();
            },
            connect($event) {
                this.client = $event;
                this.step = 2;
            },
            reject($event) {
                this.status = $event;
                this.step = 3;
            },
            confirm($event) {
                this.status = $event;
                this.step = 3;
            },
            restart() {
                this.status = {};
                this.client = {};
                this.step = 1;
            }
        }
    };
</script>

<style lang="scss">
    #passlink-connect {
        .window {
            width : 360px;

            @media all and (max-width: 480px) {
                width: 100vw;
            }
        }
    }
</style>