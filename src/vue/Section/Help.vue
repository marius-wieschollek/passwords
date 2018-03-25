<template>
    <div id="app-content" :class="{loading: loading}">
        <div class="app-content-left help">
            <breadcrumb :items="getBreadcrumbIcons" :showAddNew="false"/>
            <article v-if="!loading">
                <header>
                    <translate tag="h1" :say="getPageTitle" id="help-top"/>
                </header>
                <section class="handbook-page" v-html="source"></section>
                <div class="handbook-footer">
                    <footer>
                        <translate say="Missing something or found an error?"/>
                        <translate tag="a" say="Tell us!" target="_blank" :style="getHrefStyle" href="https://github.com/marius-wieschollek/passwords/issues/new"/>
                    </footer>
                </div>
            </article>
        </div>
    </div>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Translate from '@vue/Components/Translate';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import Localisation from '@js/Classes/Localisation';
    import ThemeManager from '@js/Manager/ThemeManager';

    export default {
        components: {
            Translate,
            Breadcrumb
        },

        data() {
            return {
                loading: true,
                source : ''
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
                        {path: {name: 'Help', params: {page: current}}, label: Localisation.translate(path[i])}
                    );
                }

                return items;
            },
            getPageTitle() {
                if(this.$route.params.page === undefined) return 'Handbook';
                let path  = this.$route.params.page,
                    title = path.substr(path.lastIndexOf('/') + 1);

                return title.replace('-', ' ');
            },
            getHrefStyle() {
                return {
                    color: ThemeManager.getColor()
                };
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
                this.source = '';
                try {
                    let Handbook = await import(/* webpackChunkName: "HandbookRenderer" */ '@js/Helper/HandbookRenderer');
                    this.source = await Handbook.Renderer.fetchPage(page);
                    this.loading = false;
                } catch(e) {
                    console.error(e);
                    if(e.message === 'Not Found') {
                        Messages.alert(['The page "{page}" could not be fetched from the handbook server.', {page}], 'Network error');
                    } else {
                        Messages.alert(['Unable to load {module}', {module: 'ManualRenderer'}], 'Network error');
                    }
                    this.loading = false;
                }
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
            max-width   : 968px;
        }

        .handbook-page {
            font-size : 0.9rem;
            max-width : 968px;
            margin    : 0 auto 4rem;

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

            p > a:only-child > img,
            p > img:only-child {
                display : block;
                margin  : 1em auto;
            }

            img {
                max-width : 100%;
                border    : 1px solid $color-grey-lighter;
            }
        }

        .handbook-footer {
            position : absolute;
            bottom   : 0;
            left     : 0;
            right    : 0;

            footer {
                font-size  : 0.9rem;
                max-width  : 968px;
                margin     : 1em auto;
                text-align : right;

                a:hover,
                a:focus,
                a:active {
                    cursor          : pointer;
                    text-decoration : underline;
                }

                @media all and (max-width : $width-extra-small) {
                    padding : 0 1em;
                }
            }
        }
    }
</style>