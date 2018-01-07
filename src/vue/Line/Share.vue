<template>
    <div class="row shared" @click="setSharedType">
        <translate class="title" icon="share-alt" :say="label" iconClass="shareIcon"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';

    export default {
        components: {
            Translate
        },

        props: {
            type : {
                type: Number
            },
            label: {
                type: String
            }
        },

        data() {
            return {}
        },

        methods: {
            setSharedType() {
                this.$router.push({name: 'Shared', params: {type: this.type}});
            }
        }
    }
</script>

<style lang="scss">

    #app-content .item-list .row.shared {
        .shareIcon {
            float       : none;
            font-size   : 1.75rem;
            line-height : 50px;
            width       : 50px;
            text-align  : center;
            color       : $color-black-lighter;
        }
    }

</style>