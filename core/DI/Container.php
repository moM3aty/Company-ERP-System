<?php
// Path: core/DI/Container.php

namespace Core\DI;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * حاوية حقن الاعتماديات (IoC Container).
 * مسؤولة عن استنساخ الكلاسات وحقن الاعتماديات التي تحتاجها تلقائياً.
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * تسجيل كلاس بحيث يتم إنشاء نسخة جديدة في كل مرة يُطلب فيها.
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        // تم الإصلاح: حفظ البيانات كمصفوفة لتتوافق مع دالة get
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * تسجيل كلاس كنسخة مفردة (Singleton).
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        if (!is_null($concrete) && !is_string($concrete) && !$concrete instanceof Closure) {
            $this->instances[$abstract] = $concrete;
            return;
        }

        // تم الإصلاح: استخدام دالة bind مع تفعيل الـ shared
        $this->bind($abstract, $concrete, true);
    }

    /**
     * التحقق مما إذا كان الكلاس مسجلاً.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * جلب نسخة من الكلاس المطلوب.
     */
    public function get(string $abstract)
    {
        // إذا كان Singleton وموجود مسبقاً، نرجعه
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // تم الإصلاح: استخراج البيانات بشكل آمن لتجنب أخطاء المصفوفات
        $binding = $this->bindings[$abstract] ?? null;
        $concrete = $binding ? $binding['concrete'] : $abstract;
        $isShared = $binding ? $binding['shared'] : false;

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        if ($isShared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * بناء الكلاس وحقن اعتمادياته باستخدام الانعكاس (Reflection).
     */
    private function build(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // تم الإصلاح: استخدام getName بدلاً من name لدعم الإصدارات الحديثة من PHP
                    throw new Exception("Cannot resolve class dependency {$parameter->getName()}");
                }
            }
        }

        return $dependencies;
    }
}