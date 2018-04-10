<template>
    <div slot="details" class="details">
        <translate tag="div" say="Name"><span>{{ password.label }}</span></translate>
        <translate tag="div" say="Username"><span>{{ password.username }}</span></translate>
        <translate tag="div" say="Password">
            <span @mouseover="showPassword=true" @mouseout="showPassword=false" class="password">{{ togglePassword }}</span>
        </translate>
        <translate tag="div" say="Website"><a :href="password.url" target="_blank" :style="linkStyle">{{ password.url }}</a></translate>

        <translate tag="div" say="Statistics" class="header"/>
        <translate tag="div" say="Created on"><span>{{ getDateTime(password.created) }}</span></translate>
        <translate tag="div" say="Last updated"><span>{{ getDateTime(password.edited) }}</span></translate>
        <translate tag="div" say="Revisions">
            <translate say="{count} revisions" :variables="{count:countRevisions}"/>
        </translate>
        <translate tag="div" say="Shares">
            <translate say="{count} shares" :variables="{count:countShares}"/>
        </translate>

        <translate tag="div" say="Security" class="header"/>
        <translate tag="div" say="Status">
            <translate :say="getSecurityStatus" :class="getSecurityStatus.toLowerCase()"/>
        </translate>
        <translate tag="div" say="SHA1 Hash"><span>{{ password.hash }}</span></translate>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import Localisation from '@js/Classes/Localisation';
    import ThemeManager from '@js/Manager/ThemeManager';

    export default {
        components: {
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                showPassword: false
            };
        },

        computed: {
            countShares() {
                let count = 0;
                for(let i in this.password.shares) {
                    if(this.password.shares.hasOwnProperty(i)) count++;
                }
                return count;
            },
            countRevisions() {
                let count = 0;
                for(let i in this.password.revisions) {
                    if(this.password.revisions.hasOwnProperty(i)) count++;
                }
                return count;
            },
            togglePassword() {
                return this.showPassword ? this.password.password:''.padStart(this.password.password.length, '*');
            },
            getSecurityStatus() {
                let status = ['Secure', 'Weak', 'Broken'];

                return status[this.password.status];
            },
            linkStyle() {
                return {
                    color: ThemeManager.getColor()
                };
            }
        },
        methods : {
            getDateTime(date) {
                return Localisation.formatDateTime(date);
            }
        }
    };
</script>

<style lang="scss">
    .item-details .details {
        padding-top : 10px;

        div:not(.header) {
            font-size     : 0.9em;
            font-style    : italic;
            margin-bottom : 5px;
            color         : $color-grey-darker;

            a,
            span {
                display    : block;
                font-style : normal;
                font-size  : 1.3em;
                color      : $color-black-light;
                text-align : right;
                cursor     : text;

                &.password {
                    cursor : pointer;

                    &:hover {
                        font-family : 'Lucida Console', 'Lucida Sans Typewriter', 'DejaVu Sans Mono', monospace;
                    }
                }

                &.secure {color : $color-green;}
                &.weak {color : $color-yellow;}
                &.broken {color : $color-red;}
            }

            a {
                cursor : pointer;

                &:hover {
                    text-decoration : underline;
                }
            }
        }

        .header {
            margin-top  : 20px;
            font-size   : 1.3em;
            font-weight : bold;
            color       : $color-black-light;
        }
    }
</style>