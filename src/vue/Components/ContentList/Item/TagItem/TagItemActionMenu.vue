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
    <div class="actions">
        <nc-actions
                :inline="hasCustomAction ? 1:0"
                :boundaries-element="getBoundariesElement"
                :container="getBoundariesElement"
                :open="openedMenu"
                @closed="$emit('closed')"
                variant="tertiary"
                @click.stop.prevent
        >
            <slot name="custom-action"/>
            <nc-action-button @click="actions.favorite()">
                <template #icon>
                    <star-icon :size="20" fill-color="var(--color-element-warning)" v-if="tag.favorite"/>
                    <star-outline-icon :size="20" fill-color="var(--color-placeholder-dark)" v-else/>
                </template>
                {{ tag.favorite ? t('BatchActionRemoveFavorites'):t('BatchActionAddFavorites') }}
            </nc-action-button>
            <nc-action-button @click="actions.edit()">
                <template #icon>
                    <pencil-icon :size="20"/>
                </template>
                {{ t('Edit') }}
            </nc-action-button>
            <nc-action-separator/>
            <slot name="restore-action" />
            <nc-action-button @click="deleteTag">
                <template #icon>
                    <trash-can-icon :size="20"/>
                </template>
                {{ t('Delete') }}
            </nc-action-button>
        </nc-actions>
    </div>
</template>

<script>
    import TrashCanIcon from "@icon/TrashCan";
    import PencilIcon from "@icon/Pencil";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import NcActions from '@nc/NcActions.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import NcActionSeparator from '@nc/NcActionSeparator.js';
    import TagActions from "@js/Actions/Tag/TagActions";

    export default {
        components: {
            StarOutlineIcon,
            StarIcon,
            PencilIcon,
            TrashCanIcon,
            NcActions,
            NcActionButton,
            NcActionSeparator
        },

        props: {
            tag       : {
                type: Object
            },
            actions   : {
                type: TagActions
            },
            openedMenu: {
                type: Boolean
            }
        },

        computed: {
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action');
            },
            getBoundariesElement() {
                return document.querySelector('.app-content .item-list');
            }
        },

        methods: {
            deleteTag() {
                this.actions.delete();
            }
        }
    };

</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .actions {
                display         : flex;
                align-self      : center;
                align-items     : center;
                justify-content : center;
            }
        }
    }
}
</style>