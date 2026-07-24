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
    <div class="batch-action-toolbar">
        <nc-checkbox-radio-switch :checked.sync="selectAllValue" :indeterminate="isIntermediate">{{ selectionCountLabel }}</nc-checkbox-radio-switch>
        <nc-actions class="selection-more" :force-name="true" :inline="3" v-if="hasSelection">
            <nc-action-button type="tertiary" @click="favoriteAction" v-if="canFavorite">
                <template #icon>
                    <star-icon :size="20" v-if="allFavorites"/>
                    <star-outline-icon :size="20" v-else/>
                </template>
                {{ allFavorites ? t('BatchActionRemoveFavorites'):t('BatchActionAddFavorites') }}
            </nc-action-button>
            <nc-action-button type="tertiary" @click="manageTagsAction" v-if="canManageTags">
                <template #icon>
                    <tag-multiple-outline-icon :size="20"/>
                </template>
                {{ t('BatchActionManageTags') }}
            </nc-action-button>
            <nc-action-button type="tertiary" @click="moveAction" v-if="canMove">
                <template #icon>
                    <folder-move-outline-icon :size="20"/>
                </template>
                {{ t('Move') }}
            </nc-action-button>
            <nc-action-button @click="deleteAction" :close-after-click="true">
                <template #icon>
                    <trash-can-outline-icon :size="20"/>
                </template>
                {{ t(isTrashSection ? 'BatchActionTrashDelete':'Delete') }}
            </nc-action-button>
            <nc-action-button @click="restoreAction" :close-after-click="true" v-if="isTrashSection">
                <template #icon>
                    <restore-icon :size="20"/>
                </template>
                {{ t('BatchActionTrashRestore') }}
            </nc-action-button>
        </nc-actions>
    </div>
</template>

<script>
    import StarIcon from "vue-material-design-icons/Star.vue";
    import StarOutlineIcon from "vue-material-design-icons/StarOutline.vue";
    import TagMultipleOutlineIcon from "vue-material-design-icons/TagMultipleOutline.vue";
    import FolderMoveOutlineIcon from "vue-material-design-icons/FolderMoveOutline.vue";
    import TrashCanOutlineIcon from "vue-material-design-icons/TrashCanOutline.vue";
    import RestoreIcon from "vue-material-design-icons/Restore.vue";
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import LocalisationService from "@js/Services/LocalisationService";
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";
    import DeleteItemsBatchAction from "@js/Actions/BatchActions/DeleteItemsBatchAction";
    import MoveItemsBatchAction from "@js/Actions/BatchActions/MoveItemsBatchAction";
    import FavoriteItemsBatchAction from "@js/Actions/BatchActions/FavoriteItemsBatchAction";
    import ManageTagsBatchAction from "@js/Actions/BatchActions/ManageTagsBatchAction";
    import LoggingService from "@js/Services/LoggingService";
    import RestoreTrashItemsBatchAction from "@js/Actions/BatchActions/RestoreTrashItemsBatchAction";

    export default {
        components: {
            StarIcon,
            RestoreIcon,
            StarOutlineIcon,
            TrashCanOutlineIcon,
            FolderMoveOutlineIcon,
            TagMultipleOutlineIcon,
            NcCheckboxRadioSwitch,
            'nc-actions'      : () => import(/* webpackChunkName: "NcActions" */ '@nc/NcActions.js'),
            'nc-action-button': () => import(/* webpackChunkName: "NcActionButton" */ '@nc/NcActionButton.js')
        },

        props: {
            isTrashSection: {
                type: Boolean
            }
        },

        data() {
            return {
                selectAllValue: false
            };
        },

        computed: {
            selectionCountLabel() {
                return BatchActionManager.count ? LocalisationService.translate('BatchActionSelectedLabel', {total: BatchActionManager.count}):'';
            },
            canMove() {
                return !this.isTrashSection && (BatchActionManager.folders.length !== 0 || BatchActionManager.passwords.length !== 0);
            },
            canFavorite() {
                return !this.isTrashSection;
            },
            canManageTags() {
                return !this.isTrashSection && BatchActionManager.passwords.length !== 0;
            },
            allFavorites() {
                return this.hasSelection &&
                       (BatchActionManager.passwords.length === 0 || BatchActionManager.passwords.every((password) => password.favorite === true)) &&
                       (BatchActionManager.folders.length === 0 || BatchActionManager.folders.every((folder) => folder.favorite === true)) &&
                       (BatchActionManager.tags.length === 0 || BatchActionManager.tags.every((tag) => tag.favorite === true));
            },
            hasSelection() {
                return BatchActionManager.active;
            },
            allSelected() {
                return BatchActionManager.allSelected;
            },
            isIntermediate() {
                return this.hasSelection && !this.allSelected;
            }
        },

        methods: {
            deleteAction() {
                BatchActionManager
                    .executeAction(DeleteItemsBatchAction, {isTrashSection: this.isTrashSection})
                    .catch(LoggingService.catch);
            },
            moveAction() {
                BatchActionManager
                    .executeAction(MoveItemsBatchAction)
                    .catch(LoggingService.catch);
            },
            favoriteAction() {
                BatchActionManager
                    .executeAction(FavoriteItemsBatchAction, {favorite: !this.allFavorites})
                    .catch(LoggingService.catch);
            },
            manageTagsAction() {
                BatchActionManager
                    .executeAction(ManageTagsBatchAction)
                    .catch(LoggingService.catch);
            },
            restoreAction() {
                BatchActionManager
                    .executeAction(RestoreTrashItemsBatchAction)
                    .catch(LoggingService.catch);
            }
        },
        watch  : {
            allSelected(value) {
                if(this.selectAllValue !== value) {
                    this.selectAllValue = value;
                }
            },
            selectAllValue(value) {
                if(!value && BatchActionManager.allSelected) {
                    BatchActionManager.clear();
                } else if(value && !BatchActionManager.allSelected) {
                    BatchActionManager.selectAll();
                }
            }
        }
    };
</script>

<style lang="scss">
.batch-action-toolbar {
    display : flex;
    gap     : .5rem;
}
</style>