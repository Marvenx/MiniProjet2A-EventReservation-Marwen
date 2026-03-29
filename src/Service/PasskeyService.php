<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\WebauthnCredentialRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * PasskeyService handles WebAuthn registration and authentication.
 * Implements the WebAuthn standard for Windows Hello, Touch ID, Face ID, etc.
 */
class PasskeyService
{
    private const CHALLENGE_LENGTH = 32;
    private const CHALLENGE_SESSION_KEY = 'webauthn_challenge';
    private const USER_SESSION_KEY = 'webauthn_user_id';

    public function __construct(
        private EntityManagerInterface $em,
        private WebauthnCredentialRepository $credentialRepository,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private string $rpId = 'localhost',
        private string $rpName = 'EventHub'
    ) {}

    /**
     * Generate registration options for creating a new passkey.
     * User must be logged in.
     */
    public function generateRegistrationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();
        $this->storeChallenge($challenge, $user->getId());

        // Get existing credentials to exclude
        $existingCredentials = $this->credentialRepository->findByUser($user);
        $excludeCredentials = array_map(fn($cred) => [
            'type' => 'public-key',
            'id' => $cred->getCredentialId(), // Already stored as base64url
        ], $existingCredentials);

        return [
            'challenge' => $this->base64UrlEncode($challenge),
            'rp' => [
                'name' => $this->rpName,
                'id' => $this->rpId,
            ],
            'user' => [
                'id' => $this->base64UrlEncode((string)$user->getId()),
                'name' => $user->getEmail(),
                'displayName' => $user->getEmail(),
            ],
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'],   // ES256 (ECDSA P-256)
                ['alg' => -257, 'type' => 'public-key'], // RS256 (RSA PKCS#1)
            ],
            'authenticatorSelection' => [
                // Don't restrict to 'platform' - allow Windows Hello PIN, security keys, etc.
                'userVerification' => 'required',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $excludeCredentials,
        ];
    }

    /**
     * Verify the registration response and save the credential.
     */
    public function verifyRegistration(User $user, array $response, string $name = 'My Passkey'): WebauthnCredential
    {
        $session = $this->requestStack->getSession();
        $storedChallenge = $session->get(self::CHALLENGE_SESSION_KEY);
        $storedUserId = $session->get(self::USER_SESSION_KEY);

        if (!$storedChallenge || $storedUserId !== $user->getId()) {
            throw new \RuntimeException('Invalid session state');
        }

        // Decode the response
        $clientDataJSON = $this->base64UrlDecode($response['response']['clientDataJSON']);
        $attestationObject = $this->base64UrlDecode($response['response']['attestationObject']);
        $credentialId = $response['rawId'];

        // Parse and validate clientDataJSON
        $clientData = json_decode($clientDataJSON, true);
        if (!$clientData) {
            throw new \RuntimeException('Invalid clientDataJSON');
        }

        // Verify type
        if ($clientData['type'] !== 'webauthn.create') {
            throw new \RuntimeException('Invalid ceremony type');
        }

        // Verify challenge
        $receivedChallenge = $this->base64UrlDecode($clientData['challenge']);
        if (!hash_equals($storedChallenge, $receivedChallenge)) {
            throw new \RuntimeException('Challenge mismatch');
        }

        // Verify origin
        $expectedOrigins = ['http://localhost:8000', 'https://localhost:8000', 'http://localhost', 'https://localhost'];
        if (!in_array($clientData['origin'], $expectedOrigins, true)) {
            throw new \RuntimeException('Invalid origin: ' . $clientData['origin']);
        }

        // Parse attestation object (CBOR encoded)
        $attestation = $this->parseCBOR($attestationObject);
        $authData = $attestation['authData'];

        // Extract public key from authData
        $publicKeyData = $this->extractPublicKeyFromAuthData($authData);

        // Create and save credential
        // Store credentialId as base64 string (column is VARCHAR)
        $credential = new WebauthnCredential();
        $credential->setUser($user);
        $credential->setCredentialId($credentialId); // Already base64url encoded
        $credential->setCredentialData(json_encode([
            'publicKey' => $this->base64UrlEncode($publicKeyData['publicKey']),
            'counter' => $publicKeyData['counter'],
            'aaguid' => $publicKeyData['aaguid'],
        ]));
        $credential->setName($name);

        $this->em->persist($credential);
        $this->em->flush();

        // Clear session
        $session->remove(self::CHALLENGE_SESSION_KEY);
        $session->remove(self::USER_SESSION_KEY);

        return $credential;
    }

    /**
     * Generate authentication options for passkey login.
     */
    public function generateAuthenticationOptions(?string $email = null): array
    {
        $challenge = $this->generateChallenge();
        
        $allowCredentials = [];
        $userId = null;

        if ($email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $userId = $user->getId();
                $credentials = $this->credentialRepository->findByUser($user);
                $allowCredentials = array_map(fn($cred) => [
                    'type' => 'public-key',
                    'id' => $cred->getCredentialId(), // Already stored as base64url
                ], $credentials);
            }
        }

        $this->storeChallenge($challenge, $userId);

        $options = [
            'challenge' => $this->base64UrlEncode($challenge),
            'rpId' => $this->rpId,
            'userVerification' => 'required',
            'timeout' => 60000,
        ];

        if (!empty($allowCredentials)) {
            $options['allowCredentials'] = $allowCredentials;
        }

        return $options;
    }

    /**
     * Verify authentication response and return the authenticated user.
     */
    public function verifyAuthentication(array $response): User
    {
        $session = $this->requestStack->getSession();
        $storedChallenge = $session->get(self::CHALLENGE_SESSION_KEY);

        if (!$storedChallenge) {
            throw new \RuntimeException('No authentication in progress');
        }

        // Find credential (stored as base64url string)
        $credentialId = $response['rawId'];
        $credential = $this->credentialRepository->findByCredentialId($credentialId);

        if (!$credential) {
            throw new \RuntimeException('Unknown credential');
        }

        // Decode response
        $clientDataJSON = $this->base64UrlDecode($response['response']['clientDataJSON']);
        $authenticatorData = $this->base64UrlDecode($response['response']['authenticatorData']);
        $signature = $this->base64UrlDecode($response['response']['signature']);

        // Parse clientData
        $clientData = json_decode($clientDataJSON, true);
        if (!$clientData) {
            throw new \RuntimeException('Invalid clientDataJSON');
        }

        // Verify type
        if ($clientData['type'] !== 'webauthn.get') {
            throw new \RuntimeException('Invalid ceremony type');
        }

        // Verify challenge
        $receivedChallenge = $this->base64UrlDecode($clientData['challenge']);
        if (!hash_equals($storedChallenge, $receivedChallenge)) {
            throw new \RuntimeException('Challenge mismatch');
        }

        // Verify signature
        $credentialData = json_decode($credential->getCredentialData(), true);
        $publicKey = $this->base64UrlDecode($credentialData['publicKey']);

        // Create verification data: authenticatorData + SHA256(clientDataJSON)
        $clientDataHash = hash('sha256', $clientDataJSON, true);
        $signedData = $authenticatorData . $clientDataHash;

        // Verify the signature
        if (!$this->verifySignature($signedData, $signature, $publicKey)) {
            throw new \RuntimeException('Invalid signature');
        }

        // Verify counter (replay protection)
        $newCounter = unpack('N', substr($authenticatorData, 33, 4))[1];
        $storedCounter = $credentialData['counter'];

        if ($newCounter <= $storedCounter && $newCounter !== 0) {
            throw new \RuntimeException('Possible replay attack detected');
        }

        // Update counter
        $credentialData['counter'] = $newCounter;
        $credential->setCredentialData(json_encode($credentialData));
        $credential->updateLastUsed();
        $this->em->flush();

        // Clear session
        $session->remove(self::CHALLENGE_SESSION_KEY);
        $session->remove(self::USER_SESSION_KEY);

        return $credential->getUser();
    }

    /**
     * Get all credentials for a user.
     * @return WebauthnCredential[]
     */
    public function getCredentials(User $user): array
    {
        return $this->credentialRepository->findByUser($user);
    }

    /**
     * Delete a credential.
     */
    public function deleteCredential(User $user, int $credentialId): void
    {
        $credential = $this->credentialRepository->find($credentialId);

        if (!$credential || $credential->getUser()->getId() !== $user->getId()) {
            throw new \RuntimeException('Credential not found');
        }

        $this->em->remove($credential);
        $this->em->flush();
    }

    // === Helper Methods ===

    private function generateChallenge(): string
    {
        return random_bytes(self::CHALLENGE_LENGTH);
    }

    private function storeChallenge(string $challenge, ?int $userId): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::CHALLENGE_SESSION_KEY, $challenge);
        $session->set(self::USER_SESSION_KEY, $userId);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        return base64_decode(strtr($padded, '-_', '+/'));
    }

    /**
     * Simple CBOR parser for attestation object.
     */
    private function parseCBOR(string $data): array
    {
        // Minimal CBOR parsing for WebAuthn attestation
        // Format: {"fmt": string, "authData": bytes, "attStmt": map}
        $pos = 0;
        $result = [];

        // Map header (0xa0-0xbf for small maps)
        $mapHeader = ord($data[$pos++]);
        $mapSize = $mapHeader & 0x1f;

        for ($i = 0; $i < $mapSize; $i++) {
            // Read key (text string)
            $keyHeader = ord($data[$pos++]);
            $keyLen = $keyHeader & 0x1f;
            $key = substr($data, $pos, $keyLen);
            $pos += $keyLen;

            // Read value based on type
            $valHeader = ord($data[$pos++]);
            $majorType = ($valHeader >> 5) & 0x07;

            switch ($majorType) {
                case 2: // Byte string
                    $len = $this->readCBORLength($valHeader, $data, $pos);
                    $result[$key] = substr($data, $pos, $len);
                    $pos += $len;
                    break;
                case 3: // Text string
                    $len = $valHeader & 0x1f;
                    $result[$key] = substr($data, $pos, $len);
                    $pos += $len;
                    break;
                case 5: // Map (skip for attStmt)
                    $result[$key] = [];
                    $subMapSize = $valHeader & 0x1f;
                    // Skip the map content for now (not needed for 'none' attestation)
                    break;
            }
        }

        return $result;
    }

    private function readCBORLength(int $header, string $data, int &$pos): int
    {
        $additionalInfo = $header & 0x1f;

        if ($additionalInfo < 24) {
            return $additionalInfo;
        } elseif ($additionalInfo === 24) {
            return ord($data[$pos++]);
        } elseif ($additionalInfo === 25) {
            $len = unpack('n', substr($data, $pos, 2))[1];
            $pos += 2;
            return $len;
        }

        return 0;
    }

    /**
     * Extract public key and counter from authenticator data.
     */
    private function extractPublicKeyFromAuthData(string $authData): array
    {
        // authData structure:
        // - rpIdHash: 32 bytes
        // - flags: 1 byte
        // - counter: 4 bytes (big-endian)
        // - aaguid: 16 bytes (if AT flag set)
        // - credentialIdLength: 2 bytes (big-endian)
        // - credentialId: L bytes
        // - credentialPublicKey: COSE_Key (CBOR)

        $rpIdHash = substr($authData, 0, 32);
        $flags = ord($authData[32]);
        $counter = unpack('N', substr($authData, 33, 4))[1];

        $pos = 37;

        // Check AT flag (bit 6)
        if (!($flags & 0x40)) {
            throw new \RuntimeException('No attestation data present');
        }

        $aaguid = substr($authData, $pos, 16);
        $pos += 16;

        $credIdLen = unpack('n', substr($authData, $pos, 2))[1];
        $pos += 2;

        $credentialId = substr($authData, $pos, $credIdLen);
        $pos += $credIdLen;

        // The rest is the COSE public key
        $publicKeyCBOR = substr($authData, $pos);

        return [
            'publicKey' => $publicKeyCBOR,
            'counter' => $counter,
            'aaguid' => bin2hex($aaguid),
        ];
    }

    /**
     * Verify signature using the stored public key.
     */
    private function verifySignature(string $data, string $signature, string $publicKeyCBOR): bool
    {
        // Parse COSE key to get algorithm and key data
        $coseKey = $this->parseCOSEKey($publicKeyCBOR);

        switch ($coseKey['alg']) {
            case -7: // ES256 (ECDSA with P-256)
                return $this->verifyES256($data, $signature, $coseKey);
            case -257: // RS256 (RSA with SHA-256)
                return $this->verifyRS256($data, $signature, $coseKey);
            default:
                throw new \RuntimeException('Unsupported algorithm: ' . $coseKey['alg']);
        }
    }

    /**
     * Parse COSE key from CBOR.
     */
    private function parseCOSEKey(string $cbor): array
    {
        $result = ['alg' => -7]; // Default to ES256
        $pos = 0;

        $header = ord($cbor[$pos++]);
        $mapSize = $header & 0x1f;

        for ($i = 0; $i < $mapSize; $i++) {
            // Read key (could be negative int or positive)
            $keyByte = ord($cbor[$pos++]);
            $majorType = ($keyByte >> 5) & 0x07;
            $key = $keyByte & 0x1f;

            if ($majorType === 1) { // Negative int
                $key = -1 - $key;
            }

            // Read value
            $valByte = ord($cbor[$pos++]);
            $valMajor = ($valByte >> 5) & 0x07;
            $valInfo = $valByte & 0x1f;

            switch ($valMajor) {
                case 0: // Unsigned int
                    $result[$key] = $valInfo;
                    break;
                case 1: // Negative int
                    $result[$key] = -1 - $valInfo;
                    break;
                case 2: // Byte string
                    $len = $this->readCBORLength($valByte, $cbor, $pos);
                    $result[$key] = substr($cbor, $pos, $len);
                    $pos += $len;
                    break;
            }
        }

        return $result;
    }

    /**
     * Verify ES256 (ECDSA P-256) signature.
     */
    private function verifyES256(string $data, string $signature, array $coseKey): bool
    {
        // COSE key labels: -2 = x, -3 = y
        $x = $coseKey[-2] ?? null;
        $y = $coseKey[-3] ?? null;

        if (!$x || !$y || strlen($x) !== 32 || strlen($y) !== 32) {
            throw new \RuntimeException('Invalid EC key coordinates');
        }

        // Build PEM public key
        // Uncompressed point: 0x04 || x || y
        $point = "\x04" . $x . $y;

        // ASN.1 DER encoding for EC P-256 public key
        $der = "\x30\x59" .                         // SEQUENCE (89 bytes)
               "\x30\x13" .                         // SEQUENCE (19 bytes)
               "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01" . // OID 1.2.840.10045.2.1 (ecPublicKey)
               "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" . // OID 1.2.840.10045.3.1.7 (prime256v1)
               "\x03\x42\x00" . $point;             // BIT STRING (66 bytes)

        $pem = "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END PUBLIC KEY-----";

        // WebAuthn signature is raw R||S format, need to convert to DER
        $derSignature = $this->convertRawECSignatureToDER($signature);

        $pubKey = openssl_pkey_get_public($pem);
        if (!$pubKey) {
            throw new \RuntimeException('Failed to parse public key');
        }

        $result = openssl_verify($data, $derSignature, $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    /**
     * Convert raw EC signature (R||S) to DER format.
     * Handles both raw format and already-DER-encoded signatures.
     */
    private function convertRawECSignatureToDER(string $raw): string
    {
        $len = strlen($raw);
        
        // If signature starts with 0x30 (SEQUENCE), it's already DER format
        if ($len > 0 && ord($raw[0]) === 0x30) {
            return $raw;
        }
        
        // For P-256, signature should be 64 bytes (32 for R, 32 for S)
        // For P-384, it would be 96 bytes, for P-521 it would be 132 bytes
        if ($len === 64) {
            $componentLen = 32;
        } elseif ($len === 96) {
            $componentLen = 48;
        } elseif ($len === 132) {
            $componentLen = 66;
        } else {
            // Try to handle other lengths - assume split in half
            if ($len % 2 === 0 && $len >= 64) {
                $componentLen = $len / 2;
            } else {
                throw new \RuntimeException("Invalid signature length: $len bytes");
            }
        }

        $r = substr($raw, 0, $componentLen);
        $s = substr($raw, $componentLen, $componentLen);

        // Remove leading zeros but ensure positive
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        
        // Ensure we have at least one byte
        if (strlen($r) === 0) $r = "\x00";
        if (strlen($s) === 0) $s = "\x00";

        // Add leading zero if high bit is set (for positive ASN.1 integer)
        if (ord($r[0]) & 0x80) {
            $r = "\x00" . $r;
        }
        if (ord($s[0]) & 0x80) {
            $s = "\x00" . $s;
        }

        // Build DER sequence
        $rDer = "\x02" . chr(strlen($r)) . $r;
        $sDer = "\x02" . chr(strlen($s)) . $s;

        return "\x30" . chr(strlen($rDer) + strlen($sDer)) . $rDer . $sDer;
    }

    /**
     * Verify RS256 (RSA PKCS#1 v1.5 with SHA-256) signature.
     */
    private function verifyRS256(string $data, string $signature, array $coseKey): bool
    {
        // COSE key labels: -1 = n (modulus), -2 = e (exponent)
        $n = $coseKey[-1] ?? null;
        $e = $coseKey[-2] ?? null;

        if (!$n || !$e) {
            throw new \RuntimeException('Invalid RSA key');
        }

        // Build ASN.1 DER for RSA public key
        $nInt = "\x02" . $this->asn1Length(strlen($n)) . $n;
        $eInt = "\x02" . $this->asn1Length(strlen($e)) . $e;
        $rsaKey = "\x30" . $this->asn1Length(strlen($nInt) + strlen($eInt)) . $nInt . $eInt;

        // Wrap in BIT STRING
        $bitString = "\x03" . $this->asn1Length(strlen($rsaKey) + 1) . "\x00" . $rsaKey;

        // Algorithm identifier for RSA
        $algorithmId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        // Final SEQUENCE
        $der = "\x30" . $this->asn1Length(strlen($algorithmId) + strlen($bitString)) . $algorithmId . $bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END PUBLIC KEY-----";

        $pubKey = openssl_pkey_get_public($pem);
        if (!$pubKey) {
            throw new \RuntimeException('Failed to parse RSA public key');
        }

        $result = openssl_verify($data, $signature, $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        } elseif ($length < 256) {
            return "\x81" . chr($length);
        } else {
            return "\x82" . pack('n', $length);
        }
    }
}
