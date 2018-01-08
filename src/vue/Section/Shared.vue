<template>
    <div id="app-content" :class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <share-line v-if="$route.params.type === undefined"
                            v-for="(title, index) in shareType"
                            :key="title"
                            :type="index"
                            :label="title">
                </share-line>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import ShareLine from "@vue/Line/Share";
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';

    export default {
        components: {
            ShareLine,
            Breadcrumb,
            PasswordLine,
            PasswordDetails
        },
        data() {
            return {
                loading   : false,
                passwords : [],
                shareType : [
                    'Shared with me', 'Shared by me'
                ],
                detail    : {
                    type   : 'none',
                    element: null
                },
                breadcrumb: []
            }
        },

        created() {
            this.refreshView();
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView      : function() {
                if(this.$route.params.type !== undefined) {
                    let status = this.$route.params.type,
                        label  = this.shareType[status];
                    if(status === 0) {
                        API.findShares({receiver: '_self'}, 'model+password').then(this.updateContentList);
                    } else {
                        API.findShares({owner: '_self'}, 'model+password').then(this.updateContentList);
                    }

                    if(this.passwords.length === 0) this.loading = true;
                    this.breadcrumb = [
                        {path: '/shared', label: Utility.translate('Shared')},
                        {path: this.$route.path, label: Utility.translate(label)}
                    ]
                } else {
                    this.passwords = [];
                    this.breadcrumb = [];
                }
            },
            updateContentList: function(shares) {
                this.loading = false;

                let passwords = {};
                for(let i in shares) {
                    if(!shares.hasOwnProperty(i)) continue;
                    let password = shares[i].password;
                    passwords[password.id] = password;
                }

                this.passwords = Utility.sortApiObjectArray(passwords, 'label', true);
            }
        },
        watch  : {
            $route: function() {
                this.refreshView()
            }
        }
    };
</script>