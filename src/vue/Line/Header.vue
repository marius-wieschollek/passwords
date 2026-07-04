<template>
    <div class="row header">
        <label class="select-all" @click.stop title="Select All">
            <input type="checkbox" :checked="allSelected" @change="toggleAll">
        </label>
        <translate class="title" :class="titleClass" say="Name" @click="updateSorting('label')" title="Sort by name"/>
        <translate class="date" :class="dateClass" say="Modified" @click="updateSorting('edited')" title="Sort by modified date"/>
        <dropdown-menu class="bulk-actions" v-if="hasSelection">
            <ul slot="items">
                <li v-if="canMove">
                    <translate tag="a" href="#" @click.prevent="moveSelection" icon="external-link" say="Move"/>
                </li>
                <li>
                    <translate tag="a" href="#" @click.prevent="deleteSelection" icon="trash" say="Delete"/>
                </li>
            </ul>
        </dropdown-menu>
    </div>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import DropdownMenu from "@vc/DropdownMenu";
    import SelectionManager from "@js/Manager/SelectionManager";
    import FolderManager from "@js/Manager/FolderManager";
    import TagManager from "@js/Manager/TagManager";
    import PasswordManager from "@js/Manager/PasswordManager";
    import MessageService from "@js/Services/MessageService";

    export default {
        components: {
            Translate,
            DropdownMenu
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
            canMove() {
                return SelectionManager.folders.length !== 0 || SelectionManager.passwords.length !== 0;
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
                let target_folder = await FolderManager.selectFolder(); 
                let target_folder_id = target_folder.id

                for(let folder of SelectionManager.folders) {
                    if(folder.id === target) continue;
                    await FolderManager.moveFolder(folder, target_folder_id);
                }
                for(let password of SelectionManager.passwords) {
                    await PasswordManager.movePassword(password, target_folder_id);
                }

                SelectionManager.clear();
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

                .bulk-actions {
                    margin-right : 10px;

                    .popovermenu {
                        background-color : var(--color-main-background);
                        border            : 1px solid var(--color-border);
                        box-shadow        : 0 1px 5px var(--color-box-shadow);
                        border-radius     : var(--border-radius);
                        top               : auto;

                        li {
                            font-size   : 0.9rem;
                            line-height : 2.5rem;
                            white-space : nowrap;
                            cursor      : pointer;
                            padding     : 0 .75rem;

                            a {
                                display     : flex;
                                align-items : center;
                                color       : var(--color-main-text);
                            }

                            &:hover {
                                background-color : var(--color-background-hover);
                            }
                        }
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
