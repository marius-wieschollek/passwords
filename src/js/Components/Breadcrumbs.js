Vue.component('passwords-breadcrumb', {
    template: '#passwords-template-breadcrumb',
    data() {
        return {
            items: [
                {link: '', label: 'All'}
            ]
        }
    },


    props: {
        newFolder: {
            type: Boolean,
            default: false
        },
        newTag: {
            type: Boolean,
            default: false
        },
        showAddNew: {
            type: Boolean,
            default: true
        }
    },

    methods : {
        clickAddButton($event) {
            $($event.target).parents('.creatable').toggleClass('active');
        },
        clickCreatePassword($event) {
            let PasswordCreateDialog = Vue.extend(PasswordsUi.getComponent('dialog.password.create'));
            new PasswordCreateDialog().$mount('#app-popup div');
        }
    }
});