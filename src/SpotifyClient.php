<?php
namespace App;

class SpotifyClient
{
    private string $clientId;
    private string $clientSecret;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getAccessToken(): string
    {
        if (!empty($_SESSION['spotify_access_token']) && !empty($_SESSION['spotify_expires_at']) && time() < $_SESSION['spotify_expires_at']) {
            return $_SESSION['spotify_access_token'];
        }

        $url = 'https://accounts.spotify.com/api/token';
        $post = 'grant_type=client_credentials';
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Token request failed: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new \RuntimeException('Token request failed, HTTP ' . $code . ': ' . $res);
        }

        $data = json_decode($res, true);
        if (empty($data['access_token'])) {
            throw new \RuntimeException('Invalid token response');
        }

        $_SESSION['spotify_access_token'] = $data['access_token'];
        $_SESSION['spotify_expires_at'] = time() + intval($data['expires_in'] ?? 3600) - 10;

        return $_SESSION['spotify_access_token'];
    }

    public function searchTracks(string $query, int $limit = 10): array
    {
        $token = $this->getAccessToken();
        $q = rawurlencode($query);
        $limit = max(1, min(50, $limit));
        $url = "https://api.spotify.com/v1/search?q={$q}&type=track&limit={$limit}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Search request failed: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new \RuntimeException('Search failed, HTTP ' . $code . ': ' . $res);
        }

        $data = json_decode($res, true);
        $items = $data['tracks']['items'] ?? [];

        $out = [];
        foreach ($items as $t) {
            $out[] = [
                'id' => $t['id'] ?? null,
                'name' => $t['name'] ?? '',
                'artists' => array_map(function($a){ return $a['name'] ?? ''; }, $t['artists'] ?? []),
                'album' => $t['album']['name'] ?? '',
                'album_image' => $t['album']['images'][0]['url'] ?? null,
                'preview_url' => $t['preview_url'] ?? null,
                'external_url' => $t['external_urls']['spotify'] ?? null,
                'duration_ms' => $t['duration_ms'] ?? null,
            ];
        }

        return $out;
    }
}
?>