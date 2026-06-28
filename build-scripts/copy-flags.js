const copy = require('copy');
const path = require('path');

const srcDir = path.join(__dirname, '../node_modules/circle-flags/flags/*.svg');
const destDir = path.join(__dirname, '../assets/flags');

console.log('Copying circle-flags SVGs...');

copy(srcDir, destDir, function(err, files) {
    if (err) {
        console.error('Error copying flags:', err);
        process.exit(1);
    }
    console.log(`Copied ${files.length} SVG flags successfully!`);
});
