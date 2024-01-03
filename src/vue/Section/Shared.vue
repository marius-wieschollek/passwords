<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="getBreadcrumb"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="isNotEmpty"/>
                <generic-line
                        v-if="$route.params.type === undefined"
                        v-for="(title, index) in shareType"
                        :key="title"
                        :label="title"
                        icon="share-alt"
                        :params="{type: index.toString()}"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id">
                    <ul slot="middle" class="line-share-icons">
                        <li v-for="user in getShareUsers(password.id)" :key="user.id" :title="user.name">
                            <img :src="user.icon" :title="user.name" :alt="user.name" loading="lazy" width="24" height="24"/>
                            {{ user.name }}
                        </li>
                    </ul>
                </password-line>
                <footer-line :passwords="passwords" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Breadcrumb from '@vc/Breadcrumb';
    import HeaderLine from '@vue/Line/Header';
    import FooterLine from '@vue/Line/Footer';
    import GenericLine from '@vue/Line/Generic';
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import Localisation from '@js/Classes/Localisation';
    import Application from '@js/Init/Application';
    import UtilityService from "@js/Services/UtilityService";

    export default {
        extends: BaseSection,

        components: {
            Breadcrumb,
            HeaderLine,
            FooterLine,
            GenericLine,
            PasswordLine,
            'empty': () => import(/* webpackChunkName: "EmptyContent" */ '@vc/Empty')
        },
        data() {
            return {
                loading    : false,
                shareType  : ['Shared with you', 'Shared by you'],
                shareUsers : []
            };
        },

        computed: {
            isEmpty() {
                if(this.loading) return false;
                if(this.search.active && this.search.total === 0) return true;

                return !this.passwords.length && this.$route.params.type !== undefined;
            },
            isNotEmpty() {
                return !this.loading && !this.isEmpty && this.$route.params.type !== undefined;
            },
            getEmptyText() {
                if(this.search.active) {
                    return Localisation.translate('We could not find anything for "{query}"', {query: this.search.query});
                }

                return this.$route.params.type.toString() === '0' ? 'No passwords were shared with you':'You did not share any passwords';
            },
            getBreadcrumb() {
                if(this.$route.params.type !== undefined) {
                    let status = Number.parseInt(this.$route.params.type),
                        label  = this.shareType[status];

                    return [
                        {path: {name: 'Shares'}, label: Localisation.translate('Shares')},
                        {path: this.$route.path, label: Localisation.translate(label)}
                    ];
                }

                return [];
            }
        },

        methods: {
            refreshView      : function() {
                if(this.$route.params.type !== undefined) {
                    let status = Number.parseInt(this.$route.params.type);

                    if(status === 0) {
                        API.findShares({receiver: '_self'}, 'model+password')
                           .then((d) => {this.updateContentList(d, status);});
                    } else {
                        API.findShares({owner: '_self'}, 'model+password')
                           .then((d) => {this.updateContentList(d, status);});
                    }

                    if(!this.passwords.length) this.loading = true;
                } else {
                    this.loading = false;
                    this.passwords = [];
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
                    if(password.trashed) continue;
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

                for(let i in shareUsers) {
                    if(shareUsers.hasOwnProperty(i)) shareUsers[i] = UtilityService.sortApiObjectArray(shareUsers[i], 'name');
                }

                this.shareUsers = shareUsers;
                this.passwords = UtilityService.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
            },
            getShareUsers(id) {
                return this.shareUsers[id];
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
                Application.sidebar = null;
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
            width         : 24px;
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