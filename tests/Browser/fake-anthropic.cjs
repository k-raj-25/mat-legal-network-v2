/**
 * Tiny stand-in for the Anthropic Messages API, so browser tests never call the real AI.
 * Point the app at it with SPAM_AI_BASE_URL=http://127.0.0.1:<port> and any ANTHROPIC_API_KEY.
 *   message contains "MOCKSPAM" -> spam, 97% confident
 *   message contains "MOCKFAIL" -> HTTP 500 (tests the fail-open path)
 *   anything else               -> genuine
 */
const http = require('http');

function start(port = Number(process.env.FAKE_ANTHROPIC_PORT || 8787)) {
    const requests = [];
    const server = http.createServer((req, res) => {
        let raw = '';
        req.on('data', (c) => { raw += c; });
        req.on('end', () => {
            requests.push({ url: req.url, headers: req.headers, raw });
            let body = {};
            try { body = JSON.parse(raw); } catch {}
            const text = JSON.stringify(body.messages || '');
            if (req.url !== '/v1/messages' || text.includes('MOCKFAIL')) {
                res.writeHead(500, { 'content-type': 'application/json' });
                return res.end(JSON.stringify({ type: 'error', error: { type: 'api_error', message: 'mock failure' } }));
            }
            const spam = text.includes('MOCKSPAM');
            const verdict = spam
                ? { is_spam: true, confidence: 97, reason: 'Unsolicited SEO sales pitch with a link; not a legal request.' }
                : { is_spam: false, confidence: 4, reason: 'Describes a genuine legal problem.' };
            res.writeHead(200, { 'content-type': 'application/json' });
            res.end(JSON.stringify({
                id: 'msg_mock', type: 'message', role: 'assistant', model: body.model || 'mock',
                content: [{ type: 'text', text: JSON.stringify(verdict) }],
                stop_reason: 'end_turn', stop_sequence: null, usage: { input_tokens: 300, output_tokens: 40 },
            }));
        });
    });
    return new Promise((resolve) => server.listen(port, '127.0.0.1', () => resolve({ server, requests, port })));
}

module.exports = { start };
if (require.main === module) start().then(({ port }) => console.log('fake Anthropic API on ' + port));
