<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<script>
    import API from '@js/Helper/api';
    import BaseSection from '@vue/Section/BaseSection';
    import SearchManager from '@js/Manager/SearchManager';
    import LocalisationService from "@js/Services/LocalisationService";
    export default {
        extends: BaseSection,

        data() {
            return {
                hasPasswords: false,
                hasFolders  : false,
                hasTags     : false
            };
        },

        computed: {
            getBreadcrumb() {
                return {
                    showAddNew: false
                };
            },
            isEmpty() {
                if(this.loading) return false;
                if(!this.search.active || this.search.total === 0) return true;

                return !this.passwords.length && !this.folders.length && !this.tags.length;
            },
            isNotEmpty() {
                return SearchManager.status.total !== 0 && SearchManager.status.active;
            },
            getEmptyText() {
                if(this.search.active) {
                    return LocalisationService.translate('We could not find anything for "{query}"', {query: this.search.query});
                }

                return 'Use the search box to search';
            }
        },

        methods: {
            refreshView: function() {
                let model = this.ui.showTags ? 'model+tags':'model';

                API.listPasswords(model).then(this.updatePasswordList);
                API.listFolders('model').then(this.updateFolderList);
                API.listTags('model').then(this.updateTagList);
            },
            updateDatabase() {
                if(!this.hasPasswords || !this.hasFolders || !this.hasTags) this.loading = true;

                let db = {passwords: this.passwords, folders: this.folders, tags: this.tags};
                SearchManager.setDatabase(db);
                setTimeout(() => {
                    let query = this.$route.params.query;

                    let value = atob(query);
                    SearchManager.search(value);
                }, 10);
            }
        },
        watch  : {
            passwords() {
                this.hasPasswords = true;
                this.updateDatabase();
            },
            folders() {
                this.hasFolders = true;
                this.updateDatabase();
            },
            tags() {
                this.hasTags = true;
                this.updateDatabase();
            },
            search: {
                handler(value) {
                    if(value.active) {
                        let query = btoa(value.query);
                        if(this.$route.name === 'Search' && this.$route.params.query !== query) {
                            this.$router.push({name: 'Search', params: {query}});
                        }
                    }
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    #app-content.section-search {
        div.row.password:not(.search-visible),
        div.row.folder:not(.search-visible),
        div.row.tag:not(.search-visible) {
            display : none;
        }
    }
</style>