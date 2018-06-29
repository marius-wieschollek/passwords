<template>
    <div slot="details" class="details">
        <detail-field v-for="(field, index) in getCustomFields" :key="index" :name="field.name" :type="field.type" :value="field.value" />

        <translate tag="div" say="Statistics" class="header"/>
        <translate tag="div" say="Created on"><span>{{ getDateTime(password.created) }}</span></translate>
        <translate tag="div" say="Last updated"><span>{{ getDateTime(password.edited) }}</span></translate>
        <translate tag="div" say="Revisions">
            <translate say="{count} revisions" :variables="{count:countRevisions}"/>
        </translate>
        <translate tag="div" say="Shares">
            <translate say="{count} shares" :variables="{count:countShares}"/>
        </translate>

        <translate tag="div" say="Security" class="header"/>
        <translate tag="div" say="Status">
            <translate :say="getSecurityStatus" :class="getSecurityClass.toLowerCase()"/>
        </translate>
        <translate tag="div" say="SHA1 Hash"><span>{{ password.hash }}</span></translate>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import DetailField from '@vue/Details/Password/DetailField';
    import Translate from '@vc/Translate';
    import Localisation from '@js/Classes/Localisation';
    import SettingsManager from '@js/Manager/SettingsManager';

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
                showHiddenFields: SettingsManager.get('client.ui.custom.fields.show.hidden')
            };
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
                let status = ['secure', 'weak', 'breached'];

                return status[this.password.status];
            },
            getSecurityStatus() {
                if(this.password.status === 1) return `Weak (${this.password.statusCode.toLowerCase().capitalize()})`;

                return this.getSecurityClass.capitalize();
            },
            getCustomFields() {
                let fields = [],
                    customFields = this.password.customFields;

                fields.push({name: Localisation.translate('Name'), value:this.password.label});
                if(this.password.username) fields.push({name: Localisation.translate('Username'), value:this.password.username});
                fields.push({name: Localisation.translate('Password'), value:this.password.password, type: 'secret'});
                if(this.password.url) fields.push({name: Localisation.translate('Website'), value:this.password.url, type:'url'});


                for(let name in customFields) {
                    if(!customFields.hasOwnProperty(name) || (!this.showHiddenFields && name.substr(0, 1) === '_')) continue;

                    fields.push(
                        {
                            name,
                            type : customFields[name].type,
                            value: customFields[name].value
                        }
                    );
                }

                return fields;
            }
        },
        methods : {
            getDateTime(date) {
                return Localisation.formatDateTime(date);
            }
        }
    };
</script>

<style lang="scss">
    .item-details .details {
        padding-top : 10px;

        div:not(.header) {
            font-size     : 0.9em;
            font-style    : italic;
            margin-bottom : 5px;
            color         : $color-grey-darker;

            a,
            span {
                display    : block;
                font-style : normal;
                font-size  : 1.3em;
                color      : $color-black-light;
                text-align : right;
                cursor     : text;

                &.secret {
                    cursor : pointer;

                    &:hover {
                        font-family : 'Lucida Console', 'Lucida Sans Typewriter', 'DejaVu Sans Mono', monospace;
                    }
                }

                &.secure {color : $color-green;}
                &.weak {color : $color-yellow;}
                &.breached {color : $color-red;}
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
            font-size   : 1.3em;
            font-weight : bold;
            color       : $color-black-light;
        }
    }
</style>