<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

final class MissingRouteParameterExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        for ($throwable = $event->getThrowable(); $throwable !== null; $throwable = $throwable->getPrevious()) {
            if ($throwable instanceof MissingMandatoryParametersException) {
                $event->setThrowable(new NotFoundHttpException('Page not found.', $throwable));

                return;
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }
}
