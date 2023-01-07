<template>
    <div id="starchaser">
        <div class="stats" v-if="game.stats.lives !== 0">
            <div class="lives">
                <div class="life" v-for="n in game.stats.lives"></div>
            </div>
            <div class="points">
                <div class="number" v-for="(number,key) in getPointsAsArray()" :key="key">{{number}}</div>
            </div>
            <progress class="level-progress" :max="lvlMaximum" :value="lvlValue"/>
            <div class="level">
                <div class="number" v-for="(number,key) in getLevelArray()" :key="key">{{number}}</div>
            </div>
        </div>
        <div id="ship" :style="shipStyle" :class="shipClass"></div>
        <div class="asteroids">
            <div class="asteroid"
                 :class="{burst: asteroid.hit}"
                 :style="{top: `${asteroid.top}px`, left: `${asteroid.left}px`}"
                 v-for="asteroid in game.asteroids"
                 :key="asteroid.id"></div>
        </div>
        <div class="powerups">
            <div class="powerup" :class="getPowerUpClass(powerup)"
                 :style="{top: `${powerup.top}px`, left: `${powerup.left}px`}"
                 v-for="powerup in game.powerups"
                 :key="powerup.id"></div>
        </div>
        <div class="shots">
            <div class="shot" :style="{bottom: `${shot.bottom}px`, left: `${shot.left}px`}" v-for="shot in game.shots" :key="shot.id"></div>
        </div>
        <div class="explosion" :style="burstStyle" v-if="game.explosion.enabled"></div>
        <div class="endcard" v-if="game.stats.lives === 0" @click="endGame()">
            <div>
                <div class="text highscore" v-if="game.stats.highscore">New Highscore</div>
                <div class="fireworks"></div>
                <div class="endship"></div>
                <div class="points">
                    <div class="number" v-for="(number,key) in getPointsAsArray()" :key="key">{{number}}</div>
                </div>
                <div class="text">Points</div>
                <div class="text">{{game.stats.asteroids}} asteroids <br> {{game.stats.powerups}} powerups</div>
            </div>
        </div>
    </div>
</template>

<script>
    import StarChaser from '@js/Helper/StarChaser';

    export default {
        data() {
            let logic = new StarChaser(),
                game  = StarChaser.getNewGame();
            game.exit = () => {this.endGame();};

            return {game, logic};
        },

        created() {
            this.logic.start(this.game);
            document.getElementById('content-vue').style.zIndex = '2001';
            document.getElementById('unified-search__input').value = '';
            document.getElementById('header-menu-unified-search').dispatchEvent(new Event('focusout'));
        },

        computed: {
            shipStyle() {
                return {
                    left: `${this.game.ship.left}px`
                };
            },
            shipClass() {
                let classes = [];
                if(this.game.stats.lives === 0) {
                    classes.push('burst');
                } else {
                    if(this.game.ship.shield) classes.push('shield');
                    if(this.game.ship.move !== 'none') classes.push(`go-${this.game.ship.move}`);
                }

                return classes;
            },
            lvlValue() {
                if(this.game.stats.level === 0) return this.game.stats.points;
                return this.game.stats.points - this.game.status.levelMark / 1.2;
            },
            lvlMaximum() {
                return this.game.status.levelMark - this.game.status.levelMark / 1.2;
            },
            burstStyle() {
                let burst = this.game.explosion;

                return {
                    top    : `${burst.top - burst.radius}px`,
                    left   : `${burst.left - burst.radius}px`,
                    width  : `${burst.radius * 2}px`,
                    height : `${burst.radius * 2}px`,
                    opacity: burst.show ? 1:0
                };
            }
        },

        methods: {
            getPointsAsArray() {
                return this.game.stats.points.toString(10).split('');
            },
            getLevelArray() {
                return this.game.stats.level.toString(10).split('');
            },
            getPowerUpClass(powerup) {
                let classes = [powerup.type];
                if(powerup.hit) classes.push('burst');

                return classes;
            },
            endGame() {
                this.logic.end();
                this.$root.starChaser = false;
                document.getElementById('content-vue').style.zIndex = 'auto';
            }
        }
    };
</script>

<style lang="scss">
    #starchaser {
        position   : fixed;
        top        : 0;
        right      : 0;
        bottom     : -10px;
        left       : 0;
        background : transparentize(#192a56, 0.05);
        z-index    : 2000;

        #ship {
            background      : url(../../img/space/spaceship.png) no-repeat;
            background-size : 96px;
            width           : 96px;
            height          : 96px;
            position        : fixed;
            bottom          : 12px;
            transition      : transform 0.15s ease-in-out, left 0.033s ease-in-out;
            z-index         : 3;
            animation       : ship 1s linear infinite;

            &.go-right {
                transform : rotate(10deg);
            }
            &.go-left {
                transform : rotate(-10deg);
            }

            &.shield {
                background : none;
                &:before {
                    content          : " ";
                    margin           : -15px;
                    width            : 126px;
                    height           : 126px;
                    display          : block;
                    background-color : transparentize(#dff9fb, .75);
                    border-radius    : 50%;
                    box-shadow       : inset 0 0 10px 10px #1e90ff, 0 0 10px 3px #1e90ff;
                    animation        : ship-shield 10s linear forwards;
                }

                &:after {
                    content         : " ";
                    background      : url(../../img/space/spaceship.png) no-repeat;
                    background-size : 96px;
                    width           : 96px;
                    height          : 96px;
                    display         : block;
                    position        : absolute;
                    top             : 0;
                }
            }

            &.burst {
                animation : ship-end 2.5s linear forwards;

                &:after,
                &:before {
                    content          : " ";
                    width            : 20px;
                    height           : 20px;
                    display          : block;
                    background-color : transparentize(#f00, .75);
                    border-radius    : 50%;
                    box-shadow       : inset 0 0 5px 5px #ffd500, 0 0 5px 1px #f00;
                    position         : absolute;
                    top              : 4px;
                    right            : 36px;
                    margin           : -10px;
                    animation        : ship-burst 2s linear forwards;
                }

                &:after {
                    top    : auto;
                    right  : auto;
                    bottom : 24px;
                    left   : 24px;
                }
            }
        }

        .powerup,
        .asteroid {
            background : var(--pw-image-logo) no-repeat center;
            width      : 64px;
            height     : 64px;
            position   : fixed;
            top        : -64px;
            animation  : asteroid 2s linear infinite;
            z-index    : 4;
        }

        .asteroid {
            background : url(../../img/space/asteroid.png) no-repeat center;

            &.burst {
                animation     : asteroid-burst .3s linear forwards;
                border-radius : 50%;
                box-shadow    : inset 0 0 10px 10px transparentize($color-red, 0.4);
                width         : 104px;
                height        : 104px;
                display       : block;
                margin-left   : -20px;
                margin-top    : -20px;

                &:after {
                    content     : " ";
                    background  : url(../../img/space/burst.png) no-repeat;
                    width       : 64px;
                    height      : 64px;
                    display     : block;
                    margin-left : 20px;
                    margin-top  : 20px;
                    position    : absolute;
                }

                &:before {
                    content       : " ";
                    border-radius : 50%;
                    box-shadow    : inset 0 0 10px 10px $color-yellow;
                    width         : 32px;
                    height        : 32px;
                    display       : block;
                    margin-left   : 36px;
                    margin-top    : 36px;
                    position      : absolute;
                    top           : 0;
                    left          : 0;
                }
            }
        }

        .powerup {
            &.shield {
                background-image : url(../../img/space/shield.png);
                animation        : powerup 2s linear infinite;
            }

            &.lightning {
                background-image : url(../../img/space/lightning.png);
                animation        : powerup 2s linear infinite;

                &:after {
                    animation  : ligtning 1s linear infinite;
                    content    : " ";
                    background : url(../../img/space/lightning.png) no-repeat;
                    width      : 64px;
                    height     : 64px;
                    display    : block;
                    position   : absolute;
                }
            }

            &.star {
                background-image : url(../../img/space/star.png);
                animation        : powerup 2s linear infinite;
            }

            &.life {
                background-image : url(../../img/space/heart.png);
                animation        : life .9s linear infinite;
            }

            &.clock {
                background-image : url(../../img/space/clock.png);
                animation        : life .9s linear infinite;
            }

            &.bomb {
                background-image : url(../../img/space/bomb.png);
                animation        : life .9s linear infinite;
            }

            &.burst {
                animation : powerup-burst .5s linear forwards;
            }
        }

        .explosion {
            border-radius    : 50%;
            width            : 100px;
            height           : 100px;
            position         : fixed;
            background-color : transparentize($color-red, .85);
            border           : 1px solid $color-red;
            transition       : width 0.033s linear, height 0.033s linear, left 0.033s linear, top 0.033s linear, opacity 2s ease-in-out;
        }

        .shot {
            background-color : $color-yellow;
            border-radius    : 50%;
            width            : 8px;
            height           : 8px;
            position         : fixed;
            box-shadow       : inset -2px -2px red;
            z-index          : 2;
            animation        : asteroid 2s linear infinite;
        }

        .lives {
            position : fixed;
            top      : 8px;
            left     : 8px;

            .life {
                background      : url(../../img/space/heart.png) no-repeat;
                background-size : 48px;
                width           : 48px;
                height          : 48px;
                display         : inline-block;
                margin-right    : 0.25rem;
                animation       : life .9s linear infinite;
            }

            :nth-child(2) {
                animation-delay : .6s;
            }
            :nth-child(3) {
                animation-delay : .3s;
            }
        }

        .level,
        .points {
            position : fixed;
            top      : 8px;

            &.points {
                width      : 100%;
                text-align : center;
            }

            &.level {
                right       : 354px;
                white-space : nowrap;
                width       : 0;
            }

            .number {
                display      : inline-block;
                margin-right : 0.25rem;
                animation    : life .9s linear infinite;
                color        : #f5f6fa;
                font-size    : 42px;
                font-family  : var(--pw-game-font-face);
                line-height  : 36px;
                font-weight  : bold;
                text-shadow  : 2px 2px $color-grey-dark;
                opacity      : 0.75;
            }

            :nth-child(even) {
                animation-delay : .45s;
            }
        }

        .level-progress {
            position           : fixed;
            right              : 8px;
            width              : 360px;
            top                : 22px;
            border-radius      : 36px 12px;
            box-shadow         : 0 0 5px 2px #2ed573;
            border             : none;
            -webkit-appearance : none;
            background-color   : transparent;
            height             : 12px;
            animation          : level-shadow 8s linear infinite;

            &::-moz-progress-bar {
                background-color : #7bed9f;
                border-radius    : 36px 12px;
                animation        : level-color 8s linear infinite;
            }

            &::-webkit-progress-value {
                background-color : #7bed9f;
                border-radius    : 36px 12px;
                animation        : level-color 8s linear infinite;
            }
        }

        .endcard {
            display         : flex;
            align-items     : center;
            justify-content : center;
            position        : fixed;
            top             : 0;
            right           : 0;
            bottom          : 0;
            left            : 0;

            .fireworks {
                background    : url(../../img/space/fireworks.svg) no-repeat center bottom;
                width         : 360px;
                height        : 360px;
                margin-bottom : 64px;
                animation     : endcard-fireworks 2s linear forwards;
            }

            .endship {
                background      : url(../../img/space/spaceship.png) no-repeat center;
                position        : absolute;
                margin-top      : -200px;
                width           : 360px;
                height          : 256px;
                background-size : 256px;
                animation       : endcard-ship 1.5s linear forwards;
            }

            .points {
                position : static;
                top      : auto;
                width    : auto;
            }

            .text {
                color         : #f5f6fa;
                font-size     : 24px;
                font-family   : var(--pw-game-font-face);
                line-height   : 28px;
                font-weight   : bold;
                text-shadow   : 2px 2px $color-grey-dark;
                opacity       : 0.75;
                text-align    : center;
                width         : 360px;
                margin-bottom : 28px;

                &.highscore {
                    font-size   : 48px;
                    line-height : 56px;
                    animation   : highscore 2s linear infinite;
                    position    : absolute;
                }
            }
        }

        @keyframes ship {
            0% {
                bottom : 12px;
            }
            50% {
                bottom : 24px;
            }
            100% {
                bottom : 12px;
            }
        }

        @keyframes ship-burst {
            0% {
                margin : -10px;
                width  : 20px;
                height : 20px;
            }
            70% {
                margin  : -40px;
                width   : 80px;
                height  : 80px;
                opacity : 1;
            }
            100% {
                margin  : -50px;
                width   : 100px;
                height  : 100px;
                opacity : 0;
            }
        }

        @keyframes ship-end {
            0% {
                transform : scale(1);
            }
            85% {
                transform : scale(1.25);
                opacity   : 1;
            }
            100% {
                transform : scale(1.25);
                opacity   : 0;
                bottom    : -96px;
            }
        }

        @keyframes endcard-ship {
            from {
                margin-top : 1024px;
            }
            to {
                margin-top : -200px;
            }
        }

        @keyframes endcard-fireworks {
            0% {
                background-size : 0;
            }
            75% {
                background-size : 0;
            }
            100% {
                background-size : 100%;
            }
        }

        @keyframes highscore {
            0% {
                transform : scale(1);
                opacity   : 0.60;
            }
            25% {
                transform : scale(1.25);
                opacity   : 0.75;
            }
            50% {
                transform : scale(1);
                opacity   : 0.60;
            }
            75% {
                transform : scale(0.75);
                opacity   : 0.45;
            }
            100% {
                transform : scale(1);
                opacity   : 0.60;
            }
        }

        @keyframes powerup {
            0% {
                transform : scale(1);
            }
            25% {
                transform : scale(0.75);
            }
            50% {
                transform : scale(1);
            }
            75% {
                transform : scale(1.25);
            }
            100% {
                transform : scale(1);
            }
        }

        @keyframes life {
            0% {
                transform : scaleY(1);
            }
            33% {
                transform : scaleY(0.6) scaleX(1);
            }
            66% {
                transform : scaleY(1) scaleX(0.6);
            }
            100% {
                transform : scaleY(1) scaleX(1);
            }
        }

        @keyframes asteroid {
            0% {
                transform : scale(1) rotate(0deg);
            }
            50% {
                transform : scale(1.25) rotate(180deg);
            }
            100% {
                transform : scale(1) rotate(360deg);
            }
        }

        @keyframes ligtning {
            75% {
                transform : scale(1);
                opacity   : 1;
            }
            100% {
                transform : scale(2);
                opacity   : 0;
            }
        }

        @keyframes ship-shield {
            90% {
                opacity : 1;
            }
            91% {
                opacity : 0;
            }
            92% {
                opacity : 1;
            }
            93% {
                opacity : 0;
            }
            94% {
                opacity : 1;
            }
            95% {
                opacity : 0;
            }
            96% {
                opacity : 1;
            }
            100% {
                opacity : 0;
            }
        }

        @keyframes powerup-burst {
            from {
                transform : scale(1);
                opacity   : 1;
            }
            to {
                transform : scale(3);
                opacity   : 0;
            }
        }

        @keyframes asteroid-burst {
            from {
                transform : scale(1) rotate(0deg);
                opacity   : 1;
            }
            to {
                transform : scale(1.75) rotate(15deg);
                opacity   : 0;
            }
        }

        @keyframes level-shadow {
            0% {
                box-shadow : 0 0 5px 2px #7bed9f;
            }
            20% {
                box-shadow : 0 0 5px 2px #ff6b81
            }
            40% {
                box-shadow : 0 0 5px 2px #70a1ff
            }
            60% {
                box-shadow : 0 0 5px 2px #eccc68
            }
            80% {
                box-shadow : 0 0 5px 2px #a29bfe;
            }
        }

        @keyframes level-color {
            0% {
                background-color : transparentize(#2ed573, .1);
            }
            20% {
                background-color : transparentize(#ff4757, .1);
            }
            40% {
                background-color : transparentize(#1e90ff, .1);
            }
            60% {
                background-color : transparentize(#ffa502, .1);
            }
            80% {
                background-color : transparentize(#6c5ce7, .1);
            }
        }
    }
</style>