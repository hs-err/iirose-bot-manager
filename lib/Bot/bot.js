'use strict';
const iirose = require('iirose-bot');
const bot = new iirose.IIRoseBot({
  username: process.env.user,
  password: process.env.pass,
  roomId: process.env.room,
});

const send = (type, subtype, msg) => {
  process.send(JSON.stringify({
    type,
    sType: subtype,
    data: msg,
  }));
};

// Bot启动
bot.on(iirose.ClientConnectedEvent, () => {
  setTimeout(() => {
    send('event', 'Ready', null);
  }, 500);
});

// 群聊消息
bot.on(iirose.PublicMessageEvent, v => {
  const e = {
    user: {
      uid: v.message.user.id,
      name: v.message.user.username,
      gender: v.message.user.gender,
    },
    msg: v.message.content,
    color: v.message.color,
  };
  send('event', 'PublicMessage', e);
});

// 私聊消息
bot.on(iirose.PrivateMessageEvent, v => {
  const e = {
    user: {
      uid: v.message.user.id,
      name: v.message.user.username,
      gender: v.message.user.gender,
    },
    msg: v.message.content,
    color: v.message.color,
  };
  send('event', 'PrivateMessage', e);
});

// 切换房间
bot.on(iirose.UserChangeRoomEvent, v => {
  const e = {
    user: {
      uid: v.user.id,
      name: v.user.username,
      gender: v.user.gender,
    },
    room: v.targetRoomId,
  };
  send('event', 'ChangeRoom', e);
});

// 进入房间
bot.on(iirose.UserJoinEvent, v => {
  const e = {
    user: {
      uid: v.user.id,
      name: v.user.username,
      gender: v.user.gender,
    },
  };
  send('event', 'Join', e);
});

// 离开房间
bot.on(iirose.UserLeaveEvent, v => {
  const e = {
    user: {
      uid: v.user.id,
      name: v.user.username,
      gender: v.user.gender,
    },
  };
  send('event', 'Leave', e);
});

bot.start();

process.on('message', async data => {
  const msg = JSON.parse(data);
  if (msg.type === 'PublicMessage') {
    // 发送群聊消息
    bot.createMessage({
      color: msg.data.color,
      content: msg.data.msg,
    });
  } else if (msg.type === 'PrivateMessage') {
    // 发送私聊消息
    bot.sendPm({
      userId: msg.data.uid,
      content: msg.data.msg,
      color: msg.data.color,
    });
  } else if (msg.type === 'Like') {
    // 点赞
    bot.likeUser(msg.user);
  } else if (msg.type === 'Follow') {
    // 关注
    bot.followUser(msg.user);
  } else if (msg.type === 'UserProfile') {
    // 获取用户信息
    send('event', 'UserProfile', (await bot.getUserProfile(msg.data.user)));
  } else if (msg.type === 'SwitchRoom') {
    bot.switchRoom({
      targetRoomId: msg.room,
    });
  }
});
