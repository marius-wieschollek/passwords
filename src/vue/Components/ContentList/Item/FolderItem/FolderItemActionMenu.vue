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
                    <star-icon :size="20" fill-color="var(--color-element-warning)" v-if="folder.favorite"/>
                    <star-outline-icon :size="20" fill-color="var(--color-placeholder-dark)" v-else/>
                </template>
                {{ folder.favorite ? t('BatchActionRemoveFavorites'):t('BatchActionAddFavorites') }}
            </nc-action-button>
            <nc-action-button @click="actions.rename()">
                <template #icon>
                    <pencil-icon :size="20"/>
                </template>
                {{ t('Rename') }}
            </nc-action-button>
            <nc-action-button @click="actions.move()">
                <template #icon>
                    <folder-move-icon :size="20"/>
                </template>
                {{ t('Move') }}
            </nc-action-button>
            <nc-action-separator/>
            <nc-action-button @click="$emit('restore', folder)" v-if="folder.trashed">
                <template #icon>
                    <restore-icon :size="20"/>
                </template>
                {{ t('Restore') }}
            </nc-action-button>
            <nc-action-button @click="deleteFolder">
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
    import FolderMoveIcon from "@icon/FolderMove";
    import PencilIcon from "@icon/Pencil";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import NcActions from '@nc/NcActions.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import NcActionSeparator from '@nc/NcActionSeparator.js';
    import FolderActions from "@js/Actions/Folder/FolderActions";

    export default {
        components: {
            StarOutlineIcon,
            StarIcon,
            PencilIcon,
            FolderMoveIcon,
            'restore-icon': () => import(/* webpackChunkName: "RestoreIcon" */ '@icon/Restore'),
            TrashCanIcon,
            NcActions,
            NcActionButton,
            NcActionSeparator
        },

        props: {
            folder    : {
                type: Object
            },
            actions    : {
                type: FolderActions
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
            deleteFolder() {
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