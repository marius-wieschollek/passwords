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
    <nc-modal
            class="pw-batch-share-dialog"
            ref="window"
            size="normal"
            :inlineActions="0"
            v-on:close="close"
            :container="container"
            :name="t('BatchActionShareDialogTitle')">
        <template #default>
            <div class="pw-batch-share">
                <nc-note-card type="warning" :text="t('BatchActionShareDialogPasswordsOnlyWarning')" v-if="passwordsOnlyWarning"/>
                <nc-note-card type="warning" :text="t('BatchActionShareDialogCseDisableWarning')" v-if="cseDisableWarning"/>

                <div class="select-users">
                    <nc-select-users :input-label="t('BatchActionShareSelectUsersLabel')"
                                     :multiple="true"
                                     :options="availableUsers"
                                     :placeholder="t('BatchActionShareSelectUsersPlaceholder')"
                                     v-model="selectedUsers"
                                     @search="fetchShareSuggestions"
                                     :disabled="initializing"
                                     class="wide"
                                     keep-open
                    />
                </div>
                <div class="select-permissions">
                    <NcRadioGroup v-model="permissionLevel" label="permission level" class="wide" hide-label>
                        <NcRadioGroupButton :label="t('BatchActionSharePermissionView')" value="view" :disabled="!hasUsersSelected">
                            {{ t('BatchActionSharePermissionView') }}
                            <template #icon>
                                <eye-icon :size="20"/>
                            </template>
                        </NcRadioGroupButton>
                        <NcRadioGroupButton :label="t('BatchActionSharePermissionEdit')" value="edit" :disabled="!hasUsersSelected">
                            {{ t('BatchActionSharePermissionEdit') }}
                            <template #icon>
                                <pencil-icon :size="20"/>
                            </template>
                        </NcRadioGroupButton>
                        <NcRadioGroupButton :label="t('BatchActionSharePermissionCustom')" value="custom" :disabled="!hasUsersSelected">
                            {{ t('BatchActionSharePermissionCustom') }}
                            <template #icon>
                                <dots-horizontal-icon :size="20"/>
                            </template>
                        </NcRadioGroupButton>
                        <NcRadioGroupButton :label="t('BatchActionSharePermissionDelete')" value="delete" :disabled="!hasUsersSelected">
                            {{ t('BatchActionSharePermissionDelete') }}
                            <template #icon>
                                <trash-can-outline-icon :size="20"/>
                            </template>
                        </NcRadioGroupButton>
                    </NcRadioGroup>
                </div>
                <div class="select-options">
                    <nc-button @click="showAdvanced = !showAdvanced" variant="tertiary" alignment="start-reverse" :disabled="!hasUsersSelected">
                        {{ t('BatchActionShareAdvanced') }}
                        <template #icon>
                            <menu-up-icon :size="20" v-if="showAdvanced"/>
                            <menu-down-icon :size="20" v-else/>
                        </template>
                    </nc-button>

                    <nc-form-group :label="t('BatchActionShareShareOptions')" v-if="showAdvanced">
                        <nc-checkbox-radio-switch variant="tertiary" :checked.sync="hasExpires" :disabled="!canConfigure">{{ t('BatchActionShareExpiryDate') }}</nc-checkbox-radio-switch>
                        <nc-date-time-picker-native v-model="expires" :label="t('BatchActionShareExpiryDate')" type="date" v-if="canConfigure && hasExpires" hideLabel/>
                    </nc-form-group>
                    <nc-form-group :label="t('BatchActionSharePermissionOptions')" v-if="showAdvanced">
                        <nc-checkbox-radio-switch variant="tertiary" :checked.sync="permissions.edit" :disabled="!canConfigure || permissionLevel !== 'custom'">
                            {{ t('BatchActionShareCanEdit') }}
                        </nc-checkbox-radio-switch>
                        <nc-checkbox-radio-switch variant="tertiary" :checked.sync="permissions.share" :disabled="!canConfigure || permissionLevel !== 'custom'">
                            {{ t('BatchActionShareCanShare') }}
                        </nc-checkbox-radio-switch>
                    </nc-form-group>
                    <nc-form-group :label="t('BatchActionShareCreateOptions')" v-if="showAdvanced">
                        <nc-checkbox-radio-switch variant="tertiary" :checked.sync="overwriteExisting" :disabled="!canConfigure">
                            {{ t('BatchActionShareCreateOverwrite') }}
                        </nc-checkbox-radio-switch>
                    </nc-form-group>
                </div>

                <div class="buttons">
                    <nc-button @click="submit" type="primary" :disabled="!hasUsersSelected">
                        {{ t(permissionLevel !== 'delete' ? 'BatchActionShareSubmit':'BatchActionShareSubmitRevoke') }}
                    </nc-button>
                </div>
            </div>
        </template>
    </nc-modal>
</template>

<script>
    import NcModal from '@nc/NcModal.js';
    import NcButton from '@nc/NcButton.js';
    import UtilityService from "@js/Services/UtilityService";
    import NcSelectUsers from '@nc/NcSelectUsers.js';
    import API from "@js/Helper/api";
    import LoggingService from "@js/Services/LoggingService";
    import NcRadioGroup from '@nc/NcRadioGroup.js';
    import NcRadioGroupButton from '@nc/NcRadioGroupButton.js';
    import EyeIcon from "@icon/Eye";
    import PencilIcon from "@icon/Pencil";
    import MenuUpIcon from "@icon/MenuUp";
    import MenuDownIcon from "@icon/MenuDown";
    import DotsHorizontalIcon from "@icon/DotsHorizontal";
    import TrashCanOutlineIcon from "@icon/TrashCanOutline";
    import NcCheckboxRadioSwitch from "@nc/NcCheckboxRadioSwitch.js";
    import NcDateTimePickerNative from "@nc/NcDateTimePickerNative.js";
    import SettingsService from "@js/Services/SettingsService";
    import NcNoteCard from '@nc/NcNoteCard.js';
    import NcFormGroup from '@nc/NcFormGroup.js';
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {
            EyeIcon,
            PencilIcon,
            MenuUpIcon,
            MenuDownIcon,
            DotsHorizontalIcon,
            TrashCanOutlineIcon,
            NcSelectUsers,
            NcModal,
            NcRadioGroup,
            NcRadioGroupButton,
            NcButton,
            NcNoteCard,
            NcFormGroup,
            NcCheckboxRadioSwitch,
            NcDateTimePickerNative
        },
        props     : {
            resolve             : Function,
            passwordsOnlyWarning: Boolean,
            cseDisableWarning   : Boolean
        },
        data() {
            let edit            = SettingsService.get('user.sharing.editable'),
                share           = SettingsService.get('user.sharing.resharing'),
                permissionLevel = edit && share ? 'edit':(!edit && !share ? 'view':'custom');

            return {
                selectedUsers    : null,
                availableUsers   : [],
                groupSharing     : SettingsService.get('server.sharing.groups.enabled'),
                permissionLevel,
                permissions      : {edit, share},
                overwriteExisting: false,
                expires          : null,
                hasExpires       : false,
                showAdvanced     : false,
                container        : UtilityService.popupContainer(true),
                initializing     : true
            };
        },
        mounted() {
            this.fetchShareSuggestions('')
                .catch(LoggingService.catch);
        },
        computed: {
            hasUsersSelected() {
                return this.selectedUsers !== null && (!Array.isArray(this.selectedUsers) || this.selectedUsers.length > 0);
            },
            canConfigure() {
                return this.hasUsersSelected && this.permissionLevel !== 'delete';
            }
        },
        methods : {
            async fetchShareSuggestions(query) {
                let matches      = await API.findShareRecipients(query, 25),
                    groupSubname = LocalisationService.translate('BatchShareSubnameGroup'),
                    users        = [];

                for(let match of matches) {
                    if(this.selectedUsers !== null && this.selectedUsers.indexOf(match) !== -1) {
                        continue;
                    }
                    if(match.type === 'group' && !this.groupSharing) continue;

                    users.push(
                        {
                            id         : match.id,
                            displayName: match.name,
                            isNoUser   : match.type === 'group',
                            subname    : match.type === 'group' ? groupSubname:match.context
                        });
                }

                this.availableUsers = users;
                this.initializing = false;
            },
            close() {
                this.resolve(null);
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
            },
            resetPermissions() {
                if(this.permissionLevel === 'view') {
                    this.permissions.edit = false;
                    this.permissions.share = false;
                } else if(this.permissionLevel === 'edit') {
                    this.permissions.edit = true;
                    this.permissions.share = true;
                }
            },
            submit() {
                const shareSettings = {
                    action     : this.permissionLevel === 'delete' ? 'delete':'create',
                    recipients : this.selectedUsers,
                    permissions: this.permissions,
                    expires    : this.hasExpires ? this.expires:null,
                    overwrite  : this.overwriteExisting
                };

                this.resolve(shareSettings);
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
            }
        },

        watch: {
            permissionLevel(value) {
                this.resetPermissions();
                if(value === 'custom') {
                    this.showAdvanced = true;
                }
            }
        }
    };
</script>

<style lang="scss">
.pw-batch-share-dialog {
    .pw-batch-share {
        overflow-x    : hidden;
        border-radius : var(--border-radius-container);
        padding       : 1rem;

        .select-users,
        .select-permissions {
            display       : flex;
            margin-bottom : 2rem;

            .wide {
                width : 100%;
            }
        }

        .select-options {
            fieldset {
                margin : 1rem 0;
            }
        }


        .buttons {
            margin-top      : 2rem;
            display         : flex;
            justify-content : end;
        }
    }

    .button-vue.modal-container__close {
        z-index : 1;
    }
}
</style>