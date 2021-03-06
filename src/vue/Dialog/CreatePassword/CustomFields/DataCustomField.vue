<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<script>
    import AbstractCustomField from "@vue/Dialog/CreatePassword/CustomFields/AbstractCustomField";
    import SettingsService from "@js/Services/SettingsService";

    export default {
        extends: AbstractCustomField,
        computed: {
            isVisible() {
                return SettingsService.get('client.ui.custom.fields.show.hidden', false);
            }
        }
    };
</script>