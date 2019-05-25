<template>
    <div class="background" id="passwords-revision-details">
        <div class="window">
            <div class="title">
                <i class="fa fa-times close" @click="closeWindow()"></i>
                <span>{{revision.label}}</span>
            </div>
            <div class="content">
                <div class="details">
                    <detail-field v-for="(field, index) in getFields"
                                  :key="index"
                                  :label="field.label"
                                  :type="field.type"
                                  :value="field.value"/>
                </div>
            </div>
            <div class="controls" v-if="canRestore">
                <translate tag="button" type="submit" say="Restore" @click="restoreAction()"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import Localisation from '@js/Classes/Localisation';
    import SettingsManager from '@js/Manager/SettingsManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import DetailField from '@vue/Details/Password/DetailField';

    export default {
        components: {DetailField, Translate},
        props     : {
            password: {
                type: Object
            },
            revision: {
                type: Object
            }
        },
        computed  : {
            getFields() {
                let fields           = [],
                    customFields     = this.revision.customFields,
                    showHiddenFields = SettingsManager.get('client.ui.custom.fields.show.hidden');

                fields.push({label: Localisation.translate('Name'), value: this.revision.label});
                fields.push({
                                label: Localisation.translate('Created on'),
                                value: Localisation.formatDateTime(this.revision.created)
                            });

                let favorite = this.revision.favorite ? 'yes':'no';
                fields.push({label: Localisation.translate('Favorite'), value: Localisation.translate(favorite)});

                if(this.revision.username) {
                    fields.push({
                                    label: Localisation.translate('Username'),
                                    value: this.revision.username
                                });
                }
                fields.push({label: Localisation.translate('Password'), value: this.revision.password, type: 'secret'});
                if(this.revision.url) {
                    fields.push({
                                    label: Localisation.translate('Website'),
                                    value: this.revision.url,
                                    type : 'url'
                                });
                }

                for(let i = 0; i < customFields.length; i++) {
                    if(showHiddenFields || customFields[i].type !== 'data') fields.push(customFields[i]);
                }

                let status = ['secure', 'weak', 'breached'][this.revision.status].capitalize();
                if(this.revision.status === 1) status = `Weak (${this.revision.statusCode.toLowerCase().capitalize()})`;
                fields.push({label: Localisation.translate('Status'), value: Localisation.translate(status)});

                let sseType = 'No encryption';
                if(this.revision.sseType === 'SSEv1r1') sseType = 'Simple encryption (Gen. 1)';
                if(this.revision.sseType === 'SSEv1r2') sseType = 'Simple encryption (Gen. 2)';
                if(this.revision.sseType === 'SSEv2r1') sseType = 'Advanced encryption (SSE V2)';
                fields.push({label: Localisation.translate('Encryption on server'), value: Localisation.translate(sseType)});

                let cseType = 'No encryption';
                if(this.revision.cseType === 'CSEv1r1') cseType = 'Encryption with libsodium';
                fields.push({label: Localisation.translate('Encryption on client'), value: Localisation.translate(cseType)});
                fields.push({label: Localisation.translate('Created by'), value: this.getClientLabel(this.revision.client)});

                return fields;
            },
            canRestore() {
                return this.password.revision !== this.revision.id
            }
        },
        methods   : {
            closeWindow() {
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
            },
            restoreAction() {
                PasswordManager.restoreRevision(this.password, this.revision)
                    .then(() => {this.closeWindow()})
                    .catch(console.error);
            },
            getClientLabel(client) {
                if(client.substr(0, 8) === 'CLIENT::') {
                    return Localisation.translate(client);
                }

                return client;
            }
        }
    }
</script>

<style lang="scss">
    #app-popup #passwords-revision-details.background .window {
        width      : 100%;
        max-width  : 450px;
        max-height : 88%;
        overflow   : auto;

        .title {
            span {
                white-space   : nowrap;
                display       : block;
                text-overflow : ellipsis;
                overflow      : hidden;
            }
        }

        .content {
            padding : 0.5rem 0;

            .details {
                div {
                    font-size     : 1.15em;
                    font-style    : italic;
                    margin-bottom : 5px;
                    color         : var(--color-text-maxcontrast);
                    padding       : 0.25rem 0.5rem;

                    a,
                    span {
                        display    : block;
                        font-style : normal;
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

                    &:hover {
                        background-color : var(--color-background-dark);
                    }
                }
            }
        }

        .controls {
            display          : flex;
            align-items      : end;
            padding          : 0.5rem;
            background-color : var(--color-main-background);
            position         : sticky;
            bottom           : 0;
        }

        @media (max-width : $width-extra-small) {
            max-height : 100%;
        }
    }
</style>