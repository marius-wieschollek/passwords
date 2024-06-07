<template>
    <div class="import-guide-container">
        <translate tag="a"
                   :href="guideLink"
                   class="link"
                   target="_blank"
                   say="Read the {label} import guide"
                   :variables="guideVars"
                   v-if="hasGuide"/>
        <translate say="or" v-if="hasGuide && csvVisible"/>
        <translate class="csv-guide" tag="button" @click="openCsvHelp" :say="csvLabel" v-if="csvVisible"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import UtilityService from "@js/Services/UtilityService";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {Translate},

        props: ['current'],

        data() {
            return {
                guides: {
                    pmanCsv : {
                        label: 'Passman',
                        link : '#/help/Import%2FImport-from-Passman'
                    },
                    pmanJson: {
                        label: 'Passman',
                        link : '#/help/Import%2FImport-from-Passman'
                    },
                    enpass  : {
                        label: 'Enpass',
                        link : '#/help/Import%2FImport-from-Enpass'
                    },
                    json    : {
                        label: LocalisationService.translate('database backup'),
                        link : '#/help/Import%2FImport-from-Backup'
                    },
                    chrome  : {
                        label: 'Chrome',
                        link : '#/help/Import%2FImport-from-Chrome'
                    },
                    firefox  : {
                        label: 'Firefox',
                        link : '#/help/Import%2FImport-from-Firefox'
                    },
                    csv     : {
                        label: LocalisationService.translate('custom CSV'),
                        link : '#/help/Import%2FImport-from-custom-CSV'
                    }
                }
            };
        },

        computed: {
            csvVisible() {
                return this.current !== 'csv';
            },
            csvLabel() {
                return this.hasGuide ? 'try the generic CSV import':'No import for your CSV?';
            },
            guideVars() {
                return {label: this.guides[this.current].label};
            },
            guideLink() {
                return this.guides[this.current].link;
            },
            hasGuide() {
                return this.guides.hasOwnProperty(this.current);
            }
        },

        methods: {
            openCsvHelp() {
                UtilityService.openLink(this.guides.csv.link);
                if(!this.csvVisible) this.$emit('trigger');
            }
        }
    };
</script>

<style lang="scss">
    .import-container .import-guide-container {
        display     : inline-block;
        margin-left : 1rem;

        .csv-guide {
            border-radius : var(--border-radius);
        }
    }
</style>