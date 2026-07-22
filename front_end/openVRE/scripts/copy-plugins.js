#!/usr/bin/env node
'use strict';

/**
 * Copy frontend plugin assets into public/assets/global/plugins/.
 *
 * Invoked by: npm postinstall, npm run copy-plugins
 *
 * Sources:
 *   - npm node_modules (COPY_FILES, COPY_DIRS, and custom tasks)
 *   - CDN / GitHub (REMOTE_DOWNLOADS)
 *   - plugin-overlays/ (vendored or customized files)
 */

const fs = require('fs');
const path = require('path');
const os = require('os');
const { execSync } = require('child_process');
const https = require('https');
const http = require('http');

// --- Paths ---

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');
const PLUGIN_OVERLAYS = path.join(ROOT, 'plugin-overlays');

const fromNpm = (...parts) => path.join(NM, ...parts);
const toPlugins = (...parts) => path.join(PLUGINS, ...parts);
const fromOverlay = (...parts) => path.join(PLUGIN_OVERLAYS, ...parts);
const splitPosix = (p) => p.split('/');

const log = {
  step: (msg) => console.log(msg),
  copied: (rel) => console.log(`  ${rel}`),
  skip: (msg) => console.warn(`  skip ${msg}`),
  fail: (rel, err) => console.warn(`  skip failed: ${rel} (${err.message})`),
};

// --- npm copy manifest ---

const COPY_FILES = [
  // bootstrap
  ['bootstrap/dist/js/bootstrap.js', 'bootstrap/js/bootstrap.js'],
  ['bootstrap/dist/js/bootstrap.min.js', 'bootstrap/js/bootstrap.min.js'],

  // jquery
  ['jquery/dist/jquery.min.js', 'jquery.min.js'],

  // jquery-cookiebar
  ['jquery.cookiebar/jquery.cookieBar.min.js', 'jquery-cookiebar/jquery.cookieBar.min.js'],
  ['jquery.cookiebar/README.md', 'jquery-cookiebar/README.md'],
  ['jquery.cookiebar/license.txt', 'jquery-cookiebar/license.txt'],

  // jquery-blockui
  ['jquery-blockui/jquery.blockUI.js', 'jquery.blockui.min.js'],

  // clipboardjs
  ['clipboard/dist/clipboard.min.js', 'clipboardjs/clipboard.min.js'],

  // jquery-validation
  ['jquery-validation/dist/jquery.validate.js', 'jquery-validation/js/jquery.validate.js'],
  ['jquery-validation/dist/additional-methods.js', 'jquery-validation/js/additional-methods.js'],
  ['jquery-validation/README.md', 'jquery-validation/README.md'],

  // easy-pie-chart (jquery-easypiechart)
  ['easy-pie-chart/dist/jquery.easypiechart.js', 'jquery-easypiechart/jquery.easypiechart.js'],
  ['easy-pie-chart/dist/jquery.easypiechart.min.js', 'jquery-easypiechart/jquery.easypiechart.min.js'],
  ['easy-pie-chart/dist/angular.easypiechart.js', 'jquery-easypiechart/angular.easypiechart.js'],
  ['easy-pie-chart/dist/angular.easypiechart.min.js', 'jquery-easypiechart/angular.easypiechart.min.js'],
  ['easy-pie-chart/LICENSE', 'jquery-easypiechart/LICENSE'],
  ['easy-pie-chart/Readme.md', 'jquery-easypiechart/Readme.md'],

  // progress-tracker (CSS-only; compiled dist from npm)
  ['progress-tracker/app/styles/progress-tracker.css', 'progress-tracker/progress-tracker.css'],
  ['progress-tracker/favicon.ico', 'progress-tracker/favicon.ico'],

  // bootstrap-switch
  ['bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.css', 'bootstrap-switch/css/bootstrap-switch.css'],
  ['bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css', 'bootstrap-switch/css/bootstrap-switch.min.css'],
  ['bootstrap-switch/LICENSE', 'bootstrap-switch/LICENSE'],
  ['bootstrap-switch/README.md', 'bootstrap-switch/README.md'],

  // typeahead.js
  ['typeahead.js/dist/typeahead.bundle.min.js', 'typeahead/typeahead.bundle.min.js'],
  ['typeahead.js/LICENSE', 'typeahead/LICENSE'],
  ['handlebars/dist/handlebars.min.js', 'typeahead/handlebars.min.js'],

  // bootstrap-fileinput (jasny-bootstrap)
  ['jasny-bootstrap/js/fileinput.js', 'bootstrap-fileinput/bootstrap-fileinput.js'],

  // select2 (js/css dirs via COPY_DIRS)
  ['select2/README.md', 'select2/README.md'],
  ['select2-bootstrap-theme/dist/select2-bootstrap.min.css', 'select2/css/select2-bootstrap.min.css'],
  ['select2-bootstrap-theme/src/select2-bootstrap.scss', 'select2/sass/select2-bootstrap.min.scss'],

  // simple-line-icons (css/fonts via copySimpleLineIcons; legacy extras from webfont package)
  ['simple-line-icons-webfont/License.txt', 'simple-line-icons/License.txt'],
  ['simple-line-icons-webfont/Readme.txt', 'simple-line-icons/Readme.txt'],
  ['simple-line-icons-webfont/icons-lte-ie7.js', 'simple-line-icons/icons-lte-ie7.js'],
  ['simple-line-icons-webfont/fonts/Simple-Line-Icons.dev.svg', 'simple-line-icons/fonts/Simple-Line-Icons.dev.svg'],

  // datatables — full tree in plugin-overlays/datatables/ (except Sorting icons.psd)
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
  ['counterup', 'counterup', [
    'package.json',
    '.npmignore',
    'counterup.jquery.json',
  ]],
];

// --- Remote downloads ---
//
// Assets not available (or not wanted) from npm.
// type: 'cdn'    — download explicit files from a versioned base URL
// type: 'github' — download folders from a tagged GitHub repo via the API

const REMOTE_DOWNLOADS = [
  {
    // LICENSE.md is not copied by the select2 npm package layout used here.
    type: 'cdn',
    name: 'select2-license',
    version: '4.0.3',
    base: 'https://raw.githubusercontent.com/select2/select2/4.0.3',
    files: [
      ['LICENSE.md', 'select2/LICENSE.md'],
    ],
  },
  {
    // Full source/lib tree; npm fancybox package layout differs from legacy layout.
    type: 'github',
    name: 'fancybox',
    version: '2.1.5',
    repo: 'fancyapps/fancybox',
    ref: 'v2.1.5',
    base: 'https://raw.githubusercontent.com/fancyapps/fancybox/v2.1.5',
    dest: 'fancybox',
    clean: true,
    dirs: ['source', 'lib'],
    rootFiles: ['README.md', 'CHANGELOG.md'],
  },
  {
    // Not an npm dependency; pinned GitHub release.
    type: 'github',
    name: 'jquery-knob',
    version: '1.2.8',
    repo: 'aterrien/jQuery-Knob',
    ref: '1.2.8',
    base: 'https://raw.githubusercontent.com/aterrien/jQuery-Knob/1.2.8',
    dest: 'jquery-knob',
    clean: true,
    dirs: ['js'],
    rootFiles: ['LICENSE', 'README.md', 'knob.jquery.json'],
  },
  {
    // Not an npm dependency; pinned GitHub release.
    type: 'github',
    name: 'jquery-slimscroll',
    version: '1.3.2',
    repo: 'rochal/jQuery-slimScroll',
    ref: 'v1.3.2',
    base: 'https://raw.githubusercontent.com/rochal/jQuery-slimScroll/v1.3.2',
    dest: 'jquery-slimscroll',
    clean: true,
    dirs: [],
    rootFiles: ['README.md', 'jquery.slimscroll.js', 'jquery.slimscroll.min.js', 'slimScroll.jquery.json'],
  },
  {
    // Legacy js-cookie@2.0.4 filename expected by the app (not bundled elsewhere).
    type: 'cdn',
    name: 'js-cookie',
    version: '2.0.4',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.0.4',
    files: [
      ['js.cookie.js', 'js.cookie.min.js'],
    ],
  },
  {
    // Clean CDN dist into bootstrap-toastr/ (separate from npm toastr package).
    type: 'cdn',
    name: 'toastr',
    version: '2.1.0',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.0',
    dest: 'bootstrap-toastr',
    clean: true,
    files: [
      ['css/toastr.css', 'toastr.css'],
      ['css/toastr.min.css', 'toastr.min.css'],
      ['js/toastr.js', 'toastr.js'],
      ['js/toastr.min.js', 'toastr.min.js'],
    ],
  },
  {
    // v2.1.2 tag on GitHub only ships source; built dist is on cdnjs.
    type: 'cdn',
    name: 'jquery-sparkline',
    version: '2.1.2',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2',
    files: [
      ['jquery.sparkline.min.js', 'jquery.sparkline.min.js'],
    ],
  },
  {
    // Minified builds preferred over unminified npm dist copies.
    type: 'cdn',
    name: 'jquery-validate',
    version: '1.14.0',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.14.0',
    files: [
      ['jquery.validate.min.js', 'jquery-validation/js/jquery.validate.min.js'],
      ['additional-methods.min.js', 'jquery-validation/js/additional-methods.min.js'],
    ],
  },
  {
    // Minified flot plugins; npm flot package ships unminified source.
    type: 'cdn',
    name: 'flot',
    version: '0.8.3',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3',
    dest: 'flot',
    files: [
      'jquery.colorhelpers.min.js',
      'jquery.flot.min.js',
      'jquery.flot.canvas.min.js',
      'jquery.flot.categories.min.js',
      'jquery.flot.crosshair.min.js',
      'jquery.flot.errorbars.min.js',
      'jquery.flot.fillbetween.min.js',
      'jquery.flot.image.min.js',
      'jquery.flot.navigate.min.js',
      'jquery.flot.pie.min.js',
      'jquery.flot.resize.min.js',
      'jquery.flot.selection.min.js',
      'jquery.flot.stack.min.js',
      'jquery.flot.symbol.min.js',
      'jquery.flot.threshold.min.js',
      'jquery.flot.time.min.js',
    ],
  },
  {
    // counterup depends on waypoints but does not bundle it; v2.x is required
    // for the jQuery .waypoint() API and triggerOnce option used by counterup 1.0.
    type: 'cdn',
    name: 'waypoints',
    version: '2.0.3',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3',
    files: [
      ['waypoints.min.js', 'counterup/jquery.waypoints.min.js'],
    ],
  },
  {
    // Ace editor for bootstrap-markdown-editor (pinned legacy version).
    type: 'cdn',
    name: 'ace',
    version: '1.1.3',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3',
    dest: 'markdown',
    files: [
      'ace.js',
    ],
  },
  {
    // inacho/bootstrap-markdown-editor dist; local copy was a custom minify of this build.
    type: 'cdn',
    name: 'bootstrap-markdown-editor',
    version: '2.0.2',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-markdown-editor/2.0.2',
    dest: 'markdown',
    files: [
      ['css/bootstrap-markdown-editor.css', 'bootstrap-markdown-editor.css'],
      ['js/bootstrap-markdown-editor.js', 'bootstrap-markdown-editor.js'],
    ],
  },
  {
    // marked@0.3.2 required by markdown editor (newer npm marked is incompatible).
    type: 'cdn',
    name: 'marked',
    version: '0.3.2',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/marked/0.3.2',
    dest: 'markdown',
    files: [
      ['marked.min.js', 'marked.min.js'],
    ],
  },
  {
    // Flot plugin not included in the npm flot package.
    type: 'cdn',
    name: 'flot-axislabels',
    version: '2.0.1',
    base: 'https://raw.githubusercontent.com/markrcote/flot-axislabels/release-2.0.1',
    dest: 'flot',
    files: [
      'jquery.flot.axislabels.js',
    ],
  },
];

// --- File utilities ---

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function cleanPluginsDir(subpath) {
  const destRoot = toPlugins(subpath);
  if (fs.existsSync(destRoot)) {
    fs.rmSync(destRoot, { recursive: true, force: true });
  }
}

function copyFile(src, dest) {
  if (!fs.existsSync(src)) {
    log.skip(`missing: ${path.relative(ROOT, src)}`);
    return false;
  }
  ensureDir(path.dirname(dest));
  fs.copyFileSync(src, dest);
  return true;
}

function copyDir(src, dest, excluded = []) {
  if (!fs.existsSync(src)) {
    log.skip(`missing: ${path.relative(ROOT, src)}`);
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

// --- HTTP utilities ---

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

/** String entry uses the same basename for remote path and dest path. */
function resolveFileEntry(entry, defaultDestDir) {
  if (Array.isArray(entry)) {
    return entry;
  }
  const dest = defaultDestDir ? path.posix.join(defaultDestDir, entry) : entry;
  return [entry, dest];
}

async function listGithubFiles(repo, ref, dirs = [], rootFiles = []) {
  const treeUrl = `https://api.github.com/repos/${repo}/git/trees/${ref}?recursive=1`;
  const { tree } = await fetchJson(treeUrl);
  const dirPrefixes = dirs.map((dir) => `${dir}/`);
  const files = tree
    .filter((entry) => entry.type === 'blob')
    .map((entry) => entry.path)
    .filter((filePath) => dirPrefixes.some((prefix) => filePath.startsWith(prefix)));
  return [...rootFiles, ...files.sort()];
}

async function downloadCdnFiles({ name, version, base, dest, clean, files }) {
  if (clean && dest) {
    cleanPluginsDir(dest);
  }

  log.step(`Downloading ${name} ${version} from cdn...`);
  for (const entry of files) {
    const [remotePath, destPath] = resolveFileEntry(entry, dest);
    const targetPath = dest && !destPath.includes('/')
      ? path.posix.join(dest, destPath)
      : destPath;
    const url = `${base}/${remotePath}`;
    const target = toPlugins(targetPath);
    try {
      await downloadFile(url, target);
      log.copied(targetPath);
    } catch (err) {
      log.fail(targetPath, err);
    }
  }
}

async function downloadGithubTree({ name, version, repo, ref, base, dest, clean, dirs, rootFiles }) {
  const destRoot = toPlugins(dest);
  if (clean) {
    cleanPluginsDir(dest);
  }

  log.step(`Downloading ${name} ${version} from GitHub...`);
  const files = await listGithubFiles(repo, ref, dirs, rootFiles);
  for (const file of files) {
    const url = `${base}/${file}`;
    const target = path.join(destRoot, file);
    try {
      await downloadFile(url, target);
      log.copied(file);
    } catch (err) {
      log.fail(file, err);
    }
  }
}

async function downloadRemoteAssets(downloads) {
  for (const spec of downloads) {
    if (spec.type === 'cdn') {
      await downloadCdnFiles(spec);
    } else if (spec.type === 'github') {
      await downloadGithubTree(spec);
    } else {
      log.skip(`unknown download type: ${spec.type}`);
    }
  }
}

// --- Custom tasks ---

const SIMPLE_LINE_ICON_FONT_FILES = [
  'Simple-Line-Icons.eot',
  'Simple-Line-Icons.svg',
  'Simple-Line-Icons.ttf',
  'Simple-Line-Icons.woff',
];

function rewriteSimpleLineIconsCss(css) {
  return css.replace(/\.\.\/fonts\//g, 'fonts/');
}

function minifyCss(css) {
  return css
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/\s+/g, ' ')
    .replace(/\s*([{}:;,])\s*/g, '$1')
    .trim();
}

function copySimpleLineIcons() {
  const pkgCss = fromNpm('simple-line-icons/css/simple-line-icons.css');
  const destDir = toPlugins('simple-line-icons');
  if (!fs.existsSync(pkgCss)) {
    log.skip(`missing: ${path.relative(ROOT, pkgCss)}`);
    return;
  }

  ensureDir(path.join(destDir, 'fonts'));
  for (const file of SIMPLE_LINE_ICON_FONT_FILES) {
    copyFile(fromNpm('simple-line-icons/fonts', file), path.join(destDir, 'fonts', file));
  }

  const css = rewriteSimpleLineIconsCss(fs.readFileSync(pkgCss, 'utf8'));
  fs.writeFileSync(path.join(destDir, 'simple-line-icons.css'), css);
  fs.writeFileSync(path.join(destDir, 'simple-line-icons.min.css'), minifyCss(css));
  log.copied('simple-line-icons/simple-line-icons.css');
  log.copied('simple-line-icons/simple-line-icons.min.css');
}

const FLOT_ALL_MIN_PARTS = [
  'jquery.colorhelpers.min.js',
  'jquery.flot.min.js',
  'jquery.flot.categories.min.js',
  'jquery.flot.resize.min.js',
];

function buildFlotAllMin() {
  const flotDir = toPlugins('flot');
  const parts = FLOT_ALL_MIN_PARTS.map((file) => {
    const src = path.join(flotDir, file);
    if (!fs.existsSync(src)) {
      log.skip(`flot bundle: missing ${file}`);
      return null;
    }
    return fs.readFileSync(src, 'utf8');
  }).filter(Boolean);

  if (parts.length !== FLOT_ALL_MIN_PARTS.length) {
    return;
  }

  const dest = path.join(flotDir, 'jquery.flot.all.min.js');
  fs.writeFileSync(dest, `${parts.join('\n')}\n`);
  log.copied('flot/jquery.flot.all.min.js (bundled)');
}

const CODEMIRROR_FILES = [
  'lib/codemirror.js',
  'lib/codemirror.css',
  'mode/css/css.js',
  'mode/javascript/javascript.js',
  'mode/htmlmixed/htmlmixed.js',
  'theme/ambiance.css',
  'theme/material.css',
  'theme/neat.css',
  'theme/neo.css',
];

function copyCodemirror() {
  const pkgRoot = fromNpm('codemirror');
  if (!fs.existsSync(pkgRoot)) {
    log.skip('missing: codemirror (npm)');
    return;
  }

  log.step('Copying codemirror 5.6.0 from npm...');
  for (const rel of CODEMIRROR_FILES) {
    const dest = toPlugins('codemirror', rel);
    if (copyFile(path.join(pkgRoot, rel), dest)) {
      log.copied(`codemirror/${rel}`);
    }
  }
}

function walkOverlayDir(relativeDir) {
  const absDir = fromOverlay(relativeDir);
  for (const entry of fs.readdirSync(absDir, { withFileTypes: true })) {
    const rel = relativeDir ? path.posix.join(relativeDir, entry.name) : entry.name;
    if (entry.isDirectory()) {
      walkOverlayDir(rel);
      continue;
    }
    if (!entry.isFile()) {
      continue;
    }
    if (copyFile(fromOverlay(rel), toPlugins(rel))) {
      log.copied(`${rel} (overlay)`);
    }
  }
}

function copyPluginOverlays() {
  if (!fs.existsSync(PLUGIN_OVERLAYS)) {
    return;
  }

  log.step('Copying plugin overlays...');
  walkOverlayDir('');
}

function extractZip(zipPath, destDir) {
  ensureDir(destDir);
  execSync(`unzip -q -o ${JSON.stringify(zipPath)} -d ${JSON.stringify(destDir)}`, { stdio: 'pipe' });
}

function copyNpmFiles() {
  for (const [from, to] of COPY_FILES) {
    copyFile(fromNpm(...splitPosix(from)), toPlugins(to));
  }
}

function copyNpmDirs() {
  for (const [from, to, excluded] of COPY_DIRS) {
    copyDir(fromNpm(...splitPosix(from)), toPlugins(to), excluded || []);
  }
}

// --- Pipeline ---
//
// Order matters: remote flot min files must exist before buildFlotAllMin().
// plugin-overlays must stay last so vendored/custom files override npm/CDN copies.

const STEPS = [
  { name: 'npm files', run: copyNpmFiles },
  { name: 'npm directories', run: copyNpmDirs },
  { name: 'remote assets', run: () => downloadRemoteAssets(REMOTE_DOWNLOADS) },
  { name: 'simple-line-icons', run: copySimpleLineIcons },
  { name: 'flot bundle', run: buildFlotAllMin },
  { name: 'codemirror', run: copyCodemirror },
  { name: 'plugin overlays', run: copyPluginOverlays },
];

async function copyPlugins() {
  log.step('Copying related assets to public/assets/global/plugins...');
  for (const { run } of STEPS) {
    await run();
  }
  console.log('\nDone.');
}

copyPlugins().catch((err) => {
  console.error(err);
  process.exit(1);
});
