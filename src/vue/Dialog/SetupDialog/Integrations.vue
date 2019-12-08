<template>
    <li>
        <p>
            <translate say="Access your passwords with ease from anywhere, thanks to our apps and extensions."/>
            <translate say="You can find our Apps & Extensions section in &quot;More&quot; or just click the button below."/>
        </p>
        <translate tag="div" class="setup-cta" say="Go to Apps & Extensions" @click="openApps"/>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate},
        methods   : {
            openApps() {
                this.$emit('redirect', {path: '/apps'});
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-integrations {
        p:before {
            content : "\F12e";
        }

        .setup-cta {
            margin           : 1rem auto;
            width            : 50%;
            text-align       : center;
            padding          : 3rem;
            background-color : var(--color-primary-element);
            color            : var(--color-primary-text);
            border-radius    : var(--border-radius-large);
            cursor           : pointer;

            @media (max-width : $width-extra-small) {
                width  : auto;
                margin : 1rem 1.5rem;
            }
        }
    }
</style>