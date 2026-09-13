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
    <div class="share-edit-form">
        <share-user-select v-model="recipients" v-if="share === null"/>
        <nc-note-card type="info" v-else>
            <div class="share-receiver">
                {{ t('ShareEditUser', {name: share.receiver.name}) }}
            </div>
            <template #icon>
                <nc-avatar disable-menu :size="32" :user="share.receiver.id" :display-name="share.receiver.name"/>
            </template>
        </nc-note-card>

        <share-options-form
                :share="share"
                :can-edit="canEdit"
                :with-overwrite="share === null"
                :default-preset="defaultPreset"
                :default-expires="defaultExpires"
                v-on:options="setOptions"
                v-if="isReadyForConfigure"
        />

        <div class="share-edit-actions" v-if="isReadyForConfigure">
            <nc-button @click="cancelAction" type="secondary">
                {{ t('Cancel') }}
            </nc-button>
            <nc-button @click="revokeAction" type="error" v-if="share !== null">
                {{ t('ShareRevokeAccess') }}
            </nc-button>
            <nc-button @click="saveAction" type="primary">
                {{ t(share === null ? 'ShareActionCreate':'ShareActionUpdate') }}
            </nc-button>
        </div>
    </div>
</template>

<script>

    import ShareUserSelect from "@vc/Sidebar/PasswordSidebar/Sharing/ShareUserSelect.vue";
    import ShareOptionsForm from "@vc/Sharing/ShareOptionsForm.vue";
    import NcButton from "@nextcloud/vue/components/NcButton";
    import ShareBatchAction from "@js/Actions/BatchActions/ShareBatchAction";
    import LoggingService from "@js/Services/LoggingService";
    import UtilityService from "@js/Services/UtilityService";
    import API from "@js/Helper/api";
    import ToastService from "@js/Services/ToastService";
    import NcAvatar from "@nextcloud/vue/components/NcAvatar";
    import NcNoteCard from "@nextcloud/vue/components/NcNoteCard";

    export default {
        components: {
            ShareOptionsForm,
            ShareUserSelect,
            NcButton,
            NcAvatar,
            NcNoteCard
        },
        props     : {
            password     : Object,
            share        : {
                type   : Object,
                default: () => {
                    return null;
                }
            },
            defaultPreset: {
                type   : String,
                default: null
            }
        },
        data() {
            return {
                recipients: [],
                options   : null
            };
        },
        computed: {
            isReadyForConfigure() {
                return this.recipients.length > 0 || this.share !== null;
            },
            canEdit() {
                return this.password.share === null || this.password.share.editable;
            },
            defaultExpires() {
                if(this.password.share !== null && typeof this.password.share !== 'string') {
                    return this.password.share.expires;
                }

                return null;
            }
        },
        methods : {
            setOptions(d) {
                this.options = d;

                this.$emit('configuring', true);
            },
            cancelAction() {
                this.$emit('cancel');
                this.$emit('configuring', false);
                this.resetForm();
            },
            async revokeAction() {
                await API.deleteShare(this.share.id);
                ToastService.success('ShareDeletedToast');

                this.$emit('revoke', this.share);
                this.$emit('configuring', false);
                this.resetForm();
            },
            async saveAction() {
                if(!this.share) {
                    await this.createAction();
                } else {
                    await this.updateAction();
                }

                this.$emit('save', this.share);
                this.$emit('configuring', false);
                this.resetForm();
            },
            async createAction() {
                this.options.recipients = this.recipients;

                const action = new ShareBatchAction(
                    {
                        folders  : [],
                        passwords: [this.password],
                        tags     : []
                    },
                    this.options
                );

                await action.run().catch(LoggingService.catch);
            },
            async updateAction() {
                let share = UtilityService.cloneObject(this.share);
                share.editable = this.options.editable;
                share.shareable = this.options.shareable;
                share.expires = this.options.expires;

                API.updateShare(share);
            },
            resetForm() {
                this.recipients = [];
                this.options = null;
            }
        }
    };
</script>


<style scoped lang="scss">
.share-edit-form {
    .share-receiver {
        height      : 100%;
        display     : flex;
        align-items : center;
    }

    .share-options-container {
        margin-top : 1rem;
    }

    .share-edit-actions {
        display         : flex;
        justify-content : space-between;
        width           : 100%;
        margin-top      : 2rem;
    }
}
</style>