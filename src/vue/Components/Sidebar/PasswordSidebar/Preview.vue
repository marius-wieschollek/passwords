<template>
    <div class="preview-container" v-if="showPreview">
        <component :is="link ? 'web':'div'" :href="link" class="inner-container">
            <div class="loader">
                <img :src="loadingIcon" alt="">
            </div>
            <div class="image" :class="imgClass" :style="style" @mouseover="imageMouseOver" @mouseout="imageMouseOut">
                <img :src="image" @load="imageLoaded($event)" alt="" v-if="showImage">
            </div>
        </component>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import SettingsService from '@js/Services/SettingsService';
    import FaviconService from "@js/Services/FaviconService";

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
                showImage  : false,
                style      : {
                    marginTop: 0
                }
            };
        },

        created() {
            this.loadFavicon(this.link);
            setTimeout(() => { this.showImage = true; }, 100);
        },

        computed: {
            showPreview() {
                return window.innerWidth > 640 && SettingsService.get('client.ui.password.details.preview');
            }
        },

        methods: {
            imageMouseOver($event) {
                if(this.loading) return;
                let margin = $event.target.height - 290;

                if(margin > 0) {
                    if(margin < 500) {
                        this.imgClass = 'image-loaded s1';
                    } else if(margin < 1000) {
                        this.imgClass = 'image-loaded s5';
                    } else if(margin < 2500) {
                        this.imgClass = 'image-loaded s10';
                    } else if(margin < 4000) {
                        this.imgClass = 'image-loaded s15';
                    } else {
                        this.imgClass = 'image-loaded s20';
                    }
                    this.style.marginTop = '-' + margin + 'px';
                }
            },
            imageMouseOut() {
                this.style.marginTop = 0;
            },
            imageLoaded(event) {
                if(event.target.src === this.image) {
                    this.loading = false;
                    this.imgClass = 'image-loaded';
                }
            },
            loadFavicon() {
                setTimeout(async () => {
                    if(this.loading) {
                        this.loadingIcon = await FaviconService.fetch(this.host, 96);
                    }
                }, 450);
            }
        },
        watch  : {
            image() {
                this.loading = true;
                this.showImage = false;
                this.imgClass = 'loading-hidden';
                this.style.marginTop = 0;
                setTimeout(() => { this.showImage = true; }, 100);
                this.$el.scrollTop = 0;
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
        max-height: 250px;
        overflow: hidden;
        position: relative;

        .inner-container {
            display: block;
            font-size: 0;

            .image {
                margin-top: 0;
                min-height: 0;
                position: relative;
                opacity: 1;
                transition: min-height 0.5s ease-in-out, opacity 0.5s ease-in-out;

                img {
                    width: 100%;
                }

                &.s1 {
                    transition: min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 1s ease-in-out;
                }

                &.s5 {
                    transition: min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 5s ease-in-out;
                }

                &.s10 {
                    transition: min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 10s ease-in-out;
                }

                &.s15 {
                    transition: min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 15s ease-in-out;
                }

                &.s20 {
                    transition: min-height 0.5s ease-in-out, opacity 0.15s ease-in-out, margin-top 20s ease-in-out;
                }

                &.loading-hidden {
                    opacity: 0;
                    min-height: 250px;
                    transition: min-height 0.15s ease-in-out, opacity 0.15s ease-in-out;

                    img {
                        height: 0;
                    }
                }
            }

            .loader {
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;

                img {
                    transform: translate(-50%, -50%);
                    border-radius: var(--border-radius);
                    position: absolute;
                    left: 50%;
                    top: 50%;
                    width: 72px;
                    height: 72px;
                    transition: height 0.15s ease-in-out, width 0.15s ease-in-out;
                }
            }

            &:hover .loader img {
                width: 96px;
                height: 96px;
            }
        }

        &.hidden {
            display: none;
        }

        @media screen and (hover: none) {
            overflow-y: auto;
        }

        @media (max-height: 480px) {
            max-height: 45vh;

            .inner-container .image.loading-hidden {
                min-height: 45vh;
            }
        }

        @media (max-width: $width-extra-small) {
            display: none;
        }
    }
</style>