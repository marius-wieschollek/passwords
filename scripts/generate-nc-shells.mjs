import {readdir, readFile, writeFile} from 'fs/promises';

const useRegex = /use\s+([\w\\]+)(\s+as\s+(\w+))?\s*;/gm;
const extendsRegex = /extends\s+([\w\\]+)\s*{/gm;
const implementsRegex = /implements\s+([\w\\]+)\s*{/gm;
const namespaceRegex = /namespace\s+([\w\\]+)\s*;/;
const classRegex = /(abstract\s+)?(class|interface|trait)\s+(\w+)/;

async function main() {
    let definitions = await analyzeDirectory('src/lib'),
        shells      = generateShells(definitions);

    await writeShellFile(shells);
}

async function analyzeDirectory(path, definitions = {}) {
    try {
        let items = await readdir(path, {withFileTypes: true});
        for(let item of items) {
            if(item.isDirectory()) {
                await analyzeDirectory(path + '/' + item.name, definitions);
            } else if(item.isFile() && item.name.substr(-4) === '.php') {
                await analyzeFile(path + '/' + item.name, definitions);
            }
        }

        return definitions;
    } catch(err) {
        console.error(err);
    }
}

function extractImports(contents, definitions) {
    let matches,
        uses = {};
    while((matches = useRegex.exec(contents)) !== null) {
        if(matches.index === useRegex.lastIndex) {
            useRegex.lastIndex++;
        }

        let match     = matches[1],
            divider   = match.lastIndexOf('\\'),
            namespace = match.substr(0, divider),
            name      = match.substr(divider + 1);

        if(matches[3]) {
            uses[matches[3]] = {namespace, name};
        } else {
            uses[name] = {namespace, name};
        }

        if(match.substr(0, 2) === 'OC' || match.substr(0, 7) === 'Symfony' || match.substr(0, 3) === 'Psr' || match.substr(0, 10) === 'GuzzleHttp') {
            addClassDefinition(definitions, namespace, name);
        }
    }

    let classMatches = classRegex.exec(contents);
    if(classMatches !== null) {
        let name = classMatches[3],
            namespace = '',
            namespaceMatches = namespaceRegex.exec(contents);

        if(namespaceMatches) {
            namespace = namespaceMatches[1];
        }

        uses['self'] = {namespace, name};
        uses['static'] = {namespace, name};
        addClassDefinition(definitions, namespace, name);
        definitions[namespace][name].type = classMatches[2];
    }

    return uses;
}

function extractExtendInformation(contents, uses, definitions) {
    let extend = extendsRegex.exec(contents);
    if(extend !== null && !uses.hasOwnProperty(extend[1])) {
        let namespace = namespaceRegex.exec(contents);
        if(namespace === null) return;
        addClassDefinition(definitions, namespace[1], extend[1]);
        uses[extend[1]] = {namespace: namespace[1], name: extend[1]};
    }
}

function extractImplementsInformation(contents, uses, definitions) {
    let impl = implementsRegex.exec(contents);
    if(impl !== null) {
        let namespace = '', name = '';
        if(!uses[impl[1]]) {
            let nsmatch = namespaceRegex.exec(contents);
            if(nsmatch === null) return;
            name = impl[1];
            namespace = nsmatch[1];
        } else {
            namespace = uses[impl[1]].namespace;
            name = uses[impl[1]].name;
        }

        addClassDefinition(definitions, namespace, name);
        definitions[namespace][name].type = 'interface';
    }
}

function extractConstants(contents, uses, definitions) {
    for(let alias in uses) {
        if(!uses.hasOwnProperty(alias)) continue;

        let {namespace, name} = uses[alias];
        if(namespace === '' && name.substr(0, 3) !== 'OC_') continue;

        let constantRegex = new RegExp(`${alias}::(\\w+)`, 'gm');
        let matches;
        while((matches = constantRegex.exec(contents)) !== null) {
            if(matches.index === constantRegex.lastIndex) {
                constantRegex.lastIndex++;
            }

            if(matches[1] !== 'class' && definitions[namespace][name].constants.indexOf(matches[1]) === -1) {
                definitions[namespace][name].constants.push(matches[1]);
            }
        }
    }
}

async function analyzeFile(path, definitions) {

    try {
        let contents = await readFile(path, {encoding: 'utf-8'});
        let uses = extractImports(contents, definitions);
        extractExtendInformation(contents, uses, definitions);
        extractImplementsInformation(contents, uses, definitions);
        extractConstants(contents, uses, definitions);
    } catch(err) {
        console.error(err);
    }
}

function addClassDefinition(definitions, namespace, name) {
    if(!definitions.hasOwnProperty(namespace)) {
        definitions[namespace] = {};
    }
    if(!definitions[namespace].hasOwnProperty(name)) {
        definitions[namespace][name] = {
            type     : 'class',
            constants: []
        };
    }
}

async function writeShellFile(data) {
    try {
        let contents = new Uint8Array(Buffer.from(data));
        await writeFile('rector-shells.php', contents);
    } catch(err) {
        console.error(err);
    }
}

function generateShells(definitions) {
    let shells = '<?php\n';
    for(let definition in definitions) {
        if(!definitions.hasOwnProperty(definition)) continue;
        shells += `namespace ${definition} {\n`;
        let classes = definitions[definition];
        for(let name in classes) {
            if(!classes.hasOwnProperty(name)) continue;
            let info = classes[name];
            shells += `${info.type} ${name}{`;

            let content = '';
            for(let constant of info.constants) {
                content += `const ${constant}=0;\n`;
            }
            shells += `${(content === '' ? '' : `\n${content}`)}}\n`;
        }
        shells += '}\n';
    }

    return shells;
}

main().catch(console.error);