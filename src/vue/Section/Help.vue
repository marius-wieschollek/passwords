<template>
    <div id="app-content" :class="{loading: loading}">
        <div class="app-content-left help">
            <breadcrumb :items="getBreadcrumbIcons" :showAddNew="false"/>
            <translate tag="h1" :say="getPageTitle" id="top"/>
            <div class="handbook-page" v-html="pageHtml"></div>
        </div>
    </div>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import Localisation from '@js/Classes/Localisation';
    import Translate from "@/vue/Components/Translate";

    export default {
        components: {
            Translate,
            Breadcrumb
        },

        data() {
            return {
                loading : true,
                pages   : [],
                pageHtml: ''
            };
        },

        created() {
            this.refreshView();
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
                    current += `/${path[i]}`;
                    items.push(
                        {path: {name: 'Help', params: {page: current}}, label: path[i]}
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
                    this.showPage('Home');
                } else {
                    this.showPage(this.$route.params.page);
                }
            },
            showPage(page) {
                if(!process.env.NIGHTLY_FEATURES) return;

                if(this.pages.hasOwnProperty(page)) {
                    this.pageHtml = this.pages[page];
                    return;
                }

                this.loading = true;
                this.pageHtml = '';
                // Unsafe, needs to be done better
                $.get(`https://raw.githubusercontent.com/wiki/marius-wieschollek/passwords/Users/${page}.md`)
                 .success((d) => {this.renderPage(d, page);});
            },
            async renderPage(markdown, page) {
                try {
                    let marked   = await import(/* webpackChunkName: "marked" */ 'marked'),
                        renderer = new marked.Renderer();

                    renderer.link = this.renderLink;
                    this.pages[page] = marked(markdown, {renderer: renderer});
                    this.pageHtml = this.pages[page];
                    this.loading = false;
                } catch(e) {
                    console.error(e);
                    Messages.alert(['Unable to load {module}', {module: 'Marked'}], 'Network error');
                }
            },
            renderLink(href, title, text) {
                let target = '_blank';
                console.log(href, title, text);
                console.log(this.$route);

                if(href.substring(0, 5) === 'Users') {
                    let route = this.$router.resolve({name: 'Help', params: {page: href.substring(6)}});
                    href = route.href;
                    target = '_self';
                } else if(href[0] === '#') {
                    let route = this.$router.resolve({name: 'Help', params: {page: this.$route.params.page}, hash: href});
                    href = route.href;
                    target = '_self';
                }

                return `<a href="${href}" title="${title}" target="${target}">${text}</a>`;
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
        padding : 0 10px 10px;

        #controls {
            margin : 0 -10px;
        }

        > h1 {
            font-size   : 2rem;
            font-weight : 300;
            margin      : 10px 0 20px;
        }

        .handbook-page {
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

            h1 {
                font-size   : 1.75rem;
                font-weight : 500;
                margin      : 1.75rem 0 0.5rem;
            }

            h2 {
                font-size   : 1.5rem;
                font-weight : 500;
                margin      : 1.5rem 0 0.25rem;
            }

            h3 {
                font-size   : 1.25rem;
                font-weight : 500;
                margin      : 1.25rem 0 0.25rem;
            }

            h4 {
                font-size   : 1rem;
                font-weight : 600;
                margin      : 1rem 0 0.15rem;
            }

            h5 {
                font-size   : 0.85rem;
                font-weight : 600;
                margin      : 0.85rem 0 0;
            }

            p {
                padding-bottom : 1em;
            }

            ol,
            ul {
                padding-left    : 1em;
                list-style-type : initial;
            }

            em {
                font-style: italic;
            }

            code {
                background    : $color-grey-lighter;
                color         : $color-black-lighter;
                padding       : 2px 3px;
                border        : 1px solid $color-grey-light;
                border-radius : 3px;
            }

            pre {
                background    : $color-grey-lighter;
                color         : $color-black-lighter;
                padding       : 2px 3px;
                border        : 1px solid $color-grey-light;
                border-radius : 3px;

                code {
                    background    : inherit;
                    color         : inherit;
                    padding       : 0;
                    border        : none;
                    border-radius : 0;
                }
            }

            table {
                border-collapse : collapse;
                padding-bottom : 1em;

                tr {
                    th {
                        background-color : $color-grey-lighter;
                    }
                    th,
                    td {
                        border           : 1px solid $color-grey-light;
                        padding          : 2px;
                    }
                }
            }

            hr {
                border: none;
                height: 1px;
                background: $color-black-lighter;
                margin: 1rem 0;
            }
        }
    }
</style>