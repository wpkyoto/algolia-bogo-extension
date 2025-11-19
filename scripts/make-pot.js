#!/usr/bin/env node

/**
 * Generate .pot file from PHP files
 */

const { GettextExtractor, JsExtractors, HtmlExtractors } = require('gettext-extractor');
const glob = require('glob');
const path = require('path');
const packageJson = require('../package.json');

const extractor = new GettextExtractor();

// Find all PHP files
const phpFiles = glob.sync('**/*.php', {
  ignore: ['node_modules/**', 'vendor/**', 'tests/**']
});

// Extract strings from PHP files
phpFiles.forEach(file => {
  extractor.createJsParser([
    JsExtractors.callExpression('__', {
      arguments: { text: 0 }
    }),
    JsExtractors.callExpression('_e', {
      arguments: { text: 0 }
    }),
    JsExtractors.callExpression('_x', {
      arguments: { text: 0, context: 1 }
    }),
    JsExtractors.callExpression('_n', {
      arguments: { text: 0, textPlural: 1 }
    }),
    JsExtractors.callExpression('_nx', {
      arguments: { text: 0, textPlural: 1, context: 3 }
    }),
    JsExtractors.callExpression('esc_html__', {
      arguments: { text: 0 }
    }),
    JsExtractors.callExpression('esc_html_e', {
      arguments: { text: 0 }
    }),
    JsExtractors.callExpression('esc_attr__', {
      arguments: { text: 0 }
    }),
    JsExtractors.callExpression('esc_attr_e', {
      arguments: { text: 0 }
    })
  ]).parseFilesGlob(file);
});

// Save to .pot file
const potFilePath = path.join(__dirname, '../languages/algolia-bogo.pot');

try {
  const result = extractor.savePotFile(potFilePath, {
    'Project-Id-Version': `Search with Algolia Bogo extension ${packageJson.version}`,
    'Report-Msgid-Bugs-To': 'https://wp-kyoto.net',
    'POT-Creation-Date': new Date().toISOString(),
    'PO-Revision-Date': new Date().toISOString(),
    'Last-Translator': 'Hidetaka Okamoto',
    'Language-Team': 'Japanese',
    'Language': 'ja',
    'MIME-Version': '1.0',
    'Content-Type': 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding': '8bit'
  });

  // Handle promise if savePotFile returns one
  if (result && typeof result.then === 'function') {
    result
      .then(() => {
        extractor.printStats();
        console.log('✓ POT file generated at languages/algolia-bogo.pot');
      })
      .catch((error) => {
        console.error(`Error: Failed to save POT file to ${potFilePath}`);
        console.error('Error details:', error.stack || error);
        process.exit(1);
      });
  } else {
    // Synchronous success
    extractor.printStats();
    console.log('✓ POT file generated at languages/algolia-bogo.pot');
  }
} catch (error) {
  console.error(`Error: Failed to save POT file to ${potFilePath}`);
  console.error('Error details:', error.stack || error);
  process.exit(1);
}
