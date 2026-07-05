<template>
    <div class="row header">
        <label class="select-all" @click.stop title="Select All">
            <input type="checkbox" :checked="allSelected" @change="toggleAll">
        </label>
        <div class="selection-toolbar" v-if="hasSelection">
            <span class="selection-count">{{ selectionCountLabel }}</span>
            <nc-button type="tertiary" @click="favoriteSelection" v-if="canFavorite">
                <template #icon>
                    <star-icon v-if="allFavorites"/>
                    <star-outline-icon v-else/>
                </template>
                {{ allFavorites ? t('Remove from favorites') : t('Add to favorites') }}
            </nc-button>
            <nc-button type="tertiary" @click="manageTagsSelection" v-if="canManageTags">
                <template #icon>
                    <tag-icon/>
                </template>
                {{ t('Manage tags') }}
            </nc-button>
            <nc-button type="tertiary" @click="moveSelection" v-if="canMove">
                <template #icon>
                    <folder-move-icon/>
                </template>
                {{ t('Move') }}
            </nc-button>
            <nc-actions class="selection-more" :force-menu="true">
                <nc-action-button @click="deleteSelection" :close-after-click="true">
                    <template #icon>
                        <trash-can-icon/>
                    </template>
                    {{ t('Delete') }}
                </nc-action-button>
            </nc-actions>
        </div>
        <template v-else>
            <translate class="title" :class="titleClass" say="Name" @click="updateSorting('label')" title="Sort by name"/>
            <translate class="date" :class="dateClass" say="Modified" @click="updateSorting('edited')" title="Sort by modified date"/>
        </template>
    </div>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import SelectionManager from "@js/Manager/SelectionManager";
    import FolderManager from "@js/Manager/FolderManager";
    import TagManager from "@js/Manager/TagManager";
    import PasswordManager from "@js/Manager/PasswordManager";
    import MessageService from "@js/Services/MessageService";
    import NcButton from "@nc/NcButton.js";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import TagIcon from "@icon/Tag";
    import FolderMoveIcon from "@icon/FolderMove";
    import TrashCanIcon from "@icon/TrashCan";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {
            Translate,
            NcButton,
            StarIcon,
            StarOutlineIcon,
            TagIcon,
            FolderMoveIcon,
            TrashCanIcon,
            'nc-actions'      : () => import(/* webpackChunkName: "NcActions" */ '@nc/NcActions.js'),
            'nc-action-button': () => import(/* webpackChunkName: "NcActionButton" */ '@nc/NcActionButton.js')
        },

        props: {
            field   : {
                type: String
            },
            ascending: {
                type: Boolean
            }
        },

        computed: {
            titleClass() {
                return this.getClass('label');
            },
            dateClass() {
                return this.getClass('edited');
            },
            allSelected() {
                return SelectionManager.allSelected;
            },
            hasSelection() {
                return SelectionManager.active;
            },
            selectionCountLabel() {
                return LocalisationService.translate('{count} selected', {count: SelectionManager.count});
            },
            canMove() {
                return SelectionManager.folders.length !== 0 || SelectionManager.passwords.length !== 0;
            },
            canFavorite() {
                return SelectionManager.passwords.length !== 0;
            },
            canManageTags() {
                return SelectionManager.passwords.length !== 0;
            },
            allFavorites() {
                return SelectionManager.passwords.every((password) => password.favorite === true);
            }
        },

        methods: {
            getClass(field) {
                if(this.field === field) {
                    return this.ascending ? 'asc':'desc';
                }
                return '';
            },
            updateSorting(field) {
                if(this.field === field) {
                    this.$emit('updateSorting', {field: field, ascending: !this.ascending});
                } else {
                    this.$emit('updateSorting', {field: field, ascending: true});
                }
            },
            toggleAll() {
                SelectionManager.toggleAll();
            },
            deleteSelection() {
                let count = SelectionManager.count;

                MessageService
                    .confirm(['Do you want to delete {count} selected items?', {count}], 'Delete items')
                    .then(async () => {
                        for(let folder of SelectionManager.folders) await FolderManager.deleteFolder(folder, false);
                        for(let tag of SelectionManager.tags) await TagManager.deleteTag(tag, false);
                        for(let password of SelectionManager.passwords) await PasswordManager.deletePassword(password, false);

                        SelectionManager.clear();
                    })
                    .catch(() => {});
            },
            async moveSelection() {
                let target_folder = await FolderManager.selectFolder(),
                    target_folder_id = target_folder.id;

                for(let folder of SelectionManager.folders) {
                    if(folder.id === target_folder_id || folder.parent === target_folder_id) continue;
                    await FolderManager.moveFolder(folder, target_folder_id);
                }
                for(let password of SelectionManager.passwords) {
                    await PasswordManager.movePassword(password, target_folder_id);
                }

                SelectionManager.clear();
            },
            async favoriteSelection() {
                let makeFavorite = !this.allFavorites;

                for(let password of SelectionManager.passwords) {
                    if(password.favorite === makeFavorite) continue;
                    password.favorite = makeFavorite;
                    await PasswordManager.updatePassword(password);
                }
            },
            manageTagsSelection() {
                TagManager.manageTags(SelectionManager.passwords);
            }
        }
    };
</script>

<style lang="scss">
    #app-content {
        .item-list {
            .row.header {
                color               : $color-grey-dark;
                -webkit-user-select : none;
                -moz-user-select    : none;
                -ms-user-select     : none;
                user-select         : none;
                display             : flex;
                align-items         : center;

                .select-all {
                    width        : 40px;
                    flex-shrink  : 0;
                    cursor       : pointer;
                    line-height  : 0;
                    padding-left : 10px;

                    input[type="checkbox"] {
                        appearance         : none;
                        -webkit-appearance : none;
                        width              : 40px;
                        height             : 40px;
                        margin             : 0;
                        border             : 4px solid var(--color-primary-element);
                        border-radius      : 8px;
                        background-color   : transparent;
                        vertical-align     : middle;
                        cursor             : pointer;
                        position           : relative;

                        &:checked {
                            background-color : var(--color-primary-element);

                            &::after {
                                content      : '';
                                position     : absolute;
                                left         : 5px;
                                top          : 1px;
                                width        : 5px;
                                height       : 10px;
                                border       : solid var(--color-primary-element-text);
                                border-width : 0 2px 2px 0;
                                transform    : rotate(45deg);
                            }
                        }
                    }
                }

                .title {
                    padding-left : 99px;
                    flex-grow    : 1;
                }

                .date {
                    color     : $color-grey-dark;
                    width     : auto;
                    min-width : 85px;
                }

                .selection-toolbar {
                    display      : flex;
                    align-items  : center;
                    flex-grow    : 1;
                    gap          : .5rem;
                    padding-right: 10px;
                    font-size    : 0.875rem;

                    .selection-count {
                        font-weight  : bold;
                        font-size    : 0.875rem;
                        color        : var(--color-main-text);
                        margin-right : .5rem;
                        white-space  : nowrap;
                    }

                    .selection-more {
                        margin-left : auto;
                    }
                }

                .asc::after,
                .desc::after {
                    content      : "\f0d7";
                    font-family  : var(--pw-icon-font-face);
                    padding-left : 5px;
                }

                .asc::after {
                    content : "\f0d8";
                }

                &:active,
                &:hover {
                    background-color : initial;
                }
            }
        }
    }
</style>
