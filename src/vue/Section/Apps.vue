<template>
    <div id="app-content">
        <div class="app-content-left apps">
            <breadcrumb :show-add-new="false"></breadcrumb>

            <div class="app-overview">
                <translate say="Browsers" tag="h1" icon="globe"/>
                <translate say="Android" tag="h1" icon="android"/>
                <div class="app-list">
                    <a class="app"
                       target="_blank"
                       rel="noreferrer noopener"
                       v-for="(app, id) in getBrowserExtensions"
                       :class="id"
                       :href="app.download">
                        <translate :say="app.label" tag="h3"/>
                        <web target="_blank"
                             class="author"
                             :class="{'fa fa-certificate':app.official}"
                             :href="app.sources"
                             :text="app.author"/>
                        <translate :say="app.description" tag="div" class="description"/>
                        <div class="passlink-container" v-if="passlink">
                            <translate say="Connect with PassLink"
                                       tag="button"
                                       class="primary passlink-connect"
                                       @click.prevent="startPasslink(app.extPassLink)"/>
                        </div>
                    </a>
                </div>

                <div class="app-list">
                    <a class="app"
                       target="_blank"
                       rel="noreferrer noopener"
                       v-for="(app, id) in getAndroidApps"
                       :class="[id, app.legacy ? 'legacy':'']"
                       :href="app.download">
                        <translate :say="app.label" tag="h3"/>
                        <web target="_blank" class="author" :href="app.web" :text="app.author"/>
                        <span class="dot">⦁</span>
                        <web target="_blank" class="author" :href="app.sources" text="sources"/>
                        <translate :say="app.description" tag="div" class="description"/>
                        <translate say="This app uses an api which is no longer supported."
                                   tag="div"
                                   class="legacy"
                                   v-if="app.legacy"/>
                    </a>
                </div>

                <translate say="Libraries" tag="h1" icon="microchip"/>
                <div class="library-list">
                    <a target="_blank"
                       rel="noreferrer noopener"
                       v-for="(library, id) in getLibraries"
                       :class="[id, 'library']"
                       :href="library.download">
                        <div>
                            <translate :say="library.label" tag="b"/>
                            <web target="_blank" class="author" :class="{'fa fa-certificate':library.official}" :href="library.web" :text="library.author"/>
                            <span class="dot" v-if="!library.official">⦁</span>
                            <web target="_blank" class="author" :href="library.sources" text="sources" v-if="!library.official"/>
                        </div>
                        <translate :say="library.description"/>
                    </a>
                </div>

                <translate say="Integrations" tag="h1" icon="puzzle-piece"/>
                <div class="integration-list">
                    <a target="_blank"
                       rel="noreferrer noopener"
                       v-for="(integration, id) in getIntegrations"
                       :class="[id, 'integration']"
                       :href="integration.download">
                        <div>
                            <translate :say="integration.label" tag="b"/>
                            <web target="_blank" class="author" :class="{'fa fa-certificate':integration.official}" :href="integration.web" :text="integration.author"/>
                            <span class="dot" v-if="!integration.official">⦁</span>
                            <web target="_blank" class="author" :href="integration.sources" text="sources" v-if="!integration.official"/>
                        </div>
                        <translate :say="integration.description"/>
                    </a>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import Web from '@vue/Components/Web';
import Connect from '@js/PassLink/Connect';
import Translate from '@vue/Components/Translate';
import Breadcrumb from '@vue/Components/Breadcrumb';
import Localisation from '@js/Classes/Localisation';
import DAS from '@js/Services/DeferredActivationService';

export default {
    components: {
        Web,
        Breadcrumb,
        Translate
    },

    data() {
        return {
            passlink: false
        };
    },

    mounted() {
        DAS.check('passlink-connect')
           .then((d) => { this.passlink = d; });
    },

    computed: {
        getBrowserExtensions() {
            return {
                'firefox': {
                    'label'      : 'Official Firefox Client',
                    'author'     : Localisation.translate('official'),
                    'description': 'Access and manage all your passwords easily within Firefox thanks to our official extension from the Firefox Add-on store.',
                    'download'   : 'https://addons.mozilla.org/firefox/addon/nextcloud-passwords?src=external-apps',
                    'sources'    : 'https://github.com/marius-wieschollek/passwords-webextension',
                    'extPassLink': true,
                    'official'   : true
                },
                'chrome' : {
                    'label'      : 'Official Chrome Client',
                    'author'     : Localisation.translate('official'),
                    'description': 'Our official Chrome extension lets you manage all your passwords from your browser and is available for many Chromium based Browsers from the Chrome Web Store.',
                    'download'   : 'https://chrome.google.com/webstore/detail/nextcloud-passwords/mhajlicjhgoofheldnmollgbgjheenbi',
                    'sources'    : 'https://github.com/marius-wieschollek/passwords-webextension',
                    'extPassLink': false,
                    'official'   : true
                }
            };
        },
        getAndroidApps() {
            return {
                'daper'  : {
                    'label'      : 'Nextcloud Passwords',
                    'author'     : Localisation.translate('created by {author}', {author: 'daper'}),
                    'description': 'Finally a modern, fast and lightweight app to access and manage your passwords from your Android device. Get it from Google Play.',
                    'download'   : 'https://play.google.com/store/apps/details?id=com.nextcloudpasswords',
                    'sources'    : 'https://github.com/daper/nextcloud-passwords-app',
                    'web'        : 'https://github.com/daper',
                    'legacy'     : false
                },
                'joleaf' : {
                    'label'      : 'NC Passwords',
                    'author'     : Localisation.translate('created by {author}', {author: 'joleaf'}),
                    'description': 'Android App for the Nextcloud Passwords add-on, which includes actions for viewing, editing and creating the passwords',
                    'download'   : 'https://play.google.com/store/apps/details?id=de.jbservices.nc_passwords_app',
                    'sources'    : 'https://gitlab.com/joleaf/nc-passwords-app',
                    'web'        : 'https://github.com/joleaf',
                    'legacy'     : false
                },
                'intirix': {
                    'label'      : 'Cloud Password Manager',
                    'author'     : Localisation.translate('created by {author}', {author: 'intirix'}),
                    'description': 'Cloud Password Manager is a password manager that puts you in control. Access all the passwords stored on your Nextcloud from your Android Phone.',
                    'download'   : 'https://play.google.com/store/apps/details?id=com.intirix.cloudpasswordmanager',
                    'sources'    : 'https://github.com/intirix/cloudpasswordmanager',
                    'web'        : 'https://github.com/intirix',
                    'legacy'     : true
                }
            };
        },
        getLibraries() {
            return {
                'npm'   : {
                    'label'      : 'NPM Package',
                    'author'     : Localisation.translate('official'),
                    'description': 'Official JavaScript client for the API',
                    'download'   : 'https://www.npmjs.com/package/passwords-client',
                    'web'        : 'https://git.mdns.eu/nextcloud/passwords-client',
                    'official'   : true
                },
                'traxys': {
                    'label'      : 'Rust Library',
                    'description': 'A Rust library to bind to the API (WIP)',
                    'author'     : Localisation.translate('created by {author}', {author: 'traxys'}),
                    'download'   : 'https://github.com/traxys/nextcloud-passwords-client/',
                    'sources'    : 'https://github.com/traxys/nextcloud-passwords-client/',
                    'web'        : 'https://github.com/traxys/',
                    'official'   : false
                }
            };
        },
        getIntegrations() {
            return {
                'markuman': {
                    'label'      : 'Ansible Lookup Plugin',
                    'description': 'Store secrets for your deployment in Nextcloud',
                    'author'     : Localisation.translate('created by {author}', {author: 'markuman'}),
                    'download'   : 'https://galaxy.ansible.com/markuman/nextcloud',
                    'sources'    : 'https://git.osuv.de/m/nextcloud_collection',
                    'web'        : 'https://galaxy.ansible.com/markuman/nextcloud',
                    'official'   : false
                }
            };
        }
    },

    methods: {
        startPasslink(showConnectLink) {
            Connect.initialize(showConnectLink);
        }
    }
};
</script>

<style lang="scss">
.app-content-left.apps {
    .app-overview {
        padding               : 0 1rem 1rem;
        display               : grid;
        grid-template         : "hBrowser hBrowser hAndroid hAndroid" "cBrowser cBrowser cAndroid cAndroid"  "hLibraries hIntegrations cAndroid cAndroid" "cLibraries cIntegrations cAndroid cAndroid" ". . cAndroid cAndroid";
        grid-template-columns : 1fr 1fr 1fr 1fr;
        grid-column-gap       : 1rem;
        grid-row-gap          : 1rem;

        @media(max-width : $width-extra-small) {
            grid-template : "hAndroid" "cAndroid" "hBrowser" "cBrowser" "hLibraries" "cLibraries" "hIntegrations" "cIntegrations";
        }
    }

    h1 {
        font-size   : 2rem;
        font-weight : bold;
        line-height : 2rem;
        margin      : 2rem 0 0.5rem;
        grid-area   : hBrowser;

        &:nth-child(2) {
            grid-area : hAndroid;
        }

        &:nth-child(5) {
            grid-area : hLibraries;
        }

        &:nth-child(7) {
            grid-area : hIntegrations;
        }
    }

    .app-list {
        display               : grid;
        grid-template-columns : 1fr 1fr;
        grid-column-gap       : 1rem;
        grid-row-gap          : 1rem;
        grid-area             : cBrowser;

        &:nth-child(4) {
            grid-area : cAndroid;
        }

        .app {
            display         : block;
            border-radius   : var(--border-radius);
            padding         : 200px 0.5rem 0.5rem;
            background      : url(../../img/browser/firefox.svg) no-repeat center 20px;
            background-size : 160px;
            border          : 1px solid #0000;
            box-sizing      : border-box;
            position        : relative;
            transition      : border-color 0.15s ease-in-out;

            h3 {
                font-size     : 1.25rem;
                font-weight   : bold;
                margin-bottom : 0;
            }

            .author {
                font-style : italic;

                &.fa.fa-certificate {
                    color       : var(--color-success) !important;
                    font-family : var(--font-face);

                    &:before {
                        font-family : var(--pw-icon-font-face);
                        font-style  : normal;
                    }
                }

                &:hover {
                    text-decoration : underline;
                }
            }

            .description {
                margin-top : 0.5rem;
            }

            .legacy {
                font-weight : bold;
            }

            .dot {
                color  : var(--color-primary);
                margin : 0 0.5rem;
            }

            .passlink-container {
                height : 3rem;

                .passlink-connect {
                    position      : absolute;
                    bottom        : .25rem;
                    width         : calc(100% - 1rem);
                    border-radius : var(--border-radius);
                }
            }

            &.chrome {
                background-image : url(../../img/browser/chrome.svg);
            }

            &.daper {
                background-image : url(../../img/apps/daper.png);
            }

            &.intirix {
                background-image : url(../../img/apps/intirix.png);
            }

            &.joleaf {
                background-image : url(../../img/apps/joleaf.png);
            }

            &.legacy {
                background-color : var(--color-loading-light);
                opacity          : 0.6;
                transition       : opacity 0.15s ease-in-out;

                &:hover {
                    opacity : 1;
                }
            }

            &:hover {
                border : 1px solid var(--color-primary);
            }
        }

        @media(max-width : $width-large) {
            grid-template-columns : 1fr;
        }
    }

    .library-list,
    .integration-list {
        grid-area : cLibraries;

        &.integration-list {
            grid-area : cIntegrations;
        }

        .library,
        .integration {
            border-radius : var(--border-radius);
            padding       : 0.5rem;
            border        : 1px solid #0000;
            display       : block;

            .label {
                font-weight : bold;
            }

            .author {
                font-style : italic;

                &.fa.fa-certificate {
                    color       : var(--color-success) !important;
                    font-family : var(--font-face);

                    &:before {
                        font-family : var(--pw-icon-font-face);
                        font-style  : normal;
                    }
                }

                &:hover {
                    text-decoration : underline;
                }
            }

            &:hover {
                border : 1px solid var(--color-primary);
            }
        }
    }
}

</style>