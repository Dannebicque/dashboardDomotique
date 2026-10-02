<?php

namespace App\Controller;

use App\Infrastructure\Netatmo\NetatmoClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/netatmo')]
final readonly class NetatmoController
{
    public function __construct(
        private NetatmoClient $netatmo,
        #[Autowire('%env(string:NETATMO_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(string:NETATMO_REDIRECT_URI)%')] private string $redirectUri,
    ) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'netatmo', 'configured' => $this->netatmo->isConfigured()]);
    }

    #[Route('/connect', methods: ['GET'])]
    public function connect(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(24));
        $request->getSession()->set('netatmo_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'read_station',
            'state' => $state,
        ]);

        return new RedirectResponse('https://api.netatmo.com/oauth2/authorize?'.$query);
    }

    #[Route('/callback', methods: ['GET'])]
    public function callback(Request $request): JsonResponse
    {
        if (!hash_equals((string) $request->getSession()->remove('netatmo_oauth_state'), (string) $request->query->get('state'))) {
            return new JsonResponse(['error' => 'Invalid OAuth state.'], 400);
        }

        $this->netatmo->exchangeCode((string) $request->query->get('code'), $this->redirectUri);
        return new JsonResponse(['connected' => true]);
    }

    #[Route('/stations', methods: ['GET'])]
    public function stations(): JsonResponse
    {
        return new JsonResponse($this->netatmo->stations());
    }
}
