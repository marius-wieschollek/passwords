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
    <div class="share-options-container">
        <div class="share-options-preset">
            <NcRadioGroup v-model="preset" :label="t('ShareOptionsPreset')" class="wide" hide-label>
                <NcRadioGroupButton :label="t('SharePermissionView')" value="view" :disabled="disabled">
                    {{ t('SharePermissionView') }}
                    <template #icon>
                        <eye-icon :size="20"/>
                    </template>
                </NcRadioGroupButton>
                <NcRadioGroupButton :label="t('SharePermissionEdit')" value="edit" :disabled="disabled" v-if="canEdit">
                    {{ t('SharePermissionEdit') }}
                    <template #icon>
                        <pencil-icon :size="20"/>
                    </template>
                </NcRadioGroupButton>
                <NcRadioGroupButton :label="t('SharePermissionCustom')" value="custom" :disabled="disabled">
                    {{ t('SharePermissionCustom') }}
                    <template #icon>
                        <dots-horizontal-icon :size="20"/>
                    </template>
                </NcRadioGroupButton>
                <NcRadioGroupButton :label="t('ShareRevokeAccess')" value="delete" :disabled="disabled" v-if="withDelete">
                    {{ t('ShareRevokeAccess') }}
                    <template #icon>
                        <trash-can-outline-icon :size="20"/>
                    </template>
                </NcRadioGroupButton>
            </NcRadioGroup>
        </div>
        <div class="share-options">
            <nc-button @click="showAdvanced = !showAdvanced" variant="tertiary" alignment="start-reverse" :disabled="disabled">
                {{ t('ShareOptionsAdvanced') }}
                <template #icon>
                    <menu-up-icon :size="20" v-if="showAdvanced"/>
                    <menu-down-icon :size="20" v-else/>
                </template>
            </nc-button>

            <nc-form-group :label="t('ShareOptionsSharingOptions')" v-if="showAdvanced">
                <nc-checkbox-radio-switch variant="tertiary" :checked.sync="hasExpires" :disabled="!canConfigure">
                    {{ t('ShareOptionsHasExpirationDate') }}
                </nc-checkbox-radio-switch>
                <nc-date-time-picker-native
                        v-model="options.expires"
                        :label="t('ShareOptionsHasExpirationDate')"
                        :min="minExpirationDate"
                        :max="defaultExpires"
                        type="date"
                        v-if="canConfigure && hasExpires"
                        hideLabel/>
                <nc-checkbox-radio-switch variant="tertiary" :checked.sync="options.overwrite" :disabled="!canConfigure" v-if="withOverwrite">
                    {{ t('ShareOptionsOverwriteExisting') }}
                </nc-checkbox-radio-switch>
            </nc-form-group>
            <nc-form-group :label="t('ShareOptionsPermissionOptions')" v-if="showAdvanced && canEdit">
                <nc-checkbox-radio-switch variant="tertiary" :checked.sync="options.editable" :disabled="!canConfigure || preset !== 'custom'">
                    {{ t('SharePermissionCanEdit') }}
                </nc-checkbox-radio-switch>
                <nc-checkbox-radio-switch variant="tertiary" :checked.sync="options.shareable" :disabled="!canConfigure || preset !== 'custom'">
                    {{ t('SharePermissionCanShare') }}
                </nc-checkbox-radio-switch>
            </nc-form-group>
        </div>
    </div>
</template>

<script>
    import EyeIcon from "@icon/Eye.vue";
    import PencilIcon from "@icon/Pencil.vue";
    import MenuUpIcon from "@icon/MenuUp.vue";
    import MenuDownIcon from "@icon/MenuDown.vue";
    import DotsHorizontalIcon from "@icon/DotsHorizontal.vue";
    import TrashCanOutlineIcon from "@icon/TrashCanOutline.vue";
    import NcRadioGroup from "@nextcloud/vue/components/NcRadioGroup";
    import NcRadioGroupButton from "@nextcloud/vue/components/NcRadioGroupButton";
    import NcFormGroup from "@nextcloud/vue/components/NcFormGroup";
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";
    import NcDateTimePickerNative from "@nextcloud/vue/components/NcDateTimePickerNative";
    import SettingsService from "@js/Services/SettingsService";
    import NcButton from "@nextcloud/vue/components/NcButton";

    export default {
        components: {
            NcButton,
            EyeIcon,
            PencilIcon,
            MenuUpIcon,
            MenuDownIcon,
            DotsHorizontalIcon,
            TrashCanOutlineIcon,
            NcRadioGroup,
            NcRadioGroupButton,
            NcFormGroup,
            NcCheckboxRadioSwitch,
            NcDateTimePickerNative
        },
        props     : {
            withDelete    : {
                type   : Boolean,
                default: false
            },
            withOverwrite : {
                type   : Boolean,
                default: true
            },
            disabled      : {
                type   : Boolean,
                default: false
            },
            canEdit       : {
                type   : Boolean,
                default: true
            },
            defaultPreset : {
                type   : String,
                default: null
            },
            defaultExpires: {
                type   : Date,
                default: null
            },
            share         : {
                type   : Object,
                default: null
            }
        },
        data() {
            let hasExpires = this.share?.expires && true || this.defaultExpires !== null,
                editable   = !this.share && SettingsService.get('user.sharing.editable') && this.canEdit,
                shareable  = !this.share && SettingsService.get('user.sharing.resharing'),
                autoPreset = editable && shareable ? 'edit':(!editable && !shareable ? 'view':'custom'),
                preset     = this.defaultPreset ? this.defaultPreset:autoPreset;

            if(this.defaultPreset) {
                editable = this.defaultPreset === 'edit' || this.defaultPreset === 'custom' && this.share?.editable === true;
                shareable = this.defaultPreset === 'edit' || this.defaultPreset === 'custom' && this.share?.shareable === true;
            }

            return {
                preset,
                hasExpires,
                showAdvanced: preset === 'custom',
                options     : {
                    expires  : hasExpires ? this.share.expires:this.defaultExpires,
                    editable,
                    shareable,
                    overwrite: false
                }
            };
        },
        mounted() {
            this.emitShareSettings();
        },
        computed: {
            canConfigure() {
                return !this.disabled && this.preset !== 'delete';
            },
            minExpirationDate() {
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);

                if(this.defaultExpires && tomorrow > this.defaultExpires) {
                    return this.defaultExpires;
                }

                return tomorrow;
            }
        },
        methods : {
            resetPermissions() {
                this.options.expires = this.defaultExpires;
                if(this.preset === 'view') {
                    this.options.editable = false;
                    this.options.shareable = false;
                } else {
                    this.options.editable = this.canEdit;
                    this.options.shareable = true;
                    this.showAdvanced = this.showAdvanced || this.preset === 'custom';
                }
                this.emitShareSettings();
            },
            emitShareSettings() {
                const shareSettings = {
                    action    : this.preset === 'delete' ? 'delete':'create',
                    recipients: [],
                    expires   : this.hasExpires ? this.options.expires:null,
                    editable  : this.options.editable,
                    shareable : this.options.shareable,
                    overwrite : this.withOverwrite && this.options.overwrite
                };

                this.$emit('options', shareSettings);
            }
        },
        watch   : {
            preset() {
                this.resetPermissions();
            },
            defaultPreset(value) {
                if(value === null) {
                    const editable  = !this.share && SettingsService.get('user.sharing.editable') && this.canEdit,
                          shareable = !this.share && SettingsService.get('user.sharing.resharing');
                    this.preset = editable && shareable ? 'edit':(!editable && !shareable ? 'view':'custom');
                } else {
                    this.preset = value;
                }
            },
            canEdit() {
                this.resetPermissions();
            },
            defaultExpires() {
                this.resetPermissions();
            },
            options: {
                handler() {
                    this.emitShareSettings();
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
.share-options-container {
    .share-options-preset {
        display       : flex;
        margin-bottom : .5rem;

        .wide {
            width : 100%;
        }
    }


    .share-options {
        fieldset {
            margin : 1rem 0;
        }
    }
}
</style>