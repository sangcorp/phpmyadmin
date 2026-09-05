<?php
/**
 * Roster-branch addition: environment-driven configuration.
 *
 * A git checkout only ships config.sample.inc.php, and the roster workspace
 * needs the same env-var surface the official image exposes (PMA_HOST,
 * PMA_PORT, ...) so nothing local is baked into the image. Everything is read
 * from the environment with safe defaults; no credentials live in this file.
 */

declare(strict_types=1);

/** Non-empty getenv(), or the fallback. */
function roster_env(string $name, string $default = ''): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

$cfg['blowfish_secret'] = roster_env('PMA_BLOWFISH_SECRET', 'roster-dev-only-blowfish-secret-32b');

$i = 0;

$i++;
$cfg['Servers'][$i]['host'] = roster_env('PMA_HOST', 'mysql');
$cfg['Servers'][$i]['port'] = roster_env('PMA_PORT', '3306');
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;

$user = roster_env('PMA_USER');
$password = roster_env('PMA_PASSWORD');

if ($user !== '' && $password !== '') {
    // Credentials supplied: log straight in (opt-in, not the default).
    $cfg['Servers'][$i]['auth_type'] = 'config';
    $cfg['Servers'][$i]['user'] = $user;
    $cfg['Servers'][$i]['password'] = $password;
} else {
    // Default: prompt for a login. The database here is a copy of production
    // data, so an unauthenticated session is never the default.
    $cfg['Servers'][$i]['auth_type'] = roster_env('PMA_AUTH_TYPE', 'cookie');
}

// Set when phpMyAdmin is mounted under a subpath (e.g. .../s/db/) so generated
// links and redirects point at the public URL rather than the container root.
$absoluteUri = roster_env('PMA_ABSOLUTE_URI');

if ($absoluteUri !== '') {
    $cfg['PmaAbsoluteUri'] = $absoluteUri;
}

$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';
$cfg['TempDir'] = '/tmp';
$cfg['CheckConfigurationPermissions'] = false;
$cfg['VersionCheck'] = false;
