<template>
    <nc-breadcrumbs class="passwords-breadcrumbs">
        <NcBreadcrumb :to="getBaseRoute" title="Home"/>
        <NcBreadcrumb v-for="(item, index) in getItems"
                      :to="item.path"
                      :data-folder-id="item.folderId"
                      :data-drop-type="item.dropType"
                      :title="item.label"
                      :key="index"
        />
        <template v-if="showAddNew" #actions>
            <nc-actions>
                <nc-action-button icon="icon-folder" v-if="newFolder" @click="createFolder">
                    {{ t('New Folder') }}
                </nc-action-button>
                <nc-action-button icon="icon-tag" v-if="newTag" @click="createTag">
                    {{ t('New Tag') }}
                </nc-action-button>
                <nc-action-button icon="icon-folder" v-if="newPassword" @click="createPassword">
                    <key-icon slot="icon" :size="16"/>
                    {{ t('New Password') }}
                </nc-action-button>
                <nc-action-button icon="icon-history" v-if="restoreAll" @click="restoreAllEvent">
                    {{ t('Restore All Items') }}
                </nc-action-button>
                <nc-action-button icon="icon-delete" v-if="deleteAll" @click="deleteAllEvent">
                    {{ t('Delete All Items') }}
                </nc-action-button>
            </nc-actions>
        </template>
    </nc-breadcrumbs>
</template>

<script>
    import Translate from '@vc/Translate';
    import TagManager from '@js/Manager/TagManager';
    import Localisation from '@js/Classes/Localisation';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import NcBreadcrumbs from '@nc/NcBreadcrumbs';
    import NcBreadcrumb from '@nc/NcBreadcrumb';
    import NcActions from '@nc/NcActions';
    import NcActionButton from '@nc/NcActionButton';
    import KeyIcon from "@icon/Key";

    export default {
        components: {
            KeyIcon,
            Translate,
            NcActions,
            NcBreadcrumb,
            NcBreadcrumbs,
            NcActionButton
        },

        props: {
            newPassword: {
                type     : Boolean,
                'default': true
            },
            newFolder  : {
                type     : Boolean,
                'default': false
            },
            newTag     : {
                type     : Boolean,
                'default': false
            },
            deleteAll  : {
                type     : Boolean,
                'default': false
            },
            restoreAll : {
                type     : Boolean,
                'default': false
            },
            showAddNew : {
                type     : Boolean,
                'default': true
            },
            items      : {
                type     : Array,
                'default': () => { return []; }
            },
            folder     : {
                type     : String,
                'default': null
            },
            tag        : {
                type     : String,
                'default': null
            }
        },

        computed: {
            getBaseRoute() {
                let route = this.$route.path;

                return route.substr(0, route.indexOf('/', 1));
            },
            getItems() {
                if(this.items.length === 0) {
                    return [
                        {path: this.$route.path, label: Localisation.translate(this.$route.name)}
                    ];
                }

                return this.items;
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
            }
        }
    };
</script>

<style lang="scss">
.passwords-breadcrumbs {
    margin : .5rem;

    .breadcrumb__crumbs {
        min-width : auto !important;
    }

    @media all and (max-width : $width-1024) {
        padding-left : 2.5rem;
    }
}
</style>