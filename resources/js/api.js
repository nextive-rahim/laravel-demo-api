const TOKEN_KEY = 'auth_token';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
}

/**
 * Error thrown for any non-2xx API response. Carries the HTTP status and the
 * Laravel validation `errors` bag so components can surface field-level messages.
 */
export class ApiError extends Error {
    constructor(status, data) {
        super(data?.message || 'Something went wrong.');
        this.status = status;
        this.errors = data?.errors || {};
        this.data = data || {};
    }

    /** First validation message for a given field, if any. */
    firstError(field) {
        return this.errors?.[field]?.[0] ?? null;
    }
}

async function request(method, path, body = null) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`/api${path}`, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
    });

    let data = {};
    try {
        data = await response.json();
    } catch {
        // Empty or non-JSON body — leave data as {}.
    }

    if (!response.ok) {
        throw new ApiError(response.status, data);
    }

    return data;
}

export const api = {
    checkPhone: (phone) => request('POST', '/auth/check', { phone }),
    register: (payload) => request('POST', '/auth/register', payload),
    login: (payload) => request('POST', '/auth/login', payload),
    user: () => request('GET', '/auth/user'),
    logout: () => request('POST', '/auth/logout'),
    forgotPassword: (phone) => request('POST', '/auth/forgot-password', { phone }),
    resetPassword: (payload) => request('POST', '/auth/reset-password', payload),
    changePassword: (payload) => request('POST', '/auth/change-password', payload),
};
