<template>
    <li>
        <p>
            <translate say="Whenever you don't know how something works, have questions or need help with a problem:"/>
            <translate say="Help is just around the corner."/>
            <translate say="Our built-in handbook can be found by clicking on &quot;More&quot; and explains the app in all details."/>
            <translate say="In any other case, our community is the right place to ask your questions."/>
        </p>
        <div class="help-options">
            <translate tag="a" say="Handbook" icon="book" target="_blank" href="#/help"/>
            <translate tag="a" say="Forum" icon="users" target="_blank" href="//help.nextcloud.com/c/apps/passwords"/>
            <!-- <translate tag="a" say="Chat" icon="telegram" href="//t.me/nc-passwords"/> -->
        </div>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate}
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
                color      : var(--color-main-text);
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