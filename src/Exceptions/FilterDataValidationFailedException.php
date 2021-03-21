<?php

namespace Czim\Filter\Exceptions;

use Illuminate\Contracts\Support\MessageBag;
use RuntimeException;

class FilterDataValidationFailedException extends RuntimeException
{
    /**
     * @var MessageBag
     */
    protected $messages;

    /**
     * @param MessageBag $messages
     * @return FilterDataValidationFailedException|$this
     */
    public function setMessages(MessageBag $messages): FilterDataValidationFailedException
    {
        $this->messages = $messages;

        return $this;
    }

    public function getMessages(): MessageBag
    {
        return $this->messages;
    }
}
