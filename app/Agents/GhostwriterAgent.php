<?php

namespace App\Agents;

use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Concerns\RemembersConversations;

class GhostwriterAgent extends AnonymousAgent
{
    use RemembersConversations;

    public function __construct(
        public string $instructions,
        public iterable $tools,
    ) {
        parent::__construct($instructions, [], $tools);
    }
}
