<template>
    <div class="item-details">
        <i class="fa fa-times" @click="closeDetails()"></i>
        <preview :image="object.preview" :icon="object.icon" :link="object.url" :host="object.website"/>
        <div class="title" :title="object.label">
            <img class="icon" :src="object.icon" alt="">
            <h3>{{ object.label }}</h3>
        </div>
        <div class="infos">
            <i class="fa fa-star favorite" :class="{ active: object.favorite }" @click="favoriteAction($event)"></i>
            <span class="date">{{ object.edited.toLocaleDateString() }}</span>
            <tags :password="object"/>
        </div>
        <tabs :tabs="getTabs">
            <pw-details slot="details" :password="object"/>
            <notes slot="notes" :password="object"/>
            <div slot="share">
                <tabs :tabs="getSharingTabs">
                    <sharing slot="nextcloud" :password="object" class="password-share-nextcloud"/>
                    <qr-code slot="qrcode" :password="object"/>
                </tabs>
            </div>
            <revisions slot="revisions" :password="object"/>
        </tabs>
    </div>
</template>

<script>
    import Tabs from '@vc/Tabs';
    import Tags from '@vc/Tags';
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Events from '@js/Classes/Events';
    import Notes from '@vue/Details/Password/Notes';
    import QrCode from '@vue/Details/Password/QrCode';
    import Preview from '@vue/Details/Password/Preview';
    import PwDetails from '@vue/Details/Password/Details';
    import Revisions from '@vue/Details/Password/Revisions';
    import SettingsService from '@js/Services/SettingsService';
    import PasswordManager from '@js/Manager/PasswordManager';
    import Sharing from '@vue/Details/Password/Sharing/Sharing';

    export default {
        components: {
            Tabs,
            Tags,
            Notes,
            QrCode,
            Sharing,
            Preview,
            PwDetails,
            Revisions,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                object: this.password
            };
        },

        created() {
            Events.on('password.changed', this.processEvent);
            this.refreshView();
        },

        beforeDestroy() {
            Events.off('password.changed', this.processEvent);
        },

        computed: {
            getTabs() {
                if(this.object.notes.length !== 0) {
                    return {details: 'Details', notes: 'Notes', share: 'Share', revisions: 'Revisions'};
                }
                return {details: 'Details', share: 'Share', revisions: 'Revisions'};
            },
            getSharingTabs() {
                if(SettingsService.get('server.sharing.enabled')) {
                    return {nextcloud: 'Share', qrcode: 'QR Code'};
                }
                return {qrcode: 'QR Code'};
            }
        },

        methods: {
            favoriteAction($event) {
                $event.stopPropagation();
                this.object.favorite = !this.object.favorite;
                PasswordManager.updatePassword(this.object)
                               .catch(() => { this.object.favorite = !this.object.favorite; });
            },
            closeDetails() {
                this.$parent.detail = {
                    type   : 'none',
                    element: null
                };
            },
            async refreshView() {
                let password = await API.showPassword(this.object.id, 'model+folder+shares+tags+revisions');
                if(this.password.id === password.id) {
                    if(password.trashed && this.$route.name !== 'Trash' || !password.trashed && this.$route.name === 'Trash') {
                        this.closeDetails();
                    } else {
                        this.object = password;
                    }
                }
            },
            processEvent(event) {
                if(event.object.id === this.object.id) {
                    if(event.object.trashed && this.$route.name !== 'Trash' || !event.object.trashed && this.$route.name === 'Trash') {
                        this.closeDetails();
                    } else {
                        this.refreshView();
                    }
                }
            }
        },

        watch: {
            password(value) {
                if(this.object.id !== value.id) {
                    this.$el.offsetParent.scrollTop = 0;
                }

                this.object = value;
                if(!value.hasOwnProperty('revisions')) this.refreshView();
            }
        }
    };
</script>

<style lang="scss">
    .item-details {
        & > .fa.fa-times:nth-child(1) {
            position  : absolute;
            top       : 5px;
            right     : 5px;
            cursor    : pointer;
            padding   : 0.75rem;
            font-size : 1.3rem;
            color     : var(--color-main-text);
            z-index   : 1;

            &:hover {
                text-shadow : 0 0 2px var(--color-main-background);
            }
        }

        .title {
            margin                : 0;
            padding               : 15px 15px 2px 15px;
            line-height           : 32px;
            grid-template-columns : 32px auto;
            grid-column-gap       : 10px;
            display               : grid;

            h3 {
                white-space   : nowrap;
                text-overflow : ellipsis;
                overflow      : hidden;
                font-size     : 1rem;
                margin        : 0;
                font-weight   : 300;
                padding       : 0;
            }

            .icon {
                border-radius : var(--border-radius);
            }
        }

        .infos {
            padding : 0 15px 20px;
            color   : $color-grey-dark;

            .favorite {
                cursor : pointer;

                &:hover,
                &.active {
                    color : var(--color-warning);
                }
            }

            .tags-container {
                position    : static;
                display     : inline;
                color       : var(--color-text-light);
                margin-left : 3px;
            }
        }

        > .tab-container {
            padding : 0 15px 15px;
        }

        @media (max-width : $mobile-width) {
            .title {
                margin-bottom : 1rem;
            }
        }
    }
</style>