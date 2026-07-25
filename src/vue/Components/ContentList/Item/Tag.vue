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
    <div :class="className" @click="openAction($event)" :data-tag-id="tag.id" :data-tag-title="tag.label">
        <tag-item-batch-toggle :item="tag" v-model="isSelected"/>
        <tag-item-favicon :favorite="tag.favorite" :color="tag.color" :title="tag.label"/>
        <div class="title" :title="tag.label">
            <button :aria-label="t('TagListItemAriaLabel', {label: tag.label})">{{ tag.label }}</button>
        </div>
        <slot name="middle"/>
        <slot name="actions">
            <tag-item-action-menu
                    :actions="actions"
                    :tag="tag"
                    :opened-menu.sync="openedMenu"
                    @closed="openedMenu = false"
            >
                <template v-if="hasCustomAction" #custom-action>
                    <slot name="custom-action"/>
                </template>
                <template #restore-action>
                    <slot name="restore-action"/>
                </template>
            </tag-item-action-menu>

        </slot>
        <nc-date-time class="date" :timestamp="tag.edited"/>
    </div>
</template>

<script>
    import SearchManager from "@js/Manager/SearchManager";
    import TagItemFavicon from "@vc/ContentList/Item/TagItem/TagItemFavicon.vue";
    import NcDateTime from "@nextcloud/vue/components/NcDateTime";
    import TagActions from "@js/Actions/Tag/TagActions";
    import TagItemBatchToggle from "@vc/ContentList/Item/TagItem/TagItemBatchToggle.vue";
    import ContentItemMenuLoadingIcon from "@vc/ContentList/Item/ContentItem/ContentItemMenuLoadingIcon.vue";

    export default {
        components: {
            TagItemBatchToggle,
            TagItemFavicon,
            NcDateTime,
            'tag-item-action-menu': () => ({
                component: import(/* webpackChunkName: "TagItemActionMenu" */ '@vc/ContentList/Item/TagItem/TagItemActionMenu.vue'),
                loading  : ContentItemMenuLoadingIcon,
                delay    : 0
            })
        },

        props: {
            tag: {
                type: Object
            }
        },

        data() {
            return {
                openedMenu: false,
                isSelected: false,
                actions   : new TagActions(this.tag)
            };
        },

        computed: {
            className() {
                let classNames = 'row tag';

                if(this.isSelected) classNames += ' selected';
                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.tag.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            },
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action');
            }
        },

        methods: {
            openAction($event) {
                if($event.target.closest('.checkbox-radio-switch') !== null) return;
                this.$router.push({name: 'Tags', params: {tag: this.tag.id}});
            }
        }
    };
</script>