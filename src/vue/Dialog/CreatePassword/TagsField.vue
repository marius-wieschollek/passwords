<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="password-form-field-wrapper password-form-tags-wrapper">
        <translate tag="label" for="password-tags" say="Tags" icon="tags" class="area-label"/>
        <nc-select id="password-tags" :no-wrap="true"
                   class="area-input"
                   :multiple="true"
                   :options="options"
                   :loading="loading"
                   :placeholder="t('Add Tags...')"
                   v-model="model"/>
    </div>
</template>

<script>
    import Translate from "@vc/Translate";
    import {NcSelect} from "@nextcloud/vue";
    import API from '@js/Helper/api';
    import Utility from "@js/Classes/Utility";

    export default {
        components: {Translate, NcSelect},
        props     : {
            value: {
                type   : [Array, Object],
                default: () => {
                    return [];
                }
            }
        },
        data() {
            return {
                options        : [],
                tags           : {},
                model          : [],
                loading        : true,
                fullTagsInValue: !Array.isArray(this.value)
            };
        },
        mounted() {
            this.loadTags();
        },
        methods: {
            loadTags() {
                API.listTags()
                   .then((d) => {
                       this.options = Utility.sortApiObjectArray(Utility.objectToArray(d), 'label');
                       this.tags = d;
                       this.loadModel();
                       this.loading = false;
                   });
            },
            loadModel() {
                let model = [],
                    value = this.value;

                if(this.fullTagsInValue) {
                    value = [];
                    for(let key in this.value) {
                        if(!this.value.hasOwnProperty(key)) continue;
                        value.push(this.value[key].id);
                    }
                }

                for(let id of value) {
                    if(this.tags.hasOwnProperty(id)) {
                        model.push(this.tags[id]);
                    }
                }

                if(JSON.stringify(this.model) !== JSON.stringify(model)) {
                    for(let i = 0; i < model.length; i++) {
                        if(!this.model[i]) {
                            this.model.push(model[i]);
                        } else {
                            this.model.splice(i, 1, model[i]);
                        }
                    }
                }
            }
        },

        watch: {
            value(value) {
                this.fullTagsInValue = !Array.isArray(value);
                this.loadModel();
            },
            model(value) {
                let model = [];
                for(let tag of value) {
                    model.push(this.fullTagsInValue ? tag:tag.id);
                }

                if(JSON.stringify(this.value) !== JSON.stringify(model)) {
                    this.$emit('input', model);
                }
            }
        }
    };
</script>

<style lang="scss">
.password-form-field-wrapper.password-form-tags-wrapper {
    .select#password-tags {
        border        : 2px solid var(--color-border-dark);
        margin        : 3px 3px 3px 0;
        height        : 36px;
        border-radius : var(--border-radius-large);
        cursor        : pointer;
        min-height    : 36px;
        padding       : 0;

        &:hover {
            border-color : var(--color-primary-element);
        }

        .vs__dropdown-toggle {
            padding : 0;
        }

        input.vs__search {
            border     : none;
            margin     : 0;
            padding    : 0 .5rem;
            height     : 32px;
            min-height : 32px;
            max-height : 32px;

            &.focus-visible {
                box-shadow : none;
            }
        }

        .vs__actions {
            padding-top : 0;

            button {
                min-height : 32px;
                max-height : 32px;
            }
        }

        .vs__selected-options {
            .vs__selected {
                height     : 28px;
                min-height : 28px;
                max-height : 28px;
                margin     : 2px 2px 0;

                .vs__deselect {
                    margin     : 0 -.5em 0 0;
                    padding    : 0;
                    border     : none;
                    background : none;
                    max-height : 28px;
                    min-height : 28px;
                }
            }
        }
    }
}
</style>