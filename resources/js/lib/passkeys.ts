import {
    browserSupportsWebAuthn,
    startAuthentication,
    startRegistration,
    WebAuthnError,
    type PublicKeyCredentialCreationOptionsJSON,
    type PublicKeyCredentialRequestOptionsJSON,
} from '@simplewebauthn/browser';
import { http } from '@/lib/http';

export const passkeysSupported = () => browserSupportsWebAuthn();

/** True when the person closed or cancelled the browser's passkey prompt. */
export const cancelled = (error: unknown) =>
    (error instanceof WebAuthnError &&
        error.code === 'ERROR_CEREMONY_ABORTED') ||
    (error instanceof DOMException && error.name === 'NotAllowedError');

/**
 * Ask the console for a challenge, let the browser sign it with a passkey and send the result.
 */
export async function authenticate<T>(
    optionsUrl: string,
    verifyUrl: string,
    extra: Record<string, unknown> = {},
): Promise<T> {
    const optionsJSON = await http<PublicKeyCredentialRequestOptionsJSON>(
        optionsUrl,
        { method: 'POST' },
    );
    const credential = await startAuthentication({ optionsJSON });

    return http<T>(verifyUrl, {
        method: 'POST',
        json: { ...extra, credential },
    });
}

export async function register(
    optionsUrl: string,
    storeUrl: string,
    name: string,
): Promise<void> {
    const optionsJSON = await http<PublicKeyCredentialCreationOptionsJSON>(
        optionsUrl,
        { method: 'POST' },
    );
    const credential = await startRegistration({ optionsJSON });

    await http(storeUrl, { method: 'POST', json: { name, credential } });
}
