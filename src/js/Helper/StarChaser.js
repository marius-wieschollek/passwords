import SettingsService from '@js/Services/SettingsService';

export default class StarChaser {

    constructor() {
        this._game = null;
        this.downKeyFunc = (e) => {this._keyDown(e);};
        this.upKeyFunc = (e) => {this._keyUp(e);};
        this.drawInterval = null;
        this.lightningInterval = null;
        this.lightningTimeout = null;
        this.shieldTimeout = null;
        this.clockTimeout = null;
        this.burstTimeout = null;
        this.shotInterval = null;
    }

    start(game) {
        this._game = game;
        document.addEventListener('keydown', this.downKeyFunc);
        document.addEventListener('keyup', this.upKeyFunc);
        this._resetGame();
    }

    end() {
        document.removeEventListener('keydown', this.downKeyFunc);
        document.removeEventListener('keyup', this.upKeyFunc);
        clearInterval(this.drawInterval);

        for(let i = 0; i < this._game.asteroids.length; i++) {
            let asteroid = this._game.asteroids[i];
            setTimeout(() => {this._destroyAsteroid(asteroid, false, false);}, i * 40);
        }
        for(let i = 0; i < this._game.powerups.length; i++) {
            let powerup = this._game.powerups[i];
            setTimeout(() => {this._destroyPowerUp(powerup);}, i * 20);
        }
        this._game.shots = [];
        clearInterval(this.drawInterval);
        clearInterval(this.lightningInterval);
        clearTimeout(this.lightningTimeout);
        clearInterval(this.shieldTimeout);
        clearInterval(this.clockTimeout);
        clearInterval(this.burstTimeout);
        clearInterval(this.shotInterval);
        this._checkHighScore();
    }

    static getNewGame() {
        return {
            settings : {},
            stats    : {
                lives    : 3,
                points   : 0,
                level    : 1,
                asteroids: 0,
                powerups : 0,
                highscore: false
            },
            status   : {
                canShoot     : true,
                shootTime    : 240,
                shotSpeed    : 10,
                maxAsteroids : 10,
                asteroidSpeed: 2,
                hitPoints    : 1000,
                isInvincible : false,
                isSlowMotion : false,
                maxPowerUps  : 1,
                starPoints   : 2500,
                boltPoints   : 250,
                shieldPoints : 250,
                lifePoints   : 500,
                clockPoints  : 500,
                levelMark    : 10000
            },
            ship     : {
                left  : 0,
                move  : 'none',
                speed : 15,
                size  : 96,
                shield: false
            },
            explosion: {
                enabled: false,
                left   : 250,
                top    : 250,
                radius : 0,
                size   : 0,
                speed  : 25,
                show   : true
            },
            exit     : () => {},
            asteroids: [],
            powerups : [],
            shots    : []
        };
    }

    _resetGame() {
        this._game.ship.left = window.innerWidth / 2 - this._game.ship.size / 2;
        this.drawInterval = setInterval(() => {this._drawFrame();}, 33);
        this.shotInterval = setInterval(() => {this._addShot(true);}, this._game.status.shootTime);
    }

    _keyDown(e) {
        if(e.ctrlKey) return;
        e.preventDefault();
        e.stopPropagation();
        if(e.keyCode === 37) {
            if(this._game.ship.left > 2) this._game.ship.move = 'left';
        } else if(e.keyCode === 39) {
            if(this._game.ship.left < window.innerWidth - 66) this._game.ship.move = 'right';
        } else if(e.keyCode === 39) {
            if(this._game.ship.left < window.innerWidth - 66) this._game.ship.move = 'right';
        } else if(e.keyCode === 32) {
            this._addShot();
        } else if(e.keyCode === 80 || e.keyCode === 27) {
            if(this.drawInterval) {
                clearInterval(this.drawInterval);
                this.drawInterval = null;
            } else if(e.keyCode === 27) {
                this._game.exit();
            } else {
                this.drawInterval = setInterval(() => {this._drawFrame();}, 33);
            }
        }
    }

    _keyUp(e) {
        e.preventDefault();
        if(e.keyCode === 37 && this._game.ship.move === 'left' || e.keyCode === 39 && this._game.ship.move === 'right') {
            this._game.ship.move = 'none';
        }
    }

    _drawFrame() {
        this._updateShipPosition();
        this._updateShotPositions();
        this._createNewObjects();
        this._updateAsteroidPositions();
        this._bombStep();
    }

    _updateShipPosition() {
        if(this._game.ship.move !== 'none') {
            if(this._game.ship.move === 'left') {
                this._game.ship.left -= this._game.ship.speed;
                if(this._game.ship.left <= 2) {
                    this._game.ship.left = 2;
                    this._game.ship.move = 'none';
                }
            } else {
                this._game.ship.left += this._game.ship.speed;
                if(this._game.ship.left >= window.innerWidth - 66) {
                    this._game.ship.left = window.innerWidth - 66;
                    this._game.ship.move = 'none';
                }
            }
        }
    }

    _updateAsteroidPositions() {
        for(let i = 0; i < this._game.powerups.length; i++) {
            let powerup = this._game.powerups[i];
            powerup.top += powerup.speed;
            this._checkForPowerUpCollision(powerup);
            if(powerup.top >= window.innerHeight + powerup.size) {
                this._game.powerups.splice(i, 1);
            }
        }
        for(let i = 0; i < this._game.asteroids.length; i++) {
            let asteroid = this._game.asteroids[i];

            asteroid.top += this._game.status.isSlowMotion ? 0.5:asteroid.speed;
            if(asteroid.top >= window.innerHeight + asteroid.size) {
                this._game.asteroids.splice(i, 1);
                this._removePoints(this._game.status.hitPoints / 4);
                continue;
            }
            if(!this._checkForShotHit(asteroid)) this._checkForAsteroidCollision(asteroid);
        }
    }

    _createNewObjects() {
        if(this._game.asteroids.length < this._game.status.maxAsteroids && Math.random() < 0.10) {
            this._game.asteroids.push(
                {
                    id   : Math.random() * 100000,
                    top  : -64,
                    size : 64,
                    left : this._getAsteroidStartPosition(),
                    speed: this._game.status.asteroidSpeed + Math.random() * 2,
                    hit  : false,
                    timer: null
                }
            );
        }
        if(this._game.powerups.length < this._game.status.maxPowerUps && Math.random() < 0.005) {
            let rand = Math.random(),
                type = 'star';

            if(rand > 0.35 && rand <= 0.5) {
                type = 'lightning';
            } else if(rand > 0.2 && rand <= 0.35) {
                type = 'shield';
            } else if(rand > 0.06 && rand <= 0.2) {
                type = 'clock';
            } else if(rand > 0.02 && rand <= 0.06) {
                type = 'bomb';
            } else if(rand <= 0.02) {
                type = 'life';
            }

            this._game.powerups.push(
                {
                    id   : Math.random() * 100000,
                    top  : -64,
                    size : 64,
                    left : Math.random() * (window.innerWidth - 68),
                    speed: this._game.status.asteroidSpeed + Math.random() * 2,
                    hit  : false,
                    timer: null,
                    type
                }
            );
        }
    }

    _getAsteroidStartPosition() {
        let pos = null;
        if(Math.random() < 0.85) {
            pos = this._game.ship.left - ((Math.random() - 0.5) * (window.innerWidth / 2));
        } else {
            pos = Math.random() * window.innerWidth;
        }

        if(pos < 34 || pos > window.innerWidth - 34) return this._getAsteroidStartPosition();
        return pos;
    }

    _removeAsteroid(id) {
        for(let i = 0; i < this._game.asteroids.length; i++) {
            if(this._game.asteroids[i].id === id) {
                this._game.asteroids.splice(i, 1);
                return;
            }
        }
    }

    _removePowerUp(id) {
        for(let i = 0; i < this._game.powerups.length; i++) {
            if(this._game.powerups[i].id === id) {
                this._game.powerups.splice(i, 1);
                return;
            }
        }
    }

    _updateShotPositions() {
        for(let i = 0; i < this._game.shots.length; i++) {
            let shot = this._game.shots[i];
            shot.bottom += shot.speed;
            if(shot.bottom >= window.innerHeight + 10) {
                this._game.shots.splice(i, 1);
            }
        }
    }

    _addShot(automatic = false) {
        if(!automatic && !this._game.status.canShoot || this.drawInterval === null) return;
        this._game.shots.push(
            {
                id    : Math.random() * 100000,
                left  : this._game.ship.left + 44,
                bottom: 80,
                speed : this._game.status.shotSpeed
            }
        );
        if(!automatic) {
            this._game.status.canShoot = false;
            setTimeout(() => { this._game.status.canShoot = true;}, this._game.status.shootTime);
        }
    }

    _checkForShotHit(asteroid) {
        if(asteroid.hit) return true;

        for(let i = 0; i < this._game.shots.length; i++) {
            let shot = this._game.shots[i],
                lMin = shot.left,
                lMax = lMin + 8,
                tMax = window.innerHeight - shot.bottom;

            if(!asteroid.hit &&
               (asteroid.top + 64 > tMax) &&
               (asteroid.left < lMin && asteroid.left + 64 > lMax)
            ) {
                this._destroyAsteroid(asteroid, true);
                this._game.shots.splice(i, 1);
                return true;
            }
        }
        return false;
    }

    _checkForAsteroidCollision(asteroid) {
        if(this._game.status.isInvincible || asteroid.hit) return false;
        let offset = this._game.ship.shield ? 20:0;

        if(this._checkForCollision(asteroid, offset)) {
            this._destroyAsteroid(asteroid, this._game.ship.shield, true);

            if(!this._game.ship.shield) {
                this._game.stats.lives--;

                if(this._game.stats.lives === 0) this.end();
                this._game.status.isInvincible = true;
                setTimeout(() => {this._game.status.isInvincible = false;}, 200);
            }

            return true;
        }

        return false;
    }

    _checkForPowerUpCollision(powerup) {
        if(powerup.hit) return false;

        if(this._checkForCollision(powerup, 20)) {
            this._destroyPowerUp(powerup);
            this._game.stats.powerups++;
            if(powerup.type === 'star') {
                this._addPoints(this._game.status.starPoints);
            } else if(powerup.type === 'life') {
                this._game.stats.lives++;
                this._addPoints(this._game.status.lifePoints);
            } else if(powerup.type === 'shield') {
                this._startShield();
            } else if(powerup.type === 'lightning') {
                this._startLightning();
            } else if(powerup.type === 'clock') {
                this._startClock();
            } else if(powerup.type === 'bomb') {
                this._igniteBomb(powerup);
            }
        }
    }

    _checkForCollision(object, offset = 0) {
        let tMin  = window.innerHeight - 110,
            tMax  = tMin + 60 + offset,
            lSMin = this._game.ship.left - offset,
            lSMax = this._game.ship.left + offset + this._game.ship.size,
            pos   = object.left + object.size / 2;

        return object.top > tMin && object.top < tMax && pos >= lSMin && pos <= lSMax;
    }

    _destroyAsteroid(asteroid, addPoints = false, removePoints = false) {
        asteroid.hit = true;
        asteroid.speed = 2;
        asteroid.timer = setTimeout(() => {
            this._removeAsteroid(asteroid.id);
        }, 300);
        if(addPoints) {
            this._game.stats.asteroids++;
            this._addPoints(this._game.status.hitPoints);
        } else if(removePoints) {
            this._removePoints(this._game.status.hitPoints / 4);
        }
    }

    _destroyPowerUp(powerup) {
        powerup.hit = true;
        powerup.speed = 0;
        powerup.timer = setTimeout(() => {
            this._removePowerUp(powerup.id);
        }, 500);
    }

    _addPoints(points) {
        this._game.stats.points += Math.round(points);
        if(this._game.stats.points >= this._game.status.levelMark) {
            this._game.stats.level++;
            this._game.status.maxAsteroids++;

            this._game.status.hitPoints *= 1.1;
            this._game.status.shieldPoints *= 1.2;
            this._game.status.boltPoints *= 1.2;
            this._game.status.clockPoints *= 1.2;
            this._game.status.lifePoints *= 1.2;
            this._game.status.starPoints *= 1.2;
            this._game.status.levelMark *= 1.2;

            if(this._game.stats.level % 10 === 0) {
                this._game.ship.speed++;
                this._game.status.shotSpeed++;
                this._game.status.asteroidSpeed += 1;
                if(this._game.status.shootTime > 160) {
                    this._game.status.shootTime -= 5;
                }

                if(this._game.stats.level % 20 === 0) {
                    this._game.status.maxPowerUps++;
                }
            }
        }
    }

    _removePoints(points) {
        this._game.stats.points -= Math.round(points);
        if(this._game.stats.points < 0) {
            this._game.stats.points = 0;
            this.end();
        }
    }

    _checkHighScore() {
        let current = SettingsService.get('client.starchaser.highscore');

        if(this._game.stats.points > current) {
            this._game.stats.highscore = true;
            SettingsService.set('client.starchaser.highscore', this._game.stats.points);
        }
    }

    _startLightning() {
        if(this.lightningTimeout) this._stopLightning();
        this._addPoints(this._game.status.boltPoints);
        this._game.ship.speed *= 2;
        this.lightningInterval = setInterval(() => this._addShot(true), this._game.status.shootTime / 3);
        this.lightningTimeout = setInterval(() => this._stopLightning(true), 8000);
        clearInterval(this.shotInterval);
    }

    _stopLightning(startShots) {
        clearInterval(this.lightningInterval);
        clearTimeout(this.lightningTimeout);
        this.lightningInterval = null;
        this.lightningTimeout = null;
        this._game.ship.speed /= 2;
        if(startShots) this.shotInterval = setInterval(() => {this._addShot(true);}, this._game.status.shootTime);
    }

    _startShield() {
        if(this.shieldTimeout) {
            this._stopShield();
            let ship = document.getElementById('ship');
            ship.classList.remove('shield');
            this.shieldTimeout = setTimeout(() => { ship.classList.add('shield'); }, 10);
        }
        this._addPoints(this._game.status.shieldPoints);
        this._game.ship.shield = true;
        this.shieldTimeout = setTimeout(() => { this._stopShield(); }, 10000);
    }

    _stopShield() {
        clearTimeout(this.shieldTimeout);
        this.shieldTimeout = false;
        this._game.ship.shield = false;
    }

    _startClock() {
        if(this.clockTimeout) this._stopClock();
        this._addPoints(this._game.status.clockPoints);
        this._game.status.isSlowMotion = true;
        this.clockPoints = setTimeout(() => { this._stopClock(); }, 10000);
    }

    _stopClock() {
        clearTimeout(this.clockTimeout);
        this.clockTimeout = false;
        this._game.status.isSlowMotion = false;
    }

    _igniteBomb(powerup) {
        if(this.burstTimeout) clearTimeout(this.burstTimeout);
        for(let i = 0; i < this._game.asteroids.length; i++) {
            this._game.asteroids[i].speed = 2;
        }

        this._game.explosion.size = 0;
        this._game.explosion.radius = 0;
        this._game.explosion.show = true;
        this._game.explosion.top = powerup.top + powerup.size / 2;
        this._game.explosion.left = powerup.left + powerup.size / 2;
        this._game.explosion.enabled = true;
    }

    _bombStep() {
        let explosion = this._game.explosion;
        if(!explosion.enabled || !explosion.show) return;

        explosion.radius += explosion.speed;

        for(let i = 0; i < this._game.asteroids.length; i++) {
            let asteroid = this._game.asteroids[i];
            if(asteroid.hit) continue;

            let lPos     = asteroid.left + asteroid.size / 2,
                tPos     = asteroid.top + asteroid.size / 2,
                distance = Math.sqrt(Math.pow(lPos - explosion.left, 2) + Math.pow(tPos - explosion.top, 2));

            if(explosion.radius >= distance) this._destroyAsteroid(asteroid, true);
        }

        let tLeft  = Math.sqrt(Math.pow(0 - explosion.left, 2) + Math.pow(0 - explosion.top, 2)),
            tRight = Math.sqrt(Math.pow(window.innerWidth - explosion.left, 2) + Math.pow(0 - explosion.top, 2));
        if(tLeft <= explosion.radius && tRight <= explosion.radius) {
            explosion.show = false;

            this.burstTimeout = setTimeout(() => {
                explosion.enabled = false;
                this.burstTimeout = null;
            }, 2000);
        }
    }
}