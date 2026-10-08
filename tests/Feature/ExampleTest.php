<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The portal UI is behind authentication, so guests land on the login page.
     */
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
