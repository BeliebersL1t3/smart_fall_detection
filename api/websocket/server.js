import http from 'http';
import { WebSocketServer } from 'ws';

const PORT = parseInt(process.env.IOT_WS_PORT || '6001', 10);
const SECRET = process.env.IOT_WS_SECRET || 'careguard-ws-secret-change-me';

/** @type {Map<number, Set<import('ws').WebSocket>>} */
const clientsByUser = new Map();

function addClient(userId, ws) {
    if (!clientsByUser.has(userId)) {
        clientsByUser.set(userId, new Set());
    }
    clientsByUser.get(userId).add(ws);
}

function removeClient(userId, ws) {
    const set = clientsByUser.get(userId);
    if (!set) return;
    set.delete(ws);
    if (set.size === 0) clientsByUser.delete(userId);
}

function broadcast(userId, message) {
    const set = clientsByUser.get(userId);
    if (!set) return 0;

    const payload = JSON.stringify(message);
    let sent = 0;

    for (const ws of set) {
        if (ws.readyState === 1) {
            ws.send(payload);
            sent++;
        }
    }

    return sent;
}

const server = http.createServer((req, res) => {
    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';

        req.on('data', (chunk) => {
            body += chunk;
        });

        req.on('end', () => {
            try {
                const data = JSON.parse(body);

                if (data.secret !== SECRET) {
                    res.writeHead(403, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ ok: false, message: 'Forbidden' }));
                    return;
                }

                const userId = parseInt(data.user_id, 10);
                const sent = broadcast(userId, {
                    type: data.type,
                    payload: data.payload,
                    at: new Date().toISOString(),
                });

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: true, clients: sent }));
            } catch {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, message: 'Bad request' }));
            }
        });

        return;
    }

    if (req.method === 'GET' && req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true, clients: [...clientsByUser.values()].reduce((n, s) => n + s.size, 0) }));
        return;
    }

    res.writeHead(404);
    res.end();
});

const wss = new WebSocketServer({ server });

wss.on('connection', (ws, req) => {
    const url = new URL(req.url || '/', `http://${req.headers.host}`);
    const userId = parseInt(url.searchParams.get('user_id') || '0', 10);

    if (!userId) {
        ws.close(4001, 'user_id required');
        return;
    }

    addClient(userId, ws);

    ws.send(JSON.stringify({
        type: 'connected',
        payload: { user_id: userId, message: 'CareGuard WebSocket ready' },
        at: new Date().toISOString(),
    }));

    ws.on('close', () => removeClient(userId, ws));
    ws.on('error', () => removeClient(userId, ws));
});

server.listen(PORT, () => {
    console.log(`CareGuard WebSocket server on ws://127.0.0.1:${PORT}`);
    console.log(`Broadcast HTTP: http://127.0.0.1:${PORT}/broadcast`);
});
