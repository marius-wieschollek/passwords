<template>
    <div class="item-details">
        <i class="fa fa-times" @click="closeDetails()"></i>
        <div class="image-container">
            <a :href="object.url" target="_blank">
                <img :class="image.className"
                     :style="image.style"
                     :src="object.image"
                     @mouseover="imageMouseOver($event)"
                     @mouseout="imageMouseOut($event)"
                     alt="">
            </a>
        </div>
        <h3 class="title" :style="{'background-image': 'url(' + object.icon + ')'}">{{ object.label }}</h3>
        <div class="infos">
            <i class="fa fa-star favourite" :class="{ active: object.favourite }" @click="favouriteAction($event)"></i>
            <span class="date">{{ object.updated.toLocaleDateString() }}</span>
            <tags :password="object"/>
        </div>
        <tabs :tabs="{details: 'Details', notes: 'Notes', share: 'Share', revisions: 'Revisions'}" :uuid="object.id">
            <div slot="details">
                <pre>
                Title: {{ object.label }}
                User: {{ object.username }}
                Password: {{ object.password }}
                Website: {{ object.url }}
                </pre>
            </div>
            <div slot="notes" class="notes">
                <textarea id="password-details-notes">{{ object.notes }}</textarea>
            </div>
            <div slot="share">
                <tabs :tabs="{nextcloud: 'Share', qrcode: 'QR Code'}" :uuid="object.id">
                    <div slot="nextcloud" class="password-share-nextcloud">
                        nc
                    </div>
                    <div slot="qrcode" class="password-share-qrcode">
                        <select id="password-details-qrcode" @change="changeQrCode($event)">
                            <translate tag="option" value="login" v-if="object.username">Username</translate>
                            <translate tag="option" value="password" selected>Password</translate>
                            <translate tag="option" value="url" v-if="object.url">Website</translate>
                        </select>
                        <qr-code :text="qrcode.text"
                                 :color="qrcode.color"
                                 :bgColor="qrcode.bgColor"
                                 :size="256"
                                 errorLevel="L"/>
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
                        <translate icon="undo"
                                   title="Restore revision"
                                   @click="restoreAction(revision)"
                                   v-if="revision.id !== object.revision"/>
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
    import Events from "@js/Classes/Events";
    import QrCode from 'vue-qrcode-component'
    import Translate from '@vc/Translate.vue';
    import ThemeManager from '@js/Manager/ThemeManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import Utility from "@js/Classes/Utility";

    export default {
        components: {
            Translate,
            QrCode,
            Tabs,
            Tags
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                image : {
                    'className': '',
                    'style'    : {
                        'marginTop': 0
                    },
                },
                qrcode: {
                    color  : ThemeManager.getColor(),
                    bgColor: ThemeManager.getContrastColor(),
                    text   : this.password.password
                },
                object: this.password
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
            }
        },

        methods: {
            imageMouseOver($event) {
                let $element = $($event.target),
                    $parent  = $element.parent().parent(),
                    margin   = $element.height() - $parent.height();

                if (margin > 0) {
                    if (margin < 500) {
                        this.image.className = 's1';
                    } else if (margin < 1000) {
                        this.image.className = 's5';
                    } else if (margin < 2500) {
                        this.image.className = 's10';
                    } else if (margin < 4000) {
                        this.image.className = 's15';
                    } else {
                        this.image.className = 's20';
                    }
                    this.image.style = {'marginTop': '-' + margin + 'px'};
                }
            },
            imageMouseOut() {
                this.image.style = {'marginTop': 0};
            },
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
            password: function (value) {
                this.image.className = '';
                this.image.style = {'marginTop': 0};
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

            &:hover {
                text-shadow : 0 0 2px $color-white;
            }
        }

        .image-container {
            max-height : 290px;
            overflow   : hidden;

            a {
                display   : block;
                font-size : 0;

                img {
                    width      : 100%;
                    margin-top : 0;
                    transition : none;

                    &.s1 { transition : margin-top 1s ease-in-out; }
                    &.s5 { transition : margin-top 5s ease-in-out; }
                    &.s10 { transition : margin-top 10s ease-in-out; }
                    &.s15 { transition : margin-top 15s ease-in-out; }
                    &.s20 { transition : margin-top 20s ease-in-out; }
                }
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
    }
</style>