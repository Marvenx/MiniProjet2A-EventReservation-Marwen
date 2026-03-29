/**
 * Passkey (WebAuthn) client-side implementation for Windows Hello support.
 */
class PasskeyManager {
    constructor() {
        this.isSupported = this.checkSupport();
    }

    /**
     * Check if WebAuthn is supported by the browser.
     */
    checkSupport() {
        return !!(window.PublicKeyCredential && 
                  navigator.credentials && 
                  navigator.credentials.create && 
                  navigator.credentials.get);
    }

    /**
     * Check if platform authenticator (Windows Hello, Touch ID) is available.
     */
    async isPlatformAuthenticatorAvailable() {
        if (!this.isSupported) return false;
        
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch {
            return false;
        }
    }

    /**
     * Base64URL encode a buffer.
     */
    bufferToBase64URL(buffer) {
        const bytes = new Uint8Array(buffer);
        let str = '';
        for (const byte of bytes) {
            str += String.fromCharCode(byte);
        }
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    /**
     * Base64URL decode to ArrayBuffer.
     */
    base64URLToBuffer(base64url) {
        const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        const padded = base64 + '='.repeat((4 - base64.length % 4) % 4);
        const binary = atob(padded);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    /**
     * Register a new passkey for the current user.
     * @param {string} name - Friendly name for the passkey (e.g., "Windows Hello")
     */
    async register(name = 'Windows Hello') {
        if (!this.isSupported) {
            throw new Error('WebAuthn is not supported in this browser');
        }

        // Step 1: Get registration options from server (uses session auth)
        const optionsResponse = await fetch('/account/passkey/register/options', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include'
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.error || 'Failed to get registration options');
        }

        const options = await optionsResponse.json();

        // Step 2: Convert options for navigator.credentials.create
        const createOptions = {
            publicKey: {
                challenge: this.base64URLToBuffer(options.challenge),
                rp: options.rp,
                user: {
                    id: this.base64URLToBuffer(options.user.id),
                    name: options.user.name,
                    displayName: options.user.displayName
                },
                pubKeyCredParams: options.pubKeyCredParams,
                authenticatorSelection: options.authenticatorSelection,
                timeout: options.timeout,
                attestation: options.attestation
            }
        };

        if (options.excludeCredentials) {
            createOptions.publicKey.excludeCredentials = options.excludeCredentials.map(cred => ({
                type: cred.type,
                id: this.base64URLToBuffer(cred.id)
            }));
        }

        // Step 3: Create credential using Windows Hello / Touch ID
        let credential;
        try {
            credential = await navigator.credentials.create(createOptions);
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                throw new Error('Registration was cancelled or timed out');
            }
            throw err;
        }

        // Step 4: Send credential to server (uses session auth)
        const response = {
            id: credential.id,
            rawId: this.bufferToBase64URL(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: this.bufferToBase64URL(credential.response.clientDataJSON),
                attestationObject: this.bufferToBase64URL(credential.response.attestationObject)
            },
            name: name
        };

        const verifyResponse = await fetch('/account/passkey/register/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(response)
        });

        if (!verifyResponse.ok) {
            const error = await verifyResponse.json();
            throw new Error(error.error || 'Failed to verify registration');
        }

        return await verifyResponse.json();
    }

    /**
     * Login with passkey.
     * @param {string} email - Optional email to hint which credentials to use
     */
    async login(email = null) {
        if (!this.isSupported) {
            throw new Error('WebAuthn is not supported in this browser');
        }

        // Step 1: Get authentication options from server
        const optionsResponse = await fetch('/api/passkey/login/options', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email })
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.error || 'Failed to get login options');
        }

        const options = await optionsResponse.json();

        // Step 2: Convert options for navigator.credentials.get
        const getOptions = {
            publicKey: {
                challenge: this.base64URLToBuffer(options.challenge),
                rpId: options.rpId,
                userVerification: options.userVerification,
                timeout: options.timeout
            }
        };

        if (options.allowCredentials) {
            getOptions.publicKey.allowCredentials = options.allowCredentials.map(cred => ({
                type: cred.type,
                id: this.base64URLToBuffer(cred.id)
            }));
        }

        // Step 3: Get credential using Windows Hello / Touch ID
        let credential;
        try {
            credential = await navigator.credentials.get(getOptions);
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                throw new Error('Login was cancelled or timed out');
            }
            throw err;
        }

        // Step 4: Send assertion to server
        const response = {
            id: credential.id,
            rawId: this.bufferToBase64URL(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: this.bufferToBase64URL(credential.response.clientDataJSON),
                authenticatorData: this.bufferToBase64URL(credential.response.authenticatorData),
                signature: this.bufferToBase64URL(credential.response.signature),
                userHandle: credential.response.userHandle 
                    ? this.bufferToBase64URL(credential.response.userHandle) 
                    : null
            }
        };

        const verifyResponse = await fetch('/api/passkey/login/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(response)
        });

        if (!verifyResponse.ok) {
            const error = await verifyResponse.json();
            throw new Error(error.error || 'Authentication failed');
        }

        return await verifyResponse.json();
    }

    /**
     * Get list of registered passkeys for current user.
     */
    async listPasskeys() {
        const response = await fetch('/account/passkey/list', {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error('Failed to list passkeys');
        }

        return await response.json();
    }

    /**
     * Delete a passkey.
     */
    async deletePasskey(id) {
        const response = await fetch(`/account/passkey/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to delete passkey');
        }

        return await response.json();
    }
}

// Create global instance
window.passkeyManager = new PasskeyManager();

// Initialize passkey button on login page
document.addEventListener('DOMContentLoaded', async () => {
    const passkeyBtn = document.getElementById('passkeyLoginBtn');
    const emailInput = document.getElementById('inputEmail');
    
    if (!passkeyBtn) return;

    // Check if WebAuthn is supported (Windows Hello PIN works even without biometrics!)
    if (window.passkeyManager.isSupported) {
        // Enable the button - Windows Hello with PIN will work
        passkeyBtn.disabled = false;
        passkeyBtn.innerHTML = '<i class="bi bi-fingerprint fs-5"></i> <span>Se connecter avec Windows Hello</span>';
        
        passkeyBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const originalText = passkeyBtn.innerHTML;
            passkeyBtn.disabled = true;
            passkeyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Authentification...';

            try {
                const email = emailInput?.value?.trim();
                
                // Require email before passkey login
                if (!email) {
                    throw new Error('Veuillez entrer votre email avant d\'utiliser Windows Hello');
                }
                
                const result = await window.passkeyManager.login(email);
                
                // Store JWT token
                if (result.token) {
                    localStorage.setItem('jwt_token', result.token);
                }

                // Show success and redirect
                passkeyBtn.innerHTML = '<i class="bi bi-check-circle fs-5"></i> <span>Connexion réussie!</span>';
                passkeyBtn.classList.remove('btn-dark');
                passkeyBtn.classList.add('btn-success');
                
                // Redirect to home after brief delay
                setTimeout(() => {
                    window.location.href = '/';
                }, 500);

            } catch (error) {
                console.error('Passkey login error:', error);
                
                passkeyBtn.innerHTML = originalText;
                passkeyBtn.disabled = false;

                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger d-flex align-items-center gap-2 py-2 mt-2';
                alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> ${error.message}`;
                passkeyBtn.parentNode.insertBefore(alertDiv, passkeyBtn.nextSibling);
                
                // Remove alert after 5 seconds
                setTimeout(() => alertDiv.remove(), 5000);
            }
        });
    } else {
        // WebAuthn not supported at all
        passkeyBtn.innerHTML = '<i class="bi bi-fingerprint fs-5"></i> <span>Passkey non supporté</span>';
        passkeyBtn.classList.add('opacity-50');
    }
});
