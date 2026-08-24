function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

async function request(url, options = {}) {
    const headers = { Accept: 'application/json', ...options.headers };
    const token = xsrfToken();

    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    if (options.json !== undefined) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.json);
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error('Request failed');
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

export const api = {
    get: (url) => request(url, { method: 'GET' }),
    post: (url, json) => request(url, { method: 'POST', json }),
    patch: (url, json) => request(url, { method: 'PATCH', json }),
    postForm: (url, formData) => request(url, { method: 'POST', body: formData }),
};
