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
    <nc-app-sidebar
            :title="sidebar.title"
            :subtitle="subtitle"
            :subtitleTooltip="subtitleTooltip"
            :compact="compact"
            :active="activeTab"
            v-on:close="close()"
            class="passwords-sidebar-password"
            v-on:opened="opened"
            v-on:closed="closed"
    >
        <template #secondary-actions>
            <nc-action-button close-after-click @click="actions.edit()" class="passwords-password-edit" v-if="isEditable">
                <pencil-icon slot="icon" :size="20"/>
                {{ t('Edit password') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="actions.clone()" class="passwords-password-clone">
                <content-copy-icon slot="icon" :size="20"/>
                {{ t('Edit as new') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="actions.move()" class="passwords-password-move">
                <folder-move-icon slot="icon" :size="20"/>
                {{ t('Move') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="actions.openChangePasswordPage()" class="passwords-password-change-page">
                <lock-reset-icon slot="icon" :size="20"/>
                {{ t('PasswordActionChangePwPage') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="actions.qrcode()" class="passwords-password-qrcode">
                <qrcode-icon slot="icon" :size="20"/>
                {{ t('PasswordActionQrcode') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="actions.print()" v-if="hasPrinting" class="passwords-password-print">
                <printer-icon slot="icon" :size="20"/>
                {{ t('PasswordActionPrint') }}
            </nc-action-button>
            <nc-action-button close-after-click @click="deleteAction" class="passwords-password-delete">
                <trash-can-icon slot="icon" :size="20"/>
                {{ t('Delete') }}
            </nc-action-button>
        </template>
        <template #tertiary-actions>
            <favicon class="icon" :domain="password.website"/>
            <nc-button class="password-details-favorite" :aria-label="t(password.favorite ? 'Remove from favorites':'Mark as favorite')" type="tertiary" @click.prevent="actions.favorite()">
                <star-icon slot="icon" fill-color="var(--color-warning)" v-if="password.favorite" :size="20"/>
                <star-outline-icon slot="icon" v-else :size="20"/>
            </nc-button>
        </template>
        <div slot="header" v-if="!compact">
            <preview :image="password.preview" :icon="password.icon" :link="password.url" :host="password.website"/>
        </div>
        <tags :password="password" slot="description"/>

        <nc-app-sidebar-tab icon="icon-info" :name="t('Details')" id="details-tab">
            <pw-details :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-comment" :name="t('Notes')" id="notes-tab" v-if="password.notes">
            <notes :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-share" :name="t('Share')" id="share-tab" v-if="hasSharing">
            <share :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-history" :name="t('Revisions')" id="revisions-tab" v-if="password.revisions">
            <revisions :password="password"/>
        </nc-app-sidebar-tab>
    </nc-app-sidebar>
</template>


<script>
    import API from '@js/Helper/api';
    import PasswordActions from '@js/Actions/Password/PasswordActions';
    import Application from '@js/Init/Application';
    import SettingsService from '@js/Services/SettingsService';
    import Localisation from '@js/Classes/Localisation';
    import {emit} from '@nextcloud/event-bus';
    import Sidebar from "@js/Models/Sidebar/Sidebar";
    import NcAppSidebar from '@nc/NcAppSidebar';
    import NcAppSidebarTab from '@nc/NcAppSidebarTab';
    import Preview from '@vc/Sidebar/PasswordSidebar/Preview';
    import Tags from '@vc/Tags';
    import PwDetails from '@vc/Sidebar/PasswordSidebar/Tabs/Details';
    import NcButton from '@nc/NcButton';
    import NcActionButton from '@nc/NcActionButton';
    import PencilIcon from '@icon/Pencil';
    import Favicon from '@vc/Favicon';
    import StarIcon from '@icon/Star';
    import StarOutlineIcon from '@icon/StarOutline';
    import QrcodeIcon from '@icon/Qrcode';
    import TrashCanIcon from '@icon/TrashCan';
    import FolderMoveIcon from '@icon/FolderMove';
    import ContentCopyIcon from '@icon/ContentCopy';
    import LockResetIcon from '@icon/LockReset';

    export default {
        components: {
            ContentCopyIcon,
            FolderMoveIcon,
            TrashCanIcon,
            QrcodeIcon,
            StarOutlineIcon,
            StarIcon,
            Favicon,
            PencilIcon,
            LockResetIcon,
            'printer-icon': () => import(/* webpackChunkName: "PrinterIcon" */ '@icon/Printer'),
            'share': () => import(/* webpackChunkName: "PasswordShare" */ '@vc/Sidebar/PasswordSidebar/Tabs/Share'),
            'notes': () => import(/* webpackChunkName: "PasswordNotes" */ '@vc/Sidebar/PasswordSidebar/Tabs/Notes'),
            'revisions': () => import(/* webpackChunkName: "PasswordRevisions" */ '@vc/Sidebar/PasswordSidebar/Tabs/Revisions'),
            Tags,
            Preview,
            PwDetails,
            NcAppSidebar,
            NcActionButton,
            NcAppSidebarTab,
            NcButton
        },

        props: {
            sidebar: {
                type: Sidebar
            }
        },

        data() {
            return {
                password: this.sidebar.item,
                actions : new PasswordActions(this.sidebar.item)
            };
        },

        mounted() {
            this.loadPassword().catch(console.error);
        },

        computed: {
            subtitle() {
                return Localisation.formatDate(this.password.edited);
            },
            subtitleTooltip() {
                return Localisation.formatDateTime(this.password.edited);
            },
            compact() {
                return window.innerWidth <= 640 || window.innerHeight <= 640 || !SettingsService.get('client.ui.password.details.preview');
            },
            hasSharing() {
                return SettingsService.get('server.sharing.enabled');
            },
            hasPrinting() {
                return SettingsService.get('client.ui.password.print');
            },
            activeTab() {
                return `${this.sidebar.tab}-tab`;
            },
            isEditable() {
                return this.password.editable;
            }
        },

        methods: {
            async loadPassword() {
                this.password = await API.showPassword(this.sidebar.item.id, 'model+folder+shares+tags+revisions');
                this.actions = new PasswordActions(this.password);
                emit('passwords:sidebar:updated', this.sidebar);
            },
            close() {
                Application.sidebar = null;
            },
            opened() {
                emit('passwords:sidebar:opened', this.sidebar);
            },
            closed() {
                emit('passwords:sidebar:closed', this.sidebar);
            },
            deleteAction() {
                this.actions.delete()
                    .then(() => {
                        this.close();
                    });
            }
        },

        watch: {
            sidebar: {
                handler(value) {
                    if(value) {
                        this.loadPassword().catch(console.error);
                    } else {

                    }
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
.passwords-sidebar-password.app-sidebar {
    .app-sidebar-header__tertiary-actions {
        position : relative;

        .password-details-favorite {
            position : absolute !important;
            right    : -15px;
            bottom   : -10px;
        }
    }

    .app-sidebar-header__description {
        margin-bottom : .5rem !important;

        div.passwords-tags-field.select {
            border-color : transparent;

            &:focus,
            &:active {
                border-color : var(--color-border-dark);
            }

            &:hover,
            &.vs--open {
                border-color : var(--color-primary-element);
            }
        }
    }
}
</style>