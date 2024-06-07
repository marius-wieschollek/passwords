<template>
    <form class="passwords-form" :id="id" :data-form="name" @submit="preventSubmit($event)">
        <translate tag="div" class="message" :say="message" v-if="message"/>
        <div v-for="field in getFields" class="field" :class="{large: field.button !== null}">
            <translate tag="label" :for="field.attributes.id" :say="field.label"/>
            <input v-bind="field.attributes" v-model="fields[field.name]">
            <translate tag="a"
                       v-if="field.button"
                       :icon="field.button.icon"
                       class="button"
                       :title="field.button.title"
                       @click="executeButtonAction(field, $event)"/>
        </div>
        <input type="submit">
    </form>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {Translate},
        props     : {
            id     : {
                type: String
            },
            name     : {
                type: String
            },
            message: {
                type: String | Array
            },
            form   : {
                type: Object
            }
        },
        data() {
            return {
                fields: {}
            };
        },
        computed  : {
            getFields() {
                let fields = [];

                for(let name in this.form) {
                    if(!this.form.hasOwnProperty(name)) continue;
                    let field       = this.form[name],
                        value       = field.value ? field.value:'',
                        type        = field.type ? field.type:'text',
                        button      = this.resolveFieldButton(field),
                        id          = `password-field-${name}`,
                        label       = field.label ? field.label:name.capitalize(),
                        title       = LocalisationService.translateArray(field.title ? field.title:field.label),
                        required    = !!field.required,
                        checked     = !!field.checked,
                        minlength   = field.minlength ? field.minlength:null,
                        maxlength   = field.maxlength ? field.maxlength:null,
                        pattern     = field.pattern ? field.pattern:null,
                        placeholder = LocalisationService.translateArray(field.placeholder ? field.placeholder:field.label);

                    this.fields[name] = value;
                    if(type === 'checkbox' && !field.hasOwnProperty('value')) value = checked;
                    this.fields[name] = value;

                    if(minlength && !pattern) pattern = `.{${minlength},}`;

                    fields.push(
                        {
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
                        }
                    );
                }

                return fields;
            }
        },
        methods   : {
            preventSubmit($event) {
                $event.preventDefault();
            },
            getFormData() {
                let fields  = document.querySelectorAll(`#${this.id} input`),
                    invalid = false;

                for(let i = 0; i < fields.length; i++) {
                    let field        = fields[i],
                        name         = field.name,
                        fieldInvalid = false;

                    if(!name) continue;
                    field.setCustomValidity('');

                    if(!field.checkValidity()) {
                        invalid = true;
                        fieldInvalid = true;
                    }
                    if(!fieldInvalid && this.form[name].validator &&
                       !this.form[name].validator(this.fields[name], this.fields, field)) {
                        let message = 'Please correct your input';
                        if(this.form[name].title) message = this.form[name].title;
                        field.setCustomValidity(LocalisationService.translate(message));
                        invalid = true;
                    }
                }

                if(invalid) {
                    document.querySelector(`#${this.id} [type=submit]`).click();
                    return false;
                }

                return this.fields;
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
                let btn = $event.target.tagName === 'I' ? $event.target:$event.target.getElementsByTagName('i')[0];
                btn.classList.toggle('fa-eye-slash');
                btn.classList.toggle('fa-eye');
                $el.type = $el.type === 'text' ? 'password':'text';
            }
        }
    };
</script>

<style lang="scss">
    form.passwords-form {
        width     : 340px;
        max-width : 100%;
        padding   : 10px;
        margin    : -25px 0;

        .message {
            margin-bottom : 10px;
        }

        div.field {
            display               : grid;
            grid-template-columns : 2fr 3fr;

            &.large {
                grid-template-columns : 8fr 9fr 3fr;
            }

            label {
                align-self : center;
            }

            input {
                width  : 100%;
                cursor : text;
                margin : 3px 0;

                &[type=color] {
                    padding : 3px 5px;
                    height  : 34px;
                    cursor  : pointer;
                }

                &[type=checkbox] {
                    justify-self : end;
                    cursor       : pointer;
                    width        : auto;
                }
            }

            .button {
                font-size  : 1.25em;
                text-align : center;
                margin     : 3px 0 3px 6px;
                padding    : 6px;
            }
        }

        input[type=submit] {
            display : none;
        }
    }

</style>