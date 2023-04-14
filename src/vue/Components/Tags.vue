<template>
    <nc-select class="passwords-tags-field"
               :class="{'no-wrap': noWrap}"
               :no-wrap="noWrap"
               :multiple="true"
               :taggable="true"
               :closeOnSelect="false"
               :options="dropdownOptions"
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
    import NcSelect from '@nc/NcSelect';
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import TagManager from '@js/Manager/TagManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import TagIcon from '@icon/Tag';

    export default {
        components: {TagIcon, Translate, NcSelect},
        props     : {
            noWrap  : {
                type   : Boolean,
                default: false
            },
            value   : {
                type   : [Array, Object],
                default: () => {
                    return [];
                }
            },
            password: {
                type   : Object,
                default: () => {
                    return null;
                }
            }
        },
        data() {
            return {
                options        : [],
                tags           : {},
                model          : [],
                loading        : true,
                fullTagsInValue: this.password === null ? !Array.isArray(this.value):true,
                timeout        : null
            };
        },
        computed: {
            internalValue() {
                return this.password === null ? this.value:this.password.tags;
            },
            dropdownOptions() {
                let options = [],
                    usedIds = this.internalValue;

                if(this.fullTagsInValue) {
                    usedIds = [];
                    for(let key in this.internalValue) {
                        if(!this.internalValue.hasOwnProperty(key)) continue;
                        usedIds.push(this.internalValue[key].id);
                    }
                }

                for(let option of this.options) {
                    if(usedIds.indexOf(option.id) === -1) {
                        options.push(option);
                    }
                }

                return options;
            }
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
                    value = this.internalValue;

                if(this.fullTagsInValue) {
                    value = [];
                    for(let key in this.internalValue) {
                        if(!this.internalValue.hasOwnProperty(key)) continue;
                        value.push(this.internalValue[key].id);
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
            }
        },

        watch: {
            'password.tags'() {
                this.fullTagsInValue = true;
                this.loadModel();
            },
            value(value) {
                if(this.password === null) {
                    this.fullTagsInValue = !Array.isArray(value);
                    this.loadModel();
                }
            },
            model(value) {
                let model = [];
                for(let tag of value) {
                    model.push(this.fullTagsInValue ? tag:tag.id);
                }

                if(JSON.stringify(this.internalValue) !== JSON.stringify(model)) {
                    if(this.password === null) {
                        this.$emit('input', model);
                    } else {
                        this.password.tags = model;
                        if(this.timeout !== null) {
                            clearTimeout(this.timeout);
                        }
                        this.timeout = setTimeout(() => {
                            PasswordManager
                                .updatePassword(this.password)
                                .finally(() => {
                                    this.timeout = null;
                                });
                        }, 1000);
                    }
                }
            }
        }
    };
</script>

<style lang="scss">
div.passwords-tags-field.select {
    border        : 2px solid var(--color-border-maxcontrast);
    margin        : 3px 3px 3px 0;
    border-radius : var(--border-radius-large);
    cursor        : pointer;
    min-height    : 36px;
    padding       : 0;
    width         : 100%;

    .no-wrap {
        height : 36px;
    }

    &:focus,
    &:active {
        border-color : var(--color-border-dark);
    }

    &:hover,
    &.vs--open {
        border-color : var(--color-primary-element);
    }

    .vs__dropdown-toggle {
        padding          : 0;
        background-color : var(--color-main-background);
        border           : none;
        border-radius    : calc(var(--border-radius) + 4px);
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
        min-height: 0 !important;

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