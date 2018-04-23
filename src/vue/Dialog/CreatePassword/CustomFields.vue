<template>
    <div class="custom-fields">
        <div class="custom-field-form">
            <input type="text" placeholder="Name" v-model="field.name" maxlength="48">
            <select v-model="field.type" v-if="isValidName">
                <option value="text">Text</option>
                <option value="password">Password</option>
                <option value="email">E-Mail</option>
                <option value="url">Link</option>
                <option value="file">File</option>
            </select>
            <input :type="getFieldType" placeholder="Value" v-model="field.value" maxlength="320" v-if="isValidName">
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            fields: {
                type: Object
            }
        },
        data() {
            return {
                field: {
                    name:'',
                    type:'text',
                    value:null
                },
            }
        },
        computed: {
            isValidName() {
                return !this.fields.hasOwnProperty(this.field.name) && this.field.name.length && this.field.name.substr(0,1) !== '_';
            },
            getFieldType() {
                if(this.field.type === 'password') return 'password';
                if(this.field.type === 'email') return 'email';
                return 'text';
            }
        }
    };
</script>

<style scoped>

</style>