'use strict';
const fs = require('fs');
const bot = require('./lib/core/bot');
const db = require('./lib/core/mysql');
const logger = require('./lib/core/logger');

const configMap = {};

const getPlugin = name => {
    if (name.split('.').pop() === 'js') {
        if (fs.existsSync(`./Plugin/${name}`)) {
            return require(`./Plugin/${name}`);
        }
        return null;
    }
    return null;
};

const events = {
    add: (botId, name, config) => {
        const plugin = getPlugin(name);
        if (plugin && typeof plugin.init === 'function' && typeof plugin.config === 'function') {
            logger('info', `Bot-${botId}`, `[Plugin] load ${name}`);
          // 群聊消息
            if (typeof plugin.PublicMessage === 'function') {
                bot.bots[botId].addListener('PublicMessage', plugin.PublicMessage);
            }
          // 私聊消息
            if (typeof plugin.PrivateMessage === 'function') {
                bot.bots[botId].addListener('PrivateMessage', plugin.PrivateMessage);
            }
          // 切换房间
            if (typeof plugin.ChangeRoom === 'function') {
                bot.bots[botId].addListener('ChangeRoom', plugin.ChangeRoom);
            }
          // 进入房间
            if (typeof plugin.Join === 'function') {
                bot.bots[botId].addListener('Join', plugin.Join);
            }
          // 离开房间
            if (typeof plugin.Leave === 'function') {
                bot.bots[botId].addListener('Leave', plugin.Leave);
            }
            plugin.init(botId);
            plugin.config(config);
        }
    },
    remove: (botId, name) => {
        const plugin = getPlugin(name);
        if (plugin && typeof plugin.init === 'function') {
            logger('info', `Bot-${botId}`, `[Plugin] unload ${name}`);
          // 群聊消息
            if (typeof plugin.PublicMessage === 'function') {
                bot.bots[botId].removeListener('PublicMessage', plugin.PublicMessage);
            }
          // 私聊消息
            if (typeof plugin.PrivateMessage === 'function') {
                bot.bots[botId].removeListener('PrivateMessage', plugin.PrivateMessage);
            }
          // 切换房间
            if (typeof plugin.ChangeRoom === 'function') {
                bot.bots[botId].removeListener('ChangeRoom', plugin.ChangeRoom);
            }
          // 进入房间
            if (typeof plugin.Join === 'function') {
                bot.bots[botId].removeListener('Join', plugin.Join);
            }
          // 离开房间
            if (typeof plugin.Leave === 'function') {
                bot.bots[botId].removeListener('Leave', plugin.Leave);
            }
            plugin.init(botId);
        }
    },
};

const start = () => {
    setInterval(async() => {
        db.select('*')
        .from('bots')
        .where('end', new Date(), 'gt')
        .queryList()
        .then(e => {
            let botItem = {};
            Object.keys(e).forEach(v => {
                const item = e[v];
                botItem[item.id] = botItem;
                if (!bot.bots[item.id]) {
                    // Bot 不存在，创建 Bot
                    configMap[item.id] = {};
                    configMap[item.id].updated_at = item.updated_at;
                    configMap[item.id].end = item.end;
                    configMap[item.id].room = item.room;
                    configMap[item.id].username = item.username;
                    configMap[item.id].password = item.password;
                    configMap[item.id].plugins = item.plugins;

                    const plugins = [ ...JSON.parse(item.plugins) ];

                    logger('info', 'Main', `starting bot-${item.id}`);
                    bot.mgr.newBot(item.id, item.username, item.password, item.room);
                    plugins.forEach(e => {
                        events.add(item.id, e, null || JSON.parse(item.config)[e]);
                    });
                } else {
                    if (configMap[item.id].updated_at.toString() !== item.updated_at.toString()) {
                        configMap[item.id].updated_at = item.updated_at;
                        logger('info', `Bot-${item.id}`, `[ConfigUpdate] updated_at: ${item.updated_at}`);
                        // 配置更新
                        if (configMap[item.id].room !== item.room) {
                            logger('info', `Bot-${item.id}`, `[ConfigUpdate] room: ${item.room}`);
                          // 房间更新
                            configMap[item.id].room = item.room;
                            bot.bots[item.id].SwitchRoom(item.room);
                        }

                        if (configMap[item.id].username !== item.username || configMap[item.id].password !== item.password) {
                            logger('info', `Bot-${item.id}`, `[ConfigUpdate] username: ${item.username}`);
                          // 用户名/密码 更新
                            configMap[item.id].username = item.username;
                            configMap[item.id].password = item.password;
                            bot.bots[item.id].stop();
                            bot.mgr.newBot(item.id, item.username, item.password, item.room);
                        }

                        if (configMap[item.id].plugins !== item.plugins) {
                            configMap[item.id].plugins = item.plugins;
                            logger('info', `Bot-${item.id}`, `[ConfigUpdate] plugins: ${item.plugins}`);
                          // 插件更新
                            const botId = item.id;
                            const plugins_cache = JSON.parse(configMap[item.id].plugins);
                            const plugins_db = JSON.parse(item.plugins);
                            const diff_add = [ ... new Set([ ...plugins_db ].filter(x => !new Set(plugins_cache).has(x))) ];
                            const diff_less = [ ... new Set([ ...plugins_cache ].filter(x => !new Set(plugins_db).has(x))) ];
                            console.log(diff_add, diff_less);
                            diff_add.forEach(e => {
                                events.add(botId, e, null || JSON.parse(item.config)[e]);
                            });
                            diff_less.forEach(e => {
                                events.remove(botId, e);
                            });
                        }

                        if (configMap[item.id].config !== item.config) {
                            logger('info', `Bot-${item.id}`, `[ConfigUpdate] config: ${item.config}`);
                          // 插件配置更新
                            configMap[item.id].config = item.config;
                            const conf = JSON.parse(item.config);
                            JSON.parse(item.plugins).forEach(e => {
                                events.remove(item.id, e);
                                events.add(item.id, e, null || conf[e]);
                            });
                        }
                    }
                }
            });

        // 到期检测
        Object.keys(configMap).forEach(id => {
            if (!botItem[id] && configMap[id] !== undefined) {
              // 机器人到期
                logger('info', 'Main', `stopping bot-${id}`);
                bot.bots[id].stop();
                configMap[id] = undefined;
            }
          });
        botItem = {};
        })
      .catch(err => {
            console.log(err);
        });
    }, 5e3);
};

start();
logger('info', 'Main', 'started');
