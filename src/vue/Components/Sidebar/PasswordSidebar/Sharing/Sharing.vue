<template>
    <div class="sharing-container">
        <nc-note-card type="warning" :text="t('End-to-End encryption will be disabled for this password if you share it.')" v-if="hasCse && canBeShared"/>
        <nc-note-card type="info" class="shareby-info" v-if="isSharedWithUser">
            <template #icon>
                <nc-avatar disable-menu :size="32" :user="password.share.owner.id" :display-name="password.share.owner.name"/>
            </template>
            <span>
                <translate say="{name} has shared this password with you." :variables="password.share.owner"/>
                <translate :say="password.share.editable ? 'ShareInfoEditable':'ShareInfoNotEditable'"/>
                <translate :say="password.share.shareable ? 'ShareInfoShareable':'ShareInfoNotShareable'"/>
                <translate say="It will expire {date}." :variables="expirationDate" v-if="hasExpirationDate"/>
            </span>
        </nc-note-card>

        <share-edit-form
                :share="activeShare"
                :password="password"
                :default-preset="editingPreset"
                v-on:configuring="showShares = !$event"
                v-on:revoke="deleteShareEvent"
                v-on:save="shareSavedEvent"
                v-on:cancel="setActiveShare()"
                v-if="canBeShared"
        />

        <ul class="share-list" v-if="hasShares">
            <share-list-item
                    :share="share"
                    :password="password"
                    v-on:delete="deleteShareEvent"
                    v-on:edit="setActiveShare"
                    :data-share-id="share.id"
                    v-for="share in shares"
                    :key="share.id"/>
        </ul>
        <nc-empty-content
                :name="t('SharingNoShares')"
                :description="t('SharingNoSharesText')"
                v-else-if="isEmpty">
            <template #icon>
                <share-variant-icon/>
            </template>
        </nc-empty-content>
        <nc-loading-icon :size="64" v-else-if="isLoading"/>
    </div>
</template>

<script>
    import Field from '@vc/Field';
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import ShareListItem from '@vc/Sidebar/PasswordSidebar/Sharing/ShareListItem';
    import LocalisationService from "@js/Services/LocalisationService";
    import LoggingService from "@js/Services/LoggingService";
    import {getCurrentUser} from '@nextcloud/auth';
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import {subscribe, subscribeOnce, unsubscribe} from "@js/Helper/event-bus";
    import ShareEditForm from "@vc/Sidebar/PasswordSidebar/Sharing/ShareEditForm";
    import NcNoteCard from "@nc/NcNoteCard.js";
    import NcEmptyContent from "@nc/NcEmptyContent.js";
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';
    import ShareVariantIcon from "@icon/ShareVariant.vue";
    import UtilityService from "@js/Services/UtilityService";
    import NcAvatar from "@nextcloud/vue/components/NcAvatar";

    export default {
        components: {
            Field,
            Translate,
            ShareEditForm,
            ShareListItem,
            NcAvatar,
            NcNoteCard,
            NcLoadingIcon,
            NcEmptyContent,
            ShareVariantIcon
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            let shares = this.password.hasOwnProperty('shares') ? UtilityService.objectToArray(this.password.shares):[];

            return {
                shares,
                interval     : null,
                polling      : {interval: null, mode: null},
                cronPromise  : null,
                user         : getCurrentUser(),
                showShares   : true,
                activeShare  : null,
                editingPreset: null,
                loading      : shares.length === 0
            };
        },

        created() {
            this.reloadShares(false);
            this.startPolling();
            subscribe('passwords:password:updated', this.processPasswordUpdate);
        },

        beforeDestroy() {
            this.stopPolling();
            unsubscribe('passwords:password:updated', this.processPasswordUpdate);
        },

        computed: {
            hasCse() {
                return this.password.cseType !== 'none' && !this.password.shared;
            },
            hasShares() {
                return this.canBeShared && this.showShares && !this.loading && this.shares.length > 0;
            },
            isEmpty() {
                return this.canBeShared && this.showShares && !this.loading && this.shares.length === 0;
            },
            isLoading() {
                return this.canBeShared && this.showShares && this.loading;
            },
            canBeShared() {
                return this.password.hasOwnProperty('share') &&
                       (
                           this.password.share === null ||
                           (
                               typeof this.password.share !== 'string' &&
                               this.password.share.hasOwnProperty('shareable') &&
                               this.password.share.shareable
                           )
                       );
            },
            hasExpirationDate() {
                if(this.password.share !== null && typeof this.password.share !== 'string') {
                    return this.password.share.expires !== null;
                }

                return false;
            },
            expirationDate() {
                if(this.hasExpirationDate) {
                    return {
                        'date'    : LocalisationService.formatDate(this.password.share.expires),
                        'dateTime': LocalisationService.formatDateTime(this.password.share.expires)
                    };
                }

                return {'date': '', 'dateTime': ''};
            },
            isSharedWithUser() {
                return this.password.share && this.password.share.owner;
            }
        },

        methods: {
            shareSavedEvent() {
                this.setActiveShare(null);
                this.shares = [];
                this.reloadShares();
            },
            setActiveShare(value = null) {
                if(value === null) {
                    this.activeShare = null;
                    this.editingPreset = null;
                } else {
                    this.activeShare = value.share;
                    this.editingPreset = value.preset;
                }

                this.showShares = value !== null;
            },
            reloadShares(withLoading = true) {
                if(withLoading) this.loading = true;

                API.showPassword(this.password.id, 'shares')
                   .then((d) => {
                       this.shares = UtilityService.objectToArray(d.shares);
                       this.loading = false;
                   })
                   .catch(LoggingService.catch);
            },
            deleteShareEvent($event) {
                delete this.shares[$event.id];
                this.refreshShares();
            },
            async refreshShares() {
                await this.runCron()
                          .then((d) => { if(d.success) this.reloadShares(false);});

                this.startPolling();
                this.$forceUpdate();
            },
            startPolling(mode = 'fast') {
                if(this.polling.mode === mode) return;
                this.stopPolling();

                let time = mode === 'slow' ? 60000:5000;
                this.polling.interval = setInterval(() => { this.reloadShares(false); }, time);
            },
            stopPolling() {
                if(this.polling.interval !== null) {
                    clearInterval(this.polling.interval);
                    this.polling.interval = null;
                    this.polling.mode = null;
                }
            },
            runCron() {
                if(this.cronPromise === null) {
                    this.cronPromise = new Promise((resolve, reject) => {
                        API.runSharingCron()
                           .then((d) => {
                               this.cronPromise = null;
                               resolve(d);
                           })
                           .catch((e) => {
                               this.cronPromise = null;
                               LoggingService.error(e);
                               reject(e);
                           });
                    });
                }

                return this.cronPromise;
            },
            processPasswordUpdate(password) {
                if(password.id === this.password.id) {
                    if(BatchActionManager.isProcessingItems) {
                        subscribeOnce('passwords:batch-action:completed', () => {this.processPasswordUpdate(password);});
                        return;
                    }

                    if(this.password.hasOwnProperty('shares')) {
                        this.shares = UtilityService.objectToArray(this.password.shares);
                    } else {
                        this.refreshShares();
                    }
                }
            }
        },

        watch: {
            password(value) {
                this.shares = value.hasOwnProperty('shares') ? UtilityService.objectToArray(value.shares):[];
                this.reloadShares(this.shares.length === 0);
                this.$forceUpdate();
            },
            shares(shares) {
                for(let share of shares) {
                    if(share.updatePending) {
                        this.runCron();
                        this.startPolling();
                        return;
                    }
                }
                this.startPolling('slow');
            }
        }
    };
</script>

<style lang="scss">
.sharing-container {
    position       : relative;
    padding-bottom : 5rem;

    .shareby-info {
        margin-bottom : 1rem;
    }

    .share-list,
    .loading-icon,
    .empty-content {
        margin-top : 1rem;
    }
}
</style>