<template>
    <nc-breadcrumbs class="passwords-breadcrumbs" :class="{'actions-pull-right': actionsOnRight}">
        <NcBreadcrumb :to="getBaseRoute" :title="t('Home')">
            <template #icon>
                <earth-icon v-if="$route.name === 'All'" />
                <folder-icon v-if="$route.name === 'Folders'" />
                <clock-icon v-if="$route.name === 'Recent'" />
                <star-icon v-if="$route.name === 'Favorites'" />
                <share-variant-icon v-if="$route.name === 'Shares'" />
                <tag-icon v-if="$route.name === 'Tags'" />
                <shield-half-full-icon v-if="$route.name === 'Security'" />
                <magnify-icon v-if="$route.name === 'Search'" />
                <delete-icon v-if="$route.name === 'Trash'" />
                <cog-icon v-if="$route.name === 'Settings'" />
                <archive-icon v-if="$route.name === 'Backup'" />
                <help-circle-icon v-if="$route.name === 'Help'" />
                <puzzle-icon v-if="$route.name === 'Apps and Extensions'" />
            </template>
        </NcBreadcrumb>
        <NcBreadcrumb v-for="(item) in breadcrumbs"
                      :to="item.path"
                      :data-folder-id="item.folderId"
                      :data-drop-type="item.dropType"
                      :title="item.label"
                      :key="item.id"
        />
        <template #actions>
            <nc-actions v-if="showAddNew" :force-menu="true">
                <nc-action-button close-after-click icon="icon-folder" v-if="newFolder" @click="createFolder" class="passwords-folder-create">
                    {{ t('New Folder') }}
                </nc-action-button>
                <nc-action-button close-after-click icon="icon-tag" v-if="newTag" @click="createTag" class="passwords-tag-create">
                    {{ t('New Tag') }}
                </nc-action-button>
                <nc-action-button close-after-click v-if="newPassword" @click="createPassword" class="passwords-password-create">
                    <key-icon slot="icon" :size="16" />
                    {{ t('New Password') }}
                </nc-action-button>
                <nc-action-button close-after-click icon="icon-history" v-if="restoreAll" @click="restoreAllEvent" class="passwords-trash-restore">
                    {{ t('Restore All Items') }}
                </nc-action-button>
                <nc-action-button close-after-click icon="icon-delete" v-if="deleteAll" @click="deleteAllEvent" class="passwords-trash-delete">
                    {{ t('Delete All Items') }}
                </nc-action-button>
            </nc-actions>
            <slot />
        </template>
    </nc-breadcrumbs>
</template>

<script>
    import Translate          from '@vc/Translate';
    import TagManager         from '@js/Manager/TagManager';
    import Localisation       from '@js/Classes/Localisation';
    import FolderManager      from '@js/Manager/FolderManager';
    import PasswordManager    from '@js/Manager/PasswordManager';
    import KeyIcon            from '@icon/Key';
    import API                from '@js/Helper/api';
    import FolderIcon         from '@icon/Folder';
    import PuzzleIcon         from '@icon/Puzzle';
    import HelpCircleIcon     from '@icon/HelpCircle';
    import ArchiveIcon        from '@icon/Archive';
    import CogIcon            from '@icon/Cog';
    import MagnifyIcon        from '@icon/Magnify';
    import ShieldHalfFullIcon from '@icon/ShieldHalfFull';
    import TagIcon            from '@icon/Tag';
    import ShareVariantIcon   from '@icon/ShareVariant';
    import StarIcon           from '@icon/Star';
    import ClockIcon          from '@icon/Clock';
    import EarthIcon          from '@icon/Earth';
    import BreadcrumbLoading  from '@vc/Breadcrumb/BreadcrumbLoading';

    export default {
        components: {
            EarthIcon,
            ClockIcon,
            StarIcon,
            ShareVariantIcon,
            TagIcon,
            ShieldHalfFullIcon,
            MagnifyIcon,
            CogIcon,
            ArchiveIcon,
            HelpCircleIcon,
            PuzzleIcon,
            FolderIcon,
            KeyIcon,
            Translate,
            BreadcrumbLoading,
            'nc-actions'      : () => import(/* webpackChunkName: "NcActions" */ '@nc/NcActions'),
            'NcBreadcrumb'    : () => import(/* webpackChunkName: "NcBreadcrumb" */ '@nc/NcBreadcrumb'),
            'nc-action-button': () => import(/* webpackChunkName: "NcActionButton" */ '@nc/NcActionButton'),
            'delete-icon'     : () => import(/* webpackChunkName: "DeleteIcon" */ '@icon/Delete'),
            'nc-breadcrumbs'  : () => ({
                component: import(/* webpackChunkName: "NcBreadcrumbs" */ '@nc/NcBreadcrumbs'),
                loading  : BreadcrumbLoading,
                delay    : 0
            })
        },

        props: {
            newPassword   : {
                type     : Boolean,
                'default': true
            },
            newFolder     : {
                type     : Boolean,
                'default': false
            },
            newTag        : {
                type     : Boolean,
                'default': false
            },
            deleteAll     : {
                type     : Boolean,
                'default': false
            },
            restoreAll    : {
                type     : Boolean,
                'default': false
            },
            showAddNew    : {
                type     : Boolean,
                'default': true
            },
            actionsOnRight: {
                type     : Boolean,
                'default': false
            },
            items         : {
                type     : Array,
                'default': () => { return []; }
            },
            folder        : {
                type     : String,
                'default': null
            },
            tag           : {
                type     : String,
                'default': null
            }
        },

        data() {
            return {
                breadcrumbs: [],
                folders    : {}
            };
        },

        mounted() {
            this.processItems();
        },

        computed: {
            getBaseRoute() {
                return {
                    name: this.$route.name
                };
            }
        },

        methods: {
            createFolder() {
                FolderManager.createFolder(this.folder);
            },
            createTag() {
                TagManager.createTag();
            },
            createPassword() {
                PasswordManager.createPassword(this.folder, this.tag);
            },
            deleteAllEvent() {
                this.$emit('deleteAll');
            },
            restoreAllEvent() {
                this.$emit('restoreAll');
            },
            async processItems() {
                if(this.items.length === 0) {
                    this.breadcrumbs = [
                        {path: this.$route.path, label: Localisation.translate(this.$route.name)}
                    ];
                    return;
                }

                if(this.items.length === 1 || this.items[0].dropType !== 'folder' || this.$route.name === 'Trash') {
                    this.breadcrumbs = this.items;
                    return;
                } else {
                    this.breadcrumbs = this.items.slice(1);
                }

                if(this.items[2] && this.items[2].dropType === 'folder') {
                    let baseId      = this.items[0].folderId,
                        breadcrumbs = [];

                    let folder = await this.getFolder(this.items[2].folderId);
                    while(folder.id !== baseId) {
                        breadcrumbs.unshift(
                            {
                                path    : {name: 'Folders', params: {folder: folder.id}},
                                label   : folder.label,
                                dropType: 'folder',
                                folderId: folder.id
                            }
                        );
                        if(folder.parent === baseId) {
                            break;
                        }
                        folder = await this.getFolder(folder.parent);
                    }

                    this.breadcrumbs = breadcrumbs;
                }
            },
            async getFolder(id) {
                if(this.folders.hasOwnProperty(id)) {
                    return this.folders[id];
                }
                let folder = await API.showFolder(id);
                this.folders[folder.id] = folder;

                return folder;
            }
        },

        watch: {
            items() {
                this.processItems();
            }
        }
    };
</script>

<style lang="scss">
div.passwords-breadcrumbs {
    padding          : .5rem;
    position         : sticky;
    top              : 0;
    background-color : var(--color-main-background);

    .breadcrumb__crumbs {
        min-width : auto !important;
    }

    &.actions-pull-right div.breadcrumb__actions {
        margin-left : auto;
    }

    @media all and (max-width : $width-1024) {
        padding-left : 2.5rem;
    }
}
</style>