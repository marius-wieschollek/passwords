<template>
    <div class="item-details">
        <i class="fa fa-times" @click="closeDetails()"></i>
        <div class="image-container">
            <a :href="password.url" target="_blank">
                <img :class="image.className"
                     :style="image.style"
                     :src="password.image"
                     @mouseover="imageMouseOver($event)"
                     @mouseout="imageMouseOut($event)"
                     alt="">
            </a>
        </div>
        <h3 class="title" v-bind:style="faviconStyle">
            {{ password.title }}
        </h3>
        <div class="infos">
            <i class="fa fa-star favourite" v-bind:class="{ active: password.favourite }" @click="favouriteAction($event)"></i>
            <span class="date">{{ date }}</span>
        </div>
        <tabs :tabs="{details: 'Details', notes: 'Notes', share: 'Share', revisions: 'Revisions'}">
            <div slot="details">
                <pre>
                Title: {{ password.title }}
                User: {{ password.login }}
                Password: {{ password.password }}
                Website: {{ password.url }}
                </pre>
            </div>
            <div slot="notes" class="notes">
                <textarea id="password-details-notes">{{ password.notes }}</textarea>
            </div>
            <div slot="share">

            </div>
            <div slot="revisions">

            </div>
        </tabs>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
    import Tabs from '@vc/Tabs.vue';
    import API from '@js/Helper/api';
    import SimpleMDE from 'simplemde';

    export default {
        components: {
            Translate,
            Tabs
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                image: {
                    'className': '',
                    'style'    : {
                        'marginTop': 0
                    },
                }
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
                    initialValue           : this.password.notes
                });
            simplemde.togglePreview();
        },

        computed: {
            faviconStyle() {
                return {
                    backgroundImage: 'url(' + this.password.icon + ')'
                }
            },
            date() {
                return new Date(this.password.updated * 1e3).toLocaleDateString();
            }
        },

        watch: {
            password: function (value) {
                this.image.className = '';
                this.image.style = {'marginTop': 0};
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
                this.password.favourite = !this.password.favourite;
                API.updatePassword(this.password);
            },
            closeDetails() {
                this.$parent.detail = {
                    type   : 'none',
                    element: null
                }
            }
        }
    }
</script>

<style lang="scss">
    .item-details {
        .fa.fa-times {
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
            height   : 290px;
            overflow : hidden;

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
        }

        .tab-container {
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
        }
    }
</style>