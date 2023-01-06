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
            v-on:close="close()"
            class="passwords-sidebar-password"
            v-on:opened="opened"
            v-on:closed="closed"
    >
        <template #secondary-actions>
            <nc-action-button @click.prevent="actions.edit()">
                <pencil-icon slot="icon" :size="20"/>
                {{t('Edit password')}}
            </nc-action-button>
            <nc-action-button @click.prevent="actions.qrcode()">
                <qrcode-icon slot="icon" :size="20"/>
                {{t('PasswordActionQrcode')}}
            </nc-action-button>
            <nc-action-button @click.prevent="actions.print()">
                <printer-icon slot="icon" :size="20"/>
                {{t('PasswordActionPrint')}}
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
        <div slot="description">
            <tags :password="password"/>
        </div>

        <nc-app-sidebar-tab icon="icon-info" :name="t('Details')" id="details-tab">
            <pw-details :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-comment" :name="t('Notes')" id="notes-tab" v-if="password.notes">
            <notes :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-share" :name="t('Share')" id="share-tab" v-if="hasSharing">
            <share :password="password"/>
        </nc-app-sidebar-tab>
        <nc-app-sidebar-tab icon="icon-history" :name="t('Revisions')" id="revisions-tab">
            <revisions :password="password"/>
        </nc-app-sidebar-tab>
    </nc-app-sidebar>
</template>


<script>
    import Sidebar from "@js/Models/Sidebar/Sidebar";
    import NcAppSidebar from '@nc/NcAppSidebar';
    import NcAppSidebarTab from '@nc/NcAppSidebarTab';
    import Localisation from "@js/Classes/Localisation";
    import Preview from "@vc/Sidebar/PasswordSidebar/Preview";
    import Tags from '@vc/Sidebar/PasswordSidebar/Tags';
    import Revisions from "@vc/Sidebar/PasswordSidebar/Tabs/Revisions";
    import Notes from "@vc/Sidebar/PasswordSidebar/Tabs/Notes";
    import PwDetails from '@vc/Sidebar/PasswordSidebar/Tabs/Details';
    import Share from "@vc/Sidebar/PasswordSidebar/Tabs/Share";
    import API from "@js/Helper/api";
    import SettingsService from "@js/Services/SettingsService";
    import NcButton from '@nc/NcButton';
    import NcActionButton from '@nc/NcActionButton';
    import PencilIcon from "@icon/Pencil";
    import PrinterIcon from "@icon/Printer.vue";
    import Favicon from "@vc/Favicon.vue";
    import StarIcon from "vue-material-design-icons/Star";
    import StarOutlineIcon from "vue-material-design-icons/StarOutline";
    import PasswordActions from "@js/Actions/Password/PasswordActions";
    import Application from "@js/Init/Application";
    import { emit } from '@nextcloud/event-bus'
    import QrcodeIcon from "vue-material-design-icons/Qrcode.vue";

    export default {
        components: {
            QrcodeIcon,
            StarOutlineIcon,
            StarIcon,
            Favicon,
            PrinterIcon,
            PencilIcon,
            Share,
            Notes,
            Revisions,
            Tags,
            Preview,
            PwDetails,
            NcAppSidebar,
            NcAppSidebarTab,
            NcActionButton,
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
                return window.innerWidth <= 640 || !SettingsService.get('client.ui.password.details.preview');
            },
            hasSharing() {
                return SettingsService.get('server.sharing.enabled');
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
.passwords-sidebar-password {
    .app-sidebar-header__tertiary-actions {
        position : relative;

        .password-details-favorite {
            position : absolute !important;
            right    : -15px;
            bottom   : -10px;
        }
    }
}
</style>