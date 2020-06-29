'use strict';
const time = () => {
  const t = new Date();
  return `${t.getFullYear()}-${t.getMonth() + 1}-${t.getDate()} ${t.getHours()}:${t.getMinutes()}:${t.getSeconds()}.${t.getMilliseconds()}`;
};

module.exports = (level, thread, msg) => {
  console.log(`[${time()}][${level.toUpperCase()}][${thread}] ${msg}`);
};
