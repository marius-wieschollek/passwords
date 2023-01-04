<template>
    <div slot="details" class="details passwords-password-info">
        <detail-field v-for="(field, index) in getCustomFields"
                      :key="index"
                      :label="field.label"
                      :type="field.type"
                      :value="field.value"/>

        <translate tag="div" say="Statistics" class="header"/>
        <translate tag="div" say="Created on"><span>{{ getDateTime(password.created) }}</span></translate>
        <translate tag="div" say="Last updated"><span>{{ getDateTime(password.edited) }}</span></translate>
        <detail-field label="Id" type="text" :value="password.id"/>
        <translate tag="div" say="Folder" v-if="typeof password.folder !== 'string'">
            <router-link :to="folderRoute" class="link">
                {{password.folder.label}}
            </router-link>
        </translate>
        <translate tag="div" say="Revisions">
            <translate say="{count} revisions" :variables="{count:countRevisions}"/>
        </translate>
        <translate tag="div" say="Shares">
            <translate say="{count} shares" :variables="{count:countShares}"/>
        </translate>

        <translate tag="div" say="Security" class="header"/>
        <translate tag="div" say="Status">
            <router-link :to="hashSearchRoute" class="security-link" v-if="password.statusCode === 'DUPLICATE'">
                <translate :say="getSecurityStatus" :class="getSecurityClass"/>
            </router-link>
            <translate :say="getSecurityStatus" :class="getSecurityClass" v-else/>
        </translate>
        <translate tag="div" say="Encryption applied on server" title="The encryption applied by the server before storing the data in the database">
            <translate :say="getSseType"/>
        </translate>
        <translate tag="div" say="Encryption applied on client" title="The encryption applied by the client before sending the data to the server">
            <translate :say="getCseType"/>
        </translate>
        <detail-field label="SHA-1" type="text" :value="hash"/>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Localisation from '@js/Classes/Localisation';
    import SettingsService from '@js/Services/SettingsService';
    import DetailField from '@vc/Sidebar/PasswordSidebar/Details/DetailField';

    export default {
        components: {
            Web,
            DetailField,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                showHiddenFields: SettingsService.get('client.ui.custom.fields.show.hidden'),
                hash: ''
            };
        },

        mounted() {
            this.computeHash();
        },

        computed: {
            countShares() {
                let count = 0;
                for(let i in this.password.shares) {
                    if(this.password.shares.hasOwnProperty(i)) count++;
                }
                return count;
            },
            countRevisions() {
                let count = 0;
                for(let i in this.password.revisions) {
                    if(this.password.revisions.hasOwnProperty(i)) count++;
                }
                return count;
            },
            getSecurityClass() {
                let status = ['secure', 'weak', 'breached', 'unknown'];

                return status[this.password.status];
            },
            getSecurityStatus() {
                if(this.password.status === 1) return `Weak (${this.password.statusCode.toLowerCase().capitalize()})`;
                if(this.password.status === 3) return `Unknown (${this.password.statusCode.toLowerCase().capitalize()})`;

                return this.getSecurityClass.capitalize();
            },
            folderRoute() {
                return {name: 'Folders', params: {folder: this.password.folder.id}}
            },
            hashSearchRoute() {
                return {name: 'Search', params: {query: btoa('hash:'+this.password.hash)}}
            },
            getCustomFields() {
                let fields       = [],
                    customFields = this.password.customFields;

                fields.push({label: Localisation.translate('Name'), value: this.password.label});
                if(this.password.username) {
                    fields.push({
                                    label: Localisation.translate('Username'),
                                    value: this.password.username
                                });
                }
                fields.push({label: Localisation.translate('Password'), value: this.password.password, type: 'secret'});
                if(this.password.url) {
                    fields.push({
                                    label: Localisation.translate('Website'),
                                    value: this.password.url,
                                    type : 'url'
                                });
                }

                for(let i = 0; i < customFields.length; i++) {
                    if(this.showHiddenFields || customFields[i].type !== 'data') fields.push(customFields[i]);
                }

                return fields;
            },
            getSseType() {
                let encryption = 'No encryption';
                if(this.password.sseType === 'SSEv1r1') encryption = 'Simple encryption (Gen. 1)';
                if(this.password.sseType === 'SSEv1r2') encryption = 'Simple encryption (Gen. 2)';
                if(this.password.sseType === 'SSEv2r1') encryption = 'Advanced encryption (SSE V2)';

                return Localisation.translate(encryption)
            },
            getCseType() {
                let encryption = 'No encryption';
                if(this.password.cseType === 'CSEv1r1') encryption = 'Encryption with libsodium';

                return Localisation.translate(encryption)
            }
        },
        methods : {
            getDateTime(date) {
                return Localisation.formatDateTime(date);
            },
            async computeHash() {

                let hash = this.password.hash;

                if(hash.length === 40) {
                    this.hash = hash
                } else {
                    this.hash = await API.getHash(this.password.password, 'SHA-1', 40)
                }
            }
        },
        watch: {
            password() {
                this.computeHash();
            }
        }
    };
</script>

<style lang="scss">
    .passwords-password-info {
        padding-top : 10px;

        div:not(.header) {
            font-size     : 0.8rem;
            font-style    : italic;
            margin-bottom : 5px;
            color         : var(--color-text-maxcontrast);

            a,
            span {
                display    : block;
                font-style : normal;
                font-size  : 1rem;
                color      : var(--color-text-lighter);
                text-align : right;
                cursor     : text;
                word-wrap  : break-word;

                &.secret {
                    cursor : pointer;

                    &.visible {
                        font-family : var(--pw-mono-font-face);
                    }
                }

                &.security-link {
                    span {
                        cursor: pointer;
                    }

                    &:hover {
                        text-decoration-color: var(--color-warning);
                    }
                }

                &.secure {color : var(--color-success);}
                &.weak {color : var(--color-warning);}
                &.breached {color : var(--color-error);}
            }

            a {
                cursor : pointer;

                &:hover {
                    text-decoration : underline;
                }
            }
        }

        .header {
            margin-top  : 20px;
            font-size   : 1rem;
            font-weight : bold;
            color       : var(--color-text-light);
        }
    }
</style>