/**
 * Lance le jeu dans Chrome headless, servi par « php -S », avec une horloge virtuelle :
 * on avance image par image bien plus vite que le temps réel. Le son est coupé (fichiers audio bloqués).
 *
 * Chrome : CHROME_PATH, sinon /usr/bin/google-chrome.
 */
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { createServer } from 'node:net';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright-core';

const ROOT = fileURLToPath(new URL('../..', import.meta.url));

async function freePort() {
    const server = createServer().listen(0);
    await once(server, 'listening');
    const { port } = server.address();
    server.close();
    return port;
}

/** Remplace requestAnimationFrame et performance.now par une horloge pilotée par le test. */
function installVirtualClock() {
    let now = 0;
    let queue = [];
    performance.now = () => now;
    window.requestAnimationFrame = (cb) => queue.push(cb);
    window.__runFrames = (frames) => {
        for (let i = 0; i < frames; i++) {
            now += 1000 / 60;
            const callbacks = queue;
            queue = [];
            callbacks.forEach((cb) => cb(now));
        }
    };
}

export async function openGame(path = 'index.php?debug') {
    const port = await freePort();
    const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', ROOT], { stdio: 'ignore' });
    await new Promise((resolve) => setTimeout(resolve, 500));

    const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH ?? '/usr/bin/google-chrome' });
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    const errors = [];
    page.on('pageerror', (e) => errors.push(`${e.message}\n${e.stack}`));
    page.on('console', (m) => {
        if (m.type() === 'error' && !m.text().includes('Failed to load resource')) errors.push(m.text());
    });
    await page.route(/\.(mp3|wav|ogg)(\?.*)?$/, (route) => route.abort());
    await page.addInitScript(installVirtualClock);
    await page.goto(`http://127.0.0.1:${port}/${path}`, { timeout: 120000 });
    await page.waitForFunction(() => window.game, null, { timeout: 30000 });

    const game = {
        page,
        errors,
        /** Avance de N images (60 = une seconde de jeu). */
        async run(frames) {
            await page.evaluate((n) => window.__runFrames(n), frames);
            await page.waitForTimeout(10); // laisse arriver les fetch() des décors
        },
        async press(key, frames = 20) {
            await page.keyboard.press(key);
            await game.run(frames);
        },
        state: () => page.evaluate(() => {
            const s = window.game.state;
            return { mode: s.mode, level: s.levelIndex, selected: s.selected, lives: s.lives, score: s.score, demo: s.demo };
        }),
        /** Avance jusqu'à ce que predicate(état) soit vrai ; renvoie l'état, ou null après maxFrames. */
        async until(predicate, maxFrames) {
            for (let frames = 0; frames < maxFrames; frames += 120) {
                await game.run(120);
                const state = await game.state();
                if (predicate(state)) return state;
            }
            return null;
        },
        async close() {
            await browser.close();
            server.kill();
        },
    };
    await game.run(30);
    return game;
}
