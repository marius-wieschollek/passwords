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
    import UtilityService from "@js/Services/UtilityService";

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
                let array = UtilityService.sortApiObjectArray(passwords, 'edited', false);
                this.loading = false;
                this.passwords = UtilityService.sortApiObjectArray(array.slice(0, 15), this.getPasswordsSortingField(), this.sorting.ascending);
            }
        }
    };
</script>