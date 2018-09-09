<template>
    <div :id="id" class="blueimp-gallery blueimp-gallery-controls">

        <div class="slides"></div>
        <h3 class="title"></h3>
        <p class="description"></p>
        <i class="prev fa fa-angle-left" aria-hidden="true"></i>
        <i class="next fa fa-angle-right" aria-hidden="true"></i>
        <i class="close fa fa-close" aria-hidden="true"></i>
        <web class="open fa fa-external-link" css="" :href="imageUrl" v-if="imageUrl"></web>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import 'blueimp-gallery/js/blueimp-gallery-fullscreen.js';
    import 'blueimp-gallery/js/blueimp-gallery-video.js';
    import 'blueimp-gallery/js/blueimp-gallery-youtube.js';
    import BlueImp from 'blueimp-gallery/js/blueimp-gallery.js';

    export default {
        components: {Web},
        props: {
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
                    this.$emit('close');
                }
            }
        },

        destroyed() {
            if(this.instance !== null) {
                this.instance.close();
                this.instance = null;
            }
        },

        methods: {
            open(index = 0) {
                document.getElementById('app-content').classList.add('blocking');
                const instance = typeof BlueImp.Gallery !== 'undefined' ? BlueImp.Gallery:BlueImp,
                      options  = Object.assign(
                          {
                              toggleControlsOnReturn    : false,
                              toggleControlsOnSlideClick: false,
                              closeOnSlideClick         : true,
                              container                 : `#${this.id}`,
                              index,
                              onopen                    : () => this.$emit('onopen'),
                              onopened                  : () => this.$emit('onopened'),
                              onslide                   : this.onSlideCustom,
                              onslideend                : (index, slide) => this.$emit('onslideend', {index, slide}),
                              onslidecomplete           : (index, slide) => this.$emit('onslidecomplete', {index, slide}),
                              onclose                   : () => this.close(),
                              onclosed                  : () => this.$emit('onclosed')
                          },
                          this.options
                      );

                this.instance = instance(this.images, options);
            },
            onSlideCustom(index, slide) {
                this.$emit('onslide', {index, slide});

                let image = this.images[index];
                if(image !== undefined) {
                    this.imageUrl = image.href;
                    if(image.type.substr(0, 5) === 'video') {
                        slide.querySelector('a').click();
                    }
                }
            },
            close() {
                document.getElementById('app-content').classList.remove('blocking');
                this.$emit('close')
            }
        }
    };
</script>

<style lang="scss">
    @import '~blueimp-gallery/css/blueimp-gallery.min.css';

    .blueimp-gallery {
        background-color : transparentize($color-black, 0.25);

        > .description {
            position : absolute;
            top      : 30px;
            left     : 15px;
            color    : #fff;
            display  : none;
        }

        > .close,
        > .title,
        > .description,
        > .play-pause {
            display : block;
        }

        > .slides > .slide > .video-content {
            display         : flex;
            justify-content : center;

            > video {
                position : static;
                width    : auto;
                height   : auto;
            }
        }

        .next,
        .prev {
            font-family : FontAwesome, sans-serif;
            border      : none;
            background  : none;
            display     : block;
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
            color           : #fff;
            text-shadow     : 0 0 2px #000;
            opacity         : .8;
            display         : block;

            &:hover {
                opacity : 1;
            }
        }
    }
</style>
