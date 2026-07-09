#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');

const FANCYBOX_VERSION = '2.1.5';
const FANCYBOX_GITHUB = `https://raw.githubusercontent.com/fancyapps/fancybox/v${FANCYBOX_VERSION}`;
const FANCYBOX_DIRS = ['source', 'lib'];
const FANCYBOX_ROOT_FILES = ['README.md', 'CHANGELOG.md'];
const COPY_FILES = [
  // bootstrap
  ['bootstrap/dist/js/bootstrap.js', 'bootstrap/js/bootstrap.js'],
  ['bootstrap/dist/js/bootstrap.min.js', 'bootstrap/js/bootstrap.min.js'],
  // jquery
  ['jquery/dist/jquery.min.js', 'jquery.min.js'],
  // js-cookie
  ['js-cookie/src/js.cookie.js', 'js.cookie.min.js'],
  // jquery-cookiebar
  ['jquery.cookiebar/jquery.cookieBar.min.js', 'jquery-cookiebar/jquery.cookieBar.min.js'],
  ['jquery.cookiebar/README.md', 'jquery-cookiebar/README.md'],
  // jquery-blockui
  ['jquery-blockui/jquery.blockUI.js', 'jquery.blockui.min.js'],
  // clipboardjs
  ['clipboard/dist/clipboard.min.js', 'clipboardjs/clipboard.min.js'],
  // jquery-validation
  ['jquery-validation/dist/jquery.validate.js', 'jquery-validation/js/jquery.validate.js'],
  ['jquery-validation/dist/additional-methods.js', 'jquery-validation/js/additional-methods.js'],
  ['jquery-validation/README.md', 'jquery-validation/README.md'],
  // bootstrap-switch
  ['bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.css', 'bootstrap-switch/css/bootstrap-switch.css'],
  ['bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css', 'bootstrap-switch/css/bootstrap-switch.min.css'],
  ['bootstrap-switch/LICENSE', 'bootstrap-switch/LICENSE'],
  ['bootstrap-switch/README.md', 'bootstrap-switch/README.md'],
];
const COPY_DIRS = [
  ['bootstrap/dist/css', 'bootstrap/css', [
    'bootstrap-theme.css',
    'bootstrap-theme.css.map',
    'bootstrap-theme.min.css',
    'bootstrap-theme.min.css.map',
    'bootstrap.css.map',
    'bootstrap.min.css.map',
  ]],
  ['bootstrap/dist/fonts', 'bootstrap/fonts/bootstrap'],
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
  ['select2/dist/js', 'select2/js'],
  ['select2/dist/css', 'select2/css'],
  ['jquery-validation/dist/localization', 'jquery-validation/js/localization'],
  ['bootstrap-switch/dist/js', 'bootstrap-switch/js'],
  ['font-awesome/css', 'font-awesome/css'],
  ['font-awesome/fonts', 'font-awesome/fonts'],
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

function fetchJson(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { headers: { 'User-Agent': 'openvre-copy-plugins' } }, (response) => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        fetchJson(response.headers.location).then(resolve).catch(reject);
        return;
      }
      if (response.statusCode !== 200) {
        reject(new Error(`HTTP ${response.statusCode} for ${url}`));
        return;
      }
      const chunks = [];
      response.on('data', (chunk) => chunks.push(chunk));
      response.on('end', () => {
        try {
          resolve(JSON.parse(Buffer.concat(chunks).toString('utf8')));
        } catch (err) {
          reject(err);
        }
      });
      response.on('error', reject);
    }).on('error', reject);
  });
}

function downloadFile(url, dest) {
  return new Promise((resolve, reject) => {
    ensureDir(path.dirname(dest));
    const client = url.startsWith('https') ? https : http;
    const request = client.get(url, (response) => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        downloadFile(response.headers.location, dest).then(resolve).catch(reject);
        return;
      }
      if (response.statusCode !== 200) {
        reject(new Error(`HTTP ${response.statusCode} for ${url}`));
        return;
      }
      const file = fs.createWriteStream(dest);
      response.pipe(file);
      file.on('finish', () => file.close(() => resolve(true)));
      file.on('error', reject);
    });
    request.on('error', reject);
  });
}

async function listFancyboxFiles() {
  const treeUrl = `https://api.github.com/repos/fancyapps/fancybox/git/trees/v${FANCYBOX_VERSION}?recursive=1`;
  const { tree } = await fetchJson(treeUrl);
  const dirPrefixes = FANCYBOX_DIRS.map((dir) => `${dir}/`);
  const files = tree
    .filter((entry) => entry.type === 'blob')
    .map((entry) => entry.path)
    .filter((filePath) => dirPrefixes.some((prefix) => filePath.startsWith(prefix)));
  return [...FANCYBOX_ROOT_FILES, ...files.sort()];
}

async function downloadFancybox() {
  const destRoot = path.join(PLUGINS, 'fancybox');
  if (fs.existsSync(destRoot)) {
    fs.rmSync(destRoot, { recursive: true, force: true });
  }

  console.log(`Downloading fancybox ${FANCYBOX_VERSION} from GitHub...`);
  const files = await listFancyboxFiles();
  for (const file of files) {
    const url = `${FANCYBOX_GITHUB}/${file}`;
    const dest = path.join(destRoot, file);
    try {
      await downloadFile(url, dest);
      console.log(`  ${file}`);
    } catch (err) {
      console.warn(`  skip failed: ${file} (${err.message})`);
    }
  }
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

async function copyPlugins() {
  console.log('Copying related assets to public/assets/global/plugins...');
  for (const [from, to] of COPY_FILES) {
    copyFile(nm(...from.split('/')), path.join(PLUGINS, to));
  }
  for (const [from, to, excluded] of COPY_DIRS) {
    copyDir(nm(...from.split('/')), path.join(PLUGINS, to), excluded);
  }
  await downloadFancybox();
  console.log('\nDone.');
}

copyPlugins().catch((err) => {
  console.error(err);
  process.exit(1);
});
