<template>
    <div class="row" :class="rowClass" @click="setUrl">
        <translate class="title" :icon="icon" :say="label" iconClass="genericIcon"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';

    export default {
        components: {
            Translate
        },

        props: {
            params: {
                type: Object
            },
            label : {
                type: String
            },
            icon  : {
                type: String
            }
        },

        data() {
            return {
                route   : this.$route.name,
                rowClass: this.$route.name.toLowerCase()
            }
        },

        methods: {
            setUrl() {
                if(this.params) this.$router.push({name: this.route, params: this.params});
            }
        }
    }
</script>

<style lang="scss">
    #app-content .item-list .row {
        .genericIcon {
            float       : none;
            font-size   : 1.75rem;
            line-height : 50px;
            width       : 50px;
            text-align  : center;
            color       : $color-black-lighter;
        }
    }
</style>