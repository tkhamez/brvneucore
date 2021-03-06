
import Vue from 'vue';

Vue.mixin({
    data: function () {
        return {
            themes: [
                'Basic',
                'Cerulean',
                'Cosmo',
                'Cyborg',
                'Darkly',
                'Flatly',
                'Journal',
                'Litera',
                'Lumen',
                'Lux',
                'Materia',
                'Minty',
                'Pulse',
                'Sandstone',
                'Simplex',
                'Sketchy',
                'Slate',
                'Solar',
                'Spacelab',
                'Superhero',
                'United',
                'Yeti',
            ],

            messages: {
                errorMissingRequiredGroup: 'This player is not a member of the required group(s).'
            }
        }
    },

    methods: {
        ajaxLoading: function(status) {
            if (status) {
                this.$root.loadingCount ++;
            } else {
                this.$root.loadingCount --;
            }
        },

        /**
         * @param {string} text
         * @param {string} [type] One of: error, warning, info or success
         * @param {number} [timeout]
         */
        message: function(text, type, timeout) {
            type = type || 'info';
            switch (type) {
                case 'error':
                case 'info':
                case 'warning':
                    type = type === 'error' ? 'danger' : type;
                    timeout = timeout || null;
                    break;
                default: // success
                    timeout = timeout || 1500;
                    break;
            }
            this.$root.$emit('message', text, type, timeout);
        },

        showCharacters: function(playerId) {
            this.$root.$emit('showCharacters', playerId);
        },

        hasRole: function(name, player) {
            player = player || this.$root.player;
            if (! player) {
                return false;
            }
            return player.roles.indexOf(name) !== -1;
        },

        hasAnyRole: function(names) {
            for (const name of names) {
                if (this.hasRole(name)) {
                    return true;
                }
            }
            return false;
        },

        /**
         * @param {Date} date
         * @returns {string}
         */
        formatDate: function(date) {
            let str = date.toISOString();
            str = str.replace('T', ' ');
            str = str.replace('.000Z', '');
            return str.substr(0, str.length - 3);
        },

        characterPortrait(id, size) {
            if (this.$root.settings.esiDataSource === 'singularity') {
                // there are no character images on Sisi at the moment.
                return '/static/portrait_32.jpg';
            }
            return `https://images.evetech.net/characters/${id}/portrait?size=${size}&tenant=tranquility`;
        },

        buildCharacterMovements(data) {
            const movements = [];
            for (const removed of data.removedCharacters) {
                if (removed.reason === 'moved') {
                    removed.reason = 'removed';
                    removed.playerName = removed.newPlayerName;
                    removed.playerId = removed.newPlayerId;
                }
                movements.push(removed);
            }
            for (const incoming of data.incomingCharacters) {
                if (incoming.reason === 'moved') {
                    incoming.reason = 'incoming';
                    incoming.playerName = incoming.player.name;
                    incoming.playerId = incoming.player.id;
                }
                movements.push(incoming);
            }
            return movements.sort((a, b) => a.removedDate - b.removedDate);
        },
    }
});
