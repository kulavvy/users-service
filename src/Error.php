<?php

namespace App;

use Symfony\Component\Serializer\Annotation\Groups;

class Error
{
    public function __construct(private string $error)
    {
    }

    #[Groups(groups: ['api'])]
    public function getError(): string
    {
        return $this->error;
    }
}
