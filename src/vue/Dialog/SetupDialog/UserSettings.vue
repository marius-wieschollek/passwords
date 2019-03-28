<template>
    <li>
        <p>
            <translate say="There are many settings so you can customize your experience."/>
            <translate say="You can find all of them in &quot;More&quot;, but we put the most common here:"/>
        </p>
        <div class="app-settings-container">
            <translate tag="label" for="settings-section" say="After launch, show my"/>
            <select id="settings-section" v-model="section">
                <translate tag="option" value="folders" say="folders"/>
                <translate tag="option" value="tags" say="tags"/>
                <translate tag="option" value="favorites" say="favorites"/>
                <translate tag="option" value="all" say="passwords"/>
                <translate tag="option" value="recent" say="recently changed"/>
            </select>

            <translate tag="div" class="header" say="Mark my passwords if..."/>
            <div class="setting">
                <input type="checkbox" id="settings-has-age" v-model="hasAge"/>
                <translate tag="label" say="they are older than" for="settings-has-age"/>
                <select id="settings-age" v-model="maxAge" :disabled="!hasAge">
                    <translate tag="option" :value="90" say="three months"/>
                    <translate tag="option" :value="180" say="six months"/>
                    <translate tag="option" :value="356" say="one year"/>
                    <translate tag="option" :value="712" say="two years"/>
                    <translate tag="option"
                               :value="customAge"
                               say="{days} days"
                               :variables="{days: customAge}"
                               v-if="customAge"/>
                </select>
            </div>
            <div class="setting">
                <input type="checkbox" id="settings-duplicates" v-model="duplicates"/>
                <translate tag="label" say="they are duplicates" for="settings-duplicates"/>
            </div>

            <div class="header">
                <translate tag="label" say="Make my new passwords" for="settings-strength"/>
                <select id="settings-strength" v-model="strength">
                    <translate tag="option" :value="1" say="strong"/>
                    <translate tag="option" :value="2" say="stronger"/>
                    <translate tag="option" :value="3" say="very strong"/>
                    <translate tag="option" :value="4" say="extremely strong"/>
                </select>
            </div>
            <div class="setting">
                <input type="checkbox" id="settings-numbers" v-model="numbers"/>
                <translate tag="label" say="include numbers" for="settings-numbers"/>
            </div>
            <div class="setting">
                <input type="checkbox" id="settings-special" v-model="special"/>
                <translate tag="label" say="include special characters" for="settings-special"/>
            </div>
        </div>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {
        components: {Translate},

        data() {
            let section    = SettingsManager.get('client.ui.section.default'),
                maxAge     = SettingsManager.get('user.password.security.age'),
                numbers    = SettingsManager.get('user.password.generator.numbers'),
                special    = SettingsManager.get('user.password.generator.special'),
                strength   = SettingsManager.get('user.password.generator.strength'),
                duplicates = SettingsManager.get('user.password.security.duplicates'),
                hasAge     = maxAge > 0,
                customAge  = [0, 90, 180, 356, 712].indexOf(maxAge) === -1 ? maxAge:false;

            if (maxAge === 0) maxAge = 356;

            return {
                section,
                duplicates,
                customAge,
                strength,
                numbers,
                special,
                hasAge,
                maxAge
            }
        },

        methods: {},
        watch  : {
            hasAge(value) {
                SettingsManager.set('user.password.security.age', value ? this.maxAge:0);
            },
            maxAge(value) {
                SettingsManager.set('user.password.security.age', this.hasAge ? value:0);
            },
            duplicates(value) {
                SettingsManager.set('user.password.security.duplicates', value);
            },
            section(value) {
                SettingsManager.set('client.ui.section.default', value);
            },
            strength(value) {
                SettingsManager.set('user.password.generator.strength', value);
            },
            numbers(value) {
                SettingsManager.set('user.password.generator.numbers', value === true);
            },
            special(value) {
                SettingsManager.set('user.password.generator.special', value === true);
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-user-settings {
        p {
            position    : relative;
            padding     : 1.5rem;
            font-size   : 1.5rem;
            line-height : 2rem;

            &:before {
                display : none;
            }

            @media (max-width : 900px) {
                font-size: 1rem;
                line-height: 1.25rem;

                &:before {
                    display : none;
                }
            }
        }

        .app-settings-container {
            font-size : 1rem;
            width     : 50%;
            margin    : 1rem auto;

            label[for=settings-section] {
                line-height : 1.25rem;
                font-size   : 1.25rem;
            }

            .header {
                margin-top  : 1.5rem;
                line-height : 1.25rem;
                font-size   : 1.25rem;
            }

            .setting {
                margin-left : 1.5rem;
            }

            input {
                min-height : 0;
                cursor     : pointer;
            }

            select {
                margin-right : 0;
            }

            @media (max-width: $width-extra-small) {
                width     : auto;
                margin    : 0 1.5rem;
            }
        }
    }
</style>