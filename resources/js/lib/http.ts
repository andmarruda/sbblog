/**
 * Minimal JSON client for the few calls that live outside Inertia visits (media picker,
 * uploads, inline tag creation). Uses Laravel's XSRF-TOKEN cookie for CSRF.
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export class HttpError extends Error {
    constructor(
        public status: number,
        public errors: Record<string, string[]> = {},
        message = 'Request failed',
    ) {
        super(message);
    }

    /** First validation message, if any. */
    first(): string | undefined {
        return Object.values(this.errors)[0]?.[0] ?? this.message;
    }
}

export async function http<T>(
    url: string,
    init: RequestInit & { json?: unknown } = {},
): Promise<T> {
    const headers = new Headers(init.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');
    headers.set('X-XSRF-TOKEN', xsrfToken());

    let body = init.body;

    if (init.json !== undefined) {
        headers.set('Content-Type', 'application/json');
        body = JSON.stringify(init.json);
    }

    const response = await fetch(url, {
        ...init,
        body,
        headers,
        credentials: 'same-origin',
    });

    const data =
        response.status === 204
            ? null
            : await response.json().catch(() => null);

    if (!response.ok) {
        throw new HttpError(
            response.status,
            (data?.errors as Record<string, string[]>) ?? {},
            (data?.message as string) ?? response.statusText,
        );
    }

    return data as T;
}
