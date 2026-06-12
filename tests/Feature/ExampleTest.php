<?php

test('home redirects to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});
