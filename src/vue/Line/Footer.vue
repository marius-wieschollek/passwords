<template>
    <div class="row footer">
        <div class="title" :title="getText">
            <translate :say="getText"/>
        </div>
    </div>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import Localisation from "@js/Classes/Localisation";

    export default {
        components: {
            Translate
        },
        props     : {
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

        computed: {
            getText() {
                let text = [];

                if(this.passwords.length === 1) {
                    text.push(Localisation.translate('1 password'));
                } else if(this.passwords.length) {
                    text.push(Localisation.translate('{passwords} passwords', {passwords: this.passwords.length}));
                }

                if(this.folders.length === 1) {
                    text.push(Localisation.translate('1 folder'));
                } else if(this.folders.length) {
                    text.push(Localisation.translate('{folders} folders', {folders: this.folders.length}));
                }

                if(this.tags.length === 1) {
                    text.push(Localisation.translate('1 tag'));
                } else if(this.tags.length) {
                    text.push(Localisation.translate('{tags} tags', {tags: this.tags.length}));
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