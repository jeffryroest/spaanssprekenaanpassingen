<?php

namespace App\Feedback\Contracts;

use App\Feedback\TurnContext;

interface TurnContextResolver
{
    public function resolve(string $stepId): TurnContext;
}
