<script>
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import BaseSection from '@vue/Section/BaseSection';

    export default {
        extends: BaseSection,

        data() {
            return {
                sorting: {
                    field    : 'edited',
                    ascending: false
                }
            }
        },

        methods: {
            refreshView: function() {
                let model = this.ui.showTags ? 'model+tags':'model';
                API.listPasswords(model).then(this.updateContentList);
            },

            updateContentList: function(passwords) {
                let array = Utility.sortApiObjectArray(passwords, 'edited', false);
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(array.slice(0, 15), this.getPasswordsSortingField(), this.sorting.ascending);
            }
        }
    };
</script>