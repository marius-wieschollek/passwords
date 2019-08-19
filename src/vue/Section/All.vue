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