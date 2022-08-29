<?php

declare(strict_types=1);
use Everest\Http\Requests\ServerRequest;

class Middleware
{
    private $attributeName;

    private $attributeValue;

    public function __construct(string $attributeName, $attributeValue)
    {
        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
    }

    public function __invoke(Closure $next, ServerRequest $request)
    {
        return $next($request->withAttribute(
            $this->attributeName,
            $this->attributeValue
        ));
    }
}
