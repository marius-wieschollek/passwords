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
        <translate tag="label" for="password-folder" say="Folder" icon="folder" class="area-label"/>
        <input id="password-folder" type="button" class="area-input" :value="valueLabel" @click="chooseFolder">
    </div>
</template>

<script>
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import Translate from "@vc/Translate";
    import API from '@js/Helper/api';
    import FolderManager from "@js/Manager/FolderManager";

    export default {
        components: {Translate},
        extends   : AbstractField,
        props     : {
            value: [String, Object]
        },
        data() {
            let isObjectValue = typeof this.value !== 'string';

            return {
                isObjectValue,
                valueLabel: isObjectValue ? this.value.label:this.value
            };
        },
        mounted() {
            this.getFolderName().catch(console.error);
        },
        methods: {
            async getFolderName() {
                if(this.isObjectValue) return;
                let folder = await API.showFolder(this.value);

                this.valueLabel = folder.label;
                this.$emit('folder', folder);
            },
            async chooseFolder() {
                let result = await FolderManager.selectFolder(this.model);
                if(result) {
                    this.model = this.isObjectValue ? result:result.id;
                    this.valueLabel = result.label;
                    this.$emit('folder', result);
                }
            }
        }
    };
</script>

<style lang="scss">

</style>