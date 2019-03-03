<template>
    <li>
        <p>
            <translate say="Access your passwords from everywhere with ease. All thanks to our many great app and extensions."/>
            <translate say="Click on &quot;More&quot; to open our Apps & Extensions section where you can find all the apps developed by us and our community."/>
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
        }
    }
</style>