<template>
    <div class="sharing-container">
        <input type="text" v-model="search" class="share-add-user" :placeholder="placeholder" @keypress="submitAction($event)"/>
        <ul class="shares" v-for="share in shares" :key="share.id" :data-share-id="share.id">
            <share :share="share" v-on:delete="deleteShare($event)"></share>
        </ul>
        <ul class="user-search" :style="getDropDownStyle" v-if="matches.length !== 0">
            <li v-for="(name,uid) in matches" @click="shareWithUser(uid)" @mouseover="getHoverStyle($event)" @mouseout="getHoverStyle($event, false)">
                <img :src="getAvatarUrl(uid)" :alt="name" class="avatar">&nbsp;{{name}}
            </li>
        </ul>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Messages from '@js/Classes/Messages';
    import Share from "@vue/Components/Sharing/Share";
    import Localisation from "@js/Classes/Localisation";
    import ThemeManager from '@js/Manager/ThemeManager';

    export default {
        components: {
            Share,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                search     : '',
                matches    : [],
                nameMap    : [],
                idMap      : [],
                shares     : this.password.shares,
                placeholder: Localisation.translate('Search user'),
                interval: null,
            }
        },

        created() {
            this.interval = setInterval(() => { this.refreshShares() }, 10000)
        },

        beforeDestroy() {
            clearInterval(this.interval);
        },

        computed: {
            getDropDownStyle() {
                return {
                    color          : ThemeManager.getColor(),
                    border         : '1px solid ' + ThemeManager.getColor(),
                    backgroundColor: ThemeManager.getContrastColor()
                };
            }
        },

        methods: {
            async searchUsers() {
                if(this.search === '') {
                    this.matches = [];
                    return;
                }

                this.matches = await API.findSharePartners(this.search);
                for(let i in this.matches) {
                    if(!this.matches.hasOwnProperty(i)) continue;
                    let name = this.matches[i];

                    this.nameMap[name] = i;
                    this.idMap[i] = name;
                }
            },
            addShare(receiver) {
                let share = {
                    password : this.password.id,
                    expires  : null,
                    editable : false,
                    shareable: true,
                    receiver : receiver
                };
                API.createShare(share).then(
                    (d) => {
                        share.id = d.id;
                        share.updatePending = true;
                        share.owner = {
                            id  : document.querySelector('head[data-user]').getAttribute('data-user'),
                            name: document.querySelector('head[data-user-displayname]').getAttribute('data-user-displayname')
                        };
                        share.receiver = {id: receiver, name: this.idMap[receiver]};
                        this.shares[d.id] = API._processShare(share);
                        this.search = '';
                        this.$forceUpdate();
                    }
                );
            },
            getHoverStyle($event, on = true) {
                if(on) {
                    $event.target.style.backgroundColor = ThemeManager.getColor();
                    $event.target.style.color = ThemeManager.getContrastColor();
                } else {
                    $event.target.style.backgroundColor = null;
                    $event.target.style.color = null;
                }
            },
            refreshShares() {
                API.showPassword(this.password.id, 'shares')
                    .then((d) => { this.shares = d.shares;});
            },
            submitAction($event) {
                if($event.keyCode === 13) {
                    let uid = this.search;
                    if(this.nameMap.hasOwnProperty(uid)) {
                        uid = this.nameMap[uid];
                    }

                    if(this.idMap.hasOwnProperty(uid)) {
                        this.addShare(uid);
                    } else {
                        Messages.alert(['The user {uid} does not exist', {uid: uid}], 'Invalid user');
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
                this.$forceUpdate();
            }
        },

        watch: {
            password: function(value) {
                this.shares = value.shares;
                this.$forceUpdate();
            },
            search  : function(value) {
                this.searchUsers()
            }
        }
    }
</script>

<style lang="scss">
    .sharing-container {
        position : relative;

        .share-add-user {
            width : 100%;
        }

        .shares {
            margin-top : 5px;
        }

        .user-search {
            position      : absolute;
            top           : 37px;
            width         : 100%;
            border-radius : 3px;
            z-index       : 2;

            li {
                line-height : 32px;
                display     : flex;
                padding     : 3px;
                cursor      : pointer;
            }
        }
    }
</style>