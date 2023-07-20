/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {readFile} from 'fs/promises';
import json from '../package.json' assert {type: 'json'};


async function main() {
    let contents     = await readFile('./CHANGELOG.md', {encoding: 'utf-8'}),
        lines        = contents.split("\n"),
        search       = `## ${json.version}`,
        changelog    = [],
        foundVersion = false;

    for(let line of lines) {
        if(!foundVersion) {
            if(line.indexOf(search) === 0) {
                foundVersion = true;
            }
        } else {
            if(line === '') {
                break;
            }
            changelog.push(line);
        }

    }

    console.log(changelog.join('\\n'));
}

main()
    .catch(console.error);