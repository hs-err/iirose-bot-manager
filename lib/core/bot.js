'use strict';
const bot = require('../Bot/index');
const logger = require('./logger');

const botMap = {};

const bots = {
  newBot: (id, user, password, room) => {
    botMap[id] = new bot(user, password, room);
    botMap[id].setMaxListeners(299792458);

    // Bot启动完成
    botMap[id].on('Ready', () => {
      logger('info', `Bot-${id}`, 'Ready');
    });

    // 群聊消息
    botMap[id].on('PublicMessage', e => {
      logger('info', `Bot-${id}`, `[PublicMessage] ${e.user.name} said ${e.msg}`);
    });

    // 私聊消息
    botMap[id].on('PrivateMessage', e => {
      logger('info', `Bot-${id}`, `[PrivateMessage] ${e.user.name} said ${e.msg}`);
    });

    // 切换房间
    botMap[id].on('ChangeRoom', e => {
      logger('info', `Bot-${id}`, `[ChangeRoom] ${e.user.name} move to ${e.room}`);
    });

    // 进入房间
    botMap[id].on('Join', e => {
      logger('info', `Bot-${id}`, `[Join] ${e.user.name}`);
    });

    // 离开房间
    botMap[id].on('Leave', e => {
      logger('info', `Bot-${id}`, `[Leave] ${e.user.name}`);
    });

    botMap[id].on('UserProfile', e => {
      logger('info', `Bot-${id}`, `[UserProfile] ${JSON.stringify(e)}`);
    });
  },
  addEventListener: (botId, event, fun) => {
    botMap[botId].addListener(event, fun);
  },
  delEventListener: (botId, event, fun) => {
    botMap[botId].removeListener(event, fun);
  },
};

logger('info', 'Bot-Manager', 'started');

module.exports.mgr = bots;
module.exports.bots = botMap;
