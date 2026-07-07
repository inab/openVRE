#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');
const COPY_FILES = [
  ['js-cookie/src/js.cookie.js', 'js.cookie.min.js'],
  ['jquery.cookiebar/jquery.cookieBar.min.js', 'jquery-cookiebar/jquery.cookieBar.min.js'],
  ['jquery-blockui/jquery.blockUI.js', 'jquery.blockui.min.js'],
];

function nm(...parts) {
  return path.join(NM, ...parts);
}

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function copyFile(src, dest) {
  if (!fs.existsSync(src)) {
    console.warn(`  skip missing: ${path.relative(ROOT, src)}`);
    return false;
  }
  ensureDir(path.dirname(dest));
  fs.copyFileSync(src, dest);
  return true;
}

function copyPlugins() {
  console.log('Copying related assets to public/assets/global/plugins...');
  for (const [from, to] of COPY_FILES) {
    copyFile(nm(...from.split('/')), path.join(PLUGINS, to));
  }
  console.log('\nDone.');
}

copyPlugins();
