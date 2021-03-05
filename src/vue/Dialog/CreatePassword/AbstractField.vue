<template>
    <div class="password-form-field-wrapper">
        <div class="area-label" />
        <div class="area-options" />
        <div class="area-input" />
    </div>
</template>

<script>
    export default {
        props: {
            value: String
        },

        data() {
            return {
                model: this.value
            };
        },

        watch: {
            value(value) {
                this.model = value;
            },
            model(value) {
                if(this.value !== value) {
                    this.$emit('input', value);
                }
            }
        }
    };
</script>

<style lang="scss">
.password-form-field-wrapper {
    display               : grid;
    grid-template-areas   : "label options" "input input";
    grid-column-gap       : .25rem;
    grid-row-gap          : .25rem;
    grid-template-columns : 3fr 1fr;
    min-width             : 300px;

    .area-label {
        grid-area : label;
        font-weight: bold;
    }

    .area-options {
        grid-area       : options;
        text-align      : right;
        display         : flex;
        justify-content : flex-end;
        align-items     : center;

        & > * {
            padding : 0 0.25rem;
        }
    }

    .area-input {
        grid-area : input;
        width     : 100%;
    }
}
</style>