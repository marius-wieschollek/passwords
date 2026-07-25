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
    <div @click="clickAction($event)"
         @click.middle="wheelClickAction($event)"
         @dblclick="doubleClickAction($event)"
         @contextmenu="openContextMenu"
         @dragstart="dragStartAction($event)"
         :class="className"
         :data-password-id="password.id"
         :data-password-title="password.label">
        <password-item-batch-toggle :item="password" v-model="isSelected"/>
        <password-item-favicon :domain="password.website" :title="getTitle" :favorite="password.favorite" v-if="isVisible"/>
        <div class="title" :title="getTitle">
            <button :aria-label="t('PasswordListItemAriaLabel', {label: password.label})">{{ getTitle }}</button>
        </div>
        <slot name="middle">
            <password-item-tags :password="password"/>
        </slot>
        <password-item-security-icon :password="password"/>
        <slot name="actions">
            <password-item-action-menu
                    :actions="actions"
                    :password="password"
                    :opened-menu.sync="openedMenu"
                    v-on:edit-action="editAction"
                    v-on:copy-action="copyAction"
                    v-on:details-action="detailsAction"
                    v-on:click-action="clickAction"
                    @closed="openedMenu = false"
            >
                <template v-if="hasCustomAction" #custom-action>
                    <slot name="custom-action"/>
                </template>
                <template #restore-action>
                    <slot name="restore-action"/>
                </template>
            </password-item-action-menu>
        </slot>
        <nc-date-time class="date" :timestamp="password.edited"/>
    </div>
</template>

<script>
    import DragManager from '@js/Manager/DragManager';
    import SettingsService from '@js/Services/SettingsService';
    import SearchManager from "@js/Manager/SearchManager";
    import PasswordSidebar from "@js/Models/Sidebar/PasswordSidebar";
    import Application from "@js/Init/Application";
    import PasswordActions from "@js/Actions/Password/PasswordActions";
    import {emit, subscribe, unsubscribe} from '@nextcloud/event-bus';
    import UtilityService from "@js/Services/UtilityService";
    import NcDateTime from '@nc/NcDateTime.js';
    import PasswordItemSecurityIcon from '@vc/ContentList/Item/PasswordItem/PasswordItemSecurityIcon.vue';
    import PasswordItemTags from "@vc/ContentList/Item/PasswordItem/PasswordItemTags.vue";
    import PasswordItemFavicon from "@vc/ContentList/Item/PasswordItem/PasswordItemFavicon.vue";
    import PasswordItemBatchToggle from "@vc/ContentList/Item/PasswordItem/PasswordItemBatchToggle.vue";
    import LoggingService from "@js/Services/LoggingService";

    export default {
        components: {
            NcDateTime,
            PasswordItemTags,
            PasswordItemFavicon,
            PasswordItemBatchToggle,
            PasswordItemSecurityIcon,
            'password-item-action-menu': () => import(/* webpackChunkName: "PasswordItemActionMenu" */ '@vc/ContentList/Item/PasswordItem/PasswordItemActionMenu.vue')
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                clickTimeout : null,
                detailsActive: false,
                isSelected   : false,
                openedMenu   : false,
                actions      : new PasswordActions(this.password)
            };
        },

        computed: {
            getTitle() {
                let titleField = SettingsService.get('client.ui.password.field.title'),
                    showUser   = SettingsService.get('client.ui.password.user.show'),
                    title      = this.password[titleField];

                if(!title && this.password.label) title = this.password.label;
                if(!title && this.password.website) title = this.password.website;
                if(showUser && this.password.username) title = `${title} – ${this.password.username}`;
                return title;
            },
            isVisible() {
                return !SearchManager.status.active || SearchManager.status.ids.indexOf(this.password.id) !== -1;
            },
            className() {
                let classNames = 'row password';

                if(this.detailsActive) classNames += ' details-open';
                if(this.isSelected) classNames += ' selected';
                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.password.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            },
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action');
            }
        },

        methods: {
            clickAction($event) {
                if($event && ($event.detail !== 1 ||
                              $event.target.closest('.checkbox-radio-switch') !== null ||
                              $event.target.classList.contains('duplicate')
                )
                ) {
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
                if($event && $event.target.classList.contains('duplicate')) return;
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
                    this.clickTimeout = setTimeout(() => {UtilityService.openLink(this.password.url);}, delay);
                }
            },
            copyAction(attribute, delay = 0) {
                this.clickTimeout = setTimeout(() => {
                    this.actions.clipboard(attribute);
                }, delay);
            },
            openContextMenu(event) {
                if(this.openedMenu) {
                    return;
                }

                this.openedMenu = true;
                emit('passwords:contextmenu:opened', {item: this.password, pos: {x: event.clientX, y: event.clientY}});

                event.preventDefault();
                event.stopPropagation();
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
            dragStartAction($e) {
                DragManager
                    .start($e, this.password)
                    .then(async (data) => {
                        if(data.dropType === 'folder') {
                            this.password = await this.actions.move(data.folderId);
                        } else if(data.dropType === 'tag') {
                            this.password = await this.actions.addTag(data.tagId);
                        } else if(data.dropType === 'trash') {
                            this.actions.delete().catch(LoggingService.catch);
                        }
                    });
            }
        },

        watch: {
            openedMenu(value) {
                if(!value) {
                    emit('passwords:contextmenu:closed', {item: this.password});
                }
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
            padding       : 0 1.5rem 0 .25rem;

            .title {
                font-size      : 1rem;
                cursor         : pointer;
                line-height    : 50px;
                min-width      : 0;
                white-space    : nowrap;
                overflow       : hidden;
                text-overflow  : ellipsis;
                flex-grow      : 1;
                vertical-align : baseline;
                display        : flex;

                > button {
                    text-overflow    : ellipsis;
                    overflow         : hidden;
                    cursor           : pointer;
                    border           : none;
                    background-color : transparent;
                    color            : inherit;
                    padding          : 0 0 0 .5rem;
                    margin           : 0;
                    line-height      : 50px;
                    font-weight      : normal;
                    text-align       : left;
                    width            : 100%;
                }
            }

            .date {
                line-height : 50px;
                width       : 10rem;
                font-size   : 1rem;
                text-align  : right;
            }

            &:hover,
            &:active,
            &.details-open {
                background-color : var(--color-background-hover);
            }

            &.details-open,
            &.selected {
                background-color : var(--color-primary-light);
            }

            &.search-hidden {
                display : none;
            }

            @media(max-width : $width-extra-small) {
                .date {
                    display : none;
                }
            }
        }
    }

    @media(max-width : $width-large) {
        &.show-details .item-list .row {
            .date {
                display : none;
            }
        }
    }

    @media(max-width : $width-medium) {
        &.show-details .item-list .row {
            .tags,
            .date {
                display : none;
            }
        }
    }
}

</style>
