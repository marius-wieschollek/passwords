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
    <nc-list-item
            class="password-item"
            :name="password.label"
            @click="clickAction"
            @dblclick="doubleClickAction()"
    >
        <template #icon>
            <favicon class="favicon" :domain="password.website" :size="44"/>
        </template>
        <template #subname>
            {{ password.username }}
        </template>
        <template #indicator>
            <shield-half-full-icon :size="16" :fill-color="securityColor"/>
        </template>
    </nc-list-item>
</template>

<script>
    import NcListItem from '@nc/NcListItem.js';
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";
    import SettingsService from "@js/Services/SettingsService";
    import Utility from "@js/Classes/Utility";
    import Favicon from "@vc/Favicon";
    import Localisation from "@js/Classes/Localisation";
    import {showInfo} from "@nextcloud/dialogs";

    export default {
        components: {Favicon, NcListItem, ShieldHalfFullIcon},
        props     : {
            password: {
                type: Object
            }
        },
        data() {
            return {
                clickTimeout: null
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
            }
        },
        methods : {
            clickAction() {
                if(this.clickTimeout) {
                    return this.doubleClickAction();
                }

                let action = SettingsService.get('client.ui.password.click.action');
                if(action !== 'none') this.runClickAction(action, 'password', 300);
            },
            doubleClickAction() {
                let action = SettingsService.get('client.ui.password.dblClick.action');
                if(action !== 'none') {
                    this.clearClickAction();
                    this.runClickAction(action, 'username');
                }
            },
            runClickAction(action, fallbackAction, delay = 0) {
                if(action === 'details' || action === 'edit' || action === 'print') {
                    this.copyAction(action, fallbackAction);
                } else if(action === 'open-url') {
                    this.openUrlAction(delay);
                } else {
                    this.copyAction(action, delay);
                }
            },
            copyAction(attribute, delay = 0) {
                this.clickTimeout = setTimeout(
                    () => {
                        let message = 'Error copying {element} to clipboard';
                        if(!this.password.hasOwnProperty(attribute) || this.password[attribute].length === 0) {
                            message = 'ClipboardCopyEmpty';
                        } else if(Utility.copyToClipboard(this.password[attribute])) {
                            message = '{element} was copied to clipboard';
                        }

                        let element = Localisation.translate(attribute.charAt(0).toUpperCase() + attribute.slice(1));
                        showInfo(Localisation.translate(message, {element}), {});
                        this.clickTimeout = null;
                    },
                    delay
                );
            },
            openUrlAction(delay) {
                this.clickTimeout = setTimeout(
                    () => {
                        if(this.password.url) {
                            Utility.openLink(this.password.url);
                        }
                        this.clickTimeout = null;
                    },
                    delay
                );
            },
            clearClickAction() {
                if(this.clickTimeout) {
                    clearTimeout(this.clickTimeout);
                    this.clickTimeout = null;
                }
            }
        }
    };
</script>

<style lang="scss">
.password-item {
    img {
        width         : 44px;
        border-radius : var(--border-radius-pill);
    }

    &> .list-item {
        border-radius: var(--border-radius-large) !important;
    }
}
</style>