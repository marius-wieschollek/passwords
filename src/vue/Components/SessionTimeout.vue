<template>
    <translate :tag="tag"
               :class="cssClass"
               say="You will be logged out in {time} seconds"
               :variables="{time}"
               v-if="showTimer">
        <timer-sand-icon slot="icon"/>
    </translate>
</template>
<script>
    import SettingsService from '@js/Services/SettingsService';
    import KeepAliveManager from '@js/Manager/KeepAliveManager';
    import Translate from "@/vue/Components/Translate";
    import API from '@js/Helper/api';
    import TimerSandIcon from "@icon/TimerSand.vue";
    import {subscribe} from "@nextcloud/event-bus";

    export default {
        components: {TimerSandIcon, Translate},
        props     : {
            scope: {
                type   : String,
                default: 'menu'
            }
        },
        data() {
            return {
                hasTimeout : KeepAliveManager.hasTimeout,
                lastRequest: KeepAliveManager.lastRequest,
                lifeTime   : SettingsService.get('user.session.lifetime') * 1000,
                timer      : null,
                time       : 100
            };
        },
        created() {
            if(this.hasTimeout) this.startInterval();
            subscribe('passwords:keepalive:updated', (e) => {
                this.hasTimeout = e.hasTimeout;
            });
            subscribe('passwords:keepalive:activity', (e) => {
                this.lastRequest = e.time;
            });
            SettingsService.observe('user.session.lifetime', (s) => {
                this.lifeTime = s.value * 1000;
            });
        },
        computed: {
            showTimer() {
                return this.hasTimeout && this.time <= 45 && API.isAuthorized;
            },
            tag() {
                return this.scope === 'global' ? 'div':'li';
            },
            cssClass() {
                return `session-timeout ${this.scope === 'global' ? 'session-timer-global':'app-navigation-entry--pinned'}`;
            }
        },
        methods : {
            startInterval() {
                this.timer = setInterval(() => {
                    let time = this.lastRequest + this.lifeTime - Date.now();

                    this.time = time > 0 ? Math.round(time / 1000):0;
                }, 1000);
            }
        },
        watch   : {
            hasTimeout(value) {
                clearInterval(this.timer);
                if(value) this.startInterval();
            }
        }
    };
</script>

<style lang="scss">
li.session-timeout,
div.session-timeout {
    background-color : var(--color-primary-element);
    opacity          : 1;
    color            : var(--color-primary-text);
    border-radius    : var(--border-radius-pill);
    padding          : 0 1rem;
    font-size        : .85rem;
    display          : flex;
    height           : 44px;
    align-items      : center;

    .material-design-icon {
        display      : inline-flex;
        margin-right : .5rem;
    }

    &.session-timer-global {
        position : fixed;
        bottom   : .5rem;
        left     : .5rem;
        right    : .5rem;
        z-index  : 1000;
    }
}
</style>
