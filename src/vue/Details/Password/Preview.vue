<template>
    <div class="preview-container" v-if="showPreview">
        <web :href="link">
            <div class="loader">
                <img :src="loadingIcon" alt="">
            </div>
            <div class="image" :class="imgClass" :style="style" @mouseover="imageMouseOver" @mouseout="imageMouseOut">
                <img :src="image" @load="imageLoaded" alt="">
            </div>
        </web>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import API from '@js/Helper/api';

    export default {
        components: {Web},
        props     : {
            image: {
                type     : String,
                'default': ''
            },
            icon : {
                type     : String,
                'default': ''
            },
            link : {
                type     : String,
                'default': ''
            },
            host : {
                type     : String,
                'default': 'default'
            }
        },

        data() {
            return {
                loading    : true,
                loadingIcon: this.icon,
                imgClass   : 'loading-hidden',
                style      : {
                    marginTop: 0
                }
            };
        },

        created() {
            this.loadFavicon(this.link);
        },

        computed: {
            showPreview() {
                return window.innerWidth > 640;
            }
        },

        methods: {
            imageMouseOver($event) {
                if(this.loading) return;
                let margin = $event.target.height - 290;

                if(margin > 0) {
                    if(margin < 500) {
                        this.imgClass = 's1';
                    } else if(margin < 1000) {
                        this.imgClass = 's5';
                    } else if(margin < 2500) {
                        this.imgClass = 's10';
                    } else if(margin < 4000) {
                        this.imgClass = 's15';
                    } else {
                        this.imgClass = 's20';
                    }
                    this.style.marginTop = '-' + margin + 'px';
                }
            },
            imageMouseOut() {
                this.style.marginTop = 0;
            },
            imageLoaded() {
                this.loading = false;
                this.imgClass = '';
            },
            loadFavicon(url) {
                setTimeout(() => {if(this.loading) this.loadingIcon = API.getFaviconUrl(this.host, 96);}, 350);
            }
        },
        watch  : {
            image() {
                this.loading = true;
                this.imgClass = 'loading-hidden';
                this.style.marginTop = 0;
                this.$forceUpdate();
            },
            icon(value) {
                this.loadingIcon = value;
            },
            link(value) {
                this.loadFavicon(value);
            }
        }
    };
</script>

<style lang="scss">
    .preview-container {
        max-height : 274px;
        overflow   : hidden;
        position   : relative;

        a {
            display   : block;
            font-size : 0;

            .image {
                margin-top : 0;
                min-height : 0;
                position   : relative;
                opacity    : 1;
                transition : min-height 0.5s ease-in-out, opacity 0.5s ease-in-out;

                img {
                    width : 100%;
                }

                &.s1 { transition : min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 1s ease-in-out; }
                &.s5 { transition : min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 5s ease-in-out; }
                &.s10 { transition : min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 10s ease-in-out; }
                &.s15 { transition : min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 15s ease-in-out; }
                &.s20 { transition : min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 20s ease-in-out; }
                &.loading-hidden {
                    opacity    : 0;
                    min-height : 274px;
                    transition : min-height 0.15s ease-in-out, opacity 0.15s ease-in-out;
                }
            }

            .loader {
                position : absolute;
                top      : 0;
                right    : 0;
                bottom   : 0;
                left     : 0;

                img {
                    transform     : translate(-50%, -50%);
                    border-radius : var(--border-radius);
                    position      : absolute;
                    left          : 50%;
                    top           : 50%;
                    width         : 72px;
                    height        : 72px;
                    transition    : height 0.15s ease-in-out, width 0.15s ease-in-out;
                }
            }

            &:hover .loader img {
                width  : 96px;
                height : 96px;
            }
        }

        &.hidden {
            display : none;
        }

        @media (max-width : $mobile-width) {
            display : none;
        }
    }
</style>