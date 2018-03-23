<template>
    <div class="row footer">
        <div class="title" :title="getText">
            <translate :say="getText"/>
        </div>
    </div>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import Localisation from '@js/Classes/Localisation';
    import SearchManager from '@js/Manager/SearchManager';

    export default {
        components: {
            Translate
        },

        props: {
            passwords: {
                type     : Array,
                'default': () => { return []; }
            },
            folders  : {
                type     : Array,
                'default': () => { return []; }
            },
            tags     : {
                type     : Array,
                'default': () => { return []; }
            }
        },

        data() {
            return {
                search: SearchManager.status
            };
        },

        computed: {
            getText() {
                if(this.search.active !== false) {
                    return this.getSearchText();
                }
                return this.getFooterText(this.passwords.length, this.folders.length, this.tags.length);
            }
        },

        methods: {
            getSearchText() {
                let text = this.getFooterText(this.search.passwords, this.search.folders, this.search.tags),
                    matches = '';

                if(this.search.total < 2) {
                    matches = Localisation.translate('matches')
                } else {
                    matches = Localisation.translate('match')
                }

                return `${text} ${matches} "${this.search.query}"`;
            },

            getFooterText(passwords, folders, tags) {
                let text = [];

                if(passwords === 1) {
                    text.push(Localisation.translate('1 password'));
                } else if(passwords) {
                    text.push(Localisation.translate('{passwords} passwords', {passwords: passwords}));
                }

                if(folders === 1) {
                    text.push(Localisation.translate('1 folder'));
                } else if(folders) {
                    text.push(Localisation.translate('{folders} folders', {folders: folders}));
                }

                if(tags === 1) {
                    text.push(Localisation.translate('1 tag'));
                } else if(tags) {
                    text.push(Localisation.translate('{tags} tags', {tags: tags}));
                }

                if(text.length === 3) {
                    let and = Localisation.translate(' and ');
                    return text[0] + ', ' + text[1] + and + text[2];
                } else if(text.length === 2) {
                    let and = Localisation.translate(' and ');
                    return text[0] + and + text[1];
                } else if(text.length === 1) {
                    return text[0];
                }

                return 'Nothing';
            }
        }
    };
</script>

<style lang="scss">
    #app-content {
        .item-list {
            .row.footer {
                color               : $color-grey;
                -webkit-user-select : none;
                -moz-user-select    : none;
                -ms-user-select     : none;
                user-select         : none;
                border-bottom       : none;

                .title {
                    padding-left : 99px;
                    cursor       : default;
                }

                &:active,
                &:hover {
                    background-color : initial;
                }
            }
        }
    }
</style>