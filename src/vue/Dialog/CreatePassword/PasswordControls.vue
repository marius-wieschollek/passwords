<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="passwords-password-controls">
        <icon :icon="value ? 'eye':'eye-slash'" @click="$emit('input', !value)" title="Toggle visibility"/>
        <icon icon="refresh" :spin="generating" @click="generatePassword" title="Generate password"/>
        <dropdown-menu>
            <ul slot="items">
                <li :class="{active:numbers}">
                    <translate :icon="numbers ? 'check-square':'square-o'" @click="numbers = !numbers" say="Numbers"/>
                </li>
                <li :class="{active:special}">
                    <translate :icon="special ? 'check-square':'square-o'" @click="special = !special" say="Special Characters"/>
                </li>
                <li>
                    <translate :for="id" tag="label" say="Strength"/>
                    <select :id="id" v-model.number="strength">
                        <translate tag="option" value="0" say="Low"/>
                        <translate tag="option" value="1" say="Default"/>
                        <translate tag="option" value="2" say="Medium"/>
                        <translate tag="option" value="3" say="High"/>
                        <translate tag="option" value="4" say="Ultra"/>
                    </select>
                </li>
            </ul>
        </dropdown-menu>
    </div>
</template>

<script>
    import Icon from "@vc/Icon";
    import Translate from "@vc/Translate";
    import DropdownMenu from '@vc/DropdownMenu';
    import API from "@js/Helper/api";
    import SettingsService from "@js/Services/SettingsService";

    export default {
        components: {Translate, Icon, DropdownMenu},
        props     : {
            value: Boolean
        },

        data() {
            return {
                generating: false,
                numbers   : SettingsService.get('user.password.generator.numbers', false),
                special   : SettingsService.get('user.password.generator.special', false),
                strength  : SettingsService.get('user.password.generator.strength', 1),
                id        : `pw-generator-strength-${Math.round(Math.random() * 10000)}`
            };
        },
        methods: {
            generatePassword() {
                this.generating = true;

                API.generatePassword(this.strength, this.numbers, this.special)
                   .then((d) => {
                       if(d.strength === this.strength && d.numbers === this.numbers && d.special === this.special) {
                           this.$emit('generate', d.password);
                           this.generating = false;
                       }
                   })
                   .catch(() => {
                       this.generating = false;
                   });
            }
        },
        watch  : {
            numbers() {
                this.generatePassword();
            },
            special() {
                this.generatePassword();
            },
            strength() {
                this.generatePassword();
            }
        }
    };
</script>

<style lang="scss">
.passwords-password-controls {
    flex-shrink : 0;

    & > * {
        padding : 0 .5rem;
        cursor  : pointer;
    }

    .menu-toggle.icon {
        padding : 0;
        width   : 1rem;
    }

    li {
        transition : color .15s ease-in-out;

        > span {
            cursor : pointer;
        }

        &.active .icon {
            color : var(--color-primary);
        }
    }
}
</style>