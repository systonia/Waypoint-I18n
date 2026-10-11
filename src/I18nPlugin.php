<?php

namespace Waypoint\I18n;

use Waypoint\Container;
use Waypoint\Plugin\{PluginBase, ViewHelper};
use Waypoint\Validation\Messages;

/**
 * Register with `$app->plugin(new I18nPlugin())` and configure I18nOptions. Adds
 * `$this->t('Source text', [...])` and `$this->locale()` to templates, resolves the
 * locale per request (LocaleMiddleware), translates the Validator's messages, and
 * provides the i18n:extract/check/status/add/lint CLI tasks.
 */
final class I18nPlugin extends PluginBase implements ViewHelper
{
    private Translator $translator;
    private I18nOptions $options;

    public function name(): string
    {
        return 'i18n';
    }

    public function boot(Container $container, I18nOptions $options): void
    {
        $this->options = $options;
        $this->translator = new Translator($this->options);
        $container->set($this->translator);
        $container->bind(Messages::class, $this->translator);
    }

    public function classes(): array
    {
        return [I18nTasks::class];
    }

    public function middlewares(): array
    {
        return [new LocaleMiddleware($this->translator, $this->options)];
    }

    public function hooks(): array
    {
        return [$this];
    }

    public function helpers(): array
    {
        return [
            't' => $this->translator->t(...),
            'locale' => $this->translator->locale(...),
        ];
    }
}
