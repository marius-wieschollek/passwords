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
        <field v-model="search"
               class="share-add-user"
               placeholder="Search user"
               @keypress="submitAction($event)" v-if="canBeShared"/>
        <ul class="shares" v-if="shares.length !== 0">
            <share :share="share"
                   v-on:delete="deleteShare($event)"
                   v-on:update="refreshShares()"
                   :data-share-id="share.id"
                   :editable="isEditable"
                   v-for="share in shares"
                   :key="share.id"/>
        </ul>
        <ul :class="getDropdownClasses" v-if="matches.length !== 0">
            <li v-for="match in matches" @click="shareWithUser(match.id)">
                <img :src="getAvatarUrl(match.id)" alt="" class="avatar">&nbsp;{{ match.name }}
            </li>
        </ul>
    </div>
</template>

<script>
    import Field from '@vc/Field';
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';
    import Share from '@vue/Details/Password/Sharing/Share';
    import PasswordManager from '@js/Manager/PasswordManager';
    import SettingsService from '@js/Services/SettingsService';

    export default {
        components: {
            Field,
            Share,
            Translate
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
                search      : '',
                matches     : [],
                nameMap     : [],
                idMap       : [],
                shares,
                hasCse,
                autocomplete: SettingsService.get('server.sharing.autocomplete'),
                interval    : null,
                polling     : {interval: null, mode: null},
                cronPromise : null
            };
        },

        created() {
            this.reloadShares();
            this.startPolling();
        },

        beforeDestroy() {
            this.stopPolling();
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
                        'date': Localisation.formatDate(this.password.share.expires),
                        'dateTime': Localisation.formatDateTime(this.password.share.expires),
                    }
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
                let editable  = Localisation.translate(this.password.share.editable ? 'Editing allowed':'Editing disallowed'),
                    shareable = Localisation.translate(this.password.share.shareable ? 'sharing allowed':'sharing disallowed'),
                    text      = Localisation.translate('{editable} and {shareable}.', {shareable, editable});

                if(this.password.share.expires) {
                    text += ' ' + Localisation.translate(
                        'Expires {datetime}',
                        this.getExpirationDate
                    );
                }

                return text;
            },
            getDropdownClasses() {
                let classes = ['user-search'];

                if(this.isSharedWithUser) classes.push('shared-with');

                return classes;
            }
        },

        methods: {
            async searchUsers() {
                if(this.search === '' || !this.autocomplete || !this.canBeShared) {
                    this.matches = [];
                    return;
                }

                let users   = this.getSharedWithUsers,
                    matches = await API.findSharePartners(this.search, users.length + 10);
                this.matches = [];

                for(let i in matches) {
                    if(!matches.hasOwnProperty(i) || users.indexOf(i) !== -1) continue;
                    let name = matches[i];

                    if(this.matches.length < 5) this.matches.push({id: i, name});
                    this.nameMap[name] = i;
                    this.idMap[i] = name;
                }
            },
            async disableCse() {
                let password = Utility.cloneObject(this.password);
                password.shared = true;

                await PasswordManager.updatePassword(password);
                this.hasCse = false;
            },
            async addShare(receiver) {
                if(!this.canBeShared) return;
                if(this.hasCse) await this.disableCse();

                let share = {
                    password : this.password.id,
                    expires  : this.getDefaultExpires,
                    editable : SettingsService.get('user.sharing.editable'),
                    shareable: SettingsService.get('user.sharing.resharing'),
                    receiver
                };

                try {
                    let d = await API.createShare(share);
                    this.getSharedWithUsers.push(receiver);
                    share.id = d.id;
                    share.updatePending = true;
                    share.owner = {
                        id  : document.querySelector('head[data-user]').getAttribute('data-user'),
                        name: document.querySelector('head[data-user-displayname]')
                                      .getAttribute('data-user-displayname')
                    };
                    share.receiver = {id: receiver, name: this.idMap[receiver]};
                    this.shares[d.id] = API._processShare(share);
                    this.search = '';
                    this.refreshShares();
                } catch(e) {
                    if(e.id === '65782183') {
                        Messages.notification(['The user {uid} does not exist', {uid: receiver}]);
                    } else {
                        let message = e.hasOwnProperty('message') ? e.message:e.statusText;
                        Messages.notification(['Unable to share password: {message}', {message}]);
                    }
                }
            },
            reloadShares() {
                API.showPassword(this.password.id, 'shares')
                   .then((d) => {this.shares = d.shares;})
                   .catch(console.error);
            },
            submitAction($event) {
                if($event.keyCode === 13) {
                    let uid = this.search;
                    if(this.nameMap.hasOwnProperty(uid)) {
                        uid = this.nameMap[uid];
                    }

                    if(this.idMap.hasOwnProperty(uid) || !this.autocomplete) {
                        this.addShare(uid);
                    } else {
                        Messages.notification(['The user {uid} does not exist', {uid}]);
                    }
                }
            },
            shareWithUser(uid) {
                this.addShare(uid);
            },
            getAvatarUrl(uid) {
                return API.getAvatarUrl(uid);
            },
            deleteShare($event) {
                delete this.shares[$event.id];
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
                               console.error(e);
                               reject(e);
                           });
                    });
                }

                return this.cronPromise;
            }
        },

        watch: {
            password(value) {
                this.shares = value.hasOwnProperty('shares') ? value.shares:[];
                this.hasCse = value.cseType !== 'none' && !value.shared;

                this.$forceUpdate();
            },
            search() {
                this.searchUsers();
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

    .user-search {
        position         : absolute;
        top              : 37px;
        width            : 100%;
        border-radius    : var(--border-radius);
        z-index          : 2;
        background-color : var(--color-main-background);
        color            : var(--color-primary);
        border           : 1px solid var(--color-primary);

        &.shared-with {
            top : 77px;
        }

        li {
            line-height : 32px;
            display     : flex;
            padding     : 3px;
            cursor      : pointer;

            &:hover {
                color            : var(--color-primary-text);
                background-color : var(--color-primary);
            }
        }
    }
}
</style>