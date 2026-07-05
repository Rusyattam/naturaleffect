const fs = require('fs').promises;
const path = require('path');
const root = path.join(__dirname, 'f9');
const markers = [
  /\/\* Gift box grid game \*\//,
  /<p id=\"marathon-text\"/,
  /<div class=\"form-box hidden\">/,
  /<div class=\"join-btn-wrap\">/,
  /<!-- Modal win -->/,
  /\/\/ Telegram bot/,
  /if \(document\.readyState === 'loading'\)/
];

async function find(dir) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  for (const e of entries) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) await find(p);
    else if (e.name === 'index.html') {
      const text = await fs.readFile(p, 'utf8');
      const found = markers.map(rx => rx.test(text));
      if (!found.every(Boolean)) {
        console.log(p, found);
      }
    }
  }
}

find(root).catch(err => { console.error(err); process.exit(1); });
