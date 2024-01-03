<template>
    <div>
        {{label}}
        <web :href="getLink" v-if="['url','email','file'].indexOf(type) !== -1">{{ getLinkLabel }}</web>
        <span @mouseenter="showValue=true"
              @mouseout="showValue=false"
              :class="getSecretClass"
              v-if="type === 'secret'"
              @click="copyValue">{{ getSecretValue }}</span>
        <span v-if="type === 'text'" @click="copyValue">{{ value }}</span>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import Utility from '@js/Classes/Utility';
    import SettingsService from '@js/Services/SettingsService';
    import MessageService from "@js/Services/MessageService";

    export default {
        components: {
            Web
        },
        props     : {
            label: {
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
                if (this.value === '') return '';
                return this.showValue ? this.value:''.padStart(12, '⚫');
            },
            getSecretClass() {
                return this.showValue ? 'secret visible':'secret';
            },
            getLinkLabel() {
                if (this.type === 'file' && this.value.indexOf('/') !== -1) {
                    return this.value.substr(this.value.lastIndexOf(
                        '/') + 1);
                }
                return this.value;
            },
            getLink() {
                if (this.type === 'email') return `mailto:${this.value}`;
                if (this.type === 'file') return SettingsService.get('server.baseUrl.webdav') + this.value.substr(1);
                return this.value;
            }
        },
        methods   : {
            copyValue() {
                let message = 'Error copying {element} to clipboard';
                if (Utility.copyToClipboard(this.value)) message = '{element} was copied to clipboard';
                MessageService.notification([message, {element: this.label}]);
            }
        }
    };
</script>