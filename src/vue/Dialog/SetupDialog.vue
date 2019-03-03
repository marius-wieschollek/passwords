<template>
    <div class="background" id="passwords-setup">
        <div id="setup-container">
            <div class="setup-header">
                <div class="logo"></div>
                <translate tag="h1" :say="current.title"/>
            </div>
            <ul class="setup-content" :style="getStyle">
                <start id="setup-slide-start" class="slide"/>
                <encryption id="setup-slide-encryption" class="slide" :is-current="currentId === 1" v-on:status="updateStatus" v-on:continue="nextSlide"/>
                <integrations id="setup-slide-integrations" class="slide" v-on:redirect="openSection"/>
            </ul>
            <div class="setup-navigation">
                <translate tag="div" say="Skip" class="skip" @click="nextSlide" v-if="showSkipButton"/>
                <translate tag="div" :say="current.action.label" class="continue" :class="current.action.class" @click="current.action.click" v-if="showCustomButton"/>
                <translate tag="div" :say="continueText" class="continue" @click="nextSlide" v-if="!showCustomButton"/>
            </div>
        </div>
    </div>
</template>

<script>
    import router from '@js/Helper/router';
    import Translate from '@vue/Components/Translate';
    import Start from '@vue/Dialog/SetupDialog/Start';
    import SettingsManager from '@js/Manager/SettingsManager';
    import Encryption from '@vue/Dialog/SetupDialog/Encryption';
    import Integrations from '@vue/Dialog/SetupDialog/Integrations';

    export default {
        components: {Integrations, Encryption, Translate, Start},
        data() {
            let slides = [
                {
                    title: 'A safe home for your passwords',
                    id   : 'start'
                },
                {
                    title    : 'Keep your passwords secret',
                    skippable: true,
                    action   : null,
                    id       : 'encryption'
                },
                {
                    title: 'Get connected',
                    id   : 'integrations'
                }
            ];


            return {
                current  : slides[0],
                currentId: 0,
                route    : {path: '/'},
                slides
            };
        },
        computed  : {
            getStyle() {
                if(this.currentId < 1) return '';
                if(window.innerWidth > 900) {
                    return `transform: translateX(-${this.currentId * 900}px);`;
                }

                return `transform: translateX(-${this.currentId * 100}vw);`;
            },
            continueText() {
                return this.currentId === this.slides.length - 1 ? 'Close':'Continue';
            },
            showSkipButton() {
                return this.current.skippable;
            },
            showCustomButton() {
                return this.current.hasOwnProperty('action') && this.current.action !== null;
            }
        },
        methods   : {
            goToSlide(id) {
                if(!this.slides.hasOwnProperty(id)) return;

                this.current = this.slides[id];
                this.currentId = id;
            },
            nextSlide() {
                if(this.currentId < this.slides.length - 1) {
                    this.goToSlide(this.currentId + 1);
                } else {
                    this.closeWizard();
                }
            },
            openSection($event) {
                this.route = $event;
                this.closeWizard();
            },
            updateStatus(e) {
                if(e.id === this.current.id) {
                    if(e.hasOwnProperty('skippable')) this.current.skippable = e.skippable === true;
                    if(e.hasOwnProperty('action')) this.current.action = e.action;
                }
            },
            closeWizard() {
                SettingsManager.set('client.setup.initialized', true);
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
                router.push(this.route);
            }
        }
    };
</script>

<style lang="scss">
    #passwords-setup {
        display         : flex;
        align-items     : center;
        justify-content : center;

        #setup-container {
            border-radius    : var(--border-radius-large);
            overflow         : hidden !important;
            max-width        : 900px;
            width            : 100%;
            background-color : var(--color-main-background);
            position         : relative;
        }

        .setup-header {
            padding         : 1.25rem;
            background      : var(--color-primary) var(--image-login-background) no-repeat 50% 50%;
            background-size : cover;
            color           : var(--color-primary-text);
            text-align      : center;

            .logo {
                height          : 120px;
                background      : url(../../img/app.svg) no-repeat center;
                background-size : contain;
            }

            h1 {
                font-size   : 3rem;
                line-height : 3rem;
                margin-top  : 1rem;
            }
        }

        .setup-content {
            width      : 10000px;
            transition : transform 0.25s ease-in-out;

            .slide {
                height : 500px;
                width  : 900px;
                float  : left;
            }
        }

        .setup-navigation {
            position : absolute;
            bottom   : 0;
            padding  : 0.5rem;
            width    : 100%;

            .skip,
            .continue {
                background-color : var(--color-primary-element);
                padding          : 0.75rem;
                border-radius    : var(--border-radius-pill);
                color            : var(--color-primary-text);
                cursor           : pointer;
                display          : inline-block;
                transition       : opacity 0.5s ease-in-out;

                &.continue {
                    float : right;
                }

                &.skip {
                    background-color : rgba(0,0,0,0);
                    color            : var(--color-text-lighter)
                }

                &.disabled {
                    opacity : 0.5;
                    cursor  : default;
                }
            }
        }
    }
</style>