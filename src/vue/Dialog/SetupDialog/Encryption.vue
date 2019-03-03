<template>
    <li>
        <p>
            <translate say="Passwords offers modern and strong encryption to protect your data from prying eyes."/>
            <translate say="Coose a secure password below to activate the ultimate protection for your passwords."/>
        </p>
        <form @submit="setPassword" v-if="!processing">
            <div class="password-setup">
                <input type="password" placeholder="Password" pattern=".{12,}" v-model="password" readonly>
                <input type="password" placeholder="Repeat your Password" v-model="confirm">
            </div>
        </form>
        <div class="encryption-status" v-if="processing">
            <translate tag="h2" say="Installing Encryption"/>
            <div>
                <translate say="Folders"/>
                <span :class="getFolderClass">{{getFolderStatus}}</span>
            </div>
            <div>
                <translate say="Tags"/>
                <span :class="getTagsClass">{{getTagsStatus}}</span>
            </div>
            <div>
                <translate say="Passwords"/>
                <span :class="getPasswordsClass">{{getPasswordsStatus}}</span>
            </div>
            <div>
                <translate say="Clean up"/>
                <span :class="getCleanupClass">{{getCleanupStatus}}</span>
            </div>
            <translate tag="div" class="result success" say="Success" v-if="ready"/>
            <translate tag="div" class="result failure" say="Failed" v-if="hasError"/>
        </div>
    </li>
</template>

<script>
    import Translate from '@vue/Components/Translate';
    import EncryptionManager from '@js/Manager/EncryptionManager';

    export default {
        components: {Translate},
        props     : {
            isCurrent: {
                type   : Boolean,
                default: false
            }
        },
        data() {
            return {
                password  : '',
                confirm   : '',
                processing: false,
                ready     : false,
                hasError  : false,
                status    : {
                    folders  : {
                        status : 'waiting',
                        current: 0,
                        total  : 0
                    },
                    tags     : {
                        status : 'waiting',
                        current: 0,
                        total  : 0
                    },
                    passwords: {
                        status : 'waiting',
                        current: 0,
                        total  : 0
                    },
                    cleanup  : {
                        status : 'waiting',
                        current: 0,
                        total  : 0
                    }
                }
            };
        },
        created() {
            setTimeout(() => {
                document.querySelector('.password-setup > [type=password]').removeAttribute('readonly');
            }, 250);
        },
        computed  : {
            getFolderClass() {
                return this.getStatusClass('folders');
            },
            getTagsClass() {
                return this.getStatusClass('tags');
            },
            getPasswordsClass() {
                return this.getStatusClass('passwords');
            },
            getCleanupClass() {
                return this.getStatusClass('cleanup');
            },
            getFolderStatus() {
                return this.getStatusText('folders');
            },
            getTagsStatus() {
                return this.getStatusText('tags');
            },
            getPasswordsStatus() {
                return this.getStatusText('passwords');
            },
            getCleanupStatus() {
                return this.getStatusText('cleanup');
            }
        },
        methods   : {
            getStatusClass(type) {
                if(this.hasError && this.status[type].status !== 'success') return 'status failed';

                return this.status[type].status === 'waiting' ? 'status loading':`status ${this.status[type].status}`;
            },
            getStatusText(type) {
                if(this.status[type].status === 'failed' || this.hasError) return '✖';
                if(this.status[type].status === 'processing') {
                    return this.status[type].current + ' / ' + this.status[type].total;
                }
                if(this.status[type].status === 'success') return '✔';
            },
            async setPassword() {
                if(this.processing) return;
                this.processing = true;
                this.sendStatusEvent();

                await EncryptionManager.install(this.password, true, true, (d) => { this.updateEncryptionStatus(d); });

                this.ready = true;
                this.sendStatusEvent();
            },
            updateEncryptionStatus(d) {
                for(let key in this.status) {
                    if(!this.status.hasOwnProperty(key)) continue;

                    this.status[key].current = d[key].current;
                    this.status[key].status = d[key].status;
                    this.status[key].total = d[key].total;

                    if(d[key].status === 'failed') this.hasError = true;
                }
                if(d.keychain.status === 'failed') this.hasError = true;
            },
            sendStatusEvent() {
                let event = {
                    action   : {
                        label: 'Save',
                        class: 'disabled',
                        click: () => { }
                    },
                    skippable: !this.processing,
                    id       : 'encryption'
                };

                if(this.password.length > 12 && this.password === this.confirm && !this.processing) {
                    event.action.class = '';
                    event.action.click = (e) => { this.setPassword(e); };
                }
                if(this.ready) {
                    event.action.label = 'Continue';
                    event.action.class = '';
                    event.action.click = (e) => { this.$emit('continue', {}); };
                }

                this.$emit('status', event);
            }
        },
        watch     : {
            isCurrent(value) {
                if(value) this.sendStatusEvent();
            },
            confirm() {
                this.sendStatusEvent();
            },
            password() {
                this.sendStatusEvent();
            }
        }
    };
</script>

<style lang="scss">
    #setup-slide-encryption,
    #setup-slide-integrations {
        font-size   : 1.25rem;
        line-height : 1.5rem;

        p {
            position : relative;
            padding  : 5rem 1.5rem 5rem 12rem;

            &:before {
                font-family     : var(--pw-icon-font-face);
                content         : "\F132";
                font-size       : 10rem;
                position        : absolute;
                left            : 0;
                top             : 0;
                bottom          : 0;
                display         : flex;
                align-items     : center;
                width           : 12rem;
                justify-content : center;
                color           : var(--color-primary);
                text-shadow     : 0 0 5px var(--color-box-shadow);
            }

            @media (max-width : 900px) {
                padding : 1.5rem;

                &:before {
                    position      : static;
                    width         : 100%;
                    display       : block;
                    font-size     : 6rem;
                    line-height   : 6rem;
                    margin-bottom : 0.5rem;
                    text-align    : center;
                }
            }
        }

        form {
            padding         : 0 1.5rem;
            line-height     : 1.5rem;
            display         : flex;
            justify-content : center;

            .password-setup {
                width : 50%;

                input {
                    display       : block;
                    padding       : 0.75rem;
                    width         : 100%;
                    margin-bottom : 1rem;
                }

                @media (max-width : $width-extra-small) {
                    width : 100%;
                }
            }
        }

        .encryption-status {
            line-height : 1.5rem;
            margin      : 0 auto;
            width       : 33%;
            position    : relative;

            h2 {
                position : absolute;
                top      : -2.1rem;
            }

            .status {
                float       : right;
                font-weight : lighter;
                font-size   : 1rem;

                &.success {
                    color     : var(--color-success);
                    font-size : 1.5rem;
                }

                &.failed {
                    color     : var(--color-error);
                    font-size : 1.5rem;
                }
            }

            .loading:after {
                height : 1rem;
                width  : 1rem;
                margin : -9px 0 0 -9px;
                top    : 10px;
                left   : -12px;
            }

            .result {
                margin           : 1.25rem 0;
                font-size        : 1rem;
                background-color : var(--color-success);
                border-radius    : var(--border-radius);
                color            : var(--color-primary-text);
                text-align       : center;
                padding          : 0.25rem;

                &.failure {
                    background-color : var(--color-error);
                }
            }
        }

        @media (max-width : 900px) {
            font-size   : 1.1rem;
            line-height : 1.25rem;
        }
    }
</style>