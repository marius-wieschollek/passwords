<template id="passwords-template-password-details">
    <div class="item-details">
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
                <passwords-translate say="Details"></passwords-translate>
            </li>
            <li>
                <passwords-translate say="Notes"></passwords-translate>
            </li>
            <li>
                <passwords-translate say="Share"></passwords-translate>
            </li>
            <li>
                <passwords-translate say="Revisions"></passwords-translate>
            </li>
        </ul>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
    import API from '@js/Helper/api';

    export default {
        template: '#passwords-template-password-details',
        name    : 'PasswordDetails',

        components: {
            'passwords-translate': Translate
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
        }
    }
</script>