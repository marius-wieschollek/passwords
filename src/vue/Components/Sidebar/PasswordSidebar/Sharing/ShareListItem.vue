<template>
    <nc-list-item
            :name="share.receiver.name"
            :force-display-actions="true"
            class="share-list-item"
    >
        <template #icon>
            <nc-avatar disable-menu :size="32" :user="share.receiver.id" :display-name="share.receiver.name"/>
        </template>

        <template #indicator>
            <sync-icon :size="16" fill-color="var(--color-element-warning)" :title="t('Some data is waiting to be synchronized')" v-if="share.updatePending"/>
        </template>

        <template #subname>
            <nc-actions :menu-name="subMenuName" v-on:opem="submenuOpen = $event" menu-align="right" variant="tertiary-no-background" class="share-item-submenu">
                <template #icon>
                    <menu-up-icon :size="20" v-if="submenuOpen"/>
                    <menu-down-icon :size="20" v-else/>
                </template>
                <nc-action-button @click="editAction('view')">
                    {{ t('SharePermissionView') }}
                    <template #icon>
                        <eye-icon :size="20"/>
                    </template>
                </nc-action-button>
                <nc-action-button @click="editAction('edit')" v-if="canEdit">
                    {{ t('SharePermissionEdit') }}
                    <template #icon>
                        <pencil-icon :size="20"/>
                    </template>
                </nc-action-button>
                <nc-action-button @click="editAction('custom')">
                    {{ t('SharePermissionCustom') }}
                    <template #icon>
                        <dots-horizontal-icon :size="20"/>
                    </template>
                </nc-action-button>
            </nc-actions>
        </template>

        <template #extra-actions v-if="share.expires">
            <nc-popover popup-role="dialog">
                <template #trigger>
                    <nc-button
                            variant="tertiary"
                            :title="ariaLabel"
                            :aria-label="ariaLabel">
                        <template #icon>
                            <clock-outline-icon :size="20"/>
                        </template>
                    </nc-button>
                </template>
                <template #default>
                    <div class="share-expiration-time">
                        <h3>
                            {{ t('ShareExpirationHint') }}
                        </h3>
                        <p>
                            <nc-date-time :timestamp="share.expires" :format="timeFormat" :relative-time="false"/>
                            (
                            <NcDateTime :timestamp="share.expires"/>
                            )
                        </p>
                    </div>
                </template>
            </nc-popover>
        </template>

        <template #actions>
            <nc-action-button @click="editAction('view')">
                {{ t('SharePermissionView') }}
                <template #icon>
                    <eye-icon :size="20"/>
                </template>
            </nc-action-button>
            <nc-action-button @click="editAction('edit')" v-if="canEdit">
                {{ t('SharePermissionEdit') }}
                <template #icon>
                    <pencil-icon :size="20"/>
                </template>
            </nc-action-button>
            <nc-action-button @click="editAction('custom')">
                {{ t('SharePermissionCustom') }}
                <template #icon>
                    <dots-horizontal-icon :size="20"/>
                </template>
            </nc-action-button>
            <nc-action-button @click="deleteAction">
                {{ t('ShareRevokeAccess') }}
                <template #icon>
                    <trash-can-outline-icon :size="20"/>
                </template>
            </nc-action-button>
        </template>
    </nc-list-item>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import SettingsService from "@js/Services/SettingsService";
    import LocalisationService from "@js/Services/LocalisationService";
    import NcListItem from '@nc/NcListItem.js';
    import NcAvatar from '@nc/NcAvatar.js';
    import NcButton from '@nc/NcButton.js';
    import NcActions from '@nc/NcActions.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import NcPopover from '@nc/NcPopover.js';
    import NcDateTime from '@nc/NcDateTime.js';
    import EyeIcon from "@icon/Eye.vue";
    import PencilIcon from "@icon/Pencil.vue";
    import DotsHorizontalIcon from "@icon/DotsHorizontal.vue";
    import TrashCanOutlineIcon from "@icon/TrashCanOutline.vue";
    import SyncIcon from "@icon/Sync.vue";
    import ClockOutlineIcon from "@icon/ClockOutline.vue";
    import ToastService from "@js/Services/ToastService";
    import MenuUpIcon from "@icon/MenuUp.vue";
    import MenuDownIcon from "@icon/MenuDown.vue";

    export default {
        components: {
            Translate,
            NcAvatar,
            NcButton,
            NcActions,
            NcActionButton,
            NcListItem,
            NcPopover,
            NcDateTime,
            EyeIcon,
            SyncIcon,
            PencilIcon,
            MenuUpIcon,
            MenuDownIcon,
            ClockOutlineIcon,
            DotsHorizontalIcon,
            TrashCanOutlineIcon
        },

        props: {
            password: Object,
            share   : Object
        },

        data() {
            return {
                hasShareable: SettingsService.get('server.sharing.resharing'),
                timeFormat  : {dateStyle: 'full'},
                submenuOpen : false
            };
        },

        computed: {
            subMenuName() {
                if(!this.share.editable && !this.share.shareable) {
                    return LocalisationService.translate('SharePermissionCanView');
                }
                if(this.share.editable && this.share.shareable) {
                    return LocalisationService.translate('SharePermissionCanEdit');
                }

                return LocalisationService.translate('SharePermissionCustom');
            },
            canEdit() {
                if(this.password?.share?.editable !== undefined) {
                    return this.password?.share.editable;
                }
                return true;
            },
            ariaLabel() {
                if(!this.share.expires) {
                    return '';
                }

                const date = new Date(this.share.expires).toLocaleString();
                return LocalisationService.translate('ShareExpirationTitle', {date});
            }
        },

        methods: {
            async editAction(preset) {
                this.$emit('edit', {share: this.share, preset});
            },
            async deleteAction() {
                await API.deleteShare(this.share.id);
                this.$emit('delete', this.share);
                ToastService.success('ShareDeletedToast');
            }
        }
    };
</script>

<style lang="scss">

.share-list-item {
    .share-item-submenu {
        div.v-popper button.action-item__menutoggle span.button-vue__wrapper {
            // Emulate NcButton's alignment=center-reverse
            flex-direction : row-reverse !important;
        }
    }
}

.share-expiration-time {
    h3 {
        text-align     : center;
        font-size      : 1rem;
        margin         : .5rem .5rem 0;
        padding-bottom : .5rem;
        border-bottom  : 1px solid var(--color-border);
    }

    p {
        padding   : 1rem;
        max-width : 300px;
    }
}
</style>