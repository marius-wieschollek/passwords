<template>
    <div class="sharing-container" v-if="enabled">
        <input type="text" v-model="search" class="share-add-user" :placeholder="placeholder" @keypress="submitAction($event)"/>
        <ul class="shares" v-for="share in shares" :key="share.id" :data-share-id="share.id">
            <share :share="share" v-on:delete="deleteShare($event)"></share>
        </ul>
        <ul class="user-search" :style="getDropDownStyle" v-if="matches.length !== 0">
            <li v-for="(name,uid) in matches" @click="shareWithUser(uid)" @mouseover="getHoverStyle($event)" @mouseout="getHoverStyle($event, false)">
                <img :src="getAvatarUrl(uid)" :alt="name" class="avatar">{{name}}
            </li>
        </ul>
    </div>
    <translate v-else say="Sharing is not enabled"/>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Utility from "@js/Classes/Utility";
    import Messages from '@js/Classes/Messages';
    import Share from "@vue/Components/Sharing/Share";
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
                enabled    : false,
                search     : '',
                matches    : [],
                nameMap    : [],
                idMap      : [],
                shares     : this.password.shares,
                placeholder: Utility.translate('Search user')
            }
        },

        created() {
            API.getSharingInfo()
                .then((e) => {this.enabled = e.enabled;});
        },

        computed: {
            getDropDownStyle() {
                return {
                    color: ThemeManager.getColor(),
                    border: '1px solid ' + ThemeManager.getColor(),
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
            submitAction($event) {
                if($event.keyCode === 13) {
                    let uid = this.search;
                    if(this.nameMap.hasOwnProperty(uid)) {
                        uid = this.nameMap[uid];
                    }

                    if(this.idMap.hasOwnProperty(uid)) {
                        this.addShare(uid);
                    } else {
                        Messages.alert(['The user {uid} does not exist', {uid:uid}], 'Invalid user');
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
                if(value.share && value.share.shareable === false) this.enabled = false;
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
        position: relative;

        .share-add-user {
            width : 100%;
        }

        img.avatar {
            border-radius : 16px;
            margin-right  : 5px;
            height        : 32px;
        }

        .shares {
            margin-top : 5px;
        }

        .user-search {
            position: absolute;
            top: 37px;
            width: 100%;
            border-radius: 3px;

            li {
                line-height: 32px;
                display: flex;
                padding: 3px;
                cursor:pointer;
            }
        }
    }
</style>