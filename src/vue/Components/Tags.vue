<template>
    <div class="tags-container" @mouseover="updateSearchResults" @mouseout="updateSearchResults">
        <ul class="tags" v-if="tags">
            <li class="tag" v-for="tag in tags" :key="tag.id" :style="{'background-color': tag.color}">
                <div class="label" @click="editAction(tag)">{{tag.label}}</div>
                <i class="fa fa-times" @click="removeAction($event, tag)"></i>
            </li>
        </ul>
        <input type="text" :placeholder="placeholder" class="add-tags" v-model="inputText" @keyup="keyUpAction($event)">
        <ul class="tag-search" v-if="searchResults">
            <li class="result"
                v-for="match in searchResults"
                :key="match.id"
                :style="{'background-color': match.color}"
                @click="addTag(match)">{{match.label}}
            </li>
        </ul>
    </div>
</template>

<script>
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Utility from "@js/Classes/Utility";
    import TagManager from '@js/Manager/TagManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                placeholder  : Utility.translate('Add Tags...'),
                tags         : this.password.tags ? Utility.sortApiObjectArray(this.password.tags, 'label'):[],
                allTags      : [],
                inputText    : '',
                searchResults: [],
                wasBackspace : false,
            }
        },

        created() {
            this.loadTags();
        },

        methods: {
            loadTags       : function () {
                API.listTags()
                    .then((t) => { this.allTags = Utility.objectToArray(t); })
            },
            keyUpAction    : function ($e) {
                let key = $e.keyCode;
                if ([8, 13, 46].indexOf(key) === -1 && $e.key.length === 1 && this.inputText.length !== 0) {
                    this.searchAction(this.inputText);
                } else if (key === 13 && this.inputText.length !== 0) {
                    this.createAndAddTag(this.inputText);
                } else if (key === 8 && this.wasBackspace && this.inputText.length === 0) {
                    this.removeLastTag();
                } else if (key === 8 && this.inputText.length === 0) {
                    this.wasBackspace = true;
                }
            },
            searchAction   : function (query) {
                this.searchResults = [];
                query = query.toLowerCase();

                for (let i = 0; i < this.allTags.length; i++) {
                    let tag = this.allTags[i];

                    if (Utility.searchApiObjectInArray(this.tags, tag) !== -1) continue;

                    if (tag.label.toLowerCase().indexOf(query) !== -1) {
                        this.searchResults.push(tag);
                    }
                }
                this.updateSearchResults();
            },
            addTag         : function (tag) {
                this.tags.push(tag);
                this.inputText = '';
                this.searchResults = [];

                if (this.password) {
                    this.password.tags = this.tags;
                    PasswordManager.updatePassword(this.password);
                }
            },
            createAndAddTag: function (label) {
                let query = label.toLowerCase();

                for (let i = 0; i < this.allTags.length; i++) {
                    let tag = this.allTags[i];

                    if (tag.label.toLowerCase() === query) {
                        this.addTag(tag);
                        return;
                    }
                }

                TagManager.createTagFromData({label: label})
                    .then((tag) => {
                        this.allTags.push(tag);
                        this.addTag(tag);
                    })
            },
            removeLastTag  : function () {
                this.tags.pop();
                this.inputText = '';
                this.searchResults = [];
                if (this.password) {
                    this.password.tags = this.tags;
                    PasswordManager.updatePassword(this.password);
                }
            },
            editAction     : function (tag) {
                TagManager.editTag(tag);
            },
            removeAction   : function ($event, tag) {
                $event.stopPropagation();
                let i = this.tags.indexOf(tag);

                if (i !== -1) this.tags.remove(i);

                if (this.password) {
                    this.password.tags = this.tags.length === 0 ? ['']:this.tags;
                    PasswordManager.updatePassword(this.password);
                }
            },
            updateSearchResults() {
                if (this.inputText.length === 0) return;

                let $input   = $('div.tags-container input.add-tags'),
                    $search  = $('div.tags-container ul.tag-search'),
                    position = $input.position();

                $search.css({
                                top  : (position.top + $input.outerHeight()) + 'px',
                                left : position.left + 'px',
                                width: $input.outerWidth()
                            })
            }
        },

        watch: {
            password: function (newPassword) {
                this.tags = Utility.sortApiObjectArray(newPassword.tags, 'label');
            }
        }
    };
</script>

<style lang="scss">
    .tags-container {
        position : relative;

        .tags {
            display : inline;

            .tag {
                display       : inline-block;
                border-radius : 2px;
                margin        : 0 5px 3px 0;

                .label {
                    display : inline-block;
                    padding : 3px 0 3px 5px;
                    cursor  : pointer;
                }

                .fa-times {
                    padding : 3px 5px 3px 3px;
                    cursor  : pointer;
                    opacity : 0.5;

                    &:hover {
                        opacity : 1;
                    }
                }
            }
        }

        .add-tags {
            display : inline-block;
            border  : none;
        }

        .tag-search {
            position : absolute;

            .result {
                padding : 3px 5px;
                cursor  : pointer;
            }
        }
    }
</style>