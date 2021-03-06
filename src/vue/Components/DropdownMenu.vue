<template>
    <div class="dropdown-menu" :id="id">
        <icon class="menu-toggle" icon="ellipsis-h" @click="open = !open"/>
        <div class="popovermenu bubble menu" :class="{open: open}">
            <slot name="items"/>
        </div>
    </div>
</template>

<script>
    import Icon from '@vc/Icon';

    export default {
        components: {Icon},
        data() {
            return {
                open : false,
                id   : `pw-dropdown-menu-${Math.round(Math.random() * 10000)}`,
                event: (e) => {
                    if(e.target.closest(`#${this.id}`) === null) {
                        this.open = false;
                    }
                }
            };
        },
        watch: {
            open(value) {
                if(value) {
                    document.addEventListener('click', this.event, {passive: true});
                } else {
                    document.removeEventListener('click', this.event);
                }
            }
        }
    };
</script>

<style lang="scss">
.dropdown-menu {
    display  : inline-block;
    position : relative;

    .menu-toggle {
        cursor : pointer;
    }

    .popovermenu {
        top   : 2em;
        right : -.75em;

        ul li {
            display     : flex;
            padding     : .25rem;
            align-items : center;
            cursor      : pointer;

            > .icon,
            > span:only-child > .icon {
                width        : 1rem;
                margin-right : .25rem;
            }

            > span:only-child {
                display     : flex;
                align-items : center;
                flex-grow   : 1;
            }

            > select,
            > input {
                flex-grow : 1;
            }

            > label {
                margin-right : .25rem;
            }
        }
    }
}
</style>