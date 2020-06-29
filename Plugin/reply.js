'use strict';
const bots = require('../lib/core/bot').bots;
const conf = {
    botId: 0,
    bot: null,
    reply: null,
    color: null,
};

module.exports = {
    init: botId => {
        conf.bot = bots[botId];
        conf.botId = botId;
    },
    config: config => {
        conf.reply = null || config.reply;
        conf.color = '66ccff' || config.color;
    },
    PublicMessage: e => {
        if (conf.reply[e.msg]) {
            conf.bot.sendPublicMessage(conf.reply[e.msg], conf.color);
        }
    },
};
