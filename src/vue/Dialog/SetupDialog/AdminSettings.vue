<template>
    <li>
        <p>
            <translate say="Passwords can be customized in a lot of different ways."/>
            <translate say="As administrator you might want to take a look at these common settings."/>
        </p>
        <div class="admin-settings-container">
            <translate tag="label" say="Check password security with"/>
            <select v-model="settings.security.value">
                <translate tag="option"
                           :value="setting.value"
                           :say="setting.label"
                           :disabled="!setting.available"
                           v-for="(setting,key) in settings.security.options"
                           :key="key"/>
            </select>
            <translate tag="label" say="Generate passwords using"/>
            <select v-model="settings.words.value">
                <translate tag="option"
                           :value="setting.value"
                           :say="setting.label"
                           :disabled="!setting.available"
                           v-for="(setting,key) in settings.words.options"
                           :key="key"/>
            </select>
            <translate tag="label" say="Get favicons from"/>
            <select v-model="settings.favicon.value">
                <translate tag="option"
                           :value="setting.value"
                           :say="setting.label"
                           :disabled="!setting.available"
                           v-for="(setting,key) in settings.favicon.options"
                           :key="key"/>
            </select>
            <translate tag="label" say="Get website previews from"/>
            <select v-model="settings.preview.value">
                <translate tag="option"
                           :value="setting.value"
                           :say="setting.label"
                           :disabled="!setting.available"
                           v-for="(setting,key) in settings.preview.options"
                           :key="key"/>
            </select>
            <translate tag="label" say="Make backups"/>
            <select v-model="settings.backups.value">
                <translate tag="option"
                           :value="setting.value"
                           :say="setting.label"
                           :disabled="!setting.available"
                           v-for="(setting,key) in settings.backups.options"
                           :key="key"/>
            </select>
        </div>
        <translate tag="a"
                   icon="cog"
                   :href="getAppSettingsLink"
                   target="_blank"
                   class="cta-admin-settings"
                   say="View all settings"/>
        <translate tag="a"
                   href="https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/F.A.Q"
                   target="_blank"
                   class="admin-notes"
                   say="Read our tips for admins"/>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import UtilityService from "@js/Services/UtilityService";
    import AppSettingsService from '@js/Services/AppSettingsService';

    export default {
        components: {Translate},
        data() {
            let api = new AppSettingsService();

            return {
                api,
                settings   : {
                    security: {
                        value  : null,
                        options: {}
                    },
                    words   : {
                        value  : null,
                        options: {}
                    },
                    favicon : {
                        value  : null,
                        options: {}
                    },
                    preview : {
                        value  : null,
                        options: {}
                    },
                    backups : {
                        value  : null,
                        options: {}
                    }
                },
                oldSettings: {}
            }
        },
        created() {
            this.api.get('service.security').then((d) => {
                this.settings.security = d;
            });
            this.api.get('service.words').then((d) => {
                this.settings.words = d;
            });
            this.api.get('service.favicon').then((d) => {
                this.settings.favicon = d;
            });
            this.api.get('service.preview').then((d) => {
                this.settings.preview = d;
            });
            this.api.get('backup.interval').then((d) => {
                this.settings.backups = d;
            });
        },
        computed   : {
            getAppSettingsLink() {
                return UtilityService.generateUrl('/settings/admin/passwords')
            }
        },
        watch     : {
            settings: {
                handler(settings) {

                    for(let key in settings) {
                        if(!settings.hasOwnProperty(key) || !this.oldSettings.hasOwnProperty(key)) continue;

                        if(settings[key].value !== this.oldSettings[key].value && this.oldSettings[key].value !== null) {
                            if(settings[key].value !== settings[key].default) {
                                this.api.set(settings[key].name, settings[key].value);
                            } else {
                                this.api.reset(settings[key].name);
                            }
                        }
                    }

                    this.oldSettings = JSON.parse(JSON.stringify(settings))
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-admin-settings {
        position : relative;

        p {
            position : relative;
            padding  : 1.5rem;

            &:before {
                display : none;
            }

            @media (max-width : 900px) {
                &:before {
                    display : none;
                }
            }
        }

        .admin-settings-container {
            display               : grid;
            grid-template-columns : 2fr 1fr;
            width                 : 75%;
            margin                : 1rem auto;
            grid-row-gap          : .5rem;

            label {
                line-height : 40px;
            }

            select {
                margin-right : 0;
            }

            @media (max-width : $width-extra-small) {
                width  : auto;
                margin : 0 1rem;

                label {
                    font-size : 1rem;
                }
            }
        }

        a.cta-admin-settings {
            background-color : var(--color-primary-element);
            color            : var(--color-primary-text);
            padding          : 0.75rem 1rem;
            border-radius    : var(--border-radius);
            width            : 60%;
            margin           : 0.5rem auto 0;
            display          : block;

            i {
                margin-right : 0.4rem;
            }

            @media (max-width : $width-extra-small) {
                width  : auto;
                margin : 1rem;
            }
        }

        .admin-notes {
            font-size : .8rem;
            color     : var(--color-text-lighter);
            position  : absolute;
            left      : 1rem;
            bottom    : 1rem;
        }
    }
</style>