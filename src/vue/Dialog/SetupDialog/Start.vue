<template>
    <li>
        <div>
            {{ t('StartText1') }}
            <div class="spacer"></div>
            {{ t("StartText2") }}
        </div>
    </li>
</template>

<script>
    export default {};
</script>

<style lang="scss">
#setup-slide-start > div {
    height          : 100%;
    display         : flex;
    justify-content : center;
    align-items     : center;
    font-size       : 2rem;
    line-height     : initial;
    text-align      : center;
    padding         : 2rem 10%;
    flex-direction  : column;

    .spacer {
        min-height : 2rem;
    }
}
</style>