<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb
                    :showAddNew="getBreadcrumb.showAddNew"
                    :newPassword="getBreadcrumb.newPassword"
                    :newFolder="getBreadcrumb.newFolder"
                    :newTag="getBreadcrumb.newTag"
                    :folder="getBreadcrumb.folder"
                    :tag="getBreadcrumb.tag"
                    :items="getBreadcrumb.items"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="isNotEmpty"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id" :draggable="isDraggable"/>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id" :draggable="isDraggable"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id" :draggable="isDraggable"/>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import TagLine from '@vue/Line/Tag';
    import Breadcrumb from '@vc/Breadcrumb';
    import Events from '@js/Classes/Events';
    import Utility from '@js/Classes/Utility';
    import FolderLine from '@vue/Line/Folder';
    import Empty from '@vue/Components/Empty';
    import HeaderLine from '@vue/Line/Header';
    import FooterLine from '@vue/Line/Footer';
    import PasswordLine from '@vue/Line/Password';
    import PasswordDetails from '@vue/Details/Password';
    import Localisation from '@js/Classes/Localisation';
    import SearchManager from '@js/Manager/SearchManager';
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {
        components: {
            Empty,
            TagLine,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        data() {
            return {
                passwords: [],
                folders  : [],
                tags     : [],
                loading  : true,
                detail   : {
                    type   : 'none',
                    element: null
                },
                sorting  : {
                    field    : SettingsManager.get('local.ui.sorting.field', 'label'),
                    ascending: SettingsManager.get('local.ui.sorting.ascending', true)
                },
                ui       : {
                    showTags: SettingsManager.get('client.ui.list.tags.show', false) && window.innerWidth > 360
                },
                search   : SearchManager.status
            };
        },

        created() {
            this.refreshView();
            Events.on('data.changed', this.refreshView);
            SearchManager.clearDatabase();
        },

        beforeDestroy() {
            Events.off('data.changed', this.refreshView);
            SearchManager.clearDatabase();
        },

        computed: {
            getContentClass() {
                let classes = {
                        'show-details': this.detail.type !== 'none',
                        'loading'     : this.loading
                    },
                    section = `section-${this.$route.name.toLowerCase()}`;
                classes[section] = true;

                return classes;
            },
            showPasswordDetails() {
                return this.detail.type === 'password';
            },
            isNotEmpty() {
                return !this.loading && !this.isEmpty;
            },
            isEmpty() {
                if(this.loading) return false;
                if(this.search.active && this.search.total === 0) return true;

                return !this.passwords.length && !this.folders.length && !this.tags.length;
            },
            getEmptyText() {
                if(this.search.active) {
                    return Localisation.translate('We could not find anything for "{query}"', {query: this.search.query});
                }

                return undefined;
            },
            getBreadcrumb() {
                return {};
            },
            isDraggable() {
                return false;
            }
        },

        methods: {
            updateSorting($event) {
                this.sorting = $event;
                SettingsManager.set('local.ui.sorting.field', $event.field);
                SettingsManager.set('local.ui.sorting.ascending', $event.ascending);

                if(this.passwords) this.passwords = Utility.sortApiObjectArray(this.passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                if(this.folders) this.folders = Utility.sortApiObjectArray(this.folders, this.sorting.field, this.sorting.ascending);
                if(this.tags) this.tags = Utility.sortApiObjectArray(this.tags, this.sorting.field, this.sorting.ascending);
            },
            updatePasswordList(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
            },
            updateFolderList(folders) {
                this.loading = false;
                this.folders = Utility.sortApiObjectArray(folders, this.sorting.field, this.sorting.ascending);
            },
            updateTagList(tags) {
                this.loading = false;
                this.tags = Utility.sortApiObjectArray(tags, this.sorting.field, this.sorting.ascending);
            },
            getPasswordsSortingField() {
                let sortingField = this.sorting.field === 'label' ? SettingsManager.get('client.ui.password.field.sorting'):this.sorting.field;
                if(sortingField === 'byTitle') sortingField = SettingsManager.get('client.ui.password.field.title');
                return sortingField;
            }
        },
        watch  : {
            passwords(passwords) {
                let db = {passwords, folders: this.folders, tags: this.tags};
                SearchManager.setDatabase(db);
            },
            tags(tags) {
                let db = {passwords: this.passwords, folders: this.folders, tags};
                SearchManager.setDatabase(db);
            },
            folders(folders) {
                let db = {passwords: this.passwords, folders, tags: this.tags};
                SearchManager.setDatabase(db);
            }
        }
    };
</script>

<style lang="scss">
    #app-content {
        position   : relative;
        height     : 100%;
        overflow-y : auto;
        overflow-x : hidden;
        transition : margin-right 300ms, transform 300ms;

        &.blocking {
            z-index : 2000;
        }

        .app-content-right {
            background-color : white;
            z-index          : 50;
            border-left      : 1px solid $color-grey-light;
            transition       : right 300ms;
            right            : -27%;
        }

        &.show-details {
            margin-right : 27%;

            .app-content-right {
                display    : block;
                position   : fixed;
                top        : 45px;
                right      : 0;
                left       : auto;
                bottom     : 0;
                width      : 27%;
                min-width  : 360px;
                overflow-y : auto;
            }
        }

        > #app-navigation-toggle {
            display : none !important;
        }

        @media(max-width : $desktop-width) {
            .app-content-right {
                right   : -360px;
                z-index : 60;
            }

            &.show-details {
                margin-right : 360px;

                .app-content-right {
                    width     : 360px;
                    min-width : 360px;
                }
            }
        }

        @media(max-width : $width-extra-small) {
            transform : translate3d(0, 0, 0);

            .app-content-right {
                border-left : none;
                transition  : width 300ms;
            }

            &.show-details {
                margin-right : 0;

                .app-content-left {
                    display : none;
                }
                .app-content-right {
                    width     : 100%;
                    min-width : auto;
                    top       : 0;
                }
            }

            &.mobile-open {
                transform : translate3d(250px, 0px, 0px);
            }
        }
    }
</style>