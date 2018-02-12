<template>
    <div v-bind:class="{ open: open }" class="foldout-container">
        <translate tag="div" class="foldout-title" icon="chevron-right" @click="toggleContent()" :style="titleStyle" :say="title"/>
        <div class="foldout-content" :style="contentStyle">
            <slot></slot>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
    import ThemeManager from '@js/Manager/ThemeManager';

    export default {

        components: {
            Translate
        },

        props: {
            title: {
                type     : String,
                'default': 'More Options'
            },
            initiallyOpen: {
                type     : Boolean,
                'default': false
            }
        },

        data() {
            return {
                open       : false,
                borderColor: ThemeManager.getColor()
            }
        },

        mounted() {
            if(this.initiallyOpen) this.open = true
        },

        computed: {
            titleStyle() {
                if(this.open) {
                    return {
                        'border-color': ThemeManager.getColor()
                    };
                }

                return {};
            },
            contentStyle() {
                if(!this.open) {
                    return {maxHeight: 0}
                } else {
                    let $el = this.$slots.default[0].elm;
                    if(!$el) return {maxHeight: 0};
                    console.log($el.offsetHeight.toString());

                    return {maxHeight: $el.offsetHeight.toString()+'px'}
                }
            }
        },

        methods: {
            toggleContent: function() {
                this.open = !this.open;
            }
        }
    };
</script>

<style lang="scss">
    .foldout-container {
        .foldout-title {
            cursor        : pointer;
            font-size     : 1.1rem;
            padding       : 0 0 0.25rem 0;
            border-bottom : 1px solid transparent;
            transition    : border-color 0.25s ease-in-out;

            .fa-chevron-right {
                cursor      : pointer;
                font-size   : 0.9rem;
                margin-left : 3px;
                transition  : transform 0.25s ease-in-out;
            }

            span {
                cursor : pointer;
            }
        }

        .foldout-content {
            max-height : 0;
            overflow   : hidden;
            transition : max-height 0.25s ease-in-out;
        }

        &.open {
            .foldout-title {
                border-color : $color-grey-light;

                .fa-chevron-right {
                    transform : rotate(90deg);
                }
            }
        }
    }
</style>