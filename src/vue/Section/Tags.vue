<script>
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import BaseSection from '@vue/Section/BaseSection';
    import Localisation from '@js/Classes/Localisation';
    import SettingsService from '@js/Service/SettingsService';

    export default {
        extends: BaseSection,

        data() {
            let showTags = SettingsService.get('client.ui.list.tags.show', false) && window.innerWidth > 360,
                model    = showTags ? 'model+passwords+password-tags':'model+passwords';

            return {
                currentTag  : null,
                model       : model
            };
        },

        computed: {
            getBreadcrumb() {
                let showAddNew = true,
                    items = [],
                    tagId;
                if(this.currentTag !== null) {
                    showAddNew = !this.currentTag.trashed;
                    tagId = this.currentTag.id;
                    let route = this.currentTag.trashed ? 'Trash':'Tags';

                    items = [
                        {path: {name:route}, label: Localisation.translate(route)},
                        {path: this.$route.path, label: this.currentTag.label}
                    ];
                }

                return {
                    newTag     : this.currentTag === null,
                    newPassword: this.currentTag !== null,
                    tag        : tagId,
                    showAddNew,
                    items
                };
            }
        },

        methods: {
            refreshView: function() {
                if(this.$route.params.tag !== undefined) {
                    this.tags = [];
                    if(!this.passwords.length) this.loading = true;
                    API.showTag(this.$route.params.tag, this.model).then(this.updatePasswordList);
                } else {
                    this.passwords = [];
                    this.currentTag = null;
                    if(!this.tags.length) this.loading = true;
                    API.listTags().then(this.updateTagList);
                }
            },

            updatePasswordList: function(tag) {
                this.loading = false;
                this.currentTag = tag;
                this.passwords = Utility.sortApiObjectArray(tag.passwords, this.getPasswordsSortingField(), this.sorting.ascending);
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
                this.detail.type = 'none';
            }
        }
    };
</script>