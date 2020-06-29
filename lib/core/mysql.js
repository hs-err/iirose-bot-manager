'use strict';
const mysql = require('ali-mysql-client');
const config = require('../../config');
const db = new mysql({
  host: config.DB_HOST,
  port: config.DB_PORT,
  user: config.DB_USERNAME,
  password: config.DB_PASSWORD,
  database: config.DB_DATABASE,
});

module.exports = db;
