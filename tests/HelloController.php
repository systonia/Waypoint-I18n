<?php

namespace Waypoint\I18n\Tests;

use Waypoint\Attributes\{Body, Controller, Get, Post, SkipCsrf};
use Waypoint\Http\{Request, View};

#[Controller]
#[SkipCsrf]
class HelloController
{
    #[Get('/hello')]
    public function hello(Request $req): View
    {
        return new View('Hello', partial: true);
    }

    #[Post('/signup')]
    public function signup(#[Body] SignupDTO $data): array
    {
        return ['ok' => true];
    }
}
