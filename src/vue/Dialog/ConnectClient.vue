<template>
    <div class="background" id="passlink-connect">
        <div class="window">
            <div class="title">
                <i class="fa fa-times close" @click="close"></i>
                <translate say="Connect a new device"/>
            </div>
            <div class="content">
                <connect-link :has-link="hasLink" :has-code="hasCode" :protocol="protocol" v-on:connect="connect($event)" v-if="step === 1"/>
                <connect-confirm :client="client"
                                 v-on:reject="reject($event)"
                                 v-on:confirm="confirm($event)"
                                 v-if="step === 2"/>
                <connect-result :status="status" v-on:restart="restart" v-on:close="close" v-if="step === 3"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import ConnectLink from '@vue/Dialog/ConnectClient/ConnectLink';
    import ConnectConfirm from '@vue/Dialog/ConnectClient/ConnectConfirm';
    import ConnectResult from '@vue/Dialog/ConnectClient/ConnectResult';

    export default {
        components: {ConnectLink, ConnectConfirm, ConnectResult, Translate},

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
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
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
        }
    }
</style>