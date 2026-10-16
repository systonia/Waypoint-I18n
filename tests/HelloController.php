<?php

namespace Waypoint\I18n\Tests;

use Waypoint\Attributes\{Body, Controller, Get, Post, SkipCsrf};
use Waypoint\Http\{Request, Response, View};

#[Controller]
#[SkipCsrf]
class HelloController
{
    #[Get('/hello')]
    public function hello(Request $req): View
    {
        return new View('Hello', partial: true);
    }

    /** Hands over to the runtime, like a Live component's WebSocket. */
    #[Get('/socket')]
    public function socket(Response $res): Response
    {
        return $res->upgrade(new \stdClass());
    }

    #[Post('/signup')]
    public function signup(#[Body] SignupDTO $data): array
    {
        return ['ok' => true];
    }
}
