<!--
  - @copyright 2024 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-list-item
            @click="viewAction()"
            :name="revision.label"
            :title="title"
            :force-display-actions="true"
            :compact="true"
    >
        <template #icon>
            <favicon class="icon" :domain="revision.website"/>
        </template>
        <template #subname>
            {{ created }}
        </template>
        <template #indicator>
            <shield-half-full-icon :size="16" :fill-color="securityColor"/>
        </template>
        <template #actions>
            <nc-action-button @click="restoreAction()" v-if="!isCurrent">
                <template #icon>
                    <restore-icon/>
                </template>
                {{ t('Restore revision') }}
            </nc-action-button>
        </template>
    </nc-list-item>
</template>

<script>
    import PasswordManager from '@js/Manager/PasswordManager';
    import LoggingService from "@js/Services/LoggingService";
    import LocalisationService from "@js/Services/LocalisationService";
    import Favicon from "@vc/Favicon";
    import RestoreIcon from '@icon/Restore';
    import NcListItem from '@nc/NcListItem.js';
    import NcActionButton from '@nc/NcActionButton.js';
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";

    export default {
        components: {
            RestoreIcon,
            Favicon,
            NcListItem,
            NcActionButton,
            ShieldHalfFullIcon
        },

        props: {
            password: {
                type: Object
            },
            revision: {
                type: Object
            }
        },

        computed: {
            created() {
                return LocalisationService.formatDateTime(this.revision.created);
            },
            title() {
                return `${this.revision.label} – ${this.created}`;
            },
            securityColor() {
                switch(this.revision.status) {
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
            isCurrent() {
                return this.revision.id === this.password.revision;
            }
        },

        methods: {
            restoreAction() {
                PasswordManager
                    .restoreRevision(this.password, this.revision)
                    .catch(LoggingService.catch);
            },
            viewAction() {
                PasswordManager.viewRevision(this.password, this.revision);
            }
        }
    };
</script>