<script>
    import API from '@js/Helper/api';
    import BaseSection from '@vue/Section/BaseSection';
    import Localisation from '@js/Classes/Localisation';
    import SearchManager from '@js/Manager/SearchManager';

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
                    return Localisation.translate('We could not find anything for "{query}"', {query: this.search.query});
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
                    let el    = document.getElementById('searchbox'),
                        query = this.$route.params.query;

                    if(el.value === '' && query !== '' && query !== undefined) {
                        el.value = query;
                        SearchManager.search(query);
                    }
                }, 1);
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
                    if(value.active) this.$router.push({name: 'Search', params: {query: value.query}});
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