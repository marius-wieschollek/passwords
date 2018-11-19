<template>
    <li class="share" :title="getTitle">
        <div class="options">
            <translate icon="pencil" :class="{active: share.editable}" title="Toggle write permissions" @click="toggleEditable(share)"/>
            <translate icon="share-alt" :class="{active: share.shareable}" title="Toggle share permissions" @click="toggleShareable(share)"/>
            <translate icon="calendar" :class="{active: share.expires}" :title="getExpirationDateTitle" @click="setExpires(share)"/>
            <translate icon="trash" title="Stop sharing" @click="deleteAction(share)"/>
        </div>
        <img :src="share.receiver.icon" :alt="share.receiver.name" class="avatar">
        <div v-if="share.updatePending" class="loading"></div>
        {{share.receiver.name}}
    </li>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';

    export default {
        components: {
            Translate
        },

        props: {
            share: {
                type: Object
            }
        },

        computed: {
            getTitle() {
                if(this.share.updatePending) {
                    return Localisation.translate('Some data is waiting to be synchronized');
                }
                return undefined;
            },
            getExpirationDateTitle() {
                if(!this.share.expires) return 'Set expiration date';
                return Localisation.translate('Expires {date}', {date: Localisation.formatDate(this.share.expires)});
            }
        },

        methods: {
            toggleEditable(share) {
                share.editable = !share.editable;
                this.update(share);
            },
            toggleShareable(share) {
                share.shareable = !share.shareable;
                this.update(share);
            },
            setExpires(share) {
                let value = share.expires ? new Date(share.expires).toISOString().substring(0, 10):null,
                    form = {
                        expires: {
                            value: value,
                            type : 'date',
                            label: 'Date'
                        }
                    };

                Messages.form(form, 'Set expiration date', 'Choose expiration date')
                    .then((data) => {
                        let expires = data.expires;
                        if(expires.length === 0) {
                            expires = null;
                        } else {
                            expires = new Date(data.expires.replace(/([0-9]+)\.([0-9]+)\.([0-9]+)/g, '$2/$1/$3'));
                            if(expires < new Date()) {
                                Messages.alert('Please choose a date in the future', 'Invalid date');
                                return;
                            }
                        }

                        share.expires = expires;
                        this.update(share);
                    });
            },
            async deleteAction(share) {
                await API.deleteShare(share.id);
                this.$emit('delete', {id: share.id});
            },
            update(share) {
                share.updatePending = true;
                API.updateShare(share);
                this.$emit('update', {id: share.id});
                this.$forceUpdate();
            }
        },
    };
</script>

<style lang="scss">
    .share {
        padding       : 5px 20px 5px 5px;
        border-bottom : 1px solid $color-grey-lighter;
        display       : flex;
        line-height   : 32px;
        position      : relative;

        &:last-child {
            border-bottom : none;
        }

        &:hover {
            background-color : darken($color-white, 3);
        }

        .loading {
            position : absolute;
            top      : 19px;
            left     : 19px;
            cursor   : help;

            &:after {
                height           : 32px;
                width            : 32px;
                background-color : transparentize($color-white, 0.75);
                animation         : ShareUpdating 0.8s infinite ease-in-out, rotate 0.8s infinite linear;

                @keyframes ShareUpdating {
                    0% {background-color : transparent}
                    50% {background-color : transparentize($color-white, 0.25)}
                    100% {background-color : transparent}
                }
            }
        }

        img.avatar {
            border-radius : 16px;
            margin-right  : 5px;
            height        : 32px;
        }

        .options {
            position : absolute;
            right    : 2px;

            span {
                line-height : 32px;
                padding     : 0 5px;
                display     : inline-block;
                cursor      : pointer;
                opacity     : 0.5;
                font-size   : larger;

                &:hover,
                &.active {
                    opacity : 1;
                }
            }
        }
    }
</style>