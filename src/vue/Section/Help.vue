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
                            <a :href="entry.href" v-html="entry.label"></a>
                        </li>
                    </ol>
                </nav>
                <section class="handbook-page" v-html="source"></section>
                <div class="handbook-sidebar">
                    <community-resources/>
                </div>
            </article>
            <nc-button class="handbook-exit" type="primary" :to="{path: '/'}" v-if="!isAuthorized">
                <template #icon>
                    <arrow-left :size="20"/>
                </template>
            </nc-button>
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
    import Application from "@js/Init/Application";
    import {emit} from "@nextcloud/event-bus";
    import CommunityResources from "@vc/Handbook/CommunityResources.vue";
    import UtilityService from "@js/Services/UtilityService";

    // noinspection JSUnusedGlobalSymbols
    export default {
        components: {
            CommunityResources,
            Web,
            Gallery,
            Translate,
            Breadcrumb,
            'arrow-left': () => import(/* webpackChunkName: "ArrowLeftIcon" */ '@icon/ArrowLeft'),
            'nc-button' : () => import(/* webpackChunkName: "NcButton" */ '@nc/NcButton.js')
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
                chatPage  : 'https://t.me/nc_passwords/1',
                forumPage : 'https://help.nextcloud.com/c/apps/passwords',
                issuesPage: 'https://github.com/marius-wieschollek/passwords/issues?q=is%3Aissue'
            };
        },

        created() {
            this.refreshView();
            document.addEventListener('scroll', this.setActiveSection);
            if(!this.isAuthorized) {
                emit('toggle-navigation', {open: false});
                document.body.classList.remove('pw-auth-visible');
                document.body.classList.add('pw-auth-passed');
            }
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
                    current = [];

                for(let i = 0; i < path.length; i++) {
                    current.push(path[i]);
                    items.push(
                        {
                            path : {name: 'Help', params: {page: current.join('/')}},
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
                let pos     = window.scrollY,
                    section = '';

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
                let scrollTarget = document.getElementById('app-content-vue');

                if(!this.$route.hash) {
                    UtilityService.scrollTo(0, 0, behavior, scrollTarget);
                    return;
                }

                let $el = document.querySelector(`#app-content ${this.$route.hash}`);
                if($el) {
                    let breadcrumb = document.querySelector('.breadcrumb.passwords-breadcrumbs'),
                        top        = $el.offsetTop - (breadcrumb ? breadcrumb.offsetHeight:0);

                    UtilityService.scrollTo(top, 0, behavior, scrollTarget);
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
    padding        : 0 0 10px;
    position       : relative;
    min-height     : 100%;
    display        : flex;
    flex-direction : column;

    > .passwords-breadcrumbs {
        flex-grow : 0 !important;
    }

    &.global {
        .handbook-exit {
            position : fixed;
            left     : 1rem;
            bottom   : 1rem;
            cursor   : pointer;

            svg {
                cursor : pointer;
            }
        }
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
        grid-template-areas   : ". header ." "nav page extra";
        grid-template-columns : 1fr 975px 1fr;
        grid-template-rows    : min-content auto;
        flex-grow             : 1;

        @media (max-width : $width-extra-large) {
            grid-template-areas   : ". header" "nav page" "nav extra";
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
            margin-right : 1rem;
            margin-left  : 1rem;
            height       : 1px;

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

        .handbook-sidebar {
            grid-area   : extra;
            display     : flex;
            align-items : end;
            margin      : 0 1rem;

            .handbook-community-resources {
                position : sticky;
                bottom   : 1rem;
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
            border-left : 4px solid var(--color-main-background-blur);
            background  : var(--color-background-dark);
            padding     : 1em 1em 0 1em;
            margin      : 0 0 1em 0;

            &.info,
            &.warning,
            &.important,
            &.recommended {
                border-color     : var(--color-main-background-blur);
                background-color : var(--color-primary-element);
                color            : var(--color-primary-text);
                border-radius    : 0 3px 3px 0;
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
}
</style>