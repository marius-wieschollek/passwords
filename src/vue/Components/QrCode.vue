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
    <qr-code class="passwords-qr-code" :style="style" :text="text" :color="color" :size="codeSize" :bgColor="bgColor" errorLevel="Q"/>
</template>

<script>
    import QrCode from 'vue-qrcode-component';
    import SettingsService from "@js/Services/SettingsService";
    import Utility from "@js/Classes/Utility";

    export default {
        components: {QrCode},

        props   : {
            text: {
                type: String
            },
            size: {
                type   : Number,
                default: 256
            }
        },
        computed: {
            color() {
                let primaryColor = SettingsService.get('server.theme.color.primary'),
                    lumaBg       = Utility.getColorLuma(SettingsService.get('server.theme.color.background')),
                    lumaFg       = Utility.getColorLuma(primaryColor);

                if(lumaBg - lumaFg < 50) {
                    return '#000';
                }

                return primaryColor;
            },
            bgColor() {
                return SettingsService.get('server.theme.color.background');
            },
            style() {
                return {
                    'border-color': this.bgColor,
                    width         : `${this.size}px`,
                    height        : `${this.size}px`
                };
            },
            codeSize() {
                return this.size - 8;
            }
        }
    };
</script>

<style lang="scss">
.passwords-qr-code {
    border : 4px solid transparent;
}
</style>