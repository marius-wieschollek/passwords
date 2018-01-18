<template>
    <div class="item-details">
        <i class="fa fa-times" @click="closeDetails()"></i>
        <image-container v-if="!isMobile" :image="object.image" :link="object.url" :title="object.title"/>
        <h3 class="title" :style="{'background-image': 'url(' + object.icon + ')'}">{{ object.label }}</h3>
        <div class="infos">
            <i class="fa fa-star favourite" :class="{ active: object.favourite }" @click="favouriteAction($event)"></i>
            <span class="date">{{ object.edited.toLocaleDateString() }}</span>
            <tags :password="object"/>
        </div>
        <tabs :tabs="{details: 'Details', notes: 'Notes', share: 'Share', revisions: 'Revisions'}" :uuid="object.id">
            <div slot="details" class="details">
                <translate tag="div" say="Name"><span>{{ object.label }}</span></translate>
                <translate tag="div" say="Username"><span>{{ object.username }}</span></translate>
                <translate tag="div" say="Password">
                    <span @mouseover="showPw=true" @mouseout="showPw=false" class="password">{{ showPassword }}</span>
                </translate>
                <translate tag="div" say="Website"><a :href="object.url" target="_blank">{{ object.url }}</a></translate>

                <translate tag="div" say="Statistics" class="header"/>
                <translate tag="div" say="Created on"><span>{{ object.created.toLocaleDateString() }} {{ object.created.toLocaleTimeString() }}</span></translate>
                <translate tag="div" say="Last updated"><span>{{ object.edited.toLocaleDateString() }} {{ object.edited.toLocaleTimeString() }}</span></translate>
                <translate tag="div" say="Revisions">
                    <translate say="{count} revisions" :variables="{count:countRevisions}"/>
                </translate>
                <translate tag="div" say="Shares">
                    <translate say="{count} shares" :variables="{count:countShares}"/>
                </translate>

                <translate tag="div" say="Security" class="header"/>
                <translate tag="div" say="Status">
                    <translate :say="getSecurityStatus" :class="getSecurityStatus.toLowerCase()"/>
                </translate>
                <translate tag="div" say="SHA1 Hash"><span>{{ object.hash }}</span></translate>
            </div>
            <div slot="notes" class="notes">
                <textarea id="password-details-notes">{{ object.notes }}</textarea>
            </div>
            <div slot="share">
                <tabs :tabs="{nextcloud: 'Share', qrcode: 'QR Code'}" :uuid="object.id">
                    <div slot="nextcloud" class="password-share-nextcloud">
                        <sharing :password="object"/>
                    </div>
                    <div slot="qrcode" class="password-share-qrcode">
                        <select id="password-details-qrcode" @change="changeQrCode($event)">
                            <translate tag="option" value="login" v-if="object.username">Username</translate>
                            <translate tag="option" value="password" selected>Password</translate>
                            <translate tag="option" value="url" v-if="object.url">Website</translate>
                        </select>
                        <qr-code :text="qrcode.text" :color="qrcode.color" :bgColor="qrcode.bgColor" :size="256" errorLevel="L"/>
                    </div>
                </tabs>
            </div>
            <div slot="revisions">
                <ul class="revision-list">
                    <li class="revision"
                        v-for="revision in getRevisions"
                        :key="revision.id"
                        :style="{'background-image': 'url(' + revision.icon + ')'}">
                        <span>
                            {{ revision.label }}<br>
                            <span class="time">
                                {{ revision.created.toLocaleDateString() }} {{ revision.created.toLocaleTimeString() }}
                            </span>
                        </span>
                        <translate icon="undo" title="Restore revision" @click="restoreAction(revision)" v-if="revision.id !== object.revision"/>
                    </li>
                </ul>
            </div>
        </tabs>
    </div>
</template>

<script>
    import $ from "jquery";
    import Tabs from '@vc/Tabs.vue';
    import Tags from '@vc/Tags.vue';
    import API from '@js/Helper/api';
    import SimpleMDE from 'simplemde';
    import Sharing from '@vc/Sharing.vue';
    import Events from "@js/Classes/Events";
    import QrCode from 'vue-qrcode-component'
    import Utility from "@js/Classes/Utility";
    import Translate from '@vc/Translate.vue';
    import ImageContainer from '@vc/ImageContainer.vue';
    import ThemeManager from '@js/Manager/ThemeManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Tabs,
            Tags,
            QrCode,
            Sharing,
            Translate,
            ImageContainer
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                qrcode: {
                    color  : ThemeManager.getColor(),
                    bgColor: ThemeManager.getContrastColor(),
                    text   : this.password.password
                },
                object: this.password,
                showPw: false
            }
        },

        mounted() {
            let simplemde = new SimpleMDE(
                {
                    element                : document.getElementById('password-details-notes'),
                    toolbar                : false,
                    autoDownloadFontAwesome: false,
                    spellChecker           : false,
                    status                 : false,
                    initialValue           : this.object.notes
                });
            simplemde.togglePreview();
        },

        created() {
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView)
        },

        computed: {
            getRevisions() {
                return Utility.sortApiObjectArray(this.object.revisions, 'created', false)
            },
            countShares() {
                let count = 0;
                for(let i in this.object.shares) {
                    if(this.object.shares.hasOwnProperty(i)) count++;
                }
                return count;
            },
            countRevisions() {
                let count = 0;
                for(let i in this.object.revisions) {
                    if(this.object.revisions.hasOwnProperty(i)) count++;
                }
                return count;
            },
            showPassword() {
                return this.showPw ? this.object.password:''.padStart(this.object.password.length, '*');
            },
            getSecurityStatus() {
                let status = ['Secure', 'Weak', 'Broken'];

                return status[this.object.status];
            },
            isMobile() {
                return window.innerWidth < 361;
            }
        },

        methods: {
            favouriteAction($event) {
                $event.stopPropagation();
                this.object.favourite = !this.object.favourite;
                API.updatePassword(this.object);
            },
            closeDetails() {
                this.$parent.detail = {
                    type   : 'none',
                    element: null
                }
            },
            changeQrCode($event) {
                let property = $($event.target).val();
                this.qrcode.text = this.object[property];
            },
            restoreAction(revision) {
                PasswordManager.restoreRevision(this.object, revision)
            },
            refreshView(event) {
                this.object = Utility.mergeObject(this.object, event.object);
            }
        },

        watch: {
            password: function(value) {
                this.qrcode.text = value.password;
                this.object = value;
                $('#password-details-qrcode').val('password');
                this.$forceUpdate();
            }
        }
    }
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
            color     : $color-black;
            z-index   : 1;

            &:hover {
                text-shadow : 0 0 2px $color-white;
            }
        }

        .title {
            white-space     : nowrap;
            text-overflow   : ellipsis;
            overflow        : hidden;
            font-size       : 1rem;
            font-weight     : 300;
            margin          : 0;
            background      : no-repeat 15px 15px;
            background-size : 32px;
            padding         : 15px 15px 2px 57px;
            line-height     : 32px;
        }

        .infos {
            padding : 0 15px 20px;
            color   : $color-grey-dark;

            .favourite {
                cursor : pointer;

                &:hover,
                &.active {
                    color : $color-yellow;
                }
            }

            .tags-container {
                position    : static;
                display     : inline;
                color       : $color-black-light;
                margin-left : 3px;
            }
        }

        > .tab-container {
            padding : 0 15px 15px;
        }

        .details {
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

                    &.password {
                        cursor : pointer;
                    }

                    &.secure {color : $color-green;}
                    &.weak {color : $color-yellow;}
                    &.broken {color : $color-red;}
                }

                a {
                    color  : $color-theme;
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

        .notes {
            blockquote {
                font-family : monospace;
                margin      : 5px 0;
                padding     : 10px 0 10px 15px;
                border-left : 2px solid $color-grey-dark;
            }
            h1, h2, h3, h4, h5, h6 {
                font-size   : 1.75rem;
                font-weight : 600;
                display     : block;
                padding     : 0;
                margin      : 0.25rem 0 0.5rem;
                line-height : initial;
            }
            h2 { font-size : 1.6rem; }
            h3 { font-size : 1.4rem; }
            h4 { font-size : 1.2rem; }
            h5 { font-size : 1.1rem; }
            h6 { font-size : 0.9rem; }
            em { font-style : italic; }
            ul {
                list-style   : disc;
                padding-left : 15px;
            }
            ol {
                list-style   : decimal;
                padding-left : 15px;
            }
            a {
                text-decoration : underline;
            }
            .CodeMirror {
                border                     : none;
                border-bottom-left-radius  : 0;
                border-bottom-right-radius : 0;

                .editor-preview {
                    padding    : 0;
                    background : #fff;
                }
            }
        }

        .password-share-qrcode {
            select {
                width         : 100%;
                margin-bottom : 15px;
            }

            img,
            canvas {
                display : block;
                margin  : 0 auto;
            }
        }

        .revision-list {
            .revision {
                position        : relative;
                background      : no-repeat 3px center;
                background-size : 32px;
                padding         : 5px 20px 5px 38px;
                font-size       : 1.1em;
                cursor          : pointer;
                border-bottom   : 1px solid $color-grey-lighter;

                &:last-child {
                    border-bottom : none;
                }

                span {
                    cursor : pointer;
                }

                .time {
                    color       : $color-grey-dark;
                    font-size   : 0.9em;
                    font-style  : italic;
                    line-height : 0.9em;
                }

                .fa {
                    position : absolute;
                    right    : 5px;
                    top      : 10px;

                    &:before {
                        line-height : 32px;
                        padding     : 0 5px;
                    }
                }

                &:hover {
                    background-color : darken($color-white, 3);
                }
            }
        }

        @media (max-width : $mobile-width) {
            .image-container {
                display : none;
            }

            .title {
                margin-bottom : 1rem;
            }
        }
    }
</style>