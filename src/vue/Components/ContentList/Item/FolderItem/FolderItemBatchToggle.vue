<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<script>
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import ItemBatchToggle from "@vc/ContentList/Item/ContentItem/ItemBatchToggle.vue";

    export default {
        extends: ItemBatchToggle,

        computed: {
            batchActionSelected() {
                return BatchActionManager.isFolderSelected(this.item);
            }
        },

        methods: {
            toggleSelection(state = null) {
                BatchActionManager.toggleFolder(this.item, state);
            }
        }
    };
</script>