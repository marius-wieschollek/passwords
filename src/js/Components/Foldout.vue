<template id="passwords-template-foldout">
    <div v-bind:class="{ open: open }" class="foldout-container" :data-foldout="name">
        <div class="foldout-title" @click="toggleContent()" v-bind:style="titleStyle">
            <i class="fa fa-chevron-right"></i>
            {{title}}
        </div>
        <div class="foldout-content">
            <slot name="content"></slot>
        </div>
    </div>
</template>

<script>
    export default {
        template: '#passwords-template-foldout',
        name    : 'PasswordsFoldout',

        props: {
            name : {
                type     : String,
                'default': ''
            },
            title: {
                type     : String,
                'default': 'More Options'
            }
        },

        data() {
            return {
                open: false
            }
        },

        computed: {
            titleStyle() {
                if (OCA.Theming && this.open) {
                    return {
                        'border-color': OCA.Theming.color
                    };
                }

                return {};
            }
        },

        methods: {
            toggleContent: function () {
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
            padding       : 1rem 0 0.25rem 0;
            border-bottom : 1px solid transparent;
            transition    : border-color 0.25s ease-in-out;

            .fa-chevron-right {
                font-size   : 0.9rem;
                margin-left : 3px;
                transition  : transform 0.25s ease-in-out;
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

            .foldout-content {
                max-height : 250px;
            }
        }
    }
</style>