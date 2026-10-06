const webhookPattern = /^https:\/\/discord\.com\/api\/webhooks\/\d{17,20}\/[A-Za-z0-9._-]+$/;

const jsonResponse = (status, body, headers = {}) => Response.json(body, { status, headers });

const validateContact = (body) => {
    const values = {
        name: typeof body?.name === 'string' ? body.name.trim() : '',
        email: typeof body?.email === 'string' ? body.email.trim() : '',
        message: typeof body?.message === 'string' ? body.message.trim() : '',
    };
    const errors = {};

    if (!values.name || values.name.length > 120) {
        errors.name = ['Nama wajib diisi dan maksimal 120 karakter.'];
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email) || values.email.length > 180) {
        errors.email = ['Gunakan format email yang valid, maksimal 180 karakter.'];
    }

    if (!values.message || values.message.length > 2000) {
        errors.message = ['Pesan wajib diisi dan maksimal 2000 karakter.'];
    }

    return { values, errors };
};

const discordFetch = async (url, payload) => fetch(url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    body: JSON.stringify(payload),
    signal: AbortSignal.timeout(5000),
});

export default {
    async fetch(request) {
        if (request.method !== 'POST') {
            return jsonResponse(405, { message: 'Metode request tidak didukung.' }, { Allow: 'POST' });
        }

        let body;

        try {
            body = await request.json();
        } catch {
            return jsonResponse(400, { message: 'Data form tidak valid.' });
        }

        if (!body || typeof body !== 'object' || Array.isArray(body)) {
            return jsonResponse(400, { message: 'Data form tidak valid.' });
        }

        const { values, errors } = validateContact(body);

        if (Object.keys(errors).length > 0) {
            return jsonResponse(422, {
                message: 'Periksa kembali data yang kamu isi.',
                errors,
            });
        }

        const webhookUrl = process.env.DISCORD_WEBHOOK_URL;

        if (!webhookUrl || !webhookPattern.test(webhookUrl)) {
            return jsonResponse(503, {
                message: 'Layanan kontak belum dikonfigurasi. Silakan hubungi saya melalui media sosial.',
            });
        }

        try {
            const discordResponse = await discordFetch(webhookUrl, {
                embeds: [
                    {
                        title: 'Pesan baru dari portofolio',
                        color: 0x176b49,
                        fields: [
                            { name: 'Nama', value: values.name, inline: true },
                            { name: 'Email', value: values.email, inline: true },
                        ],
                        description: values.message,
                    },
                ],
                allowed_mentions: { parse: [] },
            });

            if (!discordResponse.ok) {
                console.error('Discord contact webhook request failed.', {
                    status: discordResponse.status,
                });

                return jsonResponse(502, {
                    message: 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.',
                });
            }
        } catch (error) {
            console.error('Discord contact webhook request failed.', error);

            return jsonResponse(502, {
                message: 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.',
            });
        }

        return jsonResponse(200, {
            message: 'Pesanmu berhasil dikirim. Terima kasih sudah menghubungi saya.',
        });
    },
};
