<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // liek lietotājam ar pagaidu paroli vispirms izveidot jaunu
        $middleware->web(append: [
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // "nav atļauts" / "nav atrasts" / "vairs nav pieejams" kļūdu lapu vietā lietotājs nonāk sākumlapā ar īsu paziņojumu
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || ! in_array($e->getStatusCode(), [403, 404, 410], true)) {
                return null;
            }

            $message = match ($e->getStatusCode()) {
                404 => "That page doesn't exist or was removed.",
                410 => $e->getMessage() ?: 'That link is no longer valid.',
                default => "You don't have access to that page.",
            };

            // ja kontrolieris jau iedeva skaidru paziņojumu, rāda to
            if ($e->getStatusCode() !== 410 && $e->getMessage() !== '' && ! str_starts_with($e->getMessage(), 'No query results') && ! str_starts_with($e->getMessage(), 'The route')
                && ! in_array($e->getMessage(), ['This action is unauthorized.', 'Not Found', 'Forbidden', 'User does not have the right roles.'], true)) {
                $message = $e->getMessage();
            }

            return $request->user()
                ? redirect()->route('dashboard')->with('error', $message)
                : redirect('/')->with('error', $message);
        });
    })->create();
