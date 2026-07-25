<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-checkbox-radio-switch :checked.sync="isSelected" :loading="batchActionActive" :aria-label="t('BatchActionSelectToggleAriaLabel', {item: item.label})"/>
</template>

<script>
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";
    import BatchActionManager from "@js/Manager/BatchActionManager";

    export default {
        components: {
            NcCheckboxRadioSwitch
        },

        props: {
            item: {
                type: Object
            },
            value: {
                type: Boolean
            }
        },

        data() {
            return {
                isSelected: false
            };
        },

        computed: {
            batchActionSelected() {
                return false;
            },
            batchActionActive() {
                return BatchActionManager.isItemBeingProcessed(this.item);
            }
        },

        methods: {
            toggleSelection(state = null) {}
        },

        watch: {
            isSelected(value) {
                if(this.batchActionSelected !== value) {
                    this.toggleSelection(value);
                }
                this.$emit('input', value);
            },
            batchActionSelected(value) {
                if(this.isSelected !== value) {
                    this.isSelected = value;
                }
            }
        }
    };
</script>