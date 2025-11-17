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

    private function getJson(string $url): array
    {
        $token = $this->getAccessToken();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false) {
            throw new \RuntimeException('Spotify request failed: ' . $err);
        }
        $data = json_decode($res, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($data) && isset($data['error']) ? json_encode($data['error']) : $res;
            throw new \RuntimeException('Spotify API returned HTTP ' . $code . ': ' . $msg);
        }
        return (array)$data;
    }

    // changed code: get track object
    public function getTrack(string $trackId): array
    {
        $url = 'https://api.spotify.com/v1/tracks/' . rawurlencode($trackId);
        return $this->getJson($url);
    }

    // changed code: given array of artist ids, return array of artist objects (batched)
    public function getArtists(array $artistIds): array
    {
        $ids = array_map('rawurlencode', array_filter($artistIds));
        if (!$ids) return [];
        $url = 'https://api.spotify.com/v1/artists?ids=' . implode(',', $ids);
        $data = $this->getJson($url);
        return $data['artists'] ?? [];
    }

    // changed code: convenience method to get genres for a track id
    public function getTrackGenres(string $trackId): string
    {
        $track = $this->getTrack($trackId);
        $artistIds = [];
        foreach ($track['artists'] ?? [] as $a) {
            if (!empty($a['id'])) $artistIds[] = $a['id'];
        }
        if (!$artistIds) return '';

        $artists = $this->getArtists($artistIds);

        $genres = [];
        foreach ($artists as $art) {
            if (!empty($art['genres']) && is_array($art['genres'])) {
                foreach ($art['genres'] as $g) $genres[] = $g;
            }
        }

        $genres = array_values(array_unique(array_filter($genres)));
        // return up to first 5 genres joined by comma
        return $genres ? implode(', ', array_slice($genres, 0, 5)) : '';
    }
}
?>