<?php
// Path: core/Http/Middleware/MiddlewareInterface.php

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * واجهة الوسيط (Middleware Interface).
 * أي Middleware في النظام يجب أن ينفذ هذه الواجهة.
 */
interface MiddlewareInterface
{
    /**
     * معالجة الطلب وتمريره للوسيط التالي (أو الكنترولر النهائي).
     *
     * @param Request $request الطلب القادم.
     * @param callable $next الدالة التي تستدعي الوسيط التالي.
     * @return Response
     */
    public function process(Request $request, callable $next): Response;
}