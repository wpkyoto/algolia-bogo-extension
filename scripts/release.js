#!/usr/bin/env node

/**
 * WordPress Plugin Release Script
 * 
 * This script automates the release preparation process:
 * Updates version numbers in package.json, algolia-bogo.php, and readme.txt
 * 
 * Note: Distribution zip is automatically created by GitHub Actions during deployment.
 */

const fs = require('fs');
const path = require('path');

// Get version from command line argument
const newVersion = process.argv[2];

if (!newVersion) {
  console.error('Error: Version number is required');
  console.error('Usage: node scripts/release.js <version>');
  console.error('Example: node scripts/release.js 0.2.0');
  process.exit(1);
}

// Validate version format (e.g., 0.2.0, 1.0.0, 2.1.3)
if (!/^\d+\.\d+\.\d+$/.test(newVersion)) {
  console.error('Error: Invalid version format. Use semantic versioning (e.g., 0.2.0)');
  process.exit(1);
}

const projectRoot = path.resolve(__dirname, '..');
const packageJsonPath = path.join(projectRoot, 'package.json');
const pluginFilePath = path.join(projectRoot, 'algolia-bogo.php');
const readmePath = path.join(projectRoot, 'readme.txt');

console.log(`\n🚀 Preparing release v${newVersion}...\n`);

// Read current files
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
const pluginFile = fs.readFileSync(pluginFilePath, 'utf8');
const readmeFile = fs.readFileSync(readmePath, 'utf8');

const oldVersion = packageJson.version;
console.log(`Current version: ${oldVersion}`);
console.log(`New version: ${newVersion}\n`);

// Update package.json
console.log('📝 Updating package.json...');
packageJson.version = newVersion;
fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2) + '\n');

// Update algolia-bogo.php
console.log('📝 Updating algolia-bogo.php...');
const updatedPluginFile = pluginFile.replace(
  /Version:\s+\d+\.\d+\.\d+/,
  `Version:         ${newVersion}`
);
fs.writeFileSync(pluginFilePath, updatedPluginFile);

// Update readme.txt
console.log('📝 Updating readme.txt...');
let updatedReadme = readmeFile.replace(
  /Stable tag:\s+\d+\.\d+\.\d+/,
  `Stable tag: ${newVersion}`
);

// Check if changelog entry exists for new version
const changelogPattern = new RegExp(`===\\s+${newVersion.replace(/\./g, '\\.')}\\s+===`, 'i');
if (!changelogPattern.test(updatedReadme)) {
  console.log('⚠️  Warning: Changelog entry not found for v' + newVersion);
  console.log('   Please add a changelog entry in readme.txt before releasing.\n');
  
  // Add a placeholder changelog entry
  const changelogMatch = updatedReadme.match(/== Changelog ==\s*\n/);
  if (changelogMatch) {
    const insertPos = changelogMatch.index + changelogMatch[0].length;
    const placeholder = `= ${newVersion} =\n* Release\n\n`;
    updatedReadme = updatedReadme.slice(0, insertPos) + placeholder + updatedReadme.slice(insertPos);
    console.log('   Added placeholder changelog entry. Please update it with actual changes.\n');
  }
}

fs.writeFileSync(readmePath, updatedReadme);

console.log('✨ Release preparation complete!\n');
console.log('Next steps:');
console.log('1. Review the changes in package.json, algolia-bogo.php, and readme.txt');
console.log('2. Update the changelog in readme.txt with actual changes');
console.log('3. Commit the changes:');
console.log(`   git add -A`);
console.log(`   git commit -m "Release v${newVersion}"`);
console.log(`   git tag v${newVersion}`);
console.log(`   git push origin main --tags`);
console.log('4. Create a GitHub release (zip file will be automatically created by GitHub Actions)\n');

