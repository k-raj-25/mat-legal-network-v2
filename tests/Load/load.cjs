/**
 * Minimal concurrent load test (no dependencies).
 *   node tests/Load/load.cjs http://127.0.0.1:8001 20 10 /lawyers "/lawyers?q=sharma"
 * args: baseUrl concurrency seconds ...paths
 */
const http = require('http');
const [base, conc = '20', secs = '10', ...paths] = process.argv.slice(2);
const agent = new http.Agent({ keepAlive: false, maxSockets: +conc });

function hit(url) {
    return new Promise((resolve) => {
        const t = process.hrtime.bigint();
        http.get(url, { agent }, (res) => {
            res.resume();
            res.on('end', () => resolve({ status: res.statusCode, ms: Number(process.hrtime.bigint() - t) / 1e6 }));
        }).on('error', () => resolve({ status: 0, ms: Number(process.hrtime.bigint() - t) / 1e6 }));
    });
}

(async () => {
    for (const p of paths) {
        const url = base + p;
        const lat = []; const codes = {};
        const end = Date.now() + +secs * 1000;
        await Promise.all(Array.from({ length: +conc }, async () => {
            while (Date.now() < end) {
                const r = await hit(url);
                lat.push(r.ms); codes[r.status] = (codes[r.status] || 0) + 1;
            }
        }));
        lat.sort((a, b) => a - b);
        const q = (x) => lat[Math.min(lat.length - 1, Math.floor(lat.length * x))].toFixed(0);
        console.log(`${p.padEnd(58)} ${(lat.length / +secs).toFixed(0).padStart(5)} req/s  p50=${q(0.5)}ms p95=${q(0.95)}ms p99=${q(0.99)}ms  codes=${JSON.stringify(codes)}`);
    }
})();
