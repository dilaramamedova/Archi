#!/usr/bin/env node
// One-file installer: clones the ARCHI front end and copies it into an existing Laravel app.
// Node >= 18, no npm dependencies. Never touches routes/, app/, config/ or .env.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const DEFAULT_REPO = 'https://github.com/dilaramamedova/Archi.git';
const DEFAULT_BRANCH = 'laravel';
const PAYLOAD = ['resources', 'public/assets', 'lang', 'vite.config.js'];
const USAGE = 'Usage: node install.mjs <target-laravel-path> [--force] [--repo <url-or-path>] [--branch <name>]';

const die = (msg) => {
    console.error(`error: ${msg}\n${USAGE}`);
    process.exit(1);
};

const args = process.argv.slice(2);
let target, force = false, repo = DEFAULT_REPO, branch = DEFAULT_BRANCH;
for (let i = 0; i < args.length; i++) {
    const a = args[i];
    if (a === '--force') force = true;
    else if (a === '--repo') repo = args[++i];
    else if (a === '--branch') branch = args[++i];
    else if (a === '--help' || a === '-h') { console.log(USAGE); process.exit(0); }
    else if (!target) target = a;
    else die(`unexpected argument "${a}"`);
}
if (!target) die('missing <target-laravel-path>');
if (!repo || !branch) die('--repo and --branch need a value');

target = path.resolve(target);
if (!fs.existsSync(path.join(target, 'artisan'))) {
    die(`"${target}" does not look like a Laravel app (no artisan file). Point at the app root.`);
}

console.log(`ARCHI installer\n  repo    ${repo}\n  branch  ${branch}\n  target  ${target}\n`);

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'archi-'));
process.on('exit', () => fs.rmSync(tmp, { recursive: true, force: true }));

console.log('Cloning...');
try {
    execFileSync('git', ['clone', '--depth', '1', '-b', branch, repo, tmp], { stdio: ['ignore', 'ignore', 'pipe'] });
} catch (e) {
    if (e.code === 'ENOENT') die('git was not found on PATH. Install git and retry.');
    die(`git clone failed:\n${String(e.stderr || e.message).trim()}`);
}

// Flat list of every payload file, as paths relative to the clone.
const walk = (dir) => fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const p = path.join(dir, e.name);
    return e.isDirectory() ? walk(p) : [p];
});
const files = [];
for (const item of PAYLOAD) {
    const src = path.join(tmp, item);
    if (!fs.existsSync(src)) continue;
    const found = fs.statSync(src).isDirectory() ? walk(src) : [src];
    files.push(...found.map((f) => path.relative(tmp, f)));
}
if (!files.length) die(`branch "${branch}" contains none of: ${PAYLOAD.join(', ')}`);

// A conflict is a target file that already exists with different content.
const conflicts = files.filter((rel) => {
    const dst = path.join(target, rel);
    return fs.existsSync(dst) && !fs.readFileSync(dst).equals(fs.readFileSync(path.join(tmp, rel)));
});
const skip = force ? new Set() : new Set(conflicts);

for (const item of PAYLOAD) {
    const src = path.join(tmp, item);
    if (!fs.existsSync(src)) continue;
    fs.cpSync(src, path.join(target, item), {
        recursive: true,
        filter: (s) => !skip.has(path.relative(tmp, s)),
    });
}

console.log(`Copied ${files.length - skip.size} file(s): ${PAYLOAD.join(', ')}`);
if (conflicts.length && force) {
    console.log(`Overwrote ${conflicts.length} existing file(s) (--force).`);
} else if (conflicts.length) {
    console.log(`\nSkipped ${conflicts.length} file(s) that already exist with different content:`);
    for (const rel of conflicts) console.log(`  ${rel.split(path.sep).join('/')}`);
    console.log('Merge them by hand, or re-run with --force to overwrite.');
}

const deps = JSON.parse(fs.readFileSync(path.join(tmp, 'package.json'), 'utf8')).devDependencies || {};
console.log('\nNext steps');
console.log('  1. Merge into your package.json "devDependencies":');
for (const [name, version] of Object.entries(deps)) console.log(`       "${name}": "${version}",`);
console.log('  2. Add the view routes — see INTEGRATION.md §5 (routes/ is never modified by this installer).');
console.log('  3. npm install && npm run build');
