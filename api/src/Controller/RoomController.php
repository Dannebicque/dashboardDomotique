<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueRoomProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RoomController
{
    public function __construct(private HueRoomProvider $rooms) {}

    #[Route('/api/rooms', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->rooms->all());
    }
}
