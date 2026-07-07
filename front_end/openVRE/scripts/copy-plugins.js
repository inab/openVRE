#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');
const COPY_FILES = [
  // jquery
  ['jquery/dist/jquery.min.js', 'jquery.min.js'],
  // js-cookie
  ['js-cookie/src/js.cookie.js', 'js.cookie.min.js'],
  // jquery-cookiebar
  ['jquery.cookiebar/jquery.cookieBar.min.js', 'jquery-cookiebar/jquery.cookieBar.min.js'],
  // jquery-blockui
  ['jquery-blockui/jquery.blockUI.js', 'jquery.blockui.min.js'],
  // clipboardjs
  ['clipboard/dist/clipboard.min.js', 'clipboardjs/clipboard.min.js'],
  // jquery-validation
  ['jquery-validation/dist/jquery.validate.js', 'jquery-validation/js/jquery.validate.js'],
  ['jquery-validation/dist/jquery.validate.min.js', 'jquery-validation/js/jquery.validate.min.js'],
  ['jquery-validation/dist/additional-methods.js', 'jquery-validation/js/additional-methods.js'],
  ['jquery-validation/dist/additional-methods.min.js', 'jquery-validation/js/additional-methods.min.js'],
];
const COPY_DIRS = [
  ['flot', 'flot', [
    'examples',
    '.travis.yml',
    'API.md',
    'CONTRIBUTING.md',
    'LICENSE.txt',
    'FAQ.md',
    'Makefile',
    'NEWS.md',
    'PLUGINS.md',
    'README.md',
    'component.json',
    'excanvas.js',
    'excanvas.min.js',
    'flot.jquery.json',
    'jquery.js',
    'package.json',
  ]],
  ['fancybox/dist', 'fancybox/source', [
    'helpers/css',
    'helpers/js',
    'helpers/img',
    'helpers/scss',
    'img',
    'css',
    'js',
    'scss'
  ]],
  ['select2/dist/js', 'select2/js', ['i18n']],
  ['select2/dist/css', 'select2/css'],
  ['jquery-validation/dist/localization', 'jquery-validation/js/localization']
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

function copyDir(src, dest, excluded = []) {
  if (!fs.existsSync(src)) {
    console.warn(`  skip missing: ${path.relative(ROOT, src)}`);
    return false;
  }
  ensureDir(path.dirname(dest));
  const excludedPaths = new Set(excluded.map((name) => path.join(src, name)));
  fs.cpSync(src, dest, {
    recursive: true,
    filter(currentSrc) {
      for (const excludedPath of excludedPaths) {
        if (currentSrc === excludedPath || currentSrc.startsWith(`${excludedPath}${path.sep}`)) {
          return false;
        }
      }
      return true;
    },
  });
  return true;
}

function copyPlugins() {
  console.log('Copying related assets to public/assets/global/plugins...');
  for (const [from, to] of COPY_FILES) {
    copyFile(nm(...from.split('/')), path.join(PLUGINS, to));
  }
  for (const [from, to, excluded] of COPY_DIRS) {
    copyDir(nm(...from.split('/')), path.join(PLUGINS, to), excluded);
  }
  console.log('\nDone.');
}

copyPlugins();
