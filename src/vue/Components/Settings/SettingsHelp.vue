<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-actions>
        <nc-action-text class="passwords-settings-help-text">{{ t(text) }}</nc-action-text>
        <help-circle-icon slot="icon"/>
    </nc-actions>
</template>

<script>
    import NcActions from '@nc/NcActions.js';
    import NcActionText from '@nc/NcActionText.js';
    import HelpCircleIcon from "@icon/HelpCircle";

    export default {
        components: {NcActions, NcActionText, HelpCircleIcon},
        props     : {
            text: {
                type: String
            }
        }
    };
</script>

<style lang="scss">
.passwords-settings-help-text {
    .action-text__longtext {
        margin : 0 .5rem
    }
}
</style>