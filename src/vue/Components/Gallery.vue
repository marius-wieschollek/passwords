<template>
    <div :id="id" class="blueimp-gallery blueimp-gallery-controls">

        <div class="slides"></div>
        <h3 class="title"></h3>
        <p class="description"></p>
        <i class="prev fa fa-angle-left" aria-hidden="true"></i>
        <i class="next fa fa-angle-right" aria-hidden="true"></i>
        <i class="close fa fa-close" aria-hidden="true"></i>
        <web class="open fa fa-external-link" :href="imageUrl" v-if="imageUrl"></web>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import 'blueimp-gallery/js/blueimp-gallery-fullscreen.js';
    import 'blueimp-gallery/js/blueimp-gallery-video.js';
    import 'blueimp-gallery/js/blueimp-gallery-youtube.js';
    import BlueImp from 'blueimp-gallery/js/blueimp-gallery.js';
    import LocalisationService from "@js/Services/LocalisationService";
    import LoggingService from "@js/Services/LoggingService";

    export default {
        components: {Web},
        props     : {
            images: {
                type: Array,
                default() {
                    return [];
                }
            },

            options: {
                type: Object,
                default() {
                    return {};
                }
            },

            index: {
                type: Number
            },

            id: {
                type   : String,
                default: 'blueimp-gallery'
            }
        },

        data() {
            return {
                instance: null,
                imageUrl: null
            };
        },

        watch: {
            index(value) {
                if(value !== null) {
                    this.open(value);
                } else {
                    if(this.instance) this.instance.close();
                    document.getElementById('content-vue').style.zIndex = 'auto';
                    this.$emit('close');
                }
            }
        },

        destroyed() {
            if(this.instance !== null) {
                this.instance.close();
                this.instance = null;
                document.getElementById('content-vue').style.zIndex = 'auto';
            }
        },

        methods: {
            open(index = 0) {
                document.getElementById('content-vue').classList.add('blocking');
                const instance = typeof BlueImp.Gallery !== 'undefined' ? BlueImp.Gallery:BlueImp,
                      options  = Object.assign(
                          {
                              toggleControlsOnReturn    : false,
                              toggleControlsOnSlideClick: false,
                              closeOnSlideClick         : true,
                              closeOnHashChange         : false,
                              container                 : `#${this.id}`,
                              index,
                              onopen                    : () => this.$emit('onopen'),
                              onopened                  : () => this.$emit('onopened'),
                              onslide                   : this.onSlideCustom,
                              onslideend                : (index, slide) => this.$emit('onslideend', {index, slide}),
                              onslidecomplete           : (index, slide) => this.$emit('onslidecomplete', {index, slide}),
                              onclose                   : () => this.close(),
                              onclosed                  : () => this.onclosed()
                          },
                          this.options
                      );

                document.getElementById('content-vue').style.zIndex = 2001;
                this.instance = instance(this.images, options);
            },
            onSlideCustom(index, slide) {
                this.$emit('onslide', {index, slide});

                let image = this.images[index];
                if(image !== undefined) {
                    this.imageUrl = image.href;
                    if(image.type.startsWith('video')) {
                        this.loadCaptions(slide, image.href);
                        slide.querySelector('a').click();
                    }
                }
            },
            /**
             *
             * @param {HTMLElement} slide
             * @param {String} videoUrl
             * @return {Promise<void>}
             */
            async loadCaptions(slide, videoUrl) {
                let lastSlash = videoUrl.lastIndexOf('/') + 1,
                    baseUrl   = `${videoUrl.substring(0, lastSlash)}subtitles/`,
                    fileName  = videoUrl.substring(lastSlash, videoUrl.lastIndexOf('.')),
                    languages = {en: 'English', de: 'Deutsch'};

                for(let language in languages) {
                    if(languages.hasOwnProperty(language)) {
                        let url = `${baseUrl + fileName}-${language}.vtt`;
                        this.addCaption(slide, url, language, languages[language])
                            .catch(LoggingService.catch);
                    }
                }
            },
            async addCaption(slide, url, language, label) {
                let response = await fetch(new Request(url, {redirect: 'error', referrerPolicy: 'no-referrer'})),
                    mime     = response.headers.get('content-type');

                if(!response.ok || (!mime.startsWith('text/plain') && mime.startsWith('text/vtt'))) {
                    return;
                }

                let data  = await response.blob(),
                    track = document.createElement('track');

                track.kind = 'captions';
                track.label = label;
                track.srclang = language;
                track.mode = 'hidden';
                track.src = window.URL.createObjectURL(data);

                let video = slide.querySelector('video');
                video.appendChild(track);

                if(language !== 'en' && LocalisationService.language.startsWith(language)) {
                    for (let i = 0; i < video.textTracks.length; i++) {
                        video.textTracks[i].mode = video.textTracks[i].language === language ? 'showing':'hidden';
                    }
                }
            },
            close() {
                document.getElementById('content-vue').classList.remove('blocking');
                document.getElementById('content-vue').style.zIndex = 'auto';
                this.$emit('close');
            },
            onclosed() {
                this.instance = null;
                this.$emit('onclosed');
            }
        }
    };
</script>

<style lang="scss">
    @import '~blueimp-gallery/css/blueimp-gallery.min.css';

    .blueimp-gallery {
        background-color : transparentize($color-black, 0.25);
        max-height       : 100vh;
        backdrop-filter  : blur(4px);

        > .description {
            position : absolute;
            top      : 30px;
            left     : 15px;
            color    : var(--color-main-text);
            display  : none;
        }

        > .close,
        > .title,
        > .description,
        > .play-pause {
            display : block;
        }

        > .title {
            margin-right : 6rem;
        }

        > .slides > .slide > .video-content {
            display         : flex;
            justify-content : center;

            > video {
                position   : static;
                width      : auto;
                height     : auto;
                max-width  : 100%;
                max-height : 100%;
            }
        }

        .close,
        .next,
        .prev {
            font-family : var(--pw-icon-font-face);
            font-size   : 2rem;
            border      : none;
            background  : none;
            display     : block;
            color       : var(--color-primary-text);
        }

        .next,
        .prev {
            font-size : 4rem;
            width     : auto;
            height    : auto;
        }

        .open {
            padding         : 15px;
            right           : 60px;
            left            : auto;
            margin          : -15px;
            font-size       : 30px;
            text-decoration : none;
            cursor          : pointer;
            position        : absolute;
            top             : 18px;
            line-height     : 30px;
            color           : var(--color-main-text);
            text-shadow     : 0 0 2px #000000;
            opacity         : .8;
            display         : block;

            &:hover {
                opacity : 1;
            }
        }

        @media (max-width : $width-small) {
            top : 0;

            .prev,
            .next {
                display : none;
            }
        }
    }
</style>
