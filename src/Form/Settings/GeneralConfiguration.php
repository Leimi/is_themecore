<?php


declare(strict_types=1);

namespace Oksydan\Module\IsThemeCore\Form\Settings;

use PrestaShop\PrestaShop\Core\Configuration\AbstractMultistoreConfiguration;
use PrestaShopBundle\Service\Form\MultistoreCheckboxEnabler;

/**
 * Configuration is used to save data to configuration table and retrieve from it
 */
final class GeneralConfiguration extends AbstractMultistoreConfiguration
{
    /**
     * @var string
     */
    public const THEMECORE_DISPLAY_LIST = 'THEMECORE_DISPLAY_LIST';
    public const THEMECORE_EARLY_HINTS = 'THEMECORE_EARLY_HINTS';
    public const THEMECORE_PRELOAD_CSS = 'THEMECORE_PRELOAD_CSS';
    public const THEMECORE_USE_CLOUDFLARE_IMAGES = 'THEMECORE_USE_CLOUDFLARE_IMAGES';
    public const THEMECORE_CLOUDFLARE_RESIZED_IMAGES = 'THEMECORE_CLOUDFLARE_RESIZED_IMAGES';
    public const THEMECORE_CLOUDFLARE_ZONE = 'THEMECORE_CLOUDFLARE_ZONE';

    /**
     * @var array<string, string>
     */
    private $fields = [
        'list_display_settings' => self::THEMECORE_DISPLAY_LIST,
        'early_hints' => self::THEMECORE_EARLY_HINTS,
        'preload_css' => self::THEMECORE_PRELOAD_CSS,
        'cloudflare_images' => self::THEMECORE_USE_CLOUDFLARE_IMAGES,
        'cloudflare_resized_images' => self::THEMECORE_CLOUDFLARE_RESIZED_IMAGES,
        'cloudflare_zone' => self::THEMECORE_CLOUDFLARE_ZONE,
    ];

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        $configurationValues = [];

        foreach ($this->fields as $field => $configurationKey) {
            $configurationValues[$field] = $this->configuration->get($configurationKey);
        }

        return $configurationValues;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $configuration
     *
     * @return array<int, array<string, mixed>>
     */
    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if (!$this->validateConfiguration($configuration)) {
            $errors[] = [
                'key' => 'Invalid configuration',
                'parameters' => [],
                'domain' => 'Admin.Notifications.Warning',
            ];
        } else {
            $shopConstraint = $this->getShopConstraint();

            try {
                foreach ($this->fields as $field => $configurationKey) {
                    $this->updateConfigurationValue($configurationKey, $field, $configuration, $shopConstraint);
                }
            } catch (\Exception $exception) {
                $errors[] = [
                    'key' => $exception->getMessage(),
                    'parameters' => [],
                    'domain' => 'Admin.Notifications.Warning',
                ];
            }
        }

        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @param array<string, mixed> $configuration
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        foreach ($this->fields as $field => $configurationKey) {
            $multistoreKey = MultistoreCheckboxEnabler::MULTISTORE_FIELD_PREFIX . $field;
            $this->fields[$multistoreKey] = '';
        }

        foreach ($configuration as $key => $value) {
            if (!key_exists($key, $this->fields)) {
                return false;
            }
        }

        return true;
    }
}
