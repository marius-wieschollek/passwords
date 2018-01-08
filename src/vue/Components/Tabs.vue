<template>
    <div class="tab-container" :data-tab-uuid="uuid">
        <ul class="tab-titles">
            <translate tag="li"
                       v-for="(tab, name) in tabs"
                       :key="name"
                       :data-tab="name"
                       class="tab-title"
                       :class="{ active: isCurrentTab(name) }"
                       :style="tabStyle"
                       @click="setCurrentTab(name)">
                {{tab}}
            </translate>
        </ul>
        <div class="tab-contents">
            <div v-for="(tab, name) in tabs" :data-tab="name" class="tab-content" :class="{ active: isCurrentTab(name) }">
                <slot :name="name"></slot>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';

    export default {
        components: {
            Translate
        },

        props: {
            tabs: {
                type: Object
            },
            uuid: {
                type: String
            }
        },

        data() {
            return {
                tab: Object.keys(this.tabs)[0]
            }
        },

        computed: {
            tabStyle() {
                if(OCA.Theming) {
                    return {
                        'border-color': OCA.Theming.color
                    };
                }

                return {};
            }
        },

        methods: {
            isCurrentTab(tab) {
                return tab === this.tab
            },
            setCurrentTab(tab) {
                this.tab = tab;
            }
        },
        watch  : {
            uuid: function() {
                this.tab = Object.keys(this.tabs)[0];
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
                &.active { border-bottom : 1px solid $color-black-light; }

                &.active {
                    color       : $color-black;
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