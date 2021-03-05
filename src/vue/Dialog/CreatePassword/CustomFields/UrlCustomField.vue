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

    export default {
        extends: AbstractCustomField,

        computed: {
            inputType() {
                return 'url';
            },
            inputAttributes() {
                return {
                    maxlength: 368 - this.value.label.length,
                    pattern  : '\\[w\-]+:\/\/.+'
                };
            }
        }
    };
</script>