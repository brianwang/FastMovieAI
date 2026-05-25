<?php

namespace app\middleware;

use support\Request;

class Csrf
{
    public function process(Request $request, callable $next)
    {
        $excludePaths = ['/app/admin/api/Login/login', '/install'];
        $path = $request->path();
        foreach ($excludePaths as $exclude) {
            if (str_starts_with($path, ltrim($exclude, '/'))) {
                return $next($request);
            }
        }
        $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (in_array($request->method(), $safeMethods)) {
            return $next($request);
        }
        $token = $request->header('X-CSRF-TOKEN') ?: $request->post('_csrf_token');
        $sessionToken = $request->session()->get('csrf_token');
        if (!$sessionToken || !$token || $token !== $sessionToken) {
            return response(json_encode(['code' => 400, 'msg' => 'CSRF token验证失败', 'data' => null], JSON_UNESCAPED_UNICODE), 400, ['Content-Type' => 'application/json']);
        }
        return $next($request);
    }

    public static function getToken(Request $request): string
    {
        $token = $request->session()->get('csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $request->session()->set('csrf_token', $token);
        }
        return $token;
    }
}
