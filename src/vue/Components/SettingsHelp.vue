<template>
    <div class="passwords-help" :class="{active:active}" @click="togglePin">
        <translate :say="text"/>
    </div>
</template>

<script>
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate},
        props     : {
            text: {
                type: String
            }
        },
        data() {
            return {
                active: false
            };
        },
        methods   : {
            togglePin() {
                this.active = !this.active;

                if(this.active) {
                    setTimeout(() => {document.addEventListener('click', this.togglePin);}, 1);
                } else {
                    document.removeEventListener('click', this.togglePin);
                }
            }
        }
    };
</script>

<style lang="scss">
    .passwords-help {
        position : relative;

        &:before {
            content     : "\f059";
            font-family : var(--pw-icon-font-face);
            line-height : 40px;
            font-size   : 1.4em;
            width       : 26px;
            text-align  : center;
            display     : inline-block;
            cursor      : help;
            transition  : transform 0.2s ease-in-out;
        }

        span {
            display : none;
        }

        &:hover,
        &.active {
            z-index : 1000;
            cursor  : help;

            &:before {
                transform : scale(1.4);
            }

            span {
                display          : block;
                position         : absolute;
                right            : 0;
                background-color : transparentize($color-black, 0.25);
                color            : var(--color-primary-text);
                padding          : 10px;
                border-radius    : var(--border-radius);
                width            : 200px;

                &:after {
                    content            : " ";
                    border             : 10px solid transparentize($color-black, 0.25);
                    border-top-color   : transparent;
                    border-right-color : transparent;
                    border-left-color  : transparent;
                    display            : block;
                    position           : absolute;
                    top                : -20px;
                    right              : 7px;
                    cursor             : help;
                }
            }
        }
    }
</style>