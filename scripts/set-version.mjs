/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {readFile, writeFile} from 'fs/promises';
import {dirname, join} from 'path';
import {fileURLToPath} from 'url';

const args   = process.argv.slice(2),
      config = {
          basedir: dirname(dirname(fileURLToPath(import.meta.url))),
          nightly: args.includes('--nightly'),
          lsr    : args.includes('--lsr')
      };

function getArgValue(name) {
    let index = args.indexOf(name);
    if(index === -1 || index + 1 >= args.length) {
        return null;
    }

    return args[index + 1];
}

async function getBaseVersion() {
    let packageJson = JSON.parse(await readFile(join(config.basedir, 'package.json'), {encoding: 'utf-8'}));

    return packageJson.version;
}

function getLsrMode() {
    let mode = getArgValue('--lsr');
    if(mode === null) {
        throw new Error('Missing LSR Mode');
    }
    if(!/^[0-9]+$/.test(mode)) {
        throw new Error('Invalid LSR Mode');
    }

    return mode;
}

function getBuildNumber() {
    let build = getArgValue('--build');
    if(!build || !/^[0-9A-Za-z]+$/.test(build)) {
        console.error('Invalid build number');
        process.exit(1);
    }

    return build;
}

function getFullVersion(baseVersion) {
    let parts = baseVersion.split('.');

    parts[2] = (config.lsr ? getLsrMode():'2') + parts[2];

    let version = parts.join('.');

    if(config.nightly) {
        version += `-build${getBuildNumber()}`;
    }

    return version;
}

const xmlEntities = {'&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&apos;': '\''};

function decodeXml(value) {
    return value.replace(/&(amp|lt|gt|quot|apos);/g, (entity) => xmlEntities[entity]);
}

function encodeXml(value) {
    return value.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
}

/**
 * Sets the query parameter "_" to the version, as cachebuster for the app store.
 * The app store only accepts https urls with a maximum length of 256 characters (secure-url in info.xsd).
 */
function addCacheBuster(value, version) {
    let url = new URL(value.trim());
    if(url.protocol !== 'https:') {
        throw new Error(`Screenshot url is not https: ${value}`);
    }

    url.searchParams.set('_', version);
    let result = url.toString();
    if(result.length > 256) {
        throw new Error(`Screenshot url is longer than 256 characters: ${result}`);
    }

    return result;
}

function updateScreenshots(xml, version) {
    // Comments are matched first so that commented out screenshots are left untouched
    let pattern = /<!--[\s\S]*?-->|<screenshot\b([^>]*)>([\s\S]*?)<\/screenshot>/g;

    return xml.replace(pattern, (match, attributes, content) => {
        if(attributes === undefined) {
            return match;
        }

        attributes = attributes.replace(
            /(\ssmall-thumbnail\s*=\s*)(["'])([\s\S]*?)\2/,
            (attribute, start, quote, url) => `${start}"${encodeXml(addCacheBuster(decodeXml(url), version))}"`
        );

        let cdata = /<!\[CDATA\[([\s\S]*?)]]>/;
        if(cdata.test(content)) {
            content = content.replace(cdata, (section, url) => `<![CDATA[${addCacheBuster(url, version)}]]>`);
        } else if(content.trim() !== '') {
            content = content.replace(/\S[\s\S]*\S|\S/, (url) => encodeXml(addCacheBuster(decodeXml(url), version)));
        }

        return `<screenshot${attributes}>${content}</screenshot>`;
    });
}

async function updateInfoXml(version) {
    let appInfoPath = join(config.basedir, 'src', 'appinfo', 'info.xml'),
        xml         = await readFile(appInfoPath, {encoding: 'utf-8'}),
        pattern     = /(<info\b[\s\S]*?<version>)[^<]*(<\/version>)/;

    if(!pattern.test(xml)) {
        throw new Error(`No <version> element found in ${appInfoPath}`);
    }

    xml = xml.replace(pattern, (match, start, end) => `${start}${version}${end}`);
    xml = updateScreenshots(xml, version);
    await writeFile(appInfoPath, xml);
}

async function updateChangelog(baseVersion, version) {
    let changelogPath = join(config.basedir, 'CHANGELOG.md'),
        changelog     = await readFile(changelogPath, {encoding: 'utf-8'});

    changelog = changelog.replaceAll(`## ${baseVersion}`, `## ${version}`);
    await writeFile(changelogPath, changelog);
}

async function main() {
    let baseVersion = await getBaseVersion(),
        version     = getFullVersion(baseVersion);

    await updateInfoXml(version);

    if(!config.nightly) {
        await updateChangelog(baseVersion, version);
    }
}

main().catch((e) => {
    console.error(e.message);
    process.exit(1);
});
