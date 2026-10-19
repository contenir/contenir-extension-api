<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPreSave;
use Contenir\Extension\Api\Event\Settings\SettingsSave;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

use function array_keys;

/**
 * One of an add-on's settings sections in the in-memory host, saved in
 * three stages as the settings screen would save it.
 *
 * @internal
 */
final readonly class Settings implements SettingsInterface
{
    /**
     * The records section the in-memory host keeps settings under.
     */
    public const string STORE = 'settings';

    public function __construct(
        private SettingsSection $declaration,
        private Records $records,
        private Dispatcher $dispatcher,
    ) {}

    #[Override]
    public function all(): array
    {
        $values = [];
        foreach ($this->declaration->form->fields as $field) {
            $values[$field->name] = $this->get($field->name);
        }

        return $values;
    }

    #[Override]
    public function get(string $field, mixed $default = null): mixed
    {
        $declared = $this->field($field);

        return $this->stored()[$field] ?? $declared->default ?? $default;
    }

    #[Override]
    public function section(): string
    {
        return $this->declaration->name;
    }

    #[Override]
    public function set(array $values): ActionResult
    {
        foreach (array_keys($values) as $field) {
            $this->field($field);
        }

        $section = $this->section();
        $before  = $this->stored();
        $pre     = new SettingsPreSave($section, new Change($before, [...$before, ...$values]));
        $this->dispatcher->dispatch($section, $pre);
        $change = $pre->change();
        if (! $pre->proceeds()) {
            $message = $pre->stopMessage() ?? Saver::HELD;
            $this->post($change, Outcome::Stopped, $message);

            return new ActionResult(
                Outcome::Stopped,
                $section,
                $message,
                $pre->stopDetail(),
                $pre->errors(),
                $pre->warnings(),
            );
        }

        $snapshot = $this->records->snapshot();
        $this->records->put(self::STORE, $section, $change->after);
        $failure = $this->dispatcher->dispatch($section, new SettingsSave($section, $change));
        if (null !== $failure) {
            $this->records->restore($snapshot);
            $this->post($change, Outcome::Failed, $failure);

            return new ActionResult(Outcome::Failed, $section, $failure);
        }

        $this->post($change, Outcome::Done, '');

        return new ActionResult(Outcome::Done, $section, '', '', [], $pre->warnings(), $pre->flags());
    }

    /**
     * @throws UnknownSettingException
     */
    private function field(string $name): Field
    {
        return $this->declaration->form->field($name) ?? throw UnknownSettingException::field($this->section(), $name);
    }

    private function post(Change $change, Outcome $outcome, string $reason): void
    {
        $section = $this->section();
        $this->dispatcher->dispatch($section, new SettingsPostSave($section, $change, $outcome, $reason));
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        return $this->records->get(self::STORE, $this->section()) ?? [];
    }
}
