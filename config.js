'use strict';
const fs = require('fs');
const ini = fs.readFileSync('./.env').toString();
const config = {};

ini.split('\n').forEach(e => {
    if (e.indexOf('=') !== -1) {
        const tmp = e.split('=');
        const key = tmp[0];
        let value = tmp[1].replace('\r', '');

        if (value === 'null') {
            value = null;
        }

        config[key] = value;
    }
});

module.exports = config;
