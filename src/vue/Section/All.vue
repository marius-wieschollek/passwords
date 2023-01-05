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

    export default {
        extends: BaseSection,

        methods: {
            refreshView: function() {
                this.loading = true;
                let model = this.ui.showTags ? 'model+tags':'model';
                API.listPasswords(model).then(this.updatePasswordList);
            }
        }
    };
</script>