<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeader" />
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <empty v-if="!loading && !passwords.length" />
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Events from "@js/Classes/Events";
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import Empty from "@/vue/Components/Empty";
    import HeaderLine from "@/vue/Line/Header";
    import PasswordLine from '@vue/Line/Password';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        components: {
            Empty,
            Breadcrumb,
            HeaderLine,
            PasswordLine,
            PasswordDetails
        },
        data() {
            return {
                loading  : true,
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                },
                sort: {
                    by: 'edited',
                    order: false
                }
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
            },
            showHeader() {
                return !this.loading && this.passwords.length;
            }
        },

        methods: {
            refreshView: function() {
                API.listPasswords().then(this.updateContentList);
            },

            updateContentList: function(passwords) {
                let array = Utility.sortApiObjectArray(passwords, 'edited', false);
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(array.slice(0, 15), this.sort.by, this.sort.order);
            },
            updateSorting($event) {
                this.sort = $event;
                this.passwords = Utility.sortApiObjectArray(this.passwords, this.sort.by, this.sort.order);
            }
        }
    };
</script>