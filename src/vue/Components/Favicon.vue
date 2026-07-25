<template>
    <img :src="src" :title="title" :width="size" :height="size" loading="lazy" alt="" @load="lazyLoad"/>
</template>

<script>
    import SettingsService from "@js/Services/SettingsService";
    import FaviconService from "@js/Services/FaviconService";

    export default {
        props: {
            domain: String,
            size  : {
                type   : Number,
                default: 32
            },
            title : {
                type   : String,
                default: null
            }
        },

        data() {
            return {
                loaded: false,
                src   : SettingsService.get('server.theme.app.icon')
            };
        },

        methods: {
            fetchIcon() {
                this.src = SettingsService.get('server.theme.app.icon');
                this.$nextTick(() => {
                    let domain = this.domain,
                        size   = this.size;

                    FaviconService
                        .fetch(domain, size)
                        .then((data) => {
                            if(this.domain !== domain || this.size !== size) return;
                            this.src = data;
                            this.loaded = true;
                        });
                });
            },
            lazyLoad() {
                if(!this.loaded) {
                    this.fetchIcon();
                }
            }
        },

        watch: {
            domain() {
                this.lazyLoad();
            },
            size() {
                this.lazyLoad();
            }
        }
    };
</script>