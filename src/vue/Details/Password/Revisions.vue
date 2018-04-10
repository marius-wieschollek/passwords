<template>
    <ul class="revision-list">
        <li class="revision" v-for="revision in getRevisions" :key="revision.id" :style="{'background-image': 'url(' + revision.icon + ')'}">
            <span>{{ revision.label }}<br>
                <span class="time">{{ getDateTime(revision.created) }}</span>
            </span>
            <translate icon="undo" title="Restore revision" @click="restoreAction(revision)" v-if="revision.id !== password.revision"/>
        </li>
    </ul>
</template>

<script>
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import Localisation from '@js/Classes/Localisation';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
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
            restoreAction(revision) {
                PasswordManager.restoreRevision(this.password, revision);
            },
            getDateTime(date) {
                return Localisation.formatDateTime(date);
            }
        }
    };
</script>

<style lang="scss">
    .revision-list {
        .revision {
            position        : relative;
            background      : no-repeat 3px center;
            background-size : 32px;
            padding         : 5px 20px 5px 38px;
            font-size       : 1.1em;
            cursor          : pointer;
            border-bottom   : 1px solid $color-grey-lighter;

            &:last-child {
                border-bottom : none;
            }

            span {
                cursor : pointer;
            }

            .time {
                color       : $color-grey-dark;
                font-size   : 0.9em;
                font-style  : italic;
                line-height : 0.9em;
            }

            .fa {
                position : absolute;
                right    : 5px;
                top      : 10px;

                &:before {
                    line-height : 32px;
                    padding     : 0 5px;
                }
            }

            &:hover {
                background-color : darken($color-white, 3);
            }
        }
    }
</style>