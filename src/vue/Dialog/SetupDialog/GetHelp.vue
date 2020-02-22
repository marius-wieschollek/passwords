<template>
    <li>
        <p>
            <translate say="Whenever you don't know how something works, have questions or need help with a problem:"/>
            <translate say="Help is just around the corner."/>
            <translate say="Our built-in handbook can be found by clicking on &quot;More&quot; and explains the app in all details."/>
            <translate say="In any other case, our community is the right place to ask your questions."/>
        </p>
        <div class="help-options">
            <translate tag="a" say="Handbook" icon="book" @click.prevent="openHelp"/>
            <web text="Forum" icon="users" target="_blank" href="https://help.nextcloud.com/c/apps/passwords"/>
            <web text="Chat" icon="telegram" href="https://t.me/nc_passwords"/>
        </div>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import Messages from '@js/Classes/Messages';
    import Web from '@vc/Web';

    export default {
        components: {Web, Translate},
        methods   : {
            openHelp() {
                this.$emit('redirect', {path: '/help'});
                Messages.notification('Handbook will be opened after the tutorial');
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-get-help {
        > p:first-of-type {

            &:before {
                content       : "\F059";
                margin-bottom : -0.5rem;
            }

            padding : 5rem 1.5rem 4rem 12rem;

            @media (max-width : 900px) {
                padding : 1.5rem;

                &:before {
                    display : none;
                }
            }
        }

        .help-options {
            display         : flex;
            justify-content : center;

            a {
                text-align : center;
                cursor     : pointer;
                color      : var(--color-primary);
                margin     : 0 1.5rem;

                i {
                    color         : var(--color-primary);
                    font-size     : 7rem;
                    display       : block;
                    margin-bottom : 0.5rem;
                }
            }

            @media (max-width : $width-extra-small) {
                margin-top : 3rem;

                a i {
                    font-size : 4rem;
                }
            }
        }
    }
</style>