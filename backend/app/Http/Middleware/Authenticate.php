<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
protected function redirectTo($request)
{
    // Se a requisição NÃO espera JSON (ex: um navegador)
    // E a requisição NÃO é para a nossa API
    // Então redirecione para a rota web 'login'.
    if (! $request->expectsJson() && ! $request->is('api/*')) {
        return route('login');
    }

    // Em todos os outros casos (incluindo nossa falha no teste da API),
    // este método retornará 'null', e o Laravel
    // vai gerar a resposta 401 JSON correta.
}
}
