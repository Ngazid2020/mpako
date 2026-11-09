/**
 * Test de bout en bout du mode hors ligne (Chrome réel via puppeteer-core).
 *
 * Scénario : connexion → préparation automatique → coupure réseau → bascule automatique
 *            → vente hors ligne → retour du réseau → synchronisation.
 *
 * Pré-requis :
 *   - `npm run build` (les assets du serveur Vite de dev ne sont pas mis en cache)
 *   - l'application servie en HTTPS ou sur localhost/127.0.0.1 (service worker)
 *   - un commerçant de test avec au moins un produit en stock
 *
 * Variables : E2E_BASE_URL, E2E_PHONE, E2E_PASSWORD, E2E_SLUG, E2E_PRODUCT, CHROME_PATH
 *
 *   E2E_BASE_URL=https://mpako.test E2E_PHONE=+269... E2E_PASSWORD=... E2E_SLUG=ma-boutique \
 *   E2E_PRODUCT="Riz 5kg" npm run test:e2e
 */
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const env = (name, fallback) => process.env[name] ?? fallback;
const BASE = env('E2E_BASE_URL', 'http://127.0.0.1:8099');
const PHONE = env('E2E_PHONE', '+2693999999');
const PASSWORD = env('E2E_PASSWORD', 'secret123');
const SLUG = env('E2E_SLUG', 'boutique-test');
const PRODUCT = env('E2E_PRODUCT', 'Riz 5kg');
const CHROME = env('CHROME_PATH', process.platform === 'win32'
    ? 'C:/Program Files/Google/Chrome/Application/chrome.exe'
    : '/usr/bin/google-chrome');

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
const check = (label, ok, detail = '') => {
    console.log(`${ok ? '✔' : '✘'} ${label}${detail ? ` — ${detail}` : ''}`);
    if (!ok) failures.push(label);
};

const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'beez-e2e-'));
const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, userDataDir: profile, args: ['--no-first-run'] });
const page = await browser.newPage();
page.on('pageerror', (e) => console.log('  [pageerror]', e.message));
const bodyText = () => page.evaluate(() => document.body.innerText.replace(/\s+/g, ' '));

try {
    // 1. Connexion
    await page.goto(`${BASE}/commerce/login`, { waitUntil: 'networkidle0' });
    await page.type('input[id="data.phone"]', PHONE);
    await page.type('input[id="data.password"]', PASSWORD);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.keyboard.press('Enter')]);
    await page.goto(`${BASE}/commerce/${SLUG}`, { waitUntil: 'networkidle0' });
    check('Connexion au panel', page.url().includes(`/commerce/${SLUG}`), page.url());

    // 2. Préparation automatique (iframe invisible : page + assets + token + données)
    let prepared = false;
    for (let i = 0; i < 30 && !prepared; i++) {
        await sleep(1000);
        prepared = await page.evaluate(() => Object.keys(localStorage).some((k) => k.startsWith('beez-offline-ready:')));
    }
    await sleep(2000);
    const cached = await page.evaluate(async (slug) => !!(await caches.match(`/commerce/${slug}/hors-ligne`)), SLUG);
    check('Mode hors ligne préparé automatiquement', prepared && cached, `données=${prepared} page en cache=${cached}`);

    // 3. Coupure réseau → bascule automatique
    await page.setOfflineMode(true);
    await page.waitForFunction(() => location.pathname.endsWith('/hors-ligne'), { timeout: 20000 }).catch(() => {});
    await sleep(2000);
    check('Bascule automatique vers le mode hors ligne', page.url().endsWith('/hors-ligne'), page.url());
    const offlineText = await bodyText();
    check('Données disponibles hors ligne', offlineText.includes(PRODUCT), `produit « ${PRODUCT} »`);

    // 4. Vente hors ligne
    const [productButton] = await page.$$(`xpath/.//button[.//span[contains(., "${PRODUCT}")]]`);
    await productButton.click();
    await page.click('xpath/.//button[contains(., "Encaisser")]');
    await sleep(1000);
    check('Vente enregistrée hors ligne', /Sync \(1\)/.test(await bodyText()));

    // 5. Retour du réseau → synchronisation automatique
    await page.setOfflineMode(false);
    await page.evaluate(() => window.dispatchEvent(new Event('online')));
    let synced = false;
    for (let i = 0; i < 20 && !synced; i++) {
        await sleep(1000);
        synced = (await bodyText()).includes('tout est synchronisé');
    }
    check('Synchronisation au retour du réseau', synced);
} catch (e) {
    check('Scénario sans erreur', false, e.message);
} finally {
    await browser.close();
    fs.rmSync(profile, { recursive: true, force: true });
}

console.log(failures.length ? `\n${failures.length} échec(s)` : '\nMode hors ligne : OK');
process.exit(failures.length ? 1 : 0);
