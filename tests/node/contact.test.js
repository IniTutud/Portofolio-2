import assert from 'node:assert/strict';
import { afterEach, test } from 'node:test';
import handler from '../../api/contact.js';

const originalWebhookUrl = process.env.DISCORD_WEBHOOK_URL;

const contactPayload = {
    name: 'Ayu',
    email: 'ayu@example.com',
    message: 'Halo, saya ingin berdiskusi.',
};

const createRequest = (method, body = undefined) => new Request('https://portfolio.example/contact', {
    method,
    headers: body === undefined ? {} : { 'Content-Type': 'application/json' },
    body: body === undefined ? undefined : JSON.stringify(body),
});

afterEach(() => {
    if (originalWebhookUrl === undefined) {
        delete process.env.DISCORD_WEBHOOK_URL;
    } else {
        process.env.DISCORD_WEBHOOK_URL = originalWebhookUrl;
    }
});

test('rejects non-POST requests', async () => {
    const response = await handler.fetch(createRequest('GET'));

    assert.equal(response.status, 405);
    assert.equal(response.headers.get('Allow'), 'POST');
});

test('returns field errors when the contact payload is invalid', async () => {
    const response = await handler.fetch(createRequest('POST', { ...contactPayload, email: 'not-an-email' }));

    assert.equal(response.status, 422);
    assert.deepEqual(Object.keys((await response.json()).errors), ['email']);
});

test('returns service unavailable when the Discord webhook is missing', async () => {
    delete process.env.DISCORD_WEBHOOK_URL;
    const response = await handler.fetch(createRequest('POST', contactPayload));

    assert.equal(response.status, 503);
    assert.equal((await response.json()).message, 'Layanan kontak belum dikonfigurasi. Silakan hubungi saya melalui media sosial.');
});

test('rejects webhook URLs outside Discord', async () => {
    process.env.DISCORD_WEBHOOK_URL = 'https://example.com/api/webhooks/123456789012345678/test-token';
    const response = await handler.fetch(createRequest('POST', contactPayload));

    assert.equal(response.status, 503);
    assert.equal((await response.json()).message, 'Layanan kontak belum dikonfigurasi. Silakan hubungi saya melalui media sosial.');
});

test('sends valid contact details to the configured Discord webhook', async (context) => {
    process.env.DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token';
    const requests = [];
    context.mock.method(globalThis, 'fetch', async (url, options) => {
        requests.push({ url, options });

        return new Response(null, { status: 204 });
    });
    const response = await handler.fetch(createRequest('POST', contactPayload));

    assert.equal(response.status, 200);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url, process.env.DISCORD_WEBHOOK_URL);
    assert.equal(requests[0].options.headers.Authorization, undefined);
    assert.deepEqual(JSON.parse(requests[0].options.body).allowed_mentions, { parse: [] });
    assert.equal(JSON.parse(requests[0].options.body).embeds[0].description, contactPayload.message);
});

test('returns a delivery error when Discord rejects the webhook response', async (context) => {
    process.env.DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token';
    context.mock.method(globalThis, 'fetch', async () => new Response('{}', { status: 404 }));
    context.mock.method(console, 'error', () => {});
    const response = await handler.fetch(createRequest('POST', contactPayload));

    assert.equal(response.status, 502);
    assert.equal((await response.json()).message, 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.');
});
