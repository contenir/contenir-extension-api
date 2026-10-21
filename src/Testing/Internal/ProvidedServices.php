<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use LogicException;
use Throwable;

use function array_key_exists;
use function sprintf;

/**
 * The services enabled add-ons provide, each made once by the first add-on
 * to load that provides it. A factory that throws, or makes something that
 * isn't the service, is reported, and the service is null.
 *
 * @internal
 */
final class ProvidedServices
{
    /**
     * @var array<string, object|null> By interface
     */
    private array $made = [];

    public function __construct(
        private readonly Runtime $runtime,
    ) {}

    /**
     * @template T of object
     *
     * @param array<string, Registration> $registrations In load order
     * @param class-string<T>             $service
     *
     * @return T|null
     */
    public function get(array $registrations, string $service): ?object
    {
        if (! array_key_exists($service, $this->made)) {
            $this->made[$service] = $this->make($registrations, $service);
        }

        /** @var T|null */
        return $this->made[$service] ?? null;
    }

    /**
     * The add-ons that provide $service but aren't used, after the first,
     * logged under each one's source.
     *
     * @param array<string, Registration> $registrations In load order
     */
    public function warnOfRivals(array $registrations): void
    {
        $providers = [];
        foreach ($registrations as $registration) {
            foreach ($registration->services as $service => $declaration) {
                $first = $providers[$service] ?? null;
                if (null === $first) {
                    $providers[$service] = $registration->handle->value;
                    continue;
                }

                $this->runtime
                    ->context($registration->handle)
                    ->logger()
                    ->warning(sprintf(
                        'Add-on "%s" also provides %s, but add-on "%s" loaded first and provides it',
                        $registration->handle->value,
                        $declaration->service,
                        $first,
                    ));
            }
        }
    }

    /**
     * @param array<string, Registration> $registrations
     * @param class-string                $service
     */
    private function make(array $registrations, string $service): ?object
    {
        foreach ($registrations as $registration) {
            $declaration = $registration->services[$service] ?? null;
            if (null === $declaration) {
                continue;
            }

            try {
                $instance = $this->runtime
                    ->make($declaration->factory, ServiceFactoryInterface::class)
                    ->create($this->runtime->context($registration->handle));
                if ($instance instanceof $service) {
                    return $instance;
                }

                throw new LogicException(sprintf(
                    '%s made something that is not a %s',
                    $declaration->factory,
                    $service,
                ));
            } catch (Throwable $failure) {
                $this->runtime->report($registration->handle, FailurePoint::Service, $service, $failure);

                return null;
            }
        }

        return null;
    }
}
