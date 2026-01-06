<?php
class AuthMiddleware
{
    public function __construct(private Auth $auth, private Flash $flash)
    {
    }

    public function check(): void
    {
        if ($this->auth->user()) {
            return;
        }

        $this->flash->add('warning', 'Por favor inicia sesión para continuar.');
        header('Location: ' . route_to('login'));
        exit;
    }
}
