<template>
    <form class="passwords-form" :id="id">
        <translate v-if="message" :say="message"></translate>
        <div v-for="field in getFields" class="field" :class="{large: field.button !== null}">
            <translate tag="label" :for="field.id" :say="field.label"/>
            <input v-bind="field.attributes" v-model="fields[field.name]">
            <translate tag="a" v-if="field.button" :icon="field.button.icon" class="button" :title="field.button.title" @click="field.button.action(field, $event)"/>
        </div>
        <input type="submit">
    </form>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import Localisation from '@js/Classes/Localisation';

    export default {
        components: {Translate},
        props     : {
            id     : {
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
                        title       = field.title ? field.title:field.label,
                        required    = !!field.required,
                        checked     = !!field.checked,
                        minlength   = field.minlength ? field.minlength:null,
                        maxlength   = field.maxlength ? field.maxlength:null,
                        pattern     = field.pattern ? field.pattern:null,
                        placeholder = field.placeholder ? Localisation.translateArray(field.placeholder):null;

                    this.fields[name] = value;
                    if(type === 'checkbox' && !field.hasOwnProperty('value')) value = checked;
                    this.fields[name] = value;

                    if(minlength && !pattern) pattern = `.{${minlength},}`;

                    fields.push({name, label, button, attributes: {name, type, id, title, placeholder, required, checked, maxlength, pattern}});
                }

                return fields;
            }
        },
        methods   : {
            getFormData() {
                let fields = document.querySelectorAll(`#${this.id} input`);
                for(let i = 0; i < fields.length; i++) {
                    let field   = fields[i],
                        name    = field.name,
                        invalid = false;

                    if(!name) continue;
                    if(!field.checkValidity()) invalid = true;
                    if(!invalid && this.form[name].validator && !this.form[name].validator(this.fields[name], this.fields, field)) {
                        field.setCustomValidity(Localisation.translate('Please correct your input'));
                        invalid = true;
                    }

                    if(invalid) {
                        document.querySelector(`#${this.id} [type=submit]`).click();
                        return false;
                    }
                }

                return this.fields;
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
            toggleButtonAction(field, $event) {
                let el  = document.getElementById(field.attributes.id),
                    btn = $event.target;
                if(btn.tagName !== 'I') btn = btn.getElementsByTagName('i')[0];
                btn.classList.toggle('fa-eye-slash');
                btn.classList.toggle('fa-eye');
                el.type = el.type === 'text' ? 'password':'text';
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
            grid-column-start : 1;
            grid-column-end   : 3;
            margin-bottom     : 5px;
        }

        div.field {
            display               : grid;
            grid-template-columns : 2fr 3fr;
            margin-top            : 5px;

            &.large {
                grid-template-columns : 4fr 6fr 2fr;
            }

            label {
                align-self : center;
            }

            input {
                width  : auto;
                cursor : text;

                &[type=checkbox],
                &[type=color] {
                    margin-left : 80%;
                    cursor      : pointer;
                }

                &[type=checkbox] {
                    justify-self : end;
                }
            }

            .button {
                font-size  : 1.25em;
                text-align : center;
                cursor     : pointer;
            }
        }

        input[type=submit] {
            display : none;
        }
    }

</style>