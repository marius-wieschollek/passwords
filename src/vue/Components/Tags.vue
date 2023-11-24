<template>
    <nc-select class="passwords-tags-field"
               :class="{'no-wrap': noWrap}"
               :no-wrap="noWrap"
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
            <div class="vs__selected"
                 :class="tagCssClass(option)"
                 @click.prevent.stop="deselect(option)"
                 @mousedown.prevent.stop
                 :style="{backgroundColor: option.color}">
                {{ option.label }}
            </div>
        </template>
        <template #option="{label, color}">
            <div class="passwords-tag-option">
                <tag-icon :fill-color="color"/>
                {{ label }}
            </div>
        </template>
    </nc-select>
</template>

<script>
    import Translate from '@vc/Translate';
    import NcSelect from '@nc/NcSelect.js';
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import TagManager from '@js/Manager/TagManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import TagIcon from '@icon/Tag';

    export default {
        components: {TagIcon, Translate, NcSelect},
        props     : {
            noWrap: {
                type   : Boolean,
                default: false
            },
            value : {
                type   : [Array, Object],
                default: () => {
                    return [];
                }
            }
        },
        data() {
            return {
                tags   : {},
                model  : [],
                loading: true,
                timeout: null
            };
        },
        computed: {
            tagIdsInValue() {
                let tagIds;
                if(this.isPassword) {
                    if(!this.value.hasOwnProperty('tags')) {
                        return [];
                    }
                    tagIds = Array.isArray(this.value.tags) ? this.value.tags:Object.keys(this.value.tags);
                } else {
                    tagIds = Object.keys(this.value);
                }

                return tagIds;
            },
            isPassword() {
                return this.value.hasOwnProperty('type') && this.value.type === 'password';
            },
            options() {
                let options = [],
                    usedIds = Utility.arrayPluck(this.model, 'id');

                for(let id in this.tags) {
                    if(!this.tags.hasOwnProperty(id) || usedIds.indexOf(id) !== -1) {
                        continue;
                    }

                    options.push(this.tags[id]);
                }

                return Utility.sortApiObjectArray(options, 'label');
            }
        },
        mounted() {
            this.loadTags();
        },
        methods: {
            loadTags() {
                API.listTags()
                   .then((d) => {
                       this.tags = d;
                       this.loading = false;
                       this.$nextTick(() => {
                           this.handleValueUpdate();
                       });
                   });
            },
            handleValueUpdate() {
                if(this.loading) {
                    return;
                }

                let model = [];
                for(let id of this.tagIdsInValue) {
                    model.push(this.tags[id]);
                }

                if(JSON.stringify(model) !== JSON.stringify(this.model)) {
                    this.model.splice(0, this.model.length, ...model);
                }
            },
            createTag(data) {
                if(typeof data === 'string') {
                    data = {label: data};
                }

                TagManager.createTagFromData(data)
                          .then((d) => {
                              this.tags[d.id] = d;
                              for(let i = 0; i < this.model.length; i++) {
                                  if((!this.model[i].hasOwnProperty('id') && this.model[i].label === d.label) || this.model[i] === d.label) {
                                      this.model.splice(i, 1, d);
                                  }
                              }
                          });
            },
            tagCssClass(option) {
                if(!option.color) {
                    return 'is-dark';
                }

                return Utility.getColorLuma(option.color) < 96 ? 'is-dark':'is-bright';
            },
            handleModelUpdate(value) {
                if(this.loading) return;
                let modelIds = Utility.arrayPluck(value, 'id');

                if(JSON.stringify(this.tagIdsInValue) !== JSON.stringify(modelIds)) {
                    let model = {};
                    for(let id of modelIds) {
                        model[id] = this.tags[id];
                    }

                    if(!this.isPassword) {
                        this.$emit('input', model);
                    } else {
                        this.value.tags = model;
                        this.updatePassword();
                    }
                }
            },
            updatePassword() {
                if(this.timeout !== null) {
                    clearTimeout(this.timeout);
                }

                this.timeout = setTimeout(() => {
                    let data = Utility.cloneObject(this.value);
                    if(Object.keys(data.tags).length === 0) {
                        data.tags = [''];
                    }

                    PasswordManager
                        .updatePassword(data)
                        .finally(() => {
                            this.timeout = null;
                        });
                }, 1000);
            }
        },

        watch: {
            value: {
                deep: true,
                handler() {
                    this.handleValueUpdate();
                }
            },
            model(value) {
                this.handleModelUpdate(value);
            }
        }
    };
</script>

<style lang="scss">
div.passwords-tags-field.select {
    margin        : 3px 3px 3px 0;
    cursor        : pointer;
    min-height    : 36px;
    padding       : 0;
    width         : 100%;

    .no-wrap {
        height : 36px;
    }

    .vs__dropdown-toggle {
        padding          : 0;
        background-color : var(--color-main-background);
    }

    input.vs__search {
        border     : none !important;
        margin     : 0 !important;
        padding    : 0 .5rem !important;
        height     : 32px !important;
        min-height : 32px;
        max-height : 32px;

        &.focus-visible {
            box-shadow : none !important;
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
        min-height : 0 !important;

        .vs__selected {
            height      : 28px;
            min-height  : 28px;
            max-height  : 28px;
            margin      : 2px 2px 0;
            white-space : nowrap;
            border      : none;

            &.is-bright {
                color : #000
            }

            &.is-dark {
                color : #fff
            }
        }
    }
}

.vs__dropdown-menu {
    background-color : var(--color-main-background);

    .vs__dropdown-option {
        .passwords-tag-option {
            display : flex;
            color   : var(--color-main-text);
            padding : .5rem 0;

            .tag-icon {
                margin-right : .5rem;
            }
        }

        &:hover,
        &.vs__dropdown-option--highlight {
            background-color : var(--color-background-hover);
        }
    }
}
</style>