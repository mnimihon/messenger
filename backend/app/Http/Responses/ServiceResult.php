<?php

namespace App\Http\Responses;

class ServiceResult
{
    public const NOT_FOUND = 'not_found';
    public const FORBIDDEN = 'forbidden';
    public const TYPE_SELF = 'self';
    public const TYPE_EXISTS = 'exists';
    public const TYPE_CREATED = 'created';
}
