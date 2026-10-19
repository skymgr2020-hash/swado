import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync, unlinkSync } from 'node:fs';
import { dirname, join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { randomUUID } from 'node:crypto';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const root = dirname(scriptDir);
loadEnvFile(join(root, '.env.deploy'));

const config = {
  host: requireEnv('FTP_HOST'),
  port: process.env.FTP_PORT || '21',
  user: requireEnv('FTP_USER'),
  password: requireEnv('FTP_PASSWORD'),
  remoteDir: normalizeRemoteDir(process.env.FTP_REMOTE_DIR || '/'),
  ssl: process.env.FTP_SSL !== 'false',
  insecure: process.env.FTP_INSECURE === 'true',
};

const distDir = join(root, 'dist');
if (!existsSync(distDir)) {
  fail('dist folder does not exist. Run npm run build first.');
}

const curl = process.env.CURL_BIN || 'curl.exe';
const netrcPath = join(root, `.ftp-netrc-${randomUUID()}`);

try {
  writeFileSync(netrcPath, `machine ${config.host}\nlogin ${config.user}\npassword ${config.password}\n`, { mode: 0o600 });

  const files = collectFiles(distDir).sort((a, b) => {
    const aIndex = a.endsWith(`${sep}index.html`) ? 1 : 0;
    const bIndex = b.endsWith(`${sep}index.html`) ? 1 : 0;
    return aIndex - bIndex || a.localeCompare(b);
  });

  const dirs = new Set();
  for (const file of files) {
    const remotePath = toRemotePath(relative(distDir, file));
    const remoteDir = dirnameRemote(remotePath);
    if (remoteDir && !dirs.has(remoteDir)) {
      ensureRemoteDir(curl, netrcPath, config, remoteDir);
      dirs.add(remoteDir);
    }
    uploadFile(curl, netrcPath, config, file, remotePath);
  }

  console.log(`Uploaded ${files.length} files to ftp://${config.host}:${config.port}${config.remoteDir}`);
} finally {
  if (existsSync(netrcPath)) unlinkSync(netrcPath);
}

function loadEnvFile(path) {
  if (!existsSync(path)) return;
  for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const match = trimmed.match(/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/);
    if (!match) continue;
    const [, key, rawValue] = match;
    if (process.env[key] != null) continue;
    process.env[key] = rawValue.replace(/^"(.*)"$/, '$1').replace(/^'(.*)'$/, '$1');
  }
}

function requireEnv(name) {
  const value = process.env[name];
  if (!value) fail(`Missing ${name}. Add it to .env.deploy or set it in your shell.`);
  return value;
}

function collectFiles(dir) {
  return readdirSync(dir).flatMap((entry) => {
    const path = join(dir, entry);
    return statSync(path).isDirectory() ? collectFiles(path) : [path];
  });
}

function normalizeRemoteDir(value) {
  const cleaned = value.replace(/\\/g, '/').replace(/^\/+|\/+$/g, '');
  return cleaned ? `/${cleaned}` : '/';
}

function toRemotePath(path) {
  return path.split(sep).join('/');
}

function dirnameRemote(path) {
  const index = path.lastIndexOf('/');
  return index === -1 ? '' : path.slice(0, index);
}

function ftpUrl(config, remotePath = '') {
  const basePath = config.remoteDir === '/' ? '' : config.remoteDir;
  const suffix = remotePath ? `/${remotePath}` : '/';
  return `ftp://${config.host}:${config.port}${basePath}${suffix}`;
}

function curlBaseArgs(netrcPath, config) {
  const args = ['--fail', '--show-error', '--netrc-file', netrcPath];
  if (config.ssl) args.push('--ssl-reqd');
  if (config.insecure) args.push('--insecure');
  return args;
}

function ensureRemoteDir(curl, netrcPath, config, dir) {
  const parts = dir.split('/').filter(Boolean);
  let current = '';
  for (const part of parts) {
    current = current ? `${current}/${part}` : part;
    const result = spawnSync(curl, [...curlBaseArgs(netrcPath, config), '--quote', `MKD ${current}`, ftpUrl(config)], {
      stdio: 'pipe',
      encoding: 'utf8',
    });
    const output = `${result.stderr}\n${result.stdout}`;
    if (result.status !== 0 && !/File exists|already exists|550/i.test(output)) {
      fail(`Could not create remote directory ${current}.\n${output}`);
    }
  }
}

function uploadFile(curl, netrcPath, config, localPath, remotePath) {
  console.log(`Uploading ${remotePath}`);
  const result = spawnSync(
    curl,
    [...curlBaseArgs(netrcPath, config), '--ftp-create-dirs', '-T', localPath, ftpUrl(config, remotePath)],
    { stdio: 'inherit' }
  );
  if (result.status !== 0) fail(`Upload failed for ${remotePath}`);
}

function fail(message) {
  console.error(message);
  process.exit(1);
}
