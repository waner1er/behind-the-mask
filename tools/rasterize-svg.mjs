/**
 * Rend des fichiers SVG en PNG (fond transparent) avec Chrome, le seul moteur qui dessine
 * les décors exactement comme dans le navigateur. Utilisé par l'export OpenBOR.
 *
 *   node tools/rasterize-svg.mjs dossier/   → chaque dossier/*.svg devient dossier/*.png
 *
 * Chrome : CHROME_PATH, sinon /usr/bin/google-chrome.
 */
import { readdir, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { chromium } from 'playwright-core';

const [directory] = process.argv.slice(2);
const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH ?? '/usr/bin/google-chrome' });
try {
    const page = await browser.newPage();
    for (const file of (await readdir(directory)).filter((name) => name.endsWith('.svg')).sort()) {
        const svg = await readFile(join(directory, file), 'utf8');
        const [, width, height] = svg.match(/width="(\d+)" height="(\d+)"/);
        await page.setViewportSize({ width: Number(width), height: Number(height) });
        await page.setContent(`<style>html,body{margin:0;background:transparent}svg{display:block}</style>${svg}`);
        await page.screenshot({ path: join(directory, file.replace(/\.svg$/, '.png')), omitBackground: true });
    }
} finally {
    await browser.close();
}
