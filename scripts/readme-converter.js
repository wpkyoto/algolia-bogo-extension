#!/usr/bin/env node

/**
 * Convert WordPress readme.txt to README.md
 */

const fs = require('fs');
const path = require('path');

const readmePath = path.join(__dirname, '../readme.txt');
const outputPath = path.join(__dirname, '../README.md');

// Read readme.txt
const content = fs.readFileSync(readmePath, 'utf8');

// Convert WordPress readme format to Markdown
let markdown = content
  // Convert headers
  .replace(/^=== (.+?) ===/gm, '# $1 #')
  .replace(/^== (.+?) ==/gm, '## $1 ##')
  .replace(/^= (.+?) =/gm, '### $1 ###')

  // Convert metadata to bold
  .replace(/^(Donate link|Tags|Requires at least|Tested up to|Requires PHP|Stable tag|License|License URI):/gm, '**$1:**')

  // Preserve code blocks
  .replace(/`([^`]+)`/g, '`$1`');

// Write README.md
fs.writeFileSync(outputPath, markdown, 'utf8');

console.log('✓ README.md generated from readme.txt');
