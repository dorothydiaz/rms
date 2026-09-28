#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function checkPackage(pkg) {
  try {
    execSync(`npm view ${pkg} version`, { stdio: 'pipe' });
    return true;
  } catch {
    return false;
  }
}

// Scans target source directory for unknown imports
const srcDir = process.argv[2] || './src';
if (!fs.existsSync(srcDir)) {
  console.log(`Directory ${srcDir} does not exist yet. Skipping.`);
  process.exit(0);
}

const files = fs.readdirSync(srcDir, { recursive: true }).filter(f => /\.(ts|tsx|js|jsx)$/.test(f));
let hallucinated = [];

files.forEach(file => {
  const content = fs.readFileSync(path.join(srcDir, file), 'utf-8');
  const importMatches = content.matchAll(/from ['"]([^'"]+)['"]/g);
  for (const match of importMatches) {
    const pkg = match[1];
    if (!pkg.startsWith('.') && !pkg.startsWith('/') && !checkPackage(pkg.split('/')[0])) {
      hallucinated.push({ file, pkg });
    }
  }
});

if (hallucinated.length > 0) {
  console.error("🚨 AUDIT FAILURE: Hallucinated package(s) detected:");
  console.table(hallucinated);
  process.exit(1);
} else {
  console.log("✅ All external imports verified against NPM registry.");
  process.exit(0);
}
