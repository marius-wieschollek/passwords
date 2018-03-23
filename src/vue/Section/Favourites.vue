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
                API.findPasswords({favourite: true}, model).then(this.updatePasswordList);
                API.findFolders({favourite: true}).then(this.updateFolderList);
                API.findTags({favourite: true}).then(this.updateTagList);
            }
        }
    };
</script>