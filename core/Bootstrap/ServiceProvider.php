<?php
// Path: core/Bootstrap/ServiceProvider.php

namespace Core\Bootstrap;

/**
 * الكلاس الأساسي لمزودي الخدمات (Base Service Provider).
 */
abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * تُستخدم لتسجيل الاعتماديات والروابط في الحاوية (Container).
     */
    abstract public function register(): void;

    /**
     * تُستخدم لتنفيذ الأكواد بعد أن يتم تسجيل كافة مزودي الخدمات.
     */
    public function boot(): void
    {
        // افتراضياً فارغة، يمكن تجاوزها (Override) في الكلاسات الوارثة
    }
}