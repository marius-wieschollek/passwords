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
    <div class="actions">
        <nc-actions
                :inline="hasCustomAction ? 1:0"
                :boundaries-element="getBoundariesElement"
                :container="getBoundariesElement"
                :open="openedMenu"
                @closed="$emit('closed')"
                variant="tertiary"
                @click.stop.prevent
        >
            <slot name="custom-action">
                <nc-action-button @click="runCustomAction" v-if="hasCustomAction">
                    <template #icon>
                        <information-outline-icon :size="20" v-if="customAction === 'details'"/>
                        <account-plus-outline-icon :size="20" v-else-if="customAction === 'share'"/>
                        <content-copy-icon :size="20" v-else-if="customAction === 'edit'"/>
                        <printer-icon :size="20" v-else-if="customAction === 'print'"/>
                        <open-in-new-icon :size="20" v-else-if="customAction === 'open-url'"/>
                        <qrcode-icon :size="20" v-else-if="customAction === 'qrcode'"/>
                        <clipboard-arrow-left-outline-icon :size="20" v-else/>
                    </template>
                </nc-action-button>
            </slot>
            <nc-action-button @click="actions.favorite()">
                <template #icon>
                    <star-icon :size="20" fill-color="var(--color-element-warning)" v-if="password.favorite"/>
                    <star-outline-icon :size="20" fill-color="var(--color-placeholder-dark)" v-else/>
                </template>
                {{ password.favorite ? t('BatchActionRemoveFavorites'):t('BatchActionAddFavorites') }}
            </nc-action-button>
            <nc-action-button @click="$emit('details-action', null)">
                <template #icon>
                    <information-outline-icon :size="20"/>
                </template>
                {{ t('Details') }}
            </nc-action-button>
            <nc-action-button @click="$emit('details-action', 'share')">
                <template #icon>
                    <account-plus-outline-icon :size="20"/>
                </template>
                {{ t('Share') }}
            </nc-action-button>
            <nc-action-separator/>
            <nc-action-button @click="$emit('edit-action')" v-if="password.editable">
                <template #icon>
                    <pencil-icon :size="20"/>
                </template>
                {{ t('Edit') }}
            </nc-action-button>
            <nc-action-button @click="cloneAction" v-if="password.editable">
                <template #icon>
                    <content-copy-icon :size="20"/>
                </template>
                {{ t('Edit as new') }}
            </nc-action-button>
            <nc-action-button @click="moveAction">
                <template #icon>
                    <folder-move-icon :size="20"/>
                </template>
                {{ t('Move') }}
            </nc-action-button>
            <nc-action-separator/>
            <nc-action-button @click="$emit('copy-action', 'password')" v-if="showCopyOptions">
                <template #icon>
                    <clipboard-arrow-left-outline-icon :size="20"/>
                </template>
                {{ t('Copy Password') }}
            </nc-action-button>
            <nc-action-button @click="$emit('copy-action', 'username')" v-if="showCopyOptions">
                <template #icon>
                    <clipboard-arrow-left-outline-icon :size="20"/>
                </template>
                {{ t('Copy User') }}
            </nc-action-button>
            <nc-action-button @click="$emit('copy-action', 'url')" v-if="password.url">
                <template #icon>
                    <clipboard-arrow-left-outline-icon :size="20"/>
                </template>
                {{ t('Copy Url') }}
            </nc-action-button>
            <nc-action-link :href="password.url" target="_blank">
                <template #icon>
                    <open-in-new-icon :size="20"/>
                </template>
                {{ t('Open Url') }}
            </nc-action-link>
            <nc-action-separator/>
            <nc-action-button @click="actions.openChangePasswordPage()" v-if="password.url">
                <template #icon>
                    <lock-reset-icon :size="20"/>
                </template>
                {{ t('PasswordActionChangePwPage') }}
            </nc-action-button>
            <nc-action-button @click="actions.qrcode()">
                <template #icon>
                    <qrcode-icon :size="20"/>
                </template>
                {{ t('PasswordActionQrcode') }}
            </nc-action-button>
            <nc-action-button @click="actions.print()" v-if="isPrintEnabled">
                <template #icon>
                    <printer-icon :size="20"/>
                </template>
                {{ t('PasswordActionPrint') }}
            </nc-action-button>
            <nc-action-separator/>
            <nc-action-button @click="$emit('restore', password)" v-if="password.trashed">
                <template #icon>
                    <restore-icon :size="20"/>
                </template>
                {{ t('Restore') }}
            </nc-action-button>
            <nc-action-button @click="deleteAction">
                <template #icon>
                    <trash-can-icon :size="20"/>
                </template>
                {{ t('Delete') }}
            </nc-action-button>
        </nc-actions>
    </div>
</template>

<script>
    import TrashCanIcon from "@icon/TrashCan";
    import OpenInNewIcon from "@icon/OpenInNew";
    import FolderMoveIcon from "@icon/FolderMove";
    import ContentCopyIcon from "@icon/ContentCopy";
    import PencilIcon from "@icon/Pencil";
    import AccountPlusOutlineIcon from "@icon/AccountPlusOutline.vue";
    import InformationOutlineIcon from "@icon/InformationOutline";
    import ClipboardArrowLeftOutlineIcon from "@icon/ClipboardArrowLeftOutline";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import QrcodeIcon from "@icon/Qrcode";
    import LockResetIcon from "@icon/LockReset";
    import NcActions from '@nc/NcActions.js';
    import NcDateTime from '@nc/NcDateTime.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import NcActionLink from '@nc/NcActionLink.js';
    import NcActionSeparator from '@nc/NcActionSeparator.js';
    import PasswordActions from "@js/Actions/Password/PasswordActions";
    import SettingsService from "@js/Services/SettingsService";
    import PasswordManager from "@js/Manager/PasswordManager";

    export default {
        components: {
            LockResetIcon,
            QrcodeIcon,
            StarOutlineIcon,
            StarIcon,
            ClipboardArrowLeftOutlineIcon,
            InformationOutlineIcon,
            AccountPlusOutlineIcon,
            PencilIcon,
            ContentCopyIcon,
            FolderMoveIcon,
            OpenInNewIcon,
            'printer-icon': () => import(/* webpackChunkName: "PrinterIcon" */ '@icon/Printer'),
            'restore-icon': () => import(/* webpackChunkName: "RestoreIcon" */ '@icon/Restore'),
            TrashCanIcon,
            NcActions,
            NcDateTime,
            NcActionLink,
            NcActionButton,
            NcActionSeparator
        },

        props: {
            actions   : {
                type: PasswordActions
            },
            password  : {
                type: Object
            },
            openedMenu: {
                type: Boolean
            }
        },

        computed: {
            showCopyOptions() {
                return window.innerWidth < 361 || SettingsService.get('client.ui.password.menu.copy');
            },
            isPrintEnabled() {
                return SettingsService.get('client.ui.password.print');
            },
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action') ||
                       (
                           SettingsService.get('client.ui.password.custom.action') !== 'none' &&
                           (SettingsService.get('client.ui.password.custom.action') !== 'edit' || this.password.editable)
                       );
            },
            customAction() {
                return SettingsService.get('client.ui.password.custom.action');
            },
            getBoundariesElement() {
                return document.querySelector('.app-content .item-list');
            }
        },

        methods: {
            runCustomAction() {
                let action = SettingsService.get('client.ui.password.custom.action');
                if(action === 'share' || action === 'details') {
                    this.$emit('details-action', action);
                } else if(action === 'print') {
                    this.actions.print();
                } else if(action === 'qrcode') {
                    this.actions.qrcode();
                } else {
                    this.$emit('click-action', action);
                }
            },
            cloneAction() {
                PasswordManager.clonePassword(this.password);
            },
            deleteAction() {
                PasswordManager.deletePassword(this.password);
            },
            moveAction() {
                PasswordManager.movePassword(this.password);
            }
        }
    };

</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .actions {
                display         : flex;
                align-self      : center;
                align-items     : center;
                justify-content : center;
            }
        }
    }
}
</style>