<template>
</template>

<script>
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import SettingsManager from "@js/Manager/SettingsManager";

    export default {
        data() {
            return {
                passwords: [],
                loading: true,
                detail : {
                    type   : 'none',
                    element: null
                },
                sort   : {
                    by   : SettingsManager.get('ui.sort.by', 'label'),
                    order: SettingsManager.get('ui.sort.order', true)
                }
            }
        },

        created() {
            this.refreshView();
            Events.on('password.changed', this.refreshView);
            if(this.folders) Events.on('folder.changed', this.refreshView);
            if(this.tags) Events.on('tag.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView);
            Events.off('folder.changed', this.refreshView);
            Events.off('tag.changed', this.refreshView);
        },

        computed: {
            getContentClass() {
                return {
                    'show-details': this.detail.type !== 'none',
                    'loading': this.loading
                }
            },
            showHeaderAndFooter() {
                return !this.loading &&
                       ((this.passwords && this.passwords.length) ||
                       (this.folders && this.folders.length) ||
                       (this.tags && this.tags.length));
            },
            isEmpty() {
                return !this.loading &&
                       (!this.passwords || !this.passwords.length) &&
                       (!this.folders || !this.folders.length) &&
                       (!this.tags || !this.tags.length);
            },
            showPasswordDetails() {
                return this.detail.type === 'password';
            }
        },

        methods: {
            updateSorting($event) {
                this.sort = $event;
                SettingsManager.set('ui.sort.by', $event.by);
                SettingsManager.set('ui.sort.order', $event.order);
                if(this.passwords) this.passwords = Utility.sortApiObjectArray(this.passwords, this.sort.by, this.sort.order);
                if(this.folders) this.folders = Utility.sortApiObjectArray(this.folders, this.sort.by, this.sort.order);
                if(this.tags) this.tags = Utility.sortApiObjectArray(this.tags, this.sort.by, this.sort.order);
            },
            updatePasswordList: function(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, this.sort.by, this.sort.order);
            },
            updateFolderList: function(folders) {
                this.loading = false;
                this.folders = Utility.sortApiObjectArray(folders, this.sort.by, this.sort.order);
            },
            updateTagList: function(tags) {
                this.loading = false;
                this.tags = Utility.sortApiObjectArray(tags, this.sort.by, this.sort.order);
            }
        }
    }
</script>

<style lang="scss">
    #app-content {
        position   : relative;
        height     : 100%;
        overflow-y : auto;
        transition : margin-right 300ms, transform 300ms;

        .app-content-right {
            background-color : white;
            z-index          : 50;
            border-left      : 1px solid $color-grey-light;
            transition       : right 300ms;
            right            : -27%;
        }

        &.show-details {
            margin-right : 27%;

            .app-content-right {
                display    : block;
                position   : fixed;
                top        : 45px;
                right      : 0;
                left       : auto;
                bottom     : 0;
                width      : 27%;
                min-width  : 360px;
                overflow-y : auto;
            }
        }

        > #app-navigation-toggle {
            display : none !important;
        }

        @media(max-width : $tablet-width) {
            transform : translate3d(0, 0, 0);

            .app-content-right {
                border-left : none;
                transition  : width 300ms;
            }

            &.show-details {
                margin-right : 0;

                .app-content-left {
                    display : none;
                }
                .app-content-right {
                    width     : 100%;
                    min-width : auto;
                    top       : 0;
                }
            }

            &.mobile-open {
                transform : translate3d(250px, 0px, 0px);
            }
        }

        @media(max-width : $desktop-width) {
            .app-content-right {
                right : -360px;
            }

            &.show-details {
                margin-right : 360px;

                .app-content-right {
                    width     : 360px;
                    min-width : 360px;
                }
            }
        }
    }
</style>