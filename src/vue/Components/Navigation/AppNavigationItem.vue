<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<script>
    import NcAppNavigationItem from '@nc/NcAppNavigationItem';

    export default {
        extends: NcAppNavigationItem,
        computed: {
            isActive() {
                if(this.exact) {
                    return this.to && this.$route.name === this.to.name && JSON.stringify(this.$route.params) === JSON.stringify(this.to.params)
                }

                return this.to && this.$route.name === this.to.name
            },
        }
    };
</script>

<style lang="scss">
.app-navigation-entry__children {
    .loading-icon {
        margin: .5rem 0;
    }
}
</style>