<template>
    <div @click="clickAction($event)"
         @click.middle="wheelClickAction($event)"
         @dblclick="doubleClickAction($event)"
         @dragstart="dragStartAction($event)"
         :class="className"
         :data-password-id="password.id"
         :data-password-title="password.label">
        <star-icon class="favorite" data-item-action="favorite" fill-color="var(--color-warning)" @click.prevent.stop="favoriteAction" v-if="password.favorite"/>
        <star-outline-icon class="favorite" data-item-action="favorite" fill-color="var(--color-placeholder-dark)" @click.prevent.stop="favoriteAction" v-else/>
        <favicon class="favicon" :domain="password.website" :title="getTitle" v-if="isVisible"/>
        <div class="title" :title="getTitle"><span>{{ getTitle }}</span></div>
        <ul slot="middle" class="tags" v-if="showTags" :style="tagStyle">
            <li v-for="tag in getTags"
                :key="tag.id"
                :title="tag.label"
                :style="{color: tag.color}"
                @click="openTagAction($event, tag.id)">&nbsp;
            </li>
        </ul>
        <slot name="middle"/>
        <router-link :to="securityRoute" :title="securityTitle" v-if="password.statusCode === 'DUPLICATE'" @click.prevent.stop @dblclick.prevent.stop>
            <shield-half-full-icon :size="20" fill-color="var(--color-warning)"/>
        </router-link>
        <shield-half-full-icon :size="20" :fill-color="securityColor" :title="securityTitle" v-else/>
        <i v-if="hasCustomAction" @click="runCustomAction" class="action-button fa" :class="customActionClass"></i>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="passwordActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <translate tag="li" data-item-action="details" @click="detailsAction()" say="Details">
                            <information-variant-icon slot="icon"/>
                        </translate>
                        <translate tag="li" data-item-action="share" @click="detailsAction('share')" say="Share">
                            <share-variant-icon slot="icon"/>
                        </translate>
                        <translate tag="li" data-item-action="edit" @click="editAction()" v-if="password.editable" say="Edit">
                            <pencil-icon slot="icon"/>
                        </translate>
                        <translate tag="li" data-item-action="edit-new" @click="cloneAction()" v-if="password.editable" say="Edit as new">
                            <content-copy-icon slot="icon"/>
                        </translate>
                        <translate tag="li" data-item-action="move" @click="moveAction" say="Move">
                            <folder-move-icon slot="icon"/>
                        </translate>
                        <translate tag="li" v-if="showCopyOptions" @click="copyAction('password')" say="Copy Password">
                            <clipboard-arrow-left-outline-icon slot="icon"/>
                        </translate>
                        <translate tag="li" v-if="showCopyOptions" @click="copyAction('username')" say="Copy User">
                            <clipboard-arrow-left-outline-icon slot="icon"/>
                        </translate>
                        <translate tag="li" v-if="password.url" @click="copyAction('url')" say="Copy Url">
                            <clipboard-arrow-left-outline-icon slot="icon"/>
                        </translate>
                        <li v-if="password.url">
                            <translate tag="a" data-item-action="open-url" :href="password.url" target="_blank" say="Open Url">
                                <open-in-new-icon slot="icon"/>
                            </translate>
                        </li>
                        <translate tag="li" v-if="password.url" @click="actions.openChangePasswordPage()" say="PasswordActionChangePwPage">
                            <lock-reset-icon slot="icon"/>
                        </translate>
                        <translate tag="li" @click="actions.qrcode()" data-item-action="qrcode" say="PasswordActionQrcode">
                            <qrcode-icon slot="icon"/>
                        </translate>
                        <translate tag="li" v-if="isPrintEnabled" @click="printAction()" data-item-action="print" say="PasswordActionPrint">
                            <printer-icon slot="icon"/>
                        </translate>
                        <translate tag="li" data-item-action="delete" @click="deleteAction()" say="Delete">
                            <trash-can-icon slot="icon"/>
                        </translate>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date" :title="dateTitle">{{ getDate }}</div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import DragManager from '@js/Manager/DragManager';
    import Localisation from "@js/Classes/Localisation";
    import PasswordManager from '@js/Manager/PasswordManager';
    import SettingsService from '@js/Services/SettingsService';
    import Favicon from "@vc/Favicon";
    import SearchManager from "@js/Manager/SearchManager";
    import ContextMenuService from "@js/Services/ContextMenuService";
    import TrashCanIcon from "@icon/TrashCan";
    import OpenInNewIcon from "@icon/OpenInNew";
    import FolderMoveIcon from "@icon/FolderMove";
    import ContentCopyIcon from "@icon/ContentCopy";
    import PencilIcon from "@icon/Pencil";
    import ShareVariantIcon from "@icon/ShareVariant";
    import InformationVariantIcon from "@icon/InformationVariant";
    import ClipboardArrowLeftOutlineIcon from "@icon/ClipboardArrowLeftOutline";
    import PasswordSidebar from "@js/Models/Sidebar/PasswordSidebar";
    import Application from "@js/Init/Application";
    import StarIcon from "@icon/Star";
    import StarOutlineIcon from "@icon/StarOutline";
    import PasswordActions from "@js/Actions/Password/PasswordActions";
    import {subscribe, unsubscribe} from '@nextcloud/event-bus';
    import QrcodeIcon from "@icon/Qrcode";
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";
    import LockResetIcon from "@icon/LockReset";

    export default {
        components: {
            LockResetIcon,
            ShieldHalfFullIcon,
            QrcodeIcon,
            StarOutlineIcon,
            StarIcon,
            ClipboardArrowLeftOutlineIcon,
            InformationVariantIcon,
            ShareVariantIcon,
            PencilIcon,
            ContentCopyIcon,
            FolderMoveIcon,
            OpenInNewIcon,
            'printer-icon': () => import(/* webpackChunkName: "PrinterIcon" */ '@icon/Printer'),
            TrashCanIcon,
            Favicon,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                clickTimeout : null,
                showMenu     : false,
                detailsActive: false,
                actions      : new PasswordActions(this.password)
            };
        },

        computed: {
            securityColor() {
                switch(this.password.status) {
                    case 0:
                        return 'var(--color-success)';
                    case 1:
                        return 'var(--color-warning)';
                    case 2:
                        return 'var(--color-error)';
                    case 3:
                        return 'var(--color-main-text)';
                }
            },
            securityTitle() {
                let label = 'Unknown';
                if(this.password.status === 0) label = 'Secure';
                if(this.password.status === 1) label = `Weak (${this.password.statusCode.toLowerCase().capitalize()})`;
                if(this.password.status === 2) label = 'Breached';

                return Localisation.translate(label);
            },
            securityRoute() {
                return {name: 'Search', params: {query: btoa('hash:' + this.password.hash)}};
            },
            showCopyOptions() {
                return window.innerWidth < 361 || SettingsService.get('client.ui.password.menu.copy');
            },
            showTags() {
                return window.innerWidth > 360 && SettingsService.get('client.ui.list.tags.show') && this.password.tags;
            },
            getTitle() {
                let titleField = SettingsService.get('client.ui.password.field.title'),
                    showUser   = SettingsService.get('client.ui.password.user.show'),
                    title      = this.password[titleField];

                if(!title && this.password.label) title = this.password.label;
                if(!title && this.password.website) title = this.password.website;
                if(showUser && this.password.username) title = `${title} – ${this.password.username}`;
                return title;
            },
            getTags() {
                return Utility.sortApiObjectArray(this.password.tags, 'label');
            },
            tagStyle() {
                let length = Utility.objectToArray(this.password.tags).length;
                if(length) {
                    return {
                        'padding-left': (length + 18) + 'px'
                    };
                }

                return {};
            },
            getDate() {
                return Localisation.formatDate(this.password.edited);
            },
            dateTitle() {
                return Localisation.translate(
                    'Last modified on {date}',
                    {date: Localisation.formatDateTime(this.password.edited)}
                );
            },
            isVisible() {
                return !SearchManager.status.active || SearchManager.status.ids.indexOf(this.password.id) !== -1;
            },
            className() {
                let classNames = 'row password';

                if(this.detailsActive) classNames += ' details-open';
                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.password.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            },
            isPrintEnabled() {
                return SettingsService.get('client.ui.password.print');
            },
            hasCustomAction() {
                return SettingsService.get('client.ui.password.custom.action') !== 'none';
            },
            customActionClass() {
                switch(SettingsService.get('client.ui.password.custom.action')) {
                    case 'details':
                        return 'fa-info';
                    case 'share':
                        return 'fa-share-alt';
                    case 'edit':
                        return 'fa-pencil';
                    case 'print':
                        return 'fa-print';
                    case 'open-url':
                        return 'fa-link';
                    case 'qrcode':
                        return 'fa-qrcode';
                    default:
                        return 'fa-clipboard';
                }
            }
        },

        mounted() {
            ContextMenuService.register(this.password, this.$el);
        },

        methods: {
            clickAction($event) {
                if($event && ($event.detail !== 1 || $event.target.closest('.more') !== null || $event.target.classList.contains('duplicate') || $event.target.classList.contains(
                    'action-button'))) {
                    return;
                }
                if(this.clickTimeout) clearTimeout(this.clickTimeout);

                let action = SettingsService.get('client.ui.password.click.action');
                if(action !== 'none') this.runClickAction(action, 300);
            },
            wheelClickAction() {
                let action = SettingsService.get('client.ui.password.wheel.action');
                if(action !== 'none') this.runClickAction(action);
            },
            doubleClickAction($event) {
                if($event && ($event.target.closest('.more') !== null || $event.target.classList.contains('duplicate'))) return;
                let action = SettingsService.get('client.ui.password.dblClick.action');

                if(action !== 'none') {
                    if(this.clickTimeout) clearTimeout(this.clickTimeout);
                    this.runClickAction(action);
                }
            },
            runClickAction(action, delay = 0) {
                if(action !== 'details' && action !== 'edit' && action !== 'open-url') {
                    this.copyAction(action, delay);
                } else if(action === 'edit') {
                    this.clickTimeout = setTimeout(this.editAction, delay);
                } else if(action === 'details') {
                    this.clickTimeout = setTimeout(this.detailsAction, delay);
                } else if(action === 'open-url' && this.password.url) {
                    this.clickTimeout = setTimeout(() => {Utility.openLink(this.password.url);}, delay);
                }
            },
            copyAction(attribute, delay = 0) {
                this.clickTimeout = setTimeout(() => {
                    this.actions.clipboard(attribute);
                }, delay);
            },
            runCustomAction() {
                let action = SettingsService.get('client.ui.password.custom.action');
                if(action === 'share' || action === 'details') {
                    this.detailsAction(action);
                } else if(action === 'print') {
                    this.printAction();
                } else if(action === 'qrcode') {
                    this.actions.qrcode();
                } else {
                    this.runClickAction(action);
                }
            },
            favoriteAction() {
                this.actions.favorite();
            },
            printAction() {
                this.actions.print();
            },
            toggleMenu() {
                this.showMenu = !this.showMenu;
                if(this.showMenu) {
                    document.addEventListener('click', this.menuEvent);
                } else {
                    document.removeEventListener('click', this.menuEvent);
                }
            },
            menuEvent($e) {
                if($e.target.closest('[data-password-id="' + this.password.id + '"] .more') !== null) return;
                this.showMenu = false;
                document.removeEventListener('click', this.menuEvent);
            },
            detailsAction(section = null) {
                this.detailsActive = true;
                Application.sidebar = new PasswordSidebar(this.password, section);

                let updateListener = (sidebar) => {
                    if(sidebar.item.id !== this.password.id) {
                        closeListener();
                    }
                };
                let closeListener = () => {
                    unsubscribe('passwords:sidebar:opened', updateListener);
                    unsubscribe('passwords:sidebar:updated', updateListener);
                    unsubscribe('passwords:sidebar:closed', closeListener);
                    this.detailsActive = false;
                };

                subscribe('passwords:sidebar:opened', updateListener);
                subscribe('passwords:sidebar:updated', updateListener);
                subscribe('passwords:sidebar:closed', closeListener);
            },
            editAction() {
                this.actions
                    .edit()
                    .then((p) => {this.password = p;});
            },
            cloneAction() {
                PasswordManager
                    .clonePassword(this.password);
            },
            deleteAction() {
                PasswordManager.deletePassword(this.password);
            },
            moveAction() {
                PasswordManager.movePassword(this.password);
            },
            dragStartAction($e) {
                DragManager
                    .start($e, this.password)
                    .then(async (data) => {
                        if(data.dropType === 'folder') {
                            this.password = await this.actions.move(data.folderId);
                        } else if(data.dropType === 'tag') {
                            this.password = await this.actions.addTag(data.tagId);
                        } else if(data.dropType === 'trash') {
                            PasswordManager.deletePassword(this.password);
                        }
                    });
            },
            openTagAction($event, tag) {
                $event.stopPropagation();
                this.$router.push({name: 'Tags', params: {tag: tag}});
            }
        },

        watch: {
            password(value) {
                ContextMenuService.register(value, this.$el);
            }
        }
    };
</script>

<style lang="scss">

#dragicon {
    padding         : 5px 5px 5px 42px;
    background      : no-repeat 5px;
    background-size : 32px;
    line-height     : 32px;
    display         : inline-block;
    position        : absolute;
    left            : -500px;
}

#app-content {
    .item-list {
        .row {
            height        : 51px;
            font-size     : 0;
            border-bottom : 1px solid var(--color-border);
            cursor        : pointer;
            display       : flex;

            .favorite {
                line-height : 50px;
                width       : 40px;
                flex-shrink : 0;
                cursor      : pointer;
            }

            .favicon {
                display         : inline-block;
                background      : no-repeat center;
                background-size : 32px;
                border-radius   : var(--border-radius);
                line-height     : 32px;
                font-size       : 1rem;
                cursor          : pointer;
                width           : 32px;
                height          : 32px;
                margin          : 9px;
                flex-shrink     : 0;
            }

            .title {
                font-size      : 0.8rem;
                padding-left   : 8px;
                cursor         : pointer;
                line-height    : 50px;
                min-width      : 0;
                white-space    : nowrap;
                overflow       : hidden;
                text-overflow  : ellipsis;
                flex-grow      : 1;
                vertical-align : baseline;
                display        : flex;

                > span {
                    text-overflow : ellipsis;
                    overflow      : hidden;
                    cursor        : pointer;
                }
            }

            .tags {
                height       : 50px;
                flex-shrink  : 0;
                line-height  : 50px;
                font-size    : 24px;
                z-index      : 1;
                padding-left : 0;
                transition   : padding-left 0.25s ease-in-out;

                li {
                    display     : inline-block;
                    margin-left : -18px;
                    transition  : margin-left 0.25s ease-in-out;

                    &:before {
                        content     : "\F02B";
                        font-family : var(--pw-icon-font-face);
                        cursor      : pointer;
                    }
                }

                &:hover {
                    padding-left : 5px !important;

                    li {
                        margin-left : -6px;
                    }
                }
            }

            .shield-half-full-icon {
                margin      : 1rem;
                flex-grow   : 0;
                flex-shrink : 0;
            }

            .more,
            .icon,
            .action-button {
                line-height : 50px;
                width       : 50px;
                font-size   : 1rem;
                text-align  : center;
                flex-shrink : 0;
                color       : $color-grey;
                transition  : color 0.2s ease-in-out;

                &:active,
                &:hover {
                    color : var(--color-main-text);
                }

                &.duplicate {
                    transition : color 0.2s ease-in-out, transform 0.2s ease-in-out;

                    &:hover {
                        transform : scale(1.5);
                    }
                }

                &.icon {
                    font-size : 1.25rem;
                }
            }

            .more {
                position : relative;

                > i {
                    cursor : pointer;
                }

                .menu {
                    li {
                        line-height : 40px;
                        font-size   : 0.8rem;
                        padding     : 0 20px 0 15px;
                        white-space : nowrap;
                        color       : var(--color-main-text);
                        font-weight : 300;
                        cursor      : pointer;

                        a {
                            color         : var(--color-main-text);
                            margin        : 0 -20px 0 -15px;
                            padding-left  : 15px;
                            padding-right : 0 !important;
                            opacity       : 1 !important;
                            line-height   : inherit;
                            font-weight   : 300;

                            .material-design-icon {
                                margin-left : 0;
                            }
                        }

                        i,
                        .material-design-icon {
                            line-height  : 40px;
                            margin-right : 10px;
                            font-size    : 1rem;
                            width        : 1rem;
                            cursor       : pointer;
                        }

                        &:active,
                        &:hover {
                            background-color : var(--color-background-dark);
                            color            : var(--color-main-text);

                            a {
                                background-color : var(--color-background-dark);
                                color            : var(--color-main-text);
                            }

                            i {
                                color : var(--color-main-text);
                            }
                        }
                    }
                }
            }

            .date {
                line-height : 50px;
                width       : 125px;
                font-size   : 0.8rem;
                padding     : 0 15px 0 5px;
                text-align  : right;
                color       : $color-grey-darker;
                flex-shrink : 0;
            }

            &:hover,
            &:active,
            &.details-open {
                background-color : var(--color-background-hover);
            }

            &.details-open {
                background-color : var(--color-primary-light);
            }

            &.search-hidden {
                display : none;
            }

            @media(max-width : $width-extra-small) {
                .date,
                .action-button {
                    display : none;
                }
            }
        }
    }

    @media(max-width : $width-large) {
        &.show-details .item-list .row {
            .date,
            .action-button {
                display : none;
            }
        }
    }

    @media(max-width : $width-medium) {
        &.show-details .item-list .row {
            .tags,
            .date,
            .action-button {
                display : none;
            }
        }
    }
}

</style>
