'use strict';
const p = require('child_process');
const md5 = require('md5');
const EventEmitter = require('events').EventEmitter;
const util = require('util');

class Bot {
  constructor(user, passwd, room) {
    this.autoRestart = true;
    this.user = user;
    this.pass = md5(passwd);
    this.room = room;
    this.init();
  }

  init() {
    this.bot = p.fork('./lib/Bot/bot.js', [], {
      env: {
        user: this.user,
        pass: this.pass,
        room: this.room,
      },
    });

    this.bot.on('exit', (code, sign) => {
      this.emit('stop', [ code, sign ]);
      this.bot = null;
      if (this.autoRestart) {
        this.init();
      }
    });

    this.bot.on('message', e => {
      const msg = JSON.parse(e);
      if (msg.type === 'event') {
        this.emit(msg.sType, msg.data);
      }
    });
  }

  send(type, data) {
    this.bot.send(JSON.stringify({
      type,
      data,
    }));
  }

  stop() {
    this.autoRestart = false;
    this.bot.kill('SIGTERM');
  }

  sendPublicMessage(msg, color) {
    this.send('PublicMessage', {
      msg,
      color,
    });
  }

  sendPrivateMessage(uid, msg, color) {
    this.send('PrivateMessage', {
      uid,
      msg,
      color,
    });
  }

  SwitchRoom(room) {
    this.send('SwitchRoom', {
      room,
    });
  }

  like(user) {
    this.send('Like', {
      user,
    });
  }

  follow(user) {
    this.send('Follow', {
      user,
    });
  }
}

util.inherits(Bot, EventEmitter);

module.exports = Bot;
