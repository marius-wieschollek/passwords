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
                <nc-note-card type="info" :text="t('ShareGroupShareWarning')" v-if="groupShareWarning"/>

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

                <share-options-form :with-delete="true" :disabled="!hasUsersSelected" v-on:options="setOptions"/>

                <div class="buttons">
                    <nc-button @click="submit" type="primary" :disabled="!hasUsersSelected">
                        {{ t(options.action !== 'delete' ? 'BatchActionShareSubmit':'BatchActionShareSubmitRevoke') }}
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
    import NcNoteCard from '@nc/NcNoteCard.js';
    import NcFormGroup from '@nc/NcFormGroup.js';
    import LocalisationService from "@js/Services/LocalisationService";
    import ShareOptionsForm from "@vc/Sharing/ShareOptionsForm.vue";

    export default {
        components: {
            ShareOptionsForm,
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
            return {
                selectedUsers : null,
                availableUsers: [],
                options       : {action: 'create'},
                container     : UtilityService.popupContainer(true),
                initializing  : true
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
            groupShareWarning() {
                return this.selectedUsers && this.selectedUsers.some(item => item.isNoUser);
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
            setOptions(d) {
                this.options = d;

                this.$emit('configuring', true);
            },
            close() {
                this.resolve(null);
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
            },
            submit() {
                let shareSettings = this.options;
                shareSettings.recipients = this.selectedUsers;

                this.resolve(shareSettings);
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
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

        .select-users {
            display       : flex;
            margin-bottom : 2rem;

            .wide {
                width : 100%;
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