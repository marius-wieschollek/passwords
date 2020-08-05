<template>
    <img :src="src" :title="title" :width="size" :height="size" alt=""/>
</template>

<script>
    import SettingsService from "@js/Services/SettingsService";
    import FaviconService from "@js/Services/FaviconService";

    export default {
        props: {
            domain: String,
            size : {
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
                src: SettingsService.get('server.theme.app.icon')
            };
        },

        mounted() {
            this.fetchIcon();
        },

        methods: {
            fetchIcon() {
                let domain = this.domain,
                    size   = this.size;
                this.src = SettingsService.get('server.theme.app.icon');
                FaviconService
                    .fetch(domain, size)
                    .then((data) => {
                        if(this.domain !== domain || this.size !== size) return;
                        this.src = data;
                    });
            }
        },

        watch: {
            domain() {
                this.fetchIcon();
            },
            size() {
                this.fetchIcon();
            }
        }
    };
</script>