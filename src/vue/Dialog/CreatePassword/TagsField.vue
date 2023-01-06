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
                   :taggable="true"
                   :closeOnSelect="false"
                   :options="options"
                   :loading="loading"
                   :placeholder="t('Add Tags...')"
                   v-model="model"
                   v-on:option:created="createTag($event)"
        >
            <template #selected-option-container="{ option, deselect, multiple, disabled }">
                <div class="vs__selected" :class="tagCssClass(option)" @click="deselect(option)" :style="{backgroundColor: option.color}">{{ option.label }}</div>
            </template>
        </nc-select>
    </div>
</template>

<script>
    import Translate from "@vc/Translate";
    import {NcSelect} from "@nextcloud/vue";
    import API from '@js/Helper/api';
    import Utility from "@js/Classes/Utility";
    import TagManager from "@js/Manager/TagManager";

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
            },
            createTag(data) {
                TagManager.createTagFromData(data)
                          .then((d) => {
                              this.tags[d.id] = d;
                              for(let i = 0; i < this.model.length; i++) {
                                  if(!this.model[i].hasOwnProperty('id') && this.model[i].label === d.label) {
                                      this.model.splice(i, 1, d);
                                  }
                              }
                          });
            },
            tagCssClass(option) {
                if(!option.color) {
                    return 'is-dark';
                }
                let rgb  = parseInt(option.color.substring(1), 16),
                    r    = (rgb >> 16) & 0xff,
                    g    = (rgb >> 8) & 0xff,
                    b    = (rgb >> 0) & 0xff,
                    luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709

                console.log(option.label, luma);
                return luma < 96 ? 'is-dark':'is-bright';
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
                height      : 28px;
                min-height  : 28px;
                max-height  : 28px;
                margin      : 2px 2px 0;
                white-space : nowrap;

                &.is-bright {
                    color : #000
                }

                &.is-dark {
                    color : #fff
                }
            }
        }
    }
}
</style>