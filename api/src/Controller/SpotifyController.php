<?php

namespace App\Controller;

use App\Infrastructure\OAuth\RefreshTokenStore;
use App\Infrastructure\Spotify\SpotifyClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/spotify')]
final readonly class SpotifyController
{
    public function __construct(
        private SpotifyClient $spotify,
        private RefreshTokenStore $tokens,
        #[Autowire('%env(string:SPOTIFY_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(string:SPOTIFY_REDIRECT_URI)%')] private string $redirectUri,
        #[Autowire('%env(string:FRONTEND_URL)%')] private string $frontendUrl,
    ) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'spotify', 'configured' => $this->spotify->isConfigured(), 'playback' => $this->spotify->isConfigured() ? $this->spotify->playback() : null]);
    }

    #[Route('/connect', methods: ['GET'])]
    public function connect(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(24));
        $request->getSession()->set('spotify_oauth_state', $state);
        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => 'user-read-playback-state user-modify-playback-state',
            'state' => $state,
        ]);
        return new RedirectResponse('https://accounts.spotify.com/authorize?'.$query);
    }

    #[Route('/callback', methods: ['GET'])]
    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        if (!hash_equals((string) $request->getSession()->remove('spotify_oauth_state'), (string) $request->query->get('state'))) {
            return new JsonResponse(['error' => 'Invalid OAuth state.'], 400);
        }
        $this->spotify->exchangeCode((string) $request->query->get('code'), $this->redirectUri);
        return new RedirectResponse(rtrim($this->frontendUrl, '/').'/reglages?spotify=connected');
    }

    #[Route('', methods: ['DELETE'])]
    public function disconnect(): JsonResponse
    {
        $this->tokens->remove('spotify');
        return new JsonResponse(null, 204);
    }

    #[Route('/player', methods: ['GET'])]
    public function player(): JsonResponse
    {
        return new JsonResponse($this->spotify->playback());
    }

    #[Route('/queue', methods: ['GET'])]
    public function queue(): JsonResponse
    {
        return new JsonResponse($this->spotify->queue());
    }

    #[Route('/devices', methods: ['GET'])]
    public function devices(): JsonResponse
    {
        return new JsonResponse($this->spotify->devices());
    }

    #[Route('/volume', methods: ['PUT'])]
    public function volume(Request $request): JsonResponse
    {
        $this->spotify->setVolume((int) ($request->toArray()['volume'] ?? 0));
        return new JsonResponse(null, 204);
    }

    #[Route('/shuffle', methods: ['PUT'])]
    public function shuffle(Request $request): JsonResponse
    {
        $this->spotify->setShuffle((bool) ($request->toArray()['enabled'] ?? false));
        return new JsonResponse(null, 204);
    }

    #[Route('/repeat', methods: ['PUT'])]
    public function repeat(Request $request): JsonResponse
    {
        $this->spotify->setRepeat((string) ($request->toArray()['state'] ?? 'off'));
        return new JsonResponse(null, 204);
    }

    #[Route('/device', methods: ['PUT'])]
    public function device(Request $request): JsonResponse
    {
        $this->spotify->transfer((string) ($request->toArray()['deviceId'] ?? ''));
        return new JsonResponse(null, 204);
    }

    #[Route('/player/{command}', requirements: ['command' => 'play|pause|next|previous'], methods: ['POST'])]
    public function command(string $command): JsonResponse
    {
        $this->spotify->command($command);
        return new JsonResponse(null, 204);
    }
}
