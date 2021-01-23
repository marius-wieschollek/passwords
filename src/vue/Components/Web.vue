<template>
    <a :href="getHref" :target="getTarget" :rel="getRel" :class="className" :title="getTitle">
        <icon :icon="icon" :class="iconClass" v-if="icon" />
        {{ getText }}
        <slot name="default" v-if="!getText"></slot>
    </a>
</template>

<script>
    import API from '@js/Helper/api';
    import Localisation from '@js/Classes/Localisation';
    import Icon from "@vc/Icon";

    export default {
        components: {Icon},
        props   : {
            href  : {
                type: String
            },
            target: {
                type     : String,
                'default': null
            },
            title : {
                type     : String,
                'default': null
            },
            text  : {
                type     : String,
                'default': null
            },
            variables     : {
                type     : Object,
                'default': () => { return {}; }
            },
            className  : {
                type     : String,
                'default': 'link'
            },
            icon          : {
                type     : String,
                'default': null
            },
            iconClass     : {
                type     : String,
                'default': null
            }
        },
        computed: {
            getText() {
                return this.text ? Localisation.translate(this.text, this.getVariables):'';
            },
            getTitle() {
                let title = this.title ? this.title:'Go to {href}';
                return Localisation.translate(title, this.getVariables);
            },
            getVariables() {
                this.variables.href = this.getHref;
                return this.variables;
            },
            getHref() {
                if(!this.href || this.href.substr(0, 11) === 'javascript:') return location.href;
                if(this.href.substr(0, 7) === 'mailto:') return this.href;
                return API.parseUrl(this.href, 'href');
            },
            getTarget() {
                let host = API.parseUrl(this.href, 'host');
                if(host !== location.host) return '_blank';
                return this.target ? this.target:'_self';
            },
            getRel() {
                return this.getTarget === '_blank' ? 'noreferrer noopener':'';
            }
        }
    };
</script>

<style lang="scss">
    #app.passwords a.link {
        color: var(--color-primary);
    }
</style>