<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="password-form-field-wrapper password-form-folder-wrapper">
        <translate tag="label" for="password-folder" say="Folder" icon="folder" class="area-label" />
        <input id="password-folder" type="button" class="area-input" :value="valueLabel" @click="chooseFolder">
    </div>
</template>

<script>
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import Translate from "@vc/Translate";
    import API       from '@js/Helper/api';
    import Messages from "@js/Classes/Messages";

    export default {
        components: {Translate},
        extends: AbstractField,
        data() {
            return {
                valueLabel: this.value
            }
        },
        mounted() {
            this.getFolderName().catch(console.error)
        },
        methods: {
            async getFolderName() {
                let folder = await API.showFolder(this.value);

                this.valueLabel = folder.label;
            },
            chooseFolder() {
                Messages.chooseFolder()
            }
        }
    };
</script>

<style lang="scss">

</style>