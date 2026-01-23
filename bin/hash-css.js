import fs from 'fs';
import crypto from 'crypto';
import path from 'path';

const distDir = 'resources/dist';
const cssFile = path.join(distDir, 'filament-image-editor.css');
const jsFile = path.join(distDir, 'filament-image-editor.js');
const manifestFile = path.join(distDir, 'manifest.json');

function hashFile(filePath) {
    if (!fs.existsSync(filePath)) {
        return null;
    }

    const content = fs.readFileSync(filePath);
    const hash = crypto.createHash('md5').update(content).digest('hex').substring(0, 8);
    const ext = path.extname(filePath);
    const basename = path.basename(filePath, ext);
    const hashedName = `${basename}.${hash}${ext}`;

    // Copy to hashed version
    const hashedPath = path.join(distDir, hashedName);
    fs.copyFileSync(filePath, hashedPath);

    return hashedName;
}

// Hash files
const hashedCss = hashFile(cssFile);
const hashedJs = hashFile(jsFile);

// Create manifest
const manifest = {
    css: hashedCss || 'filament-image-editor.css',
    js: hashedJs || 'filament-image-editor.js',
};

fs.writeFileSync(manifestFile, JSON.stringify(manifest, null, 2));

console.log('Generated manifest:', manifest);
