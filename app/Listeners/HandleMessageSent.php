<?php

namespace App\Domain\Chat\Listeners;

use App\Domain\Chat\Events\MessageSent;
use App\Domain\Chat\Services\MessageSideEffectsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleMessageSent implements ShouldQueue
{
    public function __construct(
        protected MessageSideEffectsService $service,
    ) {}

    public function handle(MessageSent $event): void
    {
        $this->service->handle($event->message);
    }
}