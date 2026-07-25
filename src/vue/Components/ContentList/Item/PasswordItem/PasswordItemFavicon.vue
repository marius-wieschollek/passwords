<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->
<template>
    <div class="favicon">
        <favicon class="icon" :domain="domain" :title="title"/>
        <star-icon class="favorite" :size="20" fill-color="var(--color-element-warning)" v-if="favorite"/>
    </div>

</template>

<script>
    import Favicon from "@vc/Favicon.vue";
    import StarIcon from "@icon/Star.vue";

    export default {
        components: {
            Favicon,
            StarIcon
        },

        props: {
            domain  : {
                type: String
            },
            favorite: {
                type: Boolean
            },
            title   : {
                type: String
            }
        }
    };
</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .favicon {
                display         : inline-block;
                background      : no-repeat center;
                background-size : 32px;
                line-height     : 32px;
                font-size       : 1rem;
                cursor          : pointer;
                width           : 32px;
                height          : 32px;
                margin          : 9px;
                flex-shrink     : 0;
                position        : relative;

                .icon {
                    border-radius : var(--border-radius);
                    cursor        : pointer;
                }

                .favorite {
                    position : absolute;
                    top      : -.5rem;
                    right    : -.5rem;
                }
            }
        }
    }
}
</style>