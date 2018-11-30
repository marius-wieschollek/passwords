<template>
    <div class="tab-container">
        <ul class="tab-titles">
            <translate tag="li" v-for="(tab, name) in tabs" :key="name" class="tab-title" :class="{ active: isCurrent(name) }" @click="setCurrent(name)" :say="tab" :data-tab="name"/>
        </ul>
        <div class="tab-contents">
            <div class="tab-content active">
                <slot :name="tab"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';

    export default {
        components: {
            Translate
        },

        props: {
            tabs: {
                type: Object
            }
        },

        data() {
            return {
                tab: Object.keys(this.tabs)[0]
            }
        },

        methods: {
            isCurrent(tab) {
                return tab === this.tab
            },
            setCurrent(tab) {
                this.tab = tab;
            }
        }
    }
</script>

<style lang="scss">
    .tab-container {
        .tab-titles {
            .tab-title {
                float   : left;
                padding : 5px;
                cursor  : pointer;
                color   : $color-black-lighter;

                &:hover,
                &.active { border-bottom : 1px solid var(--color-primary); }

                &.active {
                    color       : var(--color-main-text);
                    font-weight : 600;
                }

                span { cursor : pointer; }
            }
        }

        .tab-contents {
            clear       : both;
            padding-top : 10px;

            .tab-content {
                display : none;

                &.active {
                    display : block;
                }
            }
        }
    }
</style>