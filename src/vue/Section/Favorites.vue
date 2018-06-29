<script>
    import API from '@js/Helper/api';
    import BaseSection from '@vue/Section/BaseSection';

    export default {
        extends: BaseSection,

        computed: {
            getBreadcrumb() {
                return {
                    showAddNew : false
                };
            },
            getEmptyText() {
                return 'Your favorites will appear here'
            }
        },

        methods: {
            refreshView: function() {
                let model = this.ui.showTags ? 'model+tags':'model';
                API.findPasswords({favorite: true}, model).then(this.updatePasswordList);
                API.findFolders({favorite: true}).then(this.updateFolderList);
                API.findTags({favorite: true}).then(this.updateTagList);
            }
        }
    };
</script>