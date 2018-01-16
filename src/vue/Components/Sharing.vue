<template>
    <div class="sharing-container" v-if="enabled">
        <input type="text"
               v-model="user"
               class="share-add-user"
               placeholder="Search username"
               @keypress="submitAction($event)"/>
        <ul class="shares" v-for="share in object.shares" :key="share.id" :data-share-id="share.id">
            <li class="share">
                <div class="options">
                    <translate icon="pencil" :class="{active: share.editable}" title="Toggle write permissions" @click="toggleEditable(share)"/>
                    <translate icon="share-alt" :class="{active: share.shareable}" title="Toggle share permissions" @click="toggleShareable(share)"/>
                    <translate icon="calendar" :class="{active: share.expires}" title="Set expiration date" @click="setExpires(share)"/>
                    <translate icon="trash" title="Delete share" @click="deleteAction(share)"/>
                </div>
                <img :src="share.receiver.icon" :alt="share.receiver.name">
                {{share.receiver.name}}
            </li>
        </ul>
    </div>
    <translate v-else>Sharing is not enabled</translate>
</template>

<script>
    import API from '@js/Helper/api';
    import Messages from '@js/Classes/Messages';
    import Translate from '@vc/Translate.vue';

    export default {
        components: {
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                user          : '',
                enabled       : false,
                partners      : [],
                partnersLoaded: false,
                object        : this.password,
                editable      : false,
                expires       : null,
                owner : {
                    id: $('head[data-user]').attr('data-user'),
                    name: $('head[data-user-displayname]').attr('data-user-displayname')
                }
            }
        },

        created() {
            API.getSharingInfo()
                .then((e) => {this.enabled = e.enabled;});
        },

        methods: {
            loadPartners() {
                this.partnersLoaded = true;

                API.findSharePartners()
                    .then((p) => {
                        this.partners = p;
                    });
            },
            addShare() {
                let share = {
                    password : this.object.id,
                    expires  : null,
                    editable : false,
                    shareable: true,
                    receiver : this.user
                };
                API.createShare(share).then(
                    (d) => {
                        share.id = d.id;
                        share.owner = this.owner;
                        share.receiver = {id: share.receiver, name: share.receiver.capitalize()};
                        this.object.shares[d.id] = API._processShare(share);
                        this.$forceUpdate();
                    }
                );
            },
            toggleEditable(share) {
                share.editable = !share.editable;

                API.updateShare(share);
                this.$forceUpdate();
            },
            toggleShareable(share) {
                share.shareable = !share.shareable;

                API.updateShare(share);
                this.$forceUpdate();
            },
            setExpires(share) {
                let date = share.expires ? new Date(share.expires):new Date(),
                    form = {
                        expires: {
                            value: date.toLocaleDateString(),
                            type : 'date',
                            label: 'Date'
                        }
                    };

                Messages.form(form, 'Share expiration date', 'Choose an expiration date or leave empty to share forever')
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
                        API.updateShare(share);
                        this.$forceUpdate();
                    });
            },
            deleteAction(share) {
                API.deleteShare(share.id);
                delete this.object.shares[share.id];
                this.$forceUpdate();
            },
            submitAction($event) {
                if($event.keyCode === 13) {
                    this.addShare();
                }
            }
        },

        watch: {
            password: function(value) {
                this.object = value;
                this.$forceUpdate();
            },
            user    : function(value) {
                if(!this.partnersLoaded) { this.loadPartners(); }
            }
        }
    }
</script>

<style lang="scss">
    .share-add-user {
        width : 100%;
    }

    .shares {
        margin-top : 5px;

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

            img {
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
    }
</style>