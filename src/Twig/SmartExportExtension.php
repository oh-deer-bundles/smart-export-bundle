<?php

namespace Odb\SmartExportBundle\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Public integration point for host applications: {{ smart_export_popup(uuid) }}
 * renders a trigger button plus an empty popup shell (cheap — no DB access), and
 * prints the popup's CSS/JS <link>/<script> tags only on the first call within
 * the current request, so calling this once per row in a list doesn't duplicate
 * assets. The popup's actual content (columns, filters, distinct values — all of
 * which need the database) is fetched lazily by the trigger's own JS only once
 * the user actually clicks it — see templates/popup/trigger.html.twig and
 * public/js/controllers/smart_export_loader_controller.js.
 *
 * Auto-registered as a Twig extension purely via this bundle's own
 * config/services.yaml `_defaults: autoconfigure: true` — a host application
 * needs no extra wiring beyond enabling the bundle itself.
 */
class SmartExportExtension extends AbstractExtension
{
    private bool $assetsEmitted = false;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'smart_export_popup',
                $this->renderPopupTrigger(...),
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param array<string, mixed> $options Recognized keys:
     *   - 'class' (string): CSS class(es) added to the trigger <a>, alongside the
     *     'smart-export-trigger' hook class — the bundle never ships any CSS for
     *     either, the trigger is deliberately unstyled (e.g. 'btn btn-sm btn-success'),
     *     so a caller's own classes are never fighting the bundle's own.
     *   - 'detailed' (bool, default true): whether each column chip in the Colonnes
     *     step additionally shows its technical property name (classProperty) under
     *     the label, or just the label alone — a plainer picker for a non-admin
     *     audience. The Colonnes step itself is always shown.
     *   - 'id' (int|string|array): restricts the export to this id, or these ids, of
     *     the engine's primary entity (e.g. one customer's id, or a contract's item
     *     ids) — still narrowed further by security.restricted_entities if that
     *     entity is configured as restricted (SmartExportQuery::applySecurityRestrictions()),
     *     never a way to bypass it.
     */
    public function renderPopupTrigger(Environment $twig, string $uuid, ?string $label = null, array $options = []): string
    {
        $routeParams = ['uuid' => $uuid];
        if (array_key_exists('detailed', $options)) {
            $routeParams['detailed'] = $options['detailed'] ? '1' : '0';
        }
        if (array_key_exists('id', $options) && null !== $options['id'] && '' !== $options['id']) {
            $routeParams['id'] = $options['id'];
        }

        $html = $twig->render('@OdbSmartExport/popup/trigger.html.twig', [
            'uuid' => $uuid,
            'label' => $label,
            'options' => $options,
            'exportUrl' => $this->urlGenerator->generate('odb_smart_export_admin_demo_export', $routeParams),
            'emitAssets' => !$this->assetsEmitted,
        ]);
        $this->assetsEmitted = true;

        return $html;
    }
}
