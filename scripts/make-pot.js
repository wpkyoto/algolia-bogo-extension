#!/usr/bin/env node

/**
 * Generate POT file for WordPress plugin
 * Extracts translatable strings from PHP and JavaScript files
 */

const fs = require('fs');
const path = require('path');

// Configuration
const config = {
    domain: 'algolia-bogo',
    slug: 'algolia-bogo',
    outputPath: path.join(__dirname, '../languages/algolia-bogo.pot'),
    sourcePath: path.join(__dirname, '..'),
    excludePatterns: [
        'node_modules',
        'vendor',
        'languages',
        '.git',
        'scripts',
    ],
};

/**
 * Check if file should be excluded
 */
function shouldExclude(filePath) {
    return config.excludePatterns.some(pattern => filePath.includes(pattern));
}

/**
 * Extract translatable strings from PHP file
 * Recognizes WordPress i18n functions: __(), _e(), _n(), _x(), _ex(), esc_html__(), esc_attr__(), etc.
 */
function extractPhpStrings(content, filePath) {
    const strings = [];
    
    // Helper function to extract string from quotes (handles both single and double quotes)
    function extractQuotedString(str, startPos) {
        const quote = str[startPos];
        if (quote !== '"' && quote !== "'") return null;
        
        let endPos = startPos + 1;
        let escaped = false;
        
        while (endPos < str.length) {
            if (escaped) {
                escaped = false;
            } else if (str[endPos] === '\\') {
                escaped = true;
            } else if (str[endPos] === quote) {
                return {
                    text: str.substring(startPos + 1, endPos).replace(/\\(.)/g, '$1'),
                    endPos: endPos + 1
                };
            }
            endPos++;
        }
        return null;
    }
    
    // WordPress i18n function names
    const i18nFunctions = [
        '__', '_e', '_n', '_x', '_ex',
        'esc_html__', 'esc_attr__', 'esc_html_e', 'esc_attr_e',
        '_n_noop', '_nx', '_nx_noop'
    ];
    
    // Pattern to find function calls
    i18nFunctions.forEach(funcName => {
        const regex = new RegExp(`\\b${funcName.replace(/_/g, '\\_')}\\s*\\(`, 'g');
        let match;
        
        while ((match = regex.exec(content)) !== null) {
            const startPos = match.index + match[0].length;
            let pos = startPos;
            
            // Skip whitespace
            while (pos < content.length && /\s/.test(content[pos])) {
                pos++;
            }
            
            // Extract first string (the translatable text)
            const firstString = extractQuotedString(content, pos);
            if (!firstString) continue;
            
            pos = firstString.endPos;
            
            // Skip whitespace and comma
            while (pos < content.length && (/\s/.test(content[pos]) || content[pos] === ',')) {
                pos++;
            }
            
            let domain = null;
            let text = firstString.text;
            
            // For functions with multiple parameters, extract domain
            if (funcName === '_n' || funcName === '_nx' || funcName === '_n_noop' || funcName === '_nx_noop') {
                // Skip second string (plural form)
                const secondString = extractQuotedString(content, pos);
                if (secondString) {
                    pos = secondString.endPos;
                    // Skip whitespace and comma
                    while (pos < content.length && (/\s/.test(content[pos]) || content[pos] === ',')) {
                        pos++;
                    }
                    // For _n and _nx, skip the number parameter
                    if (funcName === '_n' || funcName === '_nx') {
                        while (pos < content.length && content[pos] !== ',' && content[pos] !== ')') {
                            pos++;
                        }
                        if (content[pos] === ',') pos++;
                        while (pos < content.length && (/\s/.test(content[pos]) || content[pos] === ',')) {
                            pos++;
                        }
                    }
                }
            }
            
            // For _x, _ex, _nx, extract context (second string)
            if (funcName === '_x' || funcName === '_ex' || funcName === '_nx') {
                const contextString = extractQuotedString(content, pos);
                if (contextString) {
                    pos = contextString.endPos;
                    // Skip whitespace and comma
                    while (pos < content.length && (/\s/.test(content[pos]) || content[pos] === ',')) {
                        pos++;
                    }
                }
            }
            
            // Extract domain (last string parameter)
            const domainString = extractQuotedString(content, pos);
            if (domainString) {
                domain = domainString.text;
            }
            
            // Only include strings with matching domain or no domain specified
            if (!domain || domain === config.domain) {
                if (text && text.trim()) {
                    const line = content.substring(0, match.index).split('\n').length;
                    strings.push({
                        text: text.trim(),
                        file: filePath,
                        line: line,
                    });
                }
            }
        }
    });

    return strings;
}

/**
 * Extract translatable strings from JavaScript file
 * Recognizes wp.i18n functions
 */
function extractJsStrings(content, filePath) {
    const strings = [];
    
    // WordPress i18n JavaScript patterns
    const patterns = [
        // wp.i18n.__( 'text', 'domain' )
        /wp\.i18n\.__(?:\s*\(|\s+)\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*\)/g,
        // wp.i18n._x( 'text', 'context', 'domain' )
        /wp\.i18n\._x(?:\s*\(|\s+)\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*\)/g,
        // wp.i18n._n( 'single', 'plural', number, 'domain' )
        /wp\.i18n\._n(?:\s*\(|\s+)\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*,\s*[^,]+,\s*['"]([^'"]+)['"]\s*\)/g,
    ];

    patterns.forEach((pattern) => {
        let match;
        while ((match = pattern.exec(content)) !== null) {
            const text = match[1];
            const domain = match[2] || match[3] || match[4];
            
            if (!domain || domain === config.domain) {
                if (text && text.trim()) {
                    strings.push({
                        text: text,
                        file: filePath,
                        line: content.substring(0, match.index).split('\n').length,
                    });
                }
            }
        }
    });

    return strings;
}

/**
 * Get all PHP and JS files recursively
 */
function getAllFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);

        if (shouldExclude(filePath)) {
            return;
        }

        if (stat.isDirectory()) {
            getAllFiles(filePath, fileList);
        } else if (file.endsWith('.php') || file.endsWith('.js')) {
            fileList.push(filePath);
        }
    });

    return fileList;
}

/**
 * Generate POT file content
 */
function generatePotContent(strings) {
    const now = new Date();
    const dateStr = now.toISOString().replace(/\.\d{3}Z$/, 'Z');
    
    let pot = `msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\\n"
"Project-Id-Version: Search with Algolia Bogo extension 0.1.4\\n"
"Report-Msgid-Bugs-To: https://wp-kyoto.net\\n"
"POT-Creation-Date: ${dateStr}\\n"
"PO-Revision-Date: ${dateStr}\\n"
"Last-Translator: Hidetaka Okamoto\\n"
"Language-Team: Japanese\\n"
"Language: ja\\n"
"MIME-Version: 1.0\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=1; plural=0;\\n"
`;

    // Remove duplicates and sort
    const uniqueStrings = Array.from(
        new Map(strings.map(s => [s.text, s])).values()
    );

    uniqueStrings.forEach(({ text, file, line }) => {
        const relativeFile = path.relative(config.sourcePath, file);
        pot += `\n#: ${relativeFile}:${line}\n`;
        pot += `msgid "${text.replace(/"/g, '\\"')}"\n`;
        pot += `msgstr ""\n`;
    });

    return pot;
}

/**
 * Main function
 */
function main() {
    console.log('Extracting translatable strings...');
    
    const files = getAllFiles(config.sourcePath);
    const allStrings = [];

    files.forEach(file => {
        try {
            const content = fs.readFileSync(file, 'utf8');
            let strings = [];

            if (file.endsWith('.php')) {
                // Use PHP extractor for PHP files
                strings = extractPhpStrings(content, file);
            } else if (file.endsWith('.js')) {
                // Use JavaScript extractor for JS files
                strings = extractJsStrings(content, file);
            }

            allStrings.push(...strings);
        } catch (error) {
            console.error(`Error processing ${file}:`, error.message);
        }
    });

    console.log(`Found ${allStrings.length} translatable strings`);

    // Generate POT file
    const potContent = generatePotContent(allStrings);
    
    // Ensure output directory exists
    const outputDir = path.dirname(config.outputPath);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    fs.writeFileSync(config.outputPath, potContent, 'utf8');
    console.log(`✓ POT file generated: ${config.outputPath}`);
}

// Run if called directly
if (require.main === module) {
    main();
}

module.exports = { extractPhpStrings, extractJsStrings, generatePotContent };

