<template>
    <div id="app-content" :class="{loading: loading}">
        <div class="app-content-left help">
            <breadcrumb :items="getBreadcrumbIcons" :showAddNew="false"/>
            <article v-if="!loading">
                <header>
                    <translate tag="h1" :say="getPageTitle" id="help-top"/>
                </header>
                <section class="handbook-page" v-html="source"></section>
                <footer class="handbook-footer">
                    <translate say="Still need help?"/>
                    <web text="Ask in our forum!" :href="forumPage"/>
                    <br> &nbsp;<translate say="Found an error?"/>
                    <web text="Tell us!" :href="issuesPage"/>
                </footer>
            </article>
            <gallery :images="gallery.images" :index="gallery.index" @close="gallery.index = null"/>
        </div>
    </div>
</template>

<script>
    import Web from '@vc/Web';
    import Gallery from '@vc/Gallery';
    import Translate from '@vc/Translate';
    import Breadcrumb from '@vc/Breadcrumb';
    import Localisation from '@js/Classes/Localisation';
    import HandbookRenderer from '@js/Helper/HandbookRenderer';

    // noinspection JSUnusedGlobalSymbols
    export default {
        components: {
            Web,
            Gallery,
            Translate,
            Breadcrumb
        },

        data() {
            return {
                loading   : true,
                source    : '',
                gallery   : {images: [], index: null},
                forumPage : 'https://help.nextcloud.com/c/apps/passwords',
                issuesPage: 'https://github.com/marius-wieschollek/passwords/issues?q=is%3Aissue'
            };
        },

        created() {
            this.refreshView();
        },

        updated() {
            let images = document.querySelectorAll('#app-content .handbook-page img');
            for(let i = 0; i < images.length; i++) {
                images[i].addEventListener('load', () => { this.jumpToAnchor(); });
            }
            this.jumpToAnchor();
            this.updateImageGallery();
        },

        computed: {
            getBreadcrumbIcons() {
                let items = [
                    {path: {name: 'Help'}, label: Localisation.translate('Handbook')}
                ];

                if(this.$route.params.page === undefined) return items;
                let path    = this.$route.params.page.split('/'),
                    current = '';

                for(let i = 0; i < path.length; i++) {
                    current += path[i];
                    items.push(
                        {path: {name: 'Help', params: {page: current}}, label: Localisation.translate(path[i].replace(/-{1}/g, ' '))}
                    );
                }

                return items;
            },
            getPageTitle() {
                if(this.$route.params.page === undefined) return 'Handbook';
                let path  = this.$route.params.page,
                    title = path.substr(path.lastIndexOf('/') + 1);

                return title.replace('-', ' ');
            }
        },

        methods: {
            refreshView() {
                if(this.$route.params.page === undefined) {
                    this.showPage('Index');
                } else {
                    this.showPage(this.$route.params.page);
                }
            },
            async showPage(page) {
                this.loading = true;
                this.source = await HandbookRenderer.fetchPage(page);
                this.loading = false;
            },
            jumpToAnchor() {
                let $el = document.querySelector(`#app-content ${this.$route.hash}`);
                if($el) {
                    document.getElementById('app-content').scrollTop = $el.offsetTop - document.getElementById('controls').offsetHeight;
                    $el.classList.add('highlight');
                    $el.addEventListener('animationend', () => {$el.classList.remove('highlight');});
                }
            },
            updateImageGallery() {
                let images  = document.querySelectorAll('.md-image-link'),
                    gallery = [];
                if(this.gallery.images.length === images.length) return;

                for(let i = 0; i < images.length; i++) {
                    let image = images[i],
                        mime  = image.href.substr(image.href.lastIndexOf('.') + 1);

                    if(['png', 'jpg', 'jpeg', 'gif'].indexOf(mime) !== -1) {
                        gallery.push(
                            {
                                title : image.title,
                                href  : image.href,
                                type  : `image/${mime}`,
                                poster: image.href
                            }
                        );
                    } else if(['mp4', 'm4v', 'ogg', 'webm'].indexOf(mime) !== -1) {
                        let poster = image.querySelector('img').src;
                        gallery.push(
                            {
                                title: image.title,
                                href : image.href,
                                type : `video/${mime}`,
                                poster
                            }
                        );
                    } else {
                        continue;
                    }

                    image.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.gallery.index = i;
                    });
                }

                this.gallery.images = gallery;
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>

<style lang="scss">

    #app-content .help {
        padding    : 0 10px 10px;
        position   : relative;
        min-height : 100%;

        #controls {
            margin : 0 -10px;
        }

        header > h1 {
            font-size   : 2.5rem;
            font-weight : 300;
            margin      : 10px auto 40px;
            max-width   : 975px;
            line-height : 1;
        }

        .handbook-page {
            font-size : 0.9rem;
            max-width : 975px;
            margin    : 0 auto 6rem;

            * {
                cursor         : text;
                vertical-align : top;
            }

            a {
                cursor : pointer;

                &:hover,
                &:focus,
                &:active {
                    text-decoration : underline;
                }

                * {
                    cursor : pointer;
                }
            }

            h1, h2, h3, h4, h5, h6 {
                font-weight   : 500;
                position      : relative;
                margin        : 0.85rem -3px 0;
                border-radius : 2px;

                a.help-anchor {
                    vertical-align : middle;
                    margin         : 0.25em 0 0 -1.1em;
                    color          : transparent;
                    transition     : color .15s ease-in-out;
                    position       : absolute;

                    @media all and (max-width : $width-large) {
                        position : static;
                        float    : right;
                    }
                }

                &:hover a.help-anchor {
                    text-decoration : none;
                    color           : $color-grey;
                }

                &.highlight {
                    animation : Highlight 1s ease .5s 2;

                    @keyframes Highlight {
                        0% {background-color : transparent}
                        50% {background-color : $color-green}
                        100% {background-color : transparent}
                    }
                }
            }

            h1 {
                font-size : 1.75rem;
                padding   : 1.6rem 3px .5rem;
            }

            h2 {
                font-size : 1.5rem;
                padding   : 1.35rem 3px .25rem;
            }

            h3 {
                font-size : 1.25rem;
                padding   : 1.1rem 3px .25rem;
            }

            h4 {
                font-size   : 1rem;
                font-weight : 600;
                padding     : .15rem 3px .15rem;
            }

            h5 {
                font-size   : 0.85rem;
                font-weight : 600;
                padding     : 0 3px;
            }

            p {
                padding-bottom : 1em;
            }

            ol {
                padding-left    : 1em;
                list-style-type : decimal;
            }

            ul {
                padding-left    : 1em;
                list-style-type : circle;
            }

            em {
                font-style : italic;
            }

            code {
                background    : $color-grey-lighter;
                color         : $color-black-lighter;
                padding       : 1px 3px;
                border        : 1px solid $color-grey-light;
                border-radius : 3px;
                white-space   : nowrap;
                font-family   : 'Lucida Console', 'Lucida Sans Typewriter', 'DejaVu Sans Mono', monospace;
            }

            pre {
                background    : $color-grey-lighter;
                color         : $color-black-lighter;
                padding       : 2px 3px;
                border        : 1px solid $color-grey-light;
                border-radius : 3px;
                overflow-x    : auto;
                font-family   : 'Lucida Console', 'Lucida Sans Typewriter', 'DejaVu Sans Mono', monospace;

                code {
                    background    : inherit;
                    color         : inherit;
                    padding       : 0;
                    border        : none;
                    border-radius : 0;
                }
            }

            blockquote {
                border-left   : 4px solid $color-grey;
                background    : $color-grey-lighter;
                padding       : 1em 1em 0 1em;
                margin-bottom : 1em;
            }

            table {
                border-collapse : collapse;
                padding-bottom  : 1em;

                tr {
                    th {
                        background-color : $color-grey-lighter;
                    }
                    th,
                    td {
                        border  : 1px solid $color-grey-light;
                        padding : 2px;
                    }
                }
            }

            hr {
                border     : none;
                height     : 1px;
                background : $color-black-lighter;
                margin     : 1rem 0;
            }

            p > .md-image-container:only-child {
                display       : block;
                margin-bottom : 0;
            }

            .md-image-container {
                max-width     : 100%;
                display       : inline-block;
                margin-bottom : 1em;

                .md-image-link {
                    border  : 1px solid $color-grey-lighter;
                    display : inline-block;

                    &:hover,
                    &:focus,
                    &:active {
                        text-decoration : none;
                    }
                }

                .md-image {
                    display   : block;
                    max-width : 100%;
                    margin    : 0 auto;
                }

                .md-image-caption {
                    display    : block;
                    border-top : 1px solid $color-grey-lighter;
                    padding    : 2px;
                    color      : $color-black-lighter;
                    font-style : italic;
                }
            }
        }

        .handbook-footer {
            position   : absolute;
            bottom     : 0;
            left       : 0;
            right      : 0;
            font-size  : 0.9rem;
            max-width  : 975px;
            margin     : 1em auto;
            text-align : right;

            br {
                display:none;
            }

            a:hover,
            a:focus,
            a:active {
                cursor          : pointer;
                text-decoration : underline;
            }

            @media all and (max-width : $width-extra-small) {
                padding : 0 1em;

                br {
                    display: block;
                }
            }
        }
    }
</style>