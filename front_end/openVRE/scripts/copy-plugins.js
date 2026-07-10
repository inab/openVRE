#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');

const ROOT = path.resolve(__dirname, '..');
const NM = path.join(ROOT, 'node_modules');
const PLUGINS = path.join(ROOT, 'public/assets/global/plugins');

const COPY_FILES = [
  // bootstrap
  ['bootstrap/dist/js/bootstrap.js', 'bootstrap/js/bootstrap.js'],
  ['bootstrap/dist/js/bootstrap.min.js', 'bootstrap/js/bootstrap.min.js'],
  // jquery
  ['jquery/dist/jquery.min.js', 'jquery.min.js'],
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
    'counterup.jquery.json'
  ]],
];

// Remote assets not available (or not wanted) from npm.
// type: 'cdn'   — download explicit files from a versioned base URL
// type: 'github' — download folders from a tagged GitHub repo via the API
const REMOTE_DOWNLOADS = [
  {
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
    type: 'cdn',
    name: 'js-cookie',
    version: '2.0.4',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.0.4',
    files: [
      ['js.cookie.js', 'js.cookie.min.js'],
    ],
  },
  {
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
    type: 'cdn',
    name: 'jquery-sparkline',
    version: '2.1.2',
    // v2.1.2 tag on GitHub only ships source; built dist is on cdnjs.
    base: 'https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2',
    files: [
      ['jquery.sparkline.min.js', 'jquery.sparkline.min.js'],
    ],
  },
  {
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
    type: 'cdn',
    name: 'waypoints',
    version: '2.0.3',
    base: 'https://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3',
    files: [
      // counterup depends on waypoints but does not bundle it; v2.x is required
      // for the jQuery .waypoint() API and triggerOnce option used by counterup 1.0.
      ['waypoints.min.js', 'counterup/jquery.waypoints.min.js'],
    ],
  },
  {
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
    type: 'cdn',
    name: 'bootstrap-markdown-editor',
    version: '2.0.2',
    // inacho/bootstrap-markdown-editor dist; local copy was a custom minify of this build.
    base: 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-markdown-editor/2.0.2',
    dest: 'markdown',
    files: [
      ['css/bootstrap-markdown-editor.css', 'bootstrap-markdown-editor.css'],
      ['js/bootstrap-markdown-editor.js', 'bootstrap-markdown-editor.js'],
    ],
  },
  {
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
    type: 'cdn',
    name: 'simple-line-icons-extras',
    version: '2d47e408c253',
    // Not shipped in npm 1.0.0; vendored extras from openVRE-core-dev history.
    base: 'https://raw.githubusercontent.com/inab/openVRE-core-dev/2d47e408c253/front_end/openVRE/public/assets/global/plugins/simple-line-icons',
    dest: 'simple-line-icons',
    files: [
      'icons-lte-ie7.js',
      'License.txt',
      'Readme.txt',
    ],
  },
  {
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

const FLOT_ALL_MIN_PARTS = [
  'jquery.colorhelpers.min.js',
  'jquery.flot.min.js',
  'jquery.flot.categories.min.js',
  'jquery.flot.resize.min.js',
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
  const destRoot = dest ? path.join(PLUGINS, dest) : null;
  if (clean && destRoot && fs.existsSync(destRoot)) {
    fs.rmSync(destRoot, { recursive: true, force: true });
  }

  console.log(`Downloading ${name} ${version} from cdn...`);
  for (const entry of files) {
    const [remotePath, destPath] = resolveFileEntry(entry, dest);
    const targetPath = dest && !destPath.includes('/')
      ? path.posix.join(dest, destPath)
      : destPath;
    const url = `${base}/${remotePath}`;
    const target = path.join(PLUGINS, targetPath);
    try {
      await downloadFile(url, target);
      console.log(`  ${targetPath}`);
    } catch (err) {
      console.warn(`  skip failed: ${targetPath} (${err.message})`);
    }
  }
}

async function downloadGithubTree({ name, version, repo, ref, base, dest, clean, dirs, rootFiles }) {
  const destRoot = path.join(PLUGINS, dest);
  if (clean && fs.existsSync(destRoot)) {
    fs.rmSync(destRoot, { recursive: true, force: true });
  }

  console.log(`Downloading ${name} ${version} from GitHub...`);
  const files = await listGithubFiles(repo, ref, dirs, rootFiles);
  for (const file of files) {
    const url = `${base}/${file}`;
    const target = path.join(destRoot, file);
    try {
      await downloadFile(url, target);
      console.log(`  ${file}`);
    } catch (err) {
      console.warn(`  skip failed: ${file} (${err.message})`);
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
      console.warn(`  skip unknown download type: ${spec.type}`);
    }
  }
}

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
  const pkgCss = nm('simple-line-icons/css/simple-line-icons.css');
  const destDir = path.join(PLUGINS, 'simple-line-icons');
  if (!fs.existsSync(pkgCss)) {
    console.warn(`  skip missing: ${path.relative(ROOT, pkgCss)}`);
    return;
  }

  ensureDir(path.join(destDir, 'fonts'));
  for (const file of SIMPLE_LINE_ICON_FONT_FILES) {
    copyFile(nm('simple-line-icons/fonts', file), path.join(destDir, 'fonts', file));
  }

  const css = rewriteSimpleLineIconsCss(fs.readFileSync(pkgCss, 'utf8'));
  fs.writeFileSync(path.join(destDir, 'simple-line-icons.css'), css);
  fs.writeFileSync(path.join(destDir, 'simple-line-icons.min.css'), minifyCss(css));
  console.log('  simple-line-icons/simple-line-icons.css');
  console.log('  simple-line-icons/simple-line-icons.min.css');
}

function buildFlotAllMin() {
  const flotDir = path.join(PLUGINS, 'flot');
  const parts = FLOT_ALL_MIN_PARTS.map((file) => {
    const src = path.join(flotDir, file);
    if (!fs.existsSync(src)) {
      console.warn(`  skip flot bundle: missing ${file}`);
      return null;
    }
    return fs.readFileSync(src, 'utf8');
  }).filter(Boolean);

  if (parts.length !== FLOT_ALL_MIN_PARTS.length) {
    return;
  }

  const dest = path.join(flotDir, 'jquery.flot.all.min.js');
  fs.writeFileSync(dest, `${parts.join('\n')}\n`);
  console.log('  flot/jquery.flot.all.min.js (bundled)');
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
  await downloadRemoteAssets(REMOTE_DOWNLOADS);
  copySimpleLineIcons();
  buildFlotAllMin();
  console.log('\nDone.');
}

copyPlugins().catch((err) => {
  console.error(err);
  process.exit(1);
});
