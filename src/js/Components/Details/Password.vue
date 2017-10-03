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
        <div class="title">
            <img :src="password.icon" alt="">
            {{ password.title }}
        </div>
        <div class="infos">
            <i class="fa fa-star favourite" v-bind:class="{ active: password.favourite }" @click="favouriteAction($event)"></i>
            <span class="date">{{ date }}</span>
        </div>
        <ul>
            <li>
                <translate say="Details"></translate>
            </li>
            <li>
                <translate say="Notes"></translate>
            </li>
            <li>
                <translate say="Share"></translate>
            </li>
            <li>
                <translate say="Revisions"></translate>
            </li>
        </ul>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
    import API from '@js/Helper/api';

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
                image: {
                    'className': '',
                    'style'    : {
                        'marginTop': 0
                    },
                }
            }
        },

        computed: {
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
    }
</style>