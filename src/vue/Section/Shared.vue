<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <generic-line
                        v-if="$route.params.type === undefined"
                        v-for="(title, index) in shareType"
                        :key="title"
                        :label="title"
                        icon="share-alt"
                        :params="{type: index}"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id">
                    <ul slot="middle" class="line-share-icons">
                        <li v-for="user in getShareUsers(password.id)" :key="user.id" :title="user.name">
                            <img :src="user.icon" :alt="user.name"/>
                            {{user.name}}
                        </li>
                    </ul>
                </password-line>
                <footer-line :passwords="passwords" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty" :text="emptyText"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import Empty from "@vue/Components/Empty";
    import GenericLine from "@vue/Line/Generic";
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends   : BaseSection,
        components: {
            Empty,
            Breadcrumb,
            HeaderLine,
            FooterLine,
            GenericLine,
            PasswordLine,
            PasswordDetails
        },
        data() {
            return {
                loading   : false,
                shareType : ['Shared with me', 'Shared by me'],
                breadcrumb: [],
                shareUsers: []
            };
        },

        computed: {
            isEmpty() {
                return !this.loading && !this.passwords.length && this.$route.params.type !== undefined;
            },
            emptyText() {
                return this.$route.params.type.toString() === '0' ? 'No passwords were shared with you':'You did not share any passwords';
            }
        },

        methods: {
            refreshView      : function() {
                this.detail.type = 'none';

                if(this.$route.params.type !== undefined) {
                    let status = Number.parseInt(this.$route.params.type),
                        label  = this.shareType[status];

                    if(status === 0) {
                        API.findShares({receiver: '_self'}, 'model+password')
                            .then((d) => {this.updateContentList(d, status);});
                    } else {
                        API.findShares({owner: '_self'}, 'model+password')
                            .then((d) => {this.updateContentList(d, status);});
                    }

                    if(!this.passwords.length) this.loading = true;
                    this.breadcrumb = [
                        {path: '/shared', label: Utility.translate('Shared')},
                        {path: this.$route.path, label: Utility.translate(label)}
                    ];
                } else {
                    this.loading = false;
                    this.passwords = [];
                    this.breadcrumb = [];
                }
            },
            updateContentList: function(shares, status) {
                if(Number.parseInt(this.$route.params.type) !== status) return;
                this.loading = false;

                let passwords  = {},
                    shareUsers = [];
                for(let i in shares) {
                    if(!shares.hasOwnProperty(i)) continue;
                    let password = shares[i].password,
                        id       = password.id;
                    if(!passwords.hasOwnProperty(id)) {
                        passwords[id] = password;
                        shareUsers[id] = [];
                    }

                    if(status === 0) {
                        shareUsers[id].push(shares[i].owner);
                    } else {
                        shareUsers[id].push(shares[i].receiver);
                    }
                }

                this.shareUsers = shareUsers;
                this.passwords = Utility.sortApiObjectArray(passwords, this.sort.by, this.sort.order);
            },
            getShareUsers(id) {
                return this.shareUsers[id];
            },
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>

<style lang="scss">
    .line-share-icons {
        height      : 50px;
        flex-shrink : 0;
        line-height : 50px;

        li {
            display     : inline-block;
            margin-left : -12px;
            transition  : margin-left 0.25s ease-in-out;

            img {
                height        : 24px;
                border-radius : 12px;
                margin        : 13px 0;
            }
        }

        &:hover {
            li {
                margin-left : 3px;
            }
        }
    }

    @media (max-width : $mobile-width) {
        .line-share-icons {
            display : none;
        }
    }
</style>