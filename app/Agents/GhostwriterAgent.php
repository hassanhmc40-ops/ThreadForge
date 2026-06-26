<?php

namespace App\Agents;

use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\ConversationStore;

class GhostwriterAgent extends AnonymousAgent
{
    use RemembersConversations;

    public function __construct(
        public string $instructions,
        public iterable $tools,
    ) {
        parent::__construct($instructions, [], $tools);
    }

    public function messages(): iterable
    {
        if ($this->currentConversation()) {
            return resolve(ConversationStore::class)
                ->getLatestConversationMessages(
                    $this->currentConversation(),
                    $this->maxConversationMessages(),
                )->all();
        }

        return [];
    }
}
