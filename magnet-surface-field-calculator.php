<?php
/**
 * Plugin Name: Magnet Surface Field Calculator
 * Plugin URI: https://example.com/magnet-surface-field-calculator
 * Description: Estimate magnet surface field strength using a dipole-based approximation with configurable defaults.
 * Version: 1.0.0
 * Author: Magnet Tools
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: magnet-surface-field-calculator
 * Domain Path: /languages
 *
 * @package MagnetSurfaceFieldCalculator
 */

if (! defined('ABSPATH')) {
    exit;
}

final class MSFC_Plugin
{
    /**
     * Settings option key.
     */
    private const OPTION_KEY = 'msfc_settings';

    /**
     * Single instance.
     *
     * @var MSFC_Plugin|null
     */
    private static $instance = null;

    /**
     * Create singleton instance.
     *
     * @return MSFC_Plugin
     */
    public static function instance(): MSFC_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Internationalization setup.
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'magnet-surface-field-calculator',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Register shortcode handler.
     */
    public function register_shortcode(): void
    {
        add_shortcode('msfc_calculator', [$this, 'render_shortcode']);
    }

    /**
     * Register frontend assets.
     */
    public function register_frontend_assets(): void
    {
        $version = '1.0.0';

        wp_register_style(
            'msfc-styles',
            plugins_url('assets/css/msfc.css', __FILE__),
            [],
            $version
        );

        wp_register_script(
            'msfc-script',
            plugins_url('assets/js/msfc.js', __FILE__),
            [],
            $version,
            true
        );
    }

    /**
     * Render calculator markup.
     *
     * @return string
     */
    public function render_shortcode(): string
    {
        wp_enqueue_style('msfc-styles');
        wp_enqueue_script('msfc-script');

        $settings = $this->get_settings();

        wp_localize_script(
            'msfc-script',
            'MSFC_CONFIG',
            [
                'defaultBr'         => (float) $settings['default_br_tesla'],
                'defaultThickness'  => (float) $settings['default_thickness_mm'],
                'defaultDiameter'   => (float) $settings['default_diameter_mm'],
                'defaultDistance'   => (float) $settings['default_distance_mm'],
                'gaussConversion'   => 10000,
                'microTeslaFactor'  => 1000000,
            ]
        );

        ob_start();
        ?>
        <div class="msfc-calculator" data-msfc-root>
            <h3><?php esc_html_e('Magnet Surface Field Calculator', 'magnet-surface-field-calculator'); ?></h3>
            <p class="msfc-intro">
                <?php esc_html_e('Estimate magnetic flux density at a target distance using a simplified axial dipole approximation.', 'magnet-surface-field-calculator'); ?>
            </p>

            <div class="msfc-grid">
                <label>
                    <?php esc_html_e('Remanence Br (Tesla)', 'magnet-surface-field-calculator'); ?>
                    <input type="number" step="0.01" min="0" name="br" data-msfc-input="br" />
                </label>

                <label>
                    <?php esc_html_e('Magnet Thickness (mm)', 'magnet-surface-field-calculator'); ?>
                    <input type="number" step="0.01" min="0" name="thickness" data-msfc-input="thickness" />
                </label>

                <label>
                    <?php esc_html_e('Magnet Diameter (mm)', 'magnet-surface-field-calculator'); ?>
                    <input type="number" step="0.01" min="0" name="diameter" data-msfc-input="diameter" />
                </label>

                <label>
                    <?php esc_html_e('Distance from Surface (mm)', 'magnet-surface-field-calculator'); ?>
                    <input type="number" step="0.01" min="0" name="distance" data-msfc-input="distance" />
                </label>
            </div>

            <button type="button" class="msfc-button" data-msfc-action="calculate">
                <?php esc_html_e('Calculate Field', 'magnet-surface-field-calculator'); ?>
            </button>

            <div class="msfc-results" aria-live="polite" data-msfc-output>
                <p><strong><?php esc_html_e('Estimated Field:', 'magnet-surface-field-calculator'); ?></strong> <span data-msfc-result-tesla>-</span> T</p>
                <p><strong><?php esc_html_e('Gauss:', 'magnet-surface-field-calculator'); ?></strong> <span data-msfc-result-gauss>-</span> G</p>
                <p><strong><?php esc_html_e('Microtesla:', 'magnet-surface-field-calculator'); ?></strong> <span data-msfc-result-microtesla>-</span> µT</p>
            </div>

            <p class="msfc-note">
                <?php esc_html_e('Note: This is an engineering approximation and not a substitute for FEM simulation or direct measurement.', 'magnet-surface-field-calculator'); ?>
            </p>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Register plugin menu.
     */
    public function register_admin_menu(): void
    {
        add_options_page(
            __('Magnet Field Calculator', 'magnet-surface-field-calculator'),
            __('Magnet Field Calculator', 'magnet-surface-field-calculator'),
            'manage_options',
            'msfc-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings section/fields.
     */
    public function register_settings(): void
    {
        register_setting(
            'msfc_settings_group',
            self::OPTION_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => $this->defaults(),
            ]
        );

        add_settings_section(
            'msfc_general',
            __('Default Calculator Inputs', 'magnet-surface-field-calculator'),
            function (): void {
                echo '<p>' . esc_html__('Set the default values shown when the calculator loads.', 'magnet-surface-field-calculator') . '</p>';
            },
            'msfc-settings'
        );

        $fields = [
            'default_br_tesla'      => __('Default Br (Tesla)', 'magnet-surface-field-calculator'),
            'default_thickness_mm'  => __('Default Thickness (mm)', 'magnet-surface-field-calculator'),
            'default_diameter_mm'   => __('Default Diameter (mm)', 'magnet-surface-field-calculator'),
            'default_distance_mm'   => __('Default Distance (mm)', 'magnet-surface-field-calculator'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [$this, 'render_number_field'],
                'msfc-settings',
                'msfc_general',
                ['key' => $key]
            );
        }
    }

    /**
     * Render generic numeric setting field.
     *
     * @param array<string,string> $args Field args.
     */
    public function render_number_field(array $args): void
    {
        $key      = $args['key'] ?? '';
        $settings = $this->get_settings();
        $value    = $settings[$key] ?? '';

        printf(
            '<input type="number" min="0" step="0.01" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($key),
            esc_attr((string) $value)
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array<string,mixed> $input Raw settings.
     *
     * @return array<string,float>
     */
    public function sanitize_settings(array $input): array
    {
        $defaults = $this->defaults();
        $clean    = [];

        foreach ($defaults as $key => $default) {
            $raw         = $input[$key] ?? $default;
            $clean[$key] = is_numeric($raw) ? max(0, (float) $raw) : $default;
        }

        return $clean;
    }

    /**
     * Render settings page.
     */
    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Magnet Surface Field Calculator', 'magnet-surface-field-calculator'); ?></h1>
            <p><?php esc_html_e('Use shortcode [msfc_calculator] in any page, post, or widget area.', 'magnet-surface-field-calculator'); ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields('msfc_settings_group');
                do_settings_sections('msfc-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Read option values merged with defaults.
     *
     * @return array<string,float>
     */
    private function get_settings(): array
    {
        $defaults = $this->defaults();
        $stored   = get_option(self::OPTION_KEY, []);

        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, $stored);
    }

    /**
     * Provide defaults.
     *
     * @return array<string,float>
     */
    private function defaults(): array
    {
        return [
            'default_br_tesla'     => 1.25,
            'default_thickness_mm' => 5.0,
            'default_diameter_mm'  => 10.0,
            'default_distance_mm'  => 0.0,
        ];
    }
}

register_activation_hook(
    __FILE__,
    static function (): void {
        if (! get_option('msfc_settings')) {
            add_option(
                'msfc_settings',
                [
                    'default_br_tesla'     => 1.25,
                    'default_thickness_mm' => 5.0,
                    'default_diameter_mm'  => 10.0,
                    'default_distance_mm'  => 0.0,
                ]
            );
        }
    }
);

MSFC_Plugin::instance();
