<template>
    <div class="tags-container" @mouseover="updateSearchResults" @mouseout="updateSearchResults">
        <ul class="tags" v-if="tags">
            <li class="tag" v-for="tag in tags" :key="tag.id" :style="{'background-color': tag.color}" v-if="tag !== ''">
                <div class="label" @click="editAction(tag)">{{tag.label}}</div>
                <i class="fa fa-times" @click="removeAction($event, tag)"></i>
            </li>
        </ul>
        <input type="text" :placeholder="placeholder" class="add-tags" v-model="inputText" @keyup="keyUpAction($event)">
        <ul class="tag-search" v-if="searchResults.length !== 0" :style="getPopupStyle">
            <li class="result"
                v-for="match in searchResults"
                :key="match.id"
                @click="addTag(match)"
                @mouseover="getHoverStyle($event)"
                @mouseout="getHoverStyle($event, false)">
                <i class="fa fa-tag" :style="{color: match.color}"></i>{{match.label}}
            </li>
        </ul>
    </div>
</template>

<script>
    import $ from 'jquery';
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import TagManager from '@js/Manager/TagManager';
    import Localisation from '@js/Classes/Localisation';
    import ThemeManager from '@js/Manager/ThemeManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                placeholder  : Localisation.translate('Add Tags...'),
                tags         : this.password.tags ? Utility.sortApiObjectArray(this.password.tags, 'label'):[],
                allTags      : [],
                inputText    : '',
                searchResults: [],
                wasBackspace : false,
                searchBox    : {
                    top  : 0,
                    left : 0,
                    width: 0
                }
            };
        },

        created() {
            this.loadTags();
        },

        computed: {
            getPopupStyle() {
                return {
                    color          : ThemeManager.getColor(),
                    border         : `1px solid ${ThemeManager.getColor()}`,
                    backgroundColor: ThemeManager.getContrastColor(),
                    top            : this.searchBox.top,
                    left           : this.searchBox.left,
                    width          : this.searchBox.width
                };
            }
        },

        methods: {
            loadTags() {
                API.listTags()
                   .then((t) => { this.allTags = Utility.objectToArray(t); });
            },
            keyUpAction($e) {
                let key = $e.keyCode;
                if([8, 13, 46].indexOf(key) === -1 && $e.key.length === 1 && this.inputText.length !== 0) {
                    this.wasBackspace = false;
                } else if(key === 13 && this.inputText.length !== 0) {
                    this.createAndAddTag(this.inputText);
                    this.wasBackspace = false;
                } else if(key === 8 && this.wasBackspace && this.inputText.length === 0) {
                    this.removeLastTag();
                } else if(key === 8 && this.inputText.length === 0) {
                    this.searchResults = [];
                    this.wasBackspace = true;
                } else {
                    this.wasBackspace = false;
                }
            },
            searchAction(query) {
                this.searchResults = [];
                if(query === '') return;
                query = query.toLowerCase();

                for(let i = 0; i < this.allTags.length; i++) {
                    let tag = this.allTags[i];

                    if(Utility.searchApiObjectInArray(this.tags, tag) !== -1) continue;

                    if(tag.label.toLowerCase().indexOf(query) !== -1) {
                        this.searchResults.push(tag);
                    }
                }
                this.updateSearchResults();
            },
            addTag(tag) {
                this.tags.push(tag);
                this.inputText = '';
                this.searchResults = [];

                if(this.password) {
                    this.password.tags = this.tags;
                    PasswordManager.updatePassword(this.password);
                }
                $('div.tags-container input.add-tags').focus();
            },
            createAndAddTag(label) {
                let query = label.toLowerCase();

                for(let i = 0; i < this.allTags.length; i++) {
                    let tag = this.allTags[i];

                    if(tag.label.toLowerCase() === query) {
                        this.addTag(tag);
                        return;
                    }
                }

                TagManager.createTagFromData({label: label})
                          .then((tag) => {
                              this.allTags.push(tag);
                              this.addTag(tag);
                          });
            },
            removeLastTag() {
                this.tags.pop();
                this.inputText = '';
                this.searchResults = [];
                if(this.password) {
                    this.password.tags = this.tags.length === 0 ? ['']:this.tags;
                    PasswordManager.updatePassword(this.password);
                }
            },
            editAction(tag) {
                TagManager.editTag(tag);
            },
            removeAction($event, tag) {
                $event.stopPropagation();
                let i = this.tags.indexOf(tag);

                if(i !== -1) this.tags.remove(i);

                if(this.password) {
                    this.password.tags = this.tags.length === 0 ? ['']:this.tags;
                    PasswordManager.updatePassword(this.password);
                }
            },
            updateSearchResults() {
                if(this.inputText.length === 0) return;

                let $input   = $('div.tags-container input.add-tags'),
                    $search  = $('div.tags-container ul.tag-search'),
                    position = $input.position();

                this.searchBox.top = `${position.top + $input.outerHeight()}px`;
                this.searchBox.left = `${position.left}px`;
                this.searchBox.width = $input.outerWidth();
            },
            getHoverStyle($event, on = true) {
                if(on) {
                    $event.target.style.backgroundColor = ThemeManager.getColor();
                    $event.target.style.color = ThemeManager.getContrastColor();
                } else {
                    $event.target.style.backgroundColor = null;
                    $event.target.style.color = null;
                }
            }
        },

        watch: {
            password(newPassword) {
                this.tags = Utility.sortApiObjectArray(newPassword.tags, 'label');
            },
            inputText(value) {
                this.searchAction(value);
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
            display    : inline-block;
            border     : none;
            padding    : 0;
            margin     : 0;
            min-height : 0;
        }

        .tag-search {
            position      : absolute;
            border-radius : 2px;
            max-height    : 120px;
            overflow-y    : auto;
            z-index       : 1;

            .result {
                padding : 3px 5px;
                cursor  : pointer;
                white-space: nowrap;

                .fa {
                    margin-right : 5px;
                }
            }
        }
    }
</style>