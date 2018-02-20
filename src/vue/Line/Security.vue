<template>
    <div class="row security" :data-security-level="status" @click="setSecurityStatus">
        <translate class="title" icon="shield" :iconClass="securityCheck" :say="label"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';

    export default {
        components: {
            Translate
        },

        props: {
            status: {
                type: Number
            },
            label : {
                type: String
            }
        },

        computed: {
            securityCheck() {
                switch(this.status) {
                    case 0:
                        return 'security ok';
                    case 1:
                        return 'security warn';
                    case 2:
                        return 'security fail';
                }
            }
        },

        methods: {
            setSecurityStatus() {
                this.$router.push({name: 'Security', params: {status: this.status}});
            }
        }
    }
</script>

<style lang="scss">
    #app-content .item-list .row.security {
        .security {
            float     : none;
            font-size : 1.75rem;
        }
    }
</style>