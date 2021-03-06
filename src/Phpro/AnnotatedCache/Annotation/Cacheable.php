<?php
declare(strict_types=1);

namespace Phpro\AnnotatedCache\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class Cacheable extends CacheAnnotation
{
    /**
     * @var int
     */
    public $ttl = 0;

    /**
     * Cacheable constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        parent::__construct($values);

        if (isset($values['ttl'])) {
            $this->ttl = (int) $values['ttl'];
        }
    }
}
