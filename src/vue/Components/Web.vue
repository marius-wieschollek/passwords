<template>
    <a :href="getHref" :target="getTarget" :rel="getRel" :style="getStyle" :title="getTitle">
        {{ getText }}
        <slot name="default" v-if="!getText"></slot>
    </a>
</template>

<script>
    import SimpleApi from '@js/ApiClient/SimpleApi';
    import ThemeManager from '@js/Manager/ThemeManager';
    import Localisation from '@js/Classes/Localisation';

    export default {
        props   : {
            href  : {
                type: String
            },
            target: {
                type     : String,
                'default': null
            },
            css   : {
                'default': null
            },
            title : {
                type     : String,
                'default': null
            },
            text  : {
                type     : String,
                'default': null
            }
        },
        computed: {
            getText() {
                if(this.text) return Localisation.translate(this.text, {href: this.getHref});
                return '';
            },
            getTitle() {
                let title = this.title ? this.title:'Go to {href}';
                return Localisation.translate(title, {href: this.getHref});
            },
            getHref() {
                if(!this.href) return location.href;
                return SimpleApi.parseUrl(this.href, 'href');
            },
            getTarget() {
                let host = SimpleApi.parseUrl(this.href, 'host');
                if(host !== location.host) return '_blank';
                return this.target ? this.target:'_self';
            },
            getRel() {
                return this.getTarget === '_blank' ? 'noreferrer noopener':'';
            },
            getStyle() {
                if(this.css !== null) return this.css;

                return {
                    color: ThemeManager.getColor()
                };
            }
        }
    };
</script>