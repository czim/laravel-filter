<?php
namespace Czim\Filter\Exceptions;

use Exception;
use Illuminate\Contracts\Support\MessageBag;

class FilterDataValidationFailedException extends Exception
{
    /**
     * @var MessageBag
     */
    protected $messages;

    /**
     * @param MessageBag $messages
     * @return $this
     */
    public function setMessages(MessageBag $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return MessageBag
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
