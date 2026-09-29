<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Search\PropertyNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders every error raised under /api as an RFC 9457 "problem details" JSON document,
 * so API clients can rely on one consistent error shape.
 */
final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $extra = [];

        if ($exception instanceof PropertyNotFoundException) {
            $status = Response::HTTP_NOT_FOUND;
            $detail = $exception->getMessage();
        } elseif ($exception instanceof HttpExceptionInterface && $exception->getPrevious() instanceof ValidationFailedException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $detail = 'The request contains invalid parameters.';
            $extra['violations'] = $this->formatViolations($exception->getPrevious());
        } elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $detail = $status < 500 ? $exception->getMessage() : 'An unexpected error occurred.';
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $detail = $this->debug ? $exception->getMessage() : 'An unexpected error occurred.';
            $this->logger->error('Unhandled API exception', ['exception' => $exception]);
        }

        $response = new JsonResponse(
            [
                'type' => 'about:blank',
                'title' => Response::$statusTexts[$status] ?? 'Error',
                'status' => $status,
                'detail' => $detail,
                ...$extra,
            ],
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
        $response->setEncodingOptions($response->getEncodingOptions() | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        $event->setResponse($response);
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    private function formatViolations(ValidationFailedException $exception): array
    {
        $violations = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
