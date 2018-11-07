<template>
    <div>
        {{name}}
        <web :href="getLink" v-if="['url','email','file'].indexOf(type) !== -1">{{ getLinkLabel }}</web>
        <span @mouseover="showValue=true" @mouseout="showValue=false" class="secret" v-if="type === 'secret'" @click="copyValue">{{ getSecretValue }}</span>
        <span v-if="type === 'text'" @click="copyValue">{{ value }}</span>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import Utility from '@js/Classes/Utility';
    import Messages from '@js/Classes/Messages';
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {
        components: {
            Web
        },
        props     : {
            name : {
                type: String
            },
            type : {
                type     : String,
                'default': 'text'
            },
            value: {
                type: String
            }
        },
        data() {
            return {
                showValue: false
            };
        },
        computed  : {
            getSecretValue() {
                return this.showValue ? this.value:''.padStart(this.value.length, '*');
            },
            getLinkLabel() {
                if(this.type === 'file' && this.value.indexOf('/') !== -1) return this.value.substr(this.value.lastIndexOf('/') + 1);
                return this.value;
            },
            getLink() {
                if(this.type === 'email') return `mailto:${this.value}`;
                if(this.type === 'file') return SettingsManager.get('server.baseUrl.webdav') + this.value.substr(1);
                return this.value;
            }
        },
        methods   : {
            copyValue() {
                let message = 'Error copying {element} to clipboard';
                if(Utility.copyToClipboard(this.value)) message = '{element} was copied to clipboard';
                Messages.notification([message, {element: this.name}]);
            }
        }
    };
</script>