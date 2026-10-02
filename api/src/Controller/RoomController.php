<?php

namespace App\Controller;

use App\Application\Home\HomeZoneProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RoomController
{
    public function __construct(private HomeZoneProvider $zones) {}

    #[Route('/api/rooms', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->zones->all());
    }
}
