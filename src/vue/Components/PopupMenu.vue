<template>
    <div class="popup-menu">
        <icon icon="ellipsis-h" />
        <div style="display: none">
            <slot name="items" />
        </div>
    </div>
</template>

<script>
    import Icon from '@vc/Icon';

    export default {
        components: {Icon}
    };
</script>

<style lang="scss">
.popup-menu {
    display : inline-block;
}
</style>