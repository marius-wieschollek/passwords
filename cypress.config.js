const { defineConfig } = require('cypress');

module.exports = defineConfig(
    {
        e2e: {
            baseUrl       : 'https://localhost',
            viewportHeight: 800,
            viewportWidth : 1280
        },
        env: {
            browserPermissions: {
                notifications: "block",
            }
        }
    }
);
