<template>
    <translate tag="li"
               class="session-timeout"
               say="You will be logged out in {time} seconds"
               :variables="{time}"
               v-if="showTimer"/>
</template>
<script>
    import SettingsService from '@js/Service/SettingsService';
    import KeepAliveManager from '@js/Manager/KeepAliveManager';
    import Translate from "@/vue/Components/Translate";

    export default {
        components: {Translate},
        data() {
            return {
                hasTimeout : KeepAliveManager.hasTimeout,
                lastRequest: KeepAliveManager.lastRequest,
                lifeTime   : SettingsService.get('user.session.lifetime') * 1000,
                timer      : null,
                time       : 100
            }
        },
        created() {
            if(this.hasTimeout) this.startInterval();
            KeepAliveManager.events.on('keepalive.updated', (e) => {
                this.hasTimeout = e.hasTimeout;
            });
            KeepAliveManager.events.on('keepalive.activity', (e) => {
                this.lastRequest = e.time;
            });
            SettingsService.observe('user.session.lifetime', (s) => {
                this.lifeTime = s.value * 1000;
            })
        },
        computed  : {
            showTimer() {
                return this.hasTimeout && this.time <= 45;
            }
        },
        methods   : {
            startInterval() {
                this.timer = setInterval(() => {
                    let time = this.lastRequest + this.lifeTime - Date.now();

                    this.time = time > 0 ? Math.round(time / 1000):0;
                }, 1000);
            }
        },
        watch     : {
            hasTimeout(value) {
                clearInterval(this.timer);
                if(value) this.startInterval();
            }
        }
    }
</script>

<style lang="scss">
    #app-navigation li.session-timeout {

        &:before {
            content : '\f253';
        }

        background-color : var(--color-primary-element);
        opacity          : 1;
        color            : var(--color-primary-text);
        font-weight      : bold;
    }
</style>
