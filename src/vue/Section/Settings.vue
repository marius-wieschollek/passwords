<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <section>
                <translate tag="h1" say="Security"/>
                <translate tag="h3" say="Password Generation"/>
                <translate tag="label" for="setting-security-level" say="Security level"/>
                <select id="setting-security-level">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
                <input type="checkbox" id="setting-include-numbers">
                <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                <input type="checkbox" id="setting-include-special">
                <translate tag="label" for="setting-include-special" say="Include special charaters"/>
            </section>
        </div>
    </div>
</template>

<script>

    import Translate from "@/vue/Components/Translate";

    export default {
        components: {Translate}
    };
</script>

<style lang="scss">
.app-content-left.settings {
    padding: 10px;

    h1 {
        font-size: 2.25em;
        font-weight: 200;
        margin: 0.25em 0 1em;
    }

    h3 {
        margin-bottom: 0;
    }
}
</style>