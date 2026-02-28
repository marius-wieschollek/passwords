<template>
    <nc-modal :name="localizedTitle" :size="size" v-on:close="cancel" v-if="modal">
        <form
                ref="form"
                class="passwords-form"
                :id="id"
                :data-form="name"
                @submit="submit($event)"
                :class="{ 'with-actions': this.hasActions }"
        >
            <translate tag="div" class="message" :say="message" v-if="message"/>
            <br v-else>
            <div
                    v-for="field in fields"
                    class="field"
            >
                <translate tag="label" :for="field.attributes.id" :say="field.label"/>
                <input v-bind="field.attributes" v-model="models[field.name]"/>
                <translate
                        tag="a"
                        v-if="field.button"
                        :icon="field.button.icon"
                        class="button"
                        :title="field.button.title"
                        @click="executeButtonAction(field, $event)"
                />
            </div>
            <div class="actions">
                <nc-button type="primary" nativeType="button" size="large" @click="submit">
                    {{ t("Save") }}
                </nc-button>
            </div>
        </form>
    </nc-modal>
</template>

<script>
    import Translate from "@vue/Components/Translate";
    import LocalisationService from "@js/Services/LocalisationService";
    import NcButton from "@nextcloud/vue/components/NcButton";
    import NcModal from "@nextcloud/vue/components/NcModal";

    export default {
        components: {NcButton, Translate, NcModal},
        props     : {
            id     : {
                type: String
            },
            title  : {
                type: String
            },
            name   : {
                type: String
            },
            message: {
                type: String | Array
            },
            form   : {
                type: Object
            },
            size   : {
                type   : String,
                default: 'normal'
            }
        },
        data() {
            return {
                modal     : true,
                hasActions: false,
                models    : {}
            };
        },
        computed: {
            fields() {
                let fields = [];

                for(let name in this.form) {
                    if(!this.form.hasOwnProperty(name)) continue;
                    let field       = this.form[name],
                        value       = field.value ? field.value:"",
                        type        = field.type ? field.type:"text",
                        button      = this.resolveFieldButton(field),
                        id          = `password-field-${name}`,
                        label       = field.label ? field.label:name.capitalize(),
                        title       = LocalisationService.translateArray(
                            field.title ? field.title:field.label
                        ),
                        required    = !!field.required,
                        checked     = !!field.checked,
                        minlength   = field.minlength ? field.minlength:null,
                        maxlength   = field.maxlength ? field.maxlength:null,
                        pattern     = field.pattern ? field.pattern:null,
                        placeholder = LocalisationService.translateArray(
                            field.placeholder ? field.placeholder:field.label
                        );

                    if(type === "checkbox" && !field.hasOwnProperty("value")) {
                        value = checked;
                    }
                    this.models[name] = value;

                    if(minlength && !pattern) pattern = `.{${minlength},}`;

                    fields.push({
                                    name,
                                    label,
                                    button,
                                    attributes: {
                                        name,
                                        value,
                                        type,
                                        id,
                                        title,
                                        placeholder,
                                        required,
                                        checked,
                                        maxlength,
                                        pattern
                                    }
                                });
                }

                return fields;
            },
            localizedTitle() {
                return LocalisationService.translateArray(this.title);
            }
        },
        methods : {
            submit($event) {
                let fields  = this.$refs.form.querySelectorAll(`#${this.id} input`),
                    invalid = false;

                for(let field of fields) {
                    let name = field.name;

                    if(!name) continue;
                    field.setCustomValidity("");

                    if(!field.checkValidity()) {
                        invalid = true;
                        field.reportValidity();
                        continue;
                    }
                    if(
                        this.form[name].validator &&
                        !this.form[name].validator(this.models[name], this.models, field)
                    ) {
                        let message = 'Please correct your input';
                        if(this.form[name].title) message = this.form[name].title;
                        field.setCustomValidity(LocalisationService.translate(message));
                        field.reportValidity();
                        invalid = true;
                    }
                }

                if(invalid) {
                    return false;
                }
                $event.preventDefault();
                this.$emit('submit', this.models);
                this.modal = false;
            },
            cancel() {
                this.$emit('cancel');
            },
            executeButtonAction(field, $event) {
                let $input = document.getElementById(field.attributes.id),
                    value  = field.button.action(field, $input, $event);

                if(value !== undefined) {
                    $input.value = value;
                    this.fields[field.name] = value;
                }
            },
            resolveFieldButton(field) {
                if(field.button) {
                    this.hasActions = true;
                    if(typeof field.button === 'string') {
                        if(field.button === 'toggle') {
                            return {
                                icon  : 'eye',
                                title : 'Show value',
                                action: this.toggleButtonAction
                            };
                        }
                    } else {
                        return field.button;
                    }
                }
                return null;
            },
            toggleButtonAction(field, $el, $event) {
                let btn =
                        $event.target.tagName === "I"
                        ? $event.target
                        :$event.target.getElementsByTagName("i")[0];
                btn.classList.toggle("fa-eye-slash");
                btn.classList.toggle("fa-eye");
                $el.type = $el.type === "text" ? "password":"text";
            }
        }
    };
</script>

<style lang="scss">
form.passwords-form {
    padding : 1rem;

    .message {
        margin-bottom : 1rem;
    }

    div.field {
        display               : grid;
        grid-template-columns : 2fr 3fr;

        label {
            align-self : center;
        }

        input {
            width  : 100%;
            cursor : text;
            margin : 3px 0;

            &[type="color"] {
                padding : 3px 5px;
                height  : 34px;
                cursor  : pointer;
            }

            &[type="checkbox"] {
                justify-self : end;
                cursor       : pointer;
                width        : auto;
            }
        }
    }

    &.with-actions {
        div.field {
            grid-template-columns : 2fr 3fr 3rem;
        }

        .button {
            font-size  : 1.25em;
            text-align : center;
            margin     : 3px 0 3px 6px;
            padding    : 6px;
        }
    }

    .actions {
        display         : flex;
        gap             : .5rem;
        justify-content : end;
        margin-top      : 1rem;
    }
}
</style>