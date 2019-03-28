<template>
    <li>
        <p>
            <translate say="Passwords can be customized in a lot of different ways."/>
            <translate say="As administrator you might want to take a look at these common settings."/>
        </p>
        <div class="apdmin-settings-container">
            <translate tag="label" say="Check password security with"/>
            <select>
            </select>
            <translate tag="label" say="Generate passwords using"/>
            <select>
            </select>
            <translate tag="label" say="Get favicons from"/>
            <select>
            </select>
            <translate tag="label" say="Get website previews from"/>
            <select>
            </select>
            <translate tag="label" say="Make backups"/>
            <select>
            </select>
        </div>
        <translate tag="a"
                   icon="cog"
                   href="/index.php/settings/admin/passwords"
                   target="_blank"
                   class="cta-admin-settings"
                   say="View all settings"/>
        <translate tag="a"
                   href="/index.php/settings/admin/passwords"
                   target="_blank"
                   class="admin-notes"
                   say="Read our tips for admins"/>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';

    export default {
        components: {Translate},
        methods   : {}
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

        .apdmin-settings-container {
            display               : grid;
            grid-template-columns : 2fr 1fr;
            width                 : 60%;
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