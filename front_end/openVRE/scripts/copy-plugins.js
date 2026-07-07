#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');
const COOKIE_DEST = path.join(PLUGINS, 'js.cookie.min.js');

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
  console.log('Copying js-cookie to public/assets/global/plugins...');
  copyFile(nm('js-cookie', 'src', 'js.cookie.js'), COOKIE_DEST);
  console.log('\nDone.');
}

copyPlugins();
