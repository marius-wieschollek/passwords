<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div id="app-content" :class="{loading: loading}">
        <div class="app-content-left help" :class="{global: !isAuthorized}">
            <breadcrumb :items="getBreadcrumbIcons" :showAddNew="false"/>
            <article v-if="!loading">
                <header>
                    <translate tag="h1" :say="getPageTitle" id="help-top"/>
                </header>
                <nav v-if="showNavigation">
                    <translate tag="h2" say="Contents"/>
                    <ol class="help-navigation">
                        <li v-for="entry in navigation" :class="{active:section===entry.id}">
                            <a :href="entry.href">{{ entry.label }}</a>
                        </li>
                    </ol>
                </nav>
                <section class="handbook-page" v-html="source"></section>
                <footer class="handbook-footer">
                    <translate say="Still need help?"/>
                    <web text="Ask in our forum!" :href="forumPage"/>
                    <web text="Or in our Chat!" :href="chatPage"/>
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
    import Utility from '@js/Classes/Utility';
    import Localisation from '@js/Classes/Localisation';
    import HandbookRenderer from '@js/Helper/HandbookRenderer';
    import Application from "@js/Init/Application";

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
                navigation: [],
                media     : [],
                page      : '',
                section   : '',
                anchor    : '',
                gallery   : {images: [], index: null},
                chatPage  : 'https://t.me/nc_passwords',
                forumPage : 'https://help.nextcloud.com/c/apps/passwords',
                issuesPage: 'https://github.com/marius-wieschollek/passwords/issues?q=is%3Aissue'
            };
        },

        created() {
            this.refreshView();
            document.addEventListener('scroll', this.setActiveSection);
        },

        beforeDestroy() {
            document.removeEventListener('scroll', this.setActiveSection);
        },

        updated() {
            this.updateMediaElements();
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
                        {
                            path : {name: 'Help', params: {page: current}},
                            label: Localisation.translate(path[i].replace(/-{1}/g, ' '))
                        }
                    );
                }

                return items;
            },
            getPageTitle() {
                if(this.$route.params.page === undefined) return 'Handbook';
                let path  = this.page,
                    title = path.substr(path.lastIndexOf('/') + 1);

                return title.replace(/-{1}/g, ' ');
            },
            showNavigation() {
                return this.$route.params.page !== undefined && this.navigation.length > 0;
            },
            isAuthorized() {
                return Application.isAuthorized;
            }
        },

        methods: {
            refreshView() {
                if(this.$route.params.page === undefined) {
                    this.showPage('Index');
                } else if(this.$route.params.page !== this.page) {
                    this.showPage(this.$route.params.page);
                } else {
                    this.jumpToAnchor();
                }
            },
            setActiveSection() {
                let footer  = document.querySelector('footer.handbook-footer'),
                    pos     = window.scrollY,
                    section = '';

                if(footer && footer.offsetTop < window.innerHeight + pos) {
                    let item = this.navigation[this.navigation.length - 1];

                    this.section = item.id;
                    return;
                }

                for(let i = 0; i < this.navigation.length; i++) {
                    let item = this.navigation[i],
                        el   = document.getElementById(item.id);

                    if(el === null) continue;
                    if(el.offsetTop - el.offsetHeight >= pos) {
                        this.section = section;
                        return;
                    } else {
                        section = item.id;
                    }
                }

                this.section = section;
            },
            async showPage(page) {
                if(this.page === page) return;
                this.loading = true;
                let {source, media, navigation} = await HandbookRenderer.fetchPage(page);
                this.source = source;
                this.media = media;
                this.navigation = navigation;
                this.page = page;
                this.loading = false;
            },
            jumpToAnchor(behavior = 'smooth') {
                let scrollTarget = document.querySelector('#app-content .app-content-left');

                if(!this.$route.hash) {
                    Utility.scrollTo(0, 0, behavior, scrollTarget);
                    return;
                }

                let $el = document.querySelector(`#app-content ${this.$route.hash}`);
                if($el) {
                    let top = $el.offsetTop - document.getElementById('controls').offsetHeight;

                    Utility.scrollTo(top, 0, behavior, scrollTarget);
                    $el.classList.add('highlight');
                    $el.addEventListener('animationend', () => {$el.classList.remove('highlight');});
                    this.anchor = this.$route.hash;
                }

                document.querySelectorAll(`#app-content .highlight:not(${this.$route.hash})`).forEach((el) => {
                    el.classList.remove('highlight');
                });
            },
            updateMediaElements() {
                if(this.gallery.images.length === this.media.length) return;

                let gallery = [];
                for(let i = 0; i < this.media.length; i++) {
                    let image = this.media[i],
                        el    = document.getElementById(image.id);
                    if(!el) {
                        console.log(image);
                        continue;
                    }

                    gallery.push(
                        {
                            title : image.title,
                            href  : image.url,
                            type  : image.mime,
                            poster: image.thumbnail
                        }
                    );

                    el.querySelector('a')?.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.gallery.index = i;
                    });

                    el.querySelector('img')?.addEventListener('load', () => {
                        this.jumpToAnchor('auto');
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
    padding    : 0 0 10px;
    position   : relative;
    min-height : 100%;

    &.global {
        position : fixed;
        top      : var(--header-height);
        left     : 0;
        bottom   : 0;
        right    : 0;
    }

    #controls {
        margin : 0 -10px;
        width  : auto;
    }

    ol.help-navigation {
        li {
            list-style-type : decimal;
        }
    }

    article {
        display               : grid;
        grid-template-areas   : ". header ." "nav page ." ". footer .";
        grid-template-columns : 1fr 975px 1fr;

        @media (max-width : $width-extra-large) {
            grid-template-areas   : ". header" "nav page" ". footer";
            grid-template-columns : 1fr 800px;
            max-width             : 1048px;
            margin                : 0 auto;
            width                 : 100%;
        }

        @media (max-width : $width-large) {
            display   : block;
            max-width : 975px;
        }

        header {
            grid-area : header;
        }

        nav {
            grid-area    : nav;
            position     : sticky;
            top          : 110px;
            height       : 1px;
            margin-right : 1rem;

            ol {
                list-style  : decimal;
                margin-left : 1.5rem;
                line-height : 2rem;
                font-size   : 1rem;

                li.active {
                    font-weight : bold;
                }
            }

            @media (max-width : $width-large) {
                display : none;
            }
        }
    }

    header > h1 {
        font-size     : 2.5rem;
        font-weight   : 300;
        margin        : 10px auto 40px;
        line-height   : 1;
        overflow-wrap : break-word;
    }

    .handbook-page {
        grid-area : page;
        font-size : 0.9rem;
        width     : 100%;
        max-width : 975px;
        margin    : 0 auto 6rem;

        * {
            cursor         : initial;
            vertical-align : top;
        }

        a {
            cursor : pointer;
            color  : var(--color-primary);

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
            overflow-wrap : break-word;

            a.help-anchor {
                vertical-align : middle;
                margin         : 0 0 0 -1.1em;
                color          : transparent;
                transition     : color .15s ease-in-out;
                position       : absolute;
                opacity        : 0.4;

                @media(max-width : $width-large) {
                    position : static;
                    float    : right;
                }
            }

            &:hover a.help-anchor {
                text-decoration : none;
                color           : var(--color-main-text);
            }

            &.highlight {
                animation : Highlight 1s ease .5s 2;

                @keyframes Highlight {
                    0% {background-color : transparent}
                    50% {background-color : var(--color-success)}
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

            ol {
                list-style-type : upper-roman;

                ol {
                    list-style-type : lower-alpha;
                }
            }

            > li > p:only-child {
                margin-bottom : 0;
            }
        }

        ul {
            padding-left    : 1em;
            list-style-type : disc;

            li {
                list-style-type : inherit;
            }

            ul {
                list-style-type : circle;

                ul {
                    list-style-type : square;
                }
            }
        }

        em {
            font-style : italic;
        }

        code {
            background    : var(--color-background-dark);
            color         : var(--color-main-text);
            padding       : 1px 3px;
            border        : 1px solid var(--color-border-dark);
            border-radius : var(--border-radius);
            white-space   : nowrap;
            font-family   : var(--pw-mono-font-face);

            @media(max-width : $width-extra-small) {
                white-space : pre-wrap;
            }
        }

        pre {
            background    : var(--color-background-dark);
            color         : var(--color-main-text);
            padding       : 2px 3px;
            border        : 1px solid var(--color-border-dark);
            border-radius : var(--border-radius);
            overflow-x    : auto;
            font-family   : var(--pw-mono-font-face);

            code {
                background    : inherit;
                color         : inherit;
                padding       : 0;
                border        : none;
                border-radius : 0;
            }
        }

        blockquote {
            border-left : 4px solid var(--color-box-shadow);
            background  : var(--color-background-dark);
            padding     : 1em 1em 0 1em;
            margin      : 0 0 1em 0;

            &.info,
            &.warning,
            &.important,
            &.recommended {
                border-color     : var(--color-box-shadow);
                background-color : var(--color-primary-element);
                color            : var(--color-primary-text);
                border-radius    : 3px;
                padding-left     : .75rem;

                > p {
                    padding-left : 1.5rem;

                    &:first-of-type:before {
                        font-family  : var(--pw-icon-font-face);
                        content      : "\f05a";
                        margin-right : .5em;
                        margin-left  : -1.5rem;
                    }

                    a {
                        color       : var(--color-primary-text);
                        font-weight : bold;

                        &:hover,
                        &:focus,
                        &:active {
                            text-decoration : underline;
                        }
                    }
                }
            }

            &.important {
                background-color : var(--color-error);

                > p:first-of-type:before {
                    content : "\f071"
                }
            }

            &.warning {
                background-color : var(--color-warning);

                > p:first-of-type:before {
                    content : "\f06a"
                }
            }

            &.recommended {
                background-color : var(--color-success);

                > p:first-of-type:before {
                    content : "\f164"
                }
            }
        }

        table {
            border-collapse : collapse;
            padding-bottom  : 1em;
            white-space     : normal;

            tr {
                th {
                    background-color : var(--color-background-dark);
                }

                th,
                td {
                    border  : 1px solid var(--color-border-dark);
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
                border  : 1px solid var(--color-border);
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
                border-top : 1px solid var(--color-border);
                padding    : 2px;
                color      : var(--color-main-text);
                font-style : italic;
            }
        }
    }

    .handbook-footer {
        grid-area  : footer;
        position   : sticky;
        bottom     : 0;
        left       : 0;
        right      : 0;
        font-size  : 0.9rem;
        max-width  : 975px;
        margin     : 1em auto 0;
        text-align : right;
        width      : 100%;

        br {
            display : none;
        }

        a:hover,
        a:focus,
        a:active {
            cursor          : pointer;
            text-decoration : underline;
        }

        @media(max-width : $width-extra-small) {
            padding : 0 1em;

            br {
                display : block;
            }
        }
    }
}
</style>