<?php

namespace Waypoint\I18n\Tests;

use Waypoint\Attributes\{Email, NotBlank};
use Waypoint\FromArray;

final class SignupDTO
{
    use FromArray;

    #[NotBlank]
    #[Email]
    public ?string $email = null;
}
