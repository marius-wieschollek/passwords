<template id="passwords-template-password-line">
    <div class="row password" @click="singleClickAction($event)" @dblclick="doubleClickAction()" :data-password-id="password.id">
        <i class="fa fa-star favourite" v-bind:class="{ active: password.favourite }" @click="favouriteAction($event)"></i>
        <div v-bind:style="faviconStyle" class="favicon">&nbsp;</div>
        <span class="title">{{ password.title }}</span>
        <div class="date">{{ date }}</div>
        <i v-bind:class="securityCheck" class="fa fa-circle security"></i>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="passwordActionsMenu popovermenu bubble menu">
                <ul>
                    <li @click="detailsAction($event);"><span><i class="fa fa-info"></i> Details</span></li>
                    <li v-if="password.url" @click="copyUrlAction()"><span><i class="fa fa-clipboard"></i> Copy Url</span></li>
                    <li v-if="password.url"><a :href="password.url"
                                               target="_blank"><span><i class="fa fa-link"></i> Open Url</span></a></li>
                    <li><span><i class="fa fa-pencil"></i> Edit</span></li>
                    <li @click="deleteAction()"><span><i class="fa fa-trash"></i> Delete</span></li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import PwMessages  from '@js/Classes/Messages';
    import API from '@js/Helper/api';

    export default {
        template: '#passwords-template-password-line',
        name    : 'PasswordLine',

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                clickTimeout: null
            }
        },

        computed: {
            faviconStyle() {
                return {
                    backgroundImage: 'url(' + this.password.icon + ')'
                }
            },
            date() {
                return new Date(this.password.updated * 1e3).toLocaleDateString();
            },
            securityCheck() {
                switch (this.password.status) {
                    case 0:
                        return 'ok';
                    case 1:
                        return 'warn';
                    case 2:
                        return 'fail';
                }
            }
        },

        methods: {
            singleClickAction($event) {
                if ($event.detail !== 1) return;
                copyToClipboard(this.password.password);

                if (this.clickTimeout) clearTimeout(this.clickTimeout);
                this.clickTimeout =
                    setTimeout(function () { PwMessages.notification('Password was copied to clipboard') }, 300);
            },
            doubleClickAction() {
                if (this.clickTimeout) clearTimeout(this.clickTimeout);

                copyToClipboard(this.password.login);
                PwMessages.notification('Username was copied to clipboard');
            },
            favouriteAction($event) {
                $event.stopPropagation();
                this.password.favourite = !this.password.favourite;
                API.updatePassword(this.password);
            },
            toggleMenu($event) {
                $event.stopPropagation();
                $($event.target).parents('.row.password').find('.passwordActionsMenu').toggleClass('open');
            },
            copyUrlAction() {
                copyToClipboard(this.password.url);
                PwMessages.notification('Url was copied to clipboard')
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'password',
                    element: this.password
                }
            },
            deleteAction() {
                PwMessages.confirm('Do you want to delete the password', 'Delete password')
                    .then(() => {
                        API.deletePassword(this.password.id)
                            .then(() => {
                                PwMessages.notification('Password was deleted');
                            }).catch(() => {
                            PwMessages.notification('Deleting password failed');
                        });
                    })
            }
        }
    }
</script>