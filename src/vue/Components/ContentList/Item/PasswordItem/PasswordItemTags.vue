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
    <ul class="tags" v-if="showTags" :style="tagStyle">
        <li v-for="tag in getTags"
            :key="tag.id"
            :title="tag.label"
            :style="{color: tag.color}"
            @click="openTagAction($event, tag.id)">&nbsp;
        </li>
    </ul>
</template>

<script>
    import SettingsService from "@js/Services/SettingsService";
    import UtilityService from "@js/Services/UtilityService";

    export default {

        props: {
            password: {
                type: Object
            }
        },


        computed: {
            showTags() {
                return window.innerWidth > 360 && SettingsService.get('client.ui.list.tags.show') && this.password.tags;
            },
            tagStyle() {
                let length = UtilityService.objectToArray(this.password.tags).length;
                if(length) {
                    return {
                        'padding-left': (length + 18) + 'px'
                    };
                }

                return {};
            },
            getTags() {
                return UtilityService.sortApiObjectArray(this.password.tags, 'label');
            }
        },
        methods : {
            openTagAction($event, tag) {
                $event.stopPropagation();
                this.$router.push({name: 'Tags', params: {tag: tag}});
            }
        }
    };
</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .tags {
                height       : 50px;
                flex-shrink  : 0;
                line-height  : 50px;
                font-size    : 24px;
                z-index      : 1;
                padding-left : 0;
                transition   : padding-left 0.25s ease-in-out;

                li {
                    display     : inline-block;
                    margin-left : -18px;
                    transition  : margin-left 0.25s ease-in-out;

                    &:before {
                        content     : "\F02B";
                        font-family : var(--pw-icon-font-face);
                        cursor      : pointer;
                    }
                }

                &:hover {
                    padding-left : 5px !important;

                    li {
                        margin-left : -6px;
                    }
                }
            }
        }
    }
}
</style>