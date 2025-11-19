const fs = require('fs')
const util = require('util')

const argv = require('minimist')(process.argv.slice(2), {
  string: ['md', 'readme']
})

const md = argv.md || 'README.md'
const readme = argv.readme || 'readme.txt'

if (!fs.existsSync(md)) {
  console.log(util.format('Source file "%s" not found.', md))
  process.exit(1)
}

fs.readFile(md, 'utf8', (error, mdContent) => {
  if (error) {
    throw error
  }

  let readmeTxt = mdContent

  /*
   * Reverse conversion of grunt-wp-readme-to-markdown
   * Based on: https://github.com/stephenharris/wp-readme-to-markdown
   */

  // Convert Headings: # Title # -> === Title === (reverse of wp-readme-to-markdown)
  // Using the same regex pattern as the original but reversed
  readmeTxt = readmeTxt.replace(new RegExp('^#([^#]+)#*?[\\s ]*?$', 'gim'), '===$1===')
  
  // Convert Headings: ## Section ## -> == Section ==
  readmeTxt = readmeTxt.replace(new RegExp('^##([^#]+)##*?[\\s ]*?$', 'gim'), '==$1==')
  
  // Convert Headings: ### Subsection ### -> = Subsection =
  readmeTxt = readmeTxt.replace(new RegExp('^###([^#]+)###*?[\\s ]*?$', 'gim'), '=$1=')
  
  // Convert Headings: #### -> = (for nested subsections, no closing ###)
  readmeTxt = readmeTxt.replace(new RegExp('^####([^#\\n]+)$', 'gm'), (match, title) => {
    return '= ' + title.trim() + ' ='
  })
  
  // Convert any remaining ### without closing ### -> =
  readmeTxt = readmeTxt.replace(new RegExp('^###([^#\\n]+)$', 'gm'), (match, title) => {
    return '= ' + title.trim() + ' ='
  })

  // Parse header metadata (contributors, donate link, etc.) - reverse conversion
  // The original converts "Key: value" to "**Key:** value", so we reverse it
  const headerMatch = readmeTxt.match(new RegExp('([^##]*)(?:\\n##|$)', 'm'))
  if (headerMatch && headerMatch.length >= 1) {
    const headerSearch = headerMatch[1]
    // Reverse: **Key:** value -> Key: value
    const headerReplace = headerSearch.replace(new RegExp('\\*\\*([^:]+):\\*\\* (.+)', 'gim'), '$1: $2')
    readmeTxt = readmeTxt.replace(headerSearch, headerReplace)
  }

  // Convert contributors links: [name](https://profiles.wordpress.org/name/) -> name (reverse)
  // The original converts "name" to "[name](https://profiles.wordpress.org/name/)", so we reverse it
  const contributorsMatch = readmeTxt.match(new RegExp('(Contributors: )(.+)', 'm'))
  if (contributorsMatch && contributorsMatch.length >= 1) {
    const contributorsSearch = contributorsMatch[0]
    let contributorsReplace = contributorsMatch[1]
    const profiles = []

    // Extract profile names from markdown links
    const profileLinks = contributorsMatch[2].match(/\[([^\]]+)\]\(https:\/\/profiles\.wordpress\.org\/[^\/]+\/\)/g)
    if (profileLinks) {
      profileLinks.forEach((link) => {
        const nameMatch = link.match(/\[([^\]]+)\]/)
        if (nameMatch && nameMatch[1]) {
          profiles.push(nameMatch[1])
        }
      })
      contributorsReplace += profiles.join(', ')
    } else {
      // If no links found, just use the text as is
      contributorsReplace += contributorsMatch[2].replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1')
    }

    readmeTxt = readmeTxt.replace(contributorsSearch, contributorsReplace)
  }

  // Convert bold: **text** -> text (for remaining bold text)
  readmeTxt = readmeTxt.replace(/\*\*([^*]+)\*\*/g, '$1')

  // Convert links: [text](url) -> text (for other links)
  readmeTxt = readmeTxt.replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1')

  // Convert code blocks: tab-indented code -> ```code``` (reverse of wp-readme-to-markdown)
  // The original converts "```code```" to tab-indented format, so we reverse it
  readmeTxt = readmeTxt.replace(new RegExp('^`$[\\n\\r]+([^`]*)[\\n\\r]+^`$', 'gm'), (codeblock, codeblockContents) => {
    // Convert tab-indented code back to markdown code blocks
    const lines = codeblockContents.split('\n')
    const cleanedLines = lines.map(line => line.replace(/^\t/, ''))
    return '```\n' + cleanedLines.join('\n') + '\n```'
  })

  // Also handle markdown code blocks with language tags: ```language\n...\n``` -> tab-indented
  readmeTxt = readmeTxt.replace(/```[\w]*\n([\s\S]*?)```/g, (match, codeblockContents) => {
    const lines = codeblockContents.split('\n')
    // Remove empty lines at start and end
    let startIdx = 0
    let endIdx = lines.length
    while (startIdx < lines.length && lines[startIdx].trim() === '') startIdx++
    while (endIdx > startIdx && lines[endIdx - 1].trim() === '') endIdx--
    const trimmedLines = lines.slice(startIdx, endIdx)
    // Convert to tab-indented format (as in readme.txt) - no extra newlines
    return '\n\t' + trimmedLines.join('\n\t')
  })

  // Clean up code block spacing: remove blank line before tab-indented code blocks
  readmeTxt = readmeTxt.replace(/\n\n\t/g, '\n\t')
  
  // Clean up excessive blank lines (more than 2 consecutive newlines -> 2 newlines)
  readmeTxt = readmeTxt.replace(/\n{3,}/g, '\n\n')
  
  // Remove trailing whitespace from lines
  readmeTxt = readmeTxt.replace(/[ \t]+$/gm, '')
  
  // Remove trailing newlines at the end of file
  readmeTxt = readmeTxt.replace(/\n+$/, '\n')

  fs.writeFile(readme, readmeTxt, (err) => {
    if (err) {
      throw err
    }

    console.log(util.format('Saved "%s" created from "%s".', readme, md))
  })
})

