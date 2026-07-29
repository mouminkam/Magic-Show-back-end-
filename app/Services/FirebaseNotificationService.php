<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notify admin dashboard of new orders via Firebase Firestore (REST API).
 * When an order is created, we add a document to Firestore so the dashboard
 * (listening with Firebase JS SDK) can show the modal and play sound in real time.
 */
class FirebaseNotificationService
{
    protected ?array $credentials = null;
    protected ?string $projectId = null;

    public function __construct()
    {
        $path = config('services.firebase.credentials');
        $json = config('services.firebase.credentials_json');
        if ($path && is_string($path)) {
            if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:#', $path)) {
                $path = base_path($path);
            }
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $this->credentials = json_decode($content, true);
            }
        }
        if ($this->credentials === null && $json && is_string($json)) {
            $this->credentials = json_decode($json, true);
        }
        $this->projectId = $this->credentials['project_id'] ?? config('services.firebase.project_id');
    }

    public function isConfigured(): bool
    {
        return $this->credentials !== null && $this->projectId !== null;
    }

    /**
     * Get Google OAuth2 access token using service account JWT.
     */
    protected function getAccessToken(): ?string
    {
        if (!$this->credentials || !$this->projectId) {
            return null;
        }
        $clientEmail = $this->credentials['client_email'] ?? null;
        $privateKey = $this->credentials['private_key'] ?? null;
        if (!$clientEmail || !$privateKey) {
            return null;
        }
        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/datastore',
        ];
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];
        $signature = '';
        $ok = openssl_sign(
            implode('.', $segments),
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );
        if (!$ok) {
            return null;
        }
        $segments[] = $this->base64UrlEncode($signature);
        $jwt = implode('.', $segments);
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        if (!$response->successful()) {
            Log::warning('Firebase token error: ' . $response->body());
            return null;
        }
        return $response->json('access_token');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Add a document to Firestore via REST API.
     */
    protected function addDocument(string $collection, array $fields): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }
        $url = sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/%s',
            $this->projectId,
            $collection
        );
        $body = ['fields' => $this->formatFields($fields)];
        $response = Http::withToken($token)
            ->post($url, $body);
        if (!$response->successful()) {
            Log::warning('Firestore add document error: ' . $response->body());
            return false;
        }
        return true;
    }

    /**
     * Convert associative array to Firestore REST API fields format.
     */
    private function formatFields(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $out[$key] = ['integerValue' => (string) $value];
            } elseif (is_float($value)) {
                $out[$key] = ['doubleValue' => $value];
            } elseif (is_bool($value)) {
                $out[$key] = ['booleanValue' => $value];
            } else {
                $out[$key] = ['stringValue' => (string) $value];
            }
        }
        return $out;
    }

    /**
     * Notify admin dashboard of a new order (real-time via Firestore).
     */
    public function notifyNewOrder(Order $order): void
    {
        if (!$this->isConfigured()) {
            return;
        }
        try {
            $this->addDocument('admin_new_orders', [
                'order_id' => $order->id,
                'order_number' => $order->order_number ?? '#' . $order->id,
                'created_at' => $order->created_at?->format(\DateTimeInterface::ATOM) ?? now()->format(\DateTimeInterface::ATOM),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase notifyNewOrder failed: ' . $e->getMessage());
        }
    }
}
