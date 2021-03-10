<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="pw-dialog-window background">
        <div class="window">
            <div class="title" v-if="hasTitle">
                <slot name="title">
                    <translate :say="title" v-if="title !== null" />
                </slot>
                <div class="window-controls">
                    <slot name="window-controls"/>
                    <icon icon="close" class="close" title="Close" @click="closeWindow()"/>
                </div>
            </div>
            <div class="content">
                <slot name="content"/>
                <slot name="default"/>
            </div>
            <div class="controls" v-if="hasControls">
                <slot name="controls"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Icon from "@vc/Icon";
    import Translate from "../Components/Translate";

    export default {
        components: {Translate, Icon},
        props: {
            title: {
                type: String,
                default: null
            },
            hasTitle: {
                type: Boolean,
                default: true
            },
            hasControls: {
                type: Boolean,
                default: true
            }
        },

        methods: {
            closeWindow() {
                this.$emit('close');
                this.$destroy();
                this.$el.parentNode.removeChild(this.$el);
            }
        }
    };
</script>

<style lang="scss">
.pw-dialog-window.background {
    position         : fixed;
    top              : 0;
    left             : 0;
    width            : 100%;
    height           : 100%;
    background-color : rgba(0, 0, 0, 0.7);
    z-index          : 3001;
    display          : flex;
    justify-content  : center;
    align-items      : center;
    backdrop-filter  : blur(3px);

    .window {
        z-index          : 9999;
        overflow         : hidden;
        background-color : var(--color-main-background);
        border-radius    : var(--border-radius-large);
        box-sizing       : border-box;
        display          : flex;
        flex-direction   : column;

        .title {
            padding          : 1rem;
            font-size        : 1.25rem;
            color            : var(--color-primary-text);
            background-color : var(--color-primary);
            position         : sticky;
            top              : 0;
            display          : flex;
            flex-grow        : 0;
            flex-shrink      : 0;

            :first-child {
                flex-grow : 1;
            }

            .window-controls {
                flex-grow   : 0;
                flex-shrink : 0;
                display     : flex;

                .close {
                    cursor : pointer;
                }
            }
        }

        .content {
            overflow    : auto;
            padding     : .5rem;
            flex-grow   : 1;
            flex-shrink : 1;
        }

        .controls {
            padding     : 0 .5rem .5rem;
            flex-grow   : 0;
            flex-shrink : 0;
        }

        @media (max-width : $width-medium) {
            border-radius : 0;
            top           : 0;
            left          : 0;
            bottom        : 0;
            right         : 0;
            width         : 100%;
            height        : 100%;
        }
    }
}
</style>