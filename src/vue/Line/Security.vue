<template>
    <div class="row security" :data-security-level="status" @click="setSecurityStatus">
        <translate class="title" :say="label">
            <shield-half-full-icon :fill-color="fillColor" class="icon" slot="icon"/>
        </translate>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";

    export default {
        components: {
            ShieldHalfFullIcon,
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
            fillColor() {
                switch(this.status) {
                    case 0:
                        return 'var(--color-success)';
                    case 1:
                        return 'var(--color-warning)';
                    case 2:
                        return 'var(--color-error)';
                }
            }
        },

        methods: {
            setSecurityStatus() {
                this.$router.push({name: 'Security', params: {status: this.status.toString()}});
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