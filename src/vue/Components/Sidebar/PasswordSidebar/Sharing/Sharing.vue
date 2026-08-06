<template>
    <div class="sharing-container">
        <translate tag="div"
                   class="cse-warning warning"
                   say="End-to-End encryption will be disabled for this password if you share it."
                   v-if="hasCse && canBeShared"/>
        <div v-if="isSharedWithUser" class="shareby-info" :title="getShareTitle">
            <img :src="password.share.owner.icon" alt="">
            <translate say="{name} has shared this password with you." :variables="password.share.owner"/>
            <translate say="It will expire {date}." :variables="getExpirationDate" v-if="getDefaultExpires"/>
        </div>
        <nc-select-users class="share-add-user"
                         :options="matches"
                         :placeholder="t('ShareTabSearchPlaceholder')"
                         :input-label="t('ShareTabSelectRecipientLabel')"
                         :multiple="false"
                         :loading="searching"
                         v-model="selected"
                         @search="searchRecipients"
                         v-if="canBeShared"/>
        <ul class="shares" v-if="visibleShares.length !== 0">
            <share :share="share"
                   v-on:delete="deleteShare($event)"
                   v-on:update="refreshShares()"
                   :data-share-id="share.id"
                   :editable="isEditable"
                   v-for="share in visibleShares"
                   :key="share.id"/>
        </ul>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import NcSelectUsers from '@nc/NcSelectUsers.js';
    import Share from '@vc/Sidebar/PasswordSidebar/Sharing/Share';
    import SettingsService from '@js/Services/SettingsService';
    import ToastService from "@js/Services/ToastService";
    import LocalisationService from "@js/Services/LocalisationService";
    import LoggingService from "@js/Services/LoggingService";
    import CreateShareAction from "@js/Actions/Share/CreateShareAction";
    import CreateShareError from "@js/Actions/Share/CreateShareError";
    import {getCurrentUser} from '@nextcloud/auth';
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import {subscribe, subscribeOnce, unsubscribe} from "@js/Helper/event-bus";

    /**
     * The api reports errors with the crc32 hash of the message of the exception.
     * "Invalid receiver uid" and "Invalid recipient uid" are thrown when the user
     * can not be found, "Invalid recipient group" when the group can not be found.
     */
    const UNKNOWN_USER_ERRORS  = ['65782183', '6a935de5'];
    const UNKNOWN_GROUP_ERRORS = ['5abffc24'];

    export default {
        components: {
            Share,
            Translate,
            NcSelectUsers
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            let shares = this.password.hasOwnProperty('shares') ? this.password.shares:[],
                hasCse = this.password.cseType !== 'none' && !this.password.shared;

            return {
                matches     : [],
                selected    : null,
                searching   : false,
                shares,
                hasCse,
                autocomplete: SettingsService.get('server.sharing.autocomplete'),
                groupSharing: SettingsService.get('server.sharing.groups.enabled'),
                interval    : null,
                polling     : {interval: null, mode: null},
                cronPromise : null,
                user        : getCurrentUser()
            };
        },

        created() {
            this.reloadShares();
            this.startPolling();
            subscribe('passwords:password:updated', this.processPasswordUpdate);
        },

        beforeDestroy() {
            this.stopPolling();
            unsubscribe('passwords:password:updated', this.processPasswordUpdate);
        },

        computed: {
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
            isEditable() {
                if(this.password.share !== null && typeof this.password.share !== 'string') {
                    return this.password.share.editable;
                }

                return this.password.editable;
            },
            getDefaultExpires() {
                if(this.password.share !== null && typeof this.password.share !== 'string') {
                    return this.password.share.expires;
                }

                return null;
            },
            getExpirationDate() {
                if(this.password.share !== null && typeof this.password.share !== 'string') {
                    return {
                        'date'    : LocalisationService.formatDate(this.password.share.expires),
                        'dateTime': LocalisationService.formatDateTime(this.password.share.expires)
                    };
                }

                return {'date': '', 'dateTime': ''};
            },
            isSharedWithUser() {
                return this.password.share && this.password.share.owner;
            },
            getSharedWithUsers() {
                let users = [];
                for(let i in this.shares) {
                    if(this.shares.hasOwnProperty(i)) users.push(this.shares[i].receiver.id);
                }

                if(this.password.share !== null) {
                    users.push(this.password.share.owner.id);
                }

                return users;
            },
            getShareTitle() {
                let editable  = LocalisationService.translate(this.password.share.editable ? 'Editing allowed':'Editing disallowed'),
                    shareable = LocalisationService.translate(this.password.share.shareable ? 'sharing allowed':'sharing disallowed'),
                    text      = LocalisationService.translate('{editable} and {shareable}.', {shareable, editable});

                if(this.password.share.expires) {
                    text += ' ' + LocalisationService.translate(
                        'Expires {datetime}',
                        this.getExpirationDate
                    );
                }

                return text;
            },
            /**
             * The shares created by the server for the members of a group share
             * are hidden, the group share itself represents them
             */
            visibleShares() {
                let shares = [];

                for(let id in this.shares) {
                    if(!this.shares.hasOwnProperty(id)) continue;
                    if(this.shares[id].parentShare) continue;

                    shares.push(this.shares[id]);
                }

                return shares;
            }
        },

        methods: {
            async searchRecipients(query) {
                if(!this.canBeShared) {
                    this.matches = [];
                    return;
                }

                if(!this.autocomplete) {
                    // Without autocomplete no suggestions are returned, so the typed id is offered as is
                    this.matches = query === '' ? []:[{id: query, displayName: query, isNoUser: false, type: 'user'}];
                    return;
                }

                const receivers = this.getSharedWithUsers;

                this.searching = true;
                try {
                    let matches      = await API.findShareRecipients(query, receivers.length + 25),
                        groupSubname = LocalisationService.translate('BatchShareSubnameGroup'),
                        options      = [];


                    for(let match of matches) {
                        if(receivers.indexOf(match.id) !== -1) continue;
                        if(match.type === 'group' && !this.groupSharing) continue;

                        options.push(
                            {
                                id         : match.id,
                                displayName: match.name,
                                isNoUser   : match.type === 'group',
                                subname    : match.type === 'group' ? groupSubname:match.context,
                                type       : match.type
                            }
                        );
                    }

                    this.matches = options;
                } catch(e) {
                    LoggingService.error(e);
                } finally {
                    this.searching = false;
                }
            },
            async addShare(recipient) {
                if(!this.canBeShared || recipient === null) return;

                let action = new CreateShareAction(
                    this.password,
                    recipient,
                    {
                        expires  : this.getDefaultExpires,
                        editable : SettingsService.get('user.sharing.editable'),
                        shareable: SettingsService.get('user.sharing.resharing'),
                        overwrite: false
                    }
                );

                try {
                    await action.run();
                    this.matches = [];
                    this.refreshShares();
                } catch(e) {
                    if(e instanceof CreateShareError) {
                        ToastService.error(e.message);
                    } else if(UNKNOWN_GROUP_ERRORS.indexOf(e.id) !== -1) {
                        ToastService.error(['ShareTabGroupDoesNotExist', {group: recipient.displayName}]);
                    } else if(UNKNOWN_USER_ERRORS.indexOf(e.id) !== -1) {
                        ToastService.error(['The user {uid} does not exist', {uid: recipient.id}]);
                    } else {
                        let message = e.hasOwnProperty('message') ? e.message:e.statusText;
                        ToastService.error(['Unable to share password: {message}', {message}]);
                    }
                } finally {
                    // Disabling the encryption can not be undone, even if the share failed afterwards
                    if(action.cseDisabled) this.hasCse = false;
                }
            },
            reloadShares() {
                API.showPassword(this.password.id, 'shares')
                   .then((d) => {this.shares = d.shares;})
                   .catch(LoggingService.catch);
            },
            deleteShare($event) {
                this.$delete(this.shares, $event.id);
                this.refreshShares();
            },
            async refreshShares() {
                await this.runCron()
                          .then((d) => { if(d.success) this.reloadShares();});

                this.startPolling();
                this.$forceUpdate();
            },
            startPolling(mode = 'fast') {
                if(this.polling.mode === mode) return;
                this.stopPolling();

                let time = mode === 'slow' ? 60000:5000;
                this.polling.interval = setInterval(() => { this.reloadShares(); }, time);
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
                        this.shares = this.password.shares;
                    } else {
                        this.refreshShares();
                    }
                }
            }
        },

        watch: {
            password(value) {
                this.shares = value.hasOwnProperty('shares') ? value.shares:[];
                this.hasCse = value.cseType !== 'none' && !value.shared;

                this.$forceUpdate();
            },
            selected(recipient) {
                if(recipient === null) return;

                this.selected = null;
                this.addShare(recipient);
            },
            shares(shares) {
                for(let id in shares) {
                    if(shares.hasOwnProperty(id) && shares[id].updatePending) {
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

    .cse-warning {
        margin-bottom : 0.5rem;
    }

    .shareby-info {
        img {
            border-radius : var(--border-radius-pill);
            width         : 32px;
            height        : 32px;
            margin-right  : 0.5rem;
            float         : left;
        }

        line-height   : 32px;
        margin-bottom : 0.5rem;
    }

    .share-add-user {
        width : 100%;
    }

    .shares {
        margin-top : 5px;
    }
}
</style>