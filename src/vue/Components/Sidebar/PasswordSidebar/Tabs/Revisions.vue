<template>
    <ul class="revision-list">
        <li class="revision" v-for="revision in getRevisions" :key="revision.id" @click="viewAction(revision)" :title="getRevisionLabel(revision)">
            <favicon class="icon" :domain="revision.website"/>
            <span class="label">{{ revision.label }}</span>
            <span class="time">{{ getDateTime(revision.created) }}</span>
            <translate class="restore"
                       icon="undo"
                       title="Restore revision"
                       @click="restoreAction(revision, $event)"
                       v-if="revision.id !== password.revision"/>
        </li>
    </ul>
</template>

<script>
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import Localisation from '@js/Classes/Localisation';
    import PasswordManager from '@js/Manager/PasswordManager';
    import Favicon from "@vc/Favicon";

    export default {
        components: {
            Favicon,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        computed: {
            getRevisions() {
                return Utility.sortApiObjectArray(this.password.revisions, 'created', false);
            }
        },

        methods: {
            restoreAction(revision, $event) {
                $event.stopPropagation();
                PasswordManager.restoreRevision(this.password, revision)
                               .catch(console.error);
            },
            viewAction(revision) {
                PasswordManager.viewRevision(this.password, revision);
            },
            getDateTime(date) {
                return Localisation.formatDateTime(date);
            },
            getRevisionLabel(revision) {
                return `${revision.label} – ${this.getDateTime(revision.created)}`;
            }
        }
    };
</script>

<style lang="scss">
.revision-list {
    .revision {
        position              : relative;
        padding               : 5px;
        font-size             : 1.1em;
        cursor                : pointer;
        border-bottom         : 1px solid var(--color-border);
        display               : grid;
        grid-template         : "icon label restore" "icon time restore";
        grid-template-columns : 32px auto 1rem;
        grid-column-gap       : 5px;
        grid-row-gap          : 3px;

        &:last-child {
            border-bottom : none;
        }

        span {
            cursor : pointer;
        }

        .time {
            color       : var(--color-text-maxcontrast);
            font-size   : 0.9em;
            font-style  : italic;
            line-height : 0.9em;
            grid-area   : time;
        }

        .label {
            grid-area : label;
        }

        .icon {
            grid-area     : icon;
            border-radius : var(--border-radius);
            align-self    : center;
            width         : 32px;
            height        : 32px;
        }

        .restore {
            grid-area  : restore;
            align-self : center;
            font-size  : 1rem;
            text-align : right;
        }

        &:hover {
            background-color : var(--color-background-dark);
        }
    }
}
</style>