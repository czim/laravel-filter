<?php

declare(strict_types=1);

namespace Czim\Filter\Exceptions;

use Illuminate\Contracts\Support\MessageBag;
use RuntimeException;

class FilterDataValidationFailedException extends RuntimeException
{
    protected MessageBag $messages;

    /**
     * @param MessageBag $messages
     * @return $this
     */
    public function setMessages(MessageBag $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    public function hasMessagesSet(): bool
    {
        return isset($this->messages);
    }

    public function getMessages(): MessageBag
    {
        return $this->messages;
    }
}
