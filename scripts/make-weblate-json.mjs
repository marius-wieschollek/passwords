/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import {readdir, readFile, writeFile} from 'fs/promises';
import crypto from 'crypto';

async function processJsonFile(file, database) {
    let content = JSON.parse(await readFile(file, {encoding: 'utf-8'})),
        subpath = file.replace('src/l10n/', ''),
        section = 'frontend';
    if(subpath.indexOf('/') !== -1) {
        section = subpath.substr(0, subpath.indexOf('/'));
        subpath = subpath.substr(subpath.indexOf('/') + 1);
    }
    let language = subpath.replace('.json', '');
    if(Array.isArray(content.translations)) {
        for(let translations of content.translations) {
            processStrings(language, section, translations, database);
        }
    } else {
        processStrings(language, section, content.translations, database);
    }
}

async function processJsFile(file, database) {
    let subpath  = file.replace('src/l10n/', ''),
        language = subpath.replace('.js', '');

    let OC = {
        L10N: {
            register: (app, translations, plurals) => {
                processStrings(language, 'backend', translations, database);
            }
        }
    };

    let code = await readFile(file, {encoding: 'utf-8'});
    eval(code);
}

function processStrings(language, section, translations, database) {
    for(let baseString of Object.keys(translations)) {
        let index = crypto.createHash('md5').update(baseString).digest('hex'),
            key;
        if(!database.index.hasOwnProperty(index)) {
            key = section + '-' + (Object.keys(database.index).length + 1).toString().padStart(6, '0');
            database.index[index] = key;
        } else {
            key = database.index[index];
        }

        if(!database.languages.hasOwnProperty(language)) {
            database.languages[language] = {};
        }
        database.languages[language][key] = createTranslationItem(translations[baseString]);

        if(!database.languages.en.hasOwnProperty(key)) {
            database.languages.en[key] = createTranslationItem(baseString);
        }
    }
}

function createTranslationItem(string) {
    let placeholders = {},
        matches      = string.match(/%(\d+\$)?([ds])/g),
        counter      = 0;

    if(matches) {
        for(let match of matches) {
            let key,
                index;

            if(match.length === 2) {
                let type = match[1];
                counter++;
                index = counter;

                key = `${type === 's' ? 'STR_':'NUM_'}${counter}`;
            } else {
                let i    = parseInt(match[1]),
                    type = match[3];

                index = i;
                key = `${type === 's' ? 'STRING_':'NUMBER_'}${i}`;
            }

            placeholders[key.toLowerCase()] = {
                content: `$${index}`
            };

            while(string.indexOf(match) !== -1) {
                string = string.replace(match, '$' + key + '$');
            }
        }
    }

    matches = string.match(/\{\w+}/g);
    if(matches) {
        for(let match of matches) {
            counter++;

            let key = match.toUpperCase().substring(1);
            key = key.substring(0, key.length - 1);
            placeholders[key.toLowerCase()] = {
                content: `$${counter}`
            };

            while(string.indexOf(match) !== -1) {
                string = string.replace(match, '$' + key + '$');
            }
        }
    }

    if(Object.keys(placeholders).length > 0) {
        return {message: string, placeholders};
    }

    return {message: string};
}

async function main() {
    console.log('Reading files...');
    let files    = await analyzeDirectory('src/l10n'),
        database = {index: {}, languages: {en: {}}};

    console.log('Processing langauge keys...');
    for(let file of files) {
        if(file.substr(-4, 4) === 'json') {
            await processJsonFile(file, database);
        } else {
            await processJsFile(file, database);
        }
    }

    for(let language in database.languages) {
        let contents = new Uint8Array(Buffer.from(JSON.stringify(database.languages[language], undefined, 2)));
        await writeFile(`.weblate/${language}.json`, contents);
        console.log(`Writing ${language}...`);
    }
    console.log('Done');
}

async function analyzeDirectory(path, files = []) {
    try {
        let items = await readdir(path, {withFileTypes: true});
        for(let item of items) {
            if(item.isDirectory()) {
                await analyzeDirectory(path + '/' + item.name, files);
            } else if(item.isFile()) {
                files.push(path + '/' + item.name);
            }
        }

        return files;
    } catch(err) {
        console.error(err);
    }
}

main();