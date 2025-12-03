<?php

namespace Giganteck\Opcean\Core;

use Giganteck\Opcean\Abstracts\FormBuilderBase;
use Giganteck\Opcean\Contracts\SettingInterface;

class Setting extends FormBuilderBase implements SettingInterface {
    /**
     * settings sections array
     *
     * @var array
     */
    protected $settingsSections = [];

    /**
     * settings section
     *
     * @var array
     */
    protected string $settingsSection;

    /**
     * Setting page title
     *
     * @var string
     */
    private string $pageTitle;

    /**
     * Setting menu title
     *
     * @var string
     */
    private string $menuTitle;

    /**
     * Admin setting menu slug
     *
     * @var string
     */
    private string $menuSlug;

    /**
     * User role capability to use the setting
     *
     * @var string
     */
    private string $capability = 'manage_options';

    /**
     * Check if the tab script enqueud once
     *
     * @var bool
     */
    private static bool $tabScriptEnqueue = false;

    /**
     * Set admin setting page title
     *
     * @param string $pageTitle
     * @return Setting
     */
    public function pageTitle(string $pageTitle): SettingInterface {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    /**
     * Set admin setting menu title
     *
     * @param string $menuTitle
     * @return Setting
     */
    public function menuTitle(string $menuTitle): SettingInterface {
        $this->menuTitle = $menuTitle;
        return $this;
    }

    /**
     * Set admin setting menu slug
     *
     * @param string $menuSlug
     * @return Setting
     */
    public function menuSlug(string $menuSlug): SettingInterface {
        $this->menuSlug = $menuSlug;
        return $this;
    }

    /**
     * Set user role's capability to use the settings
     *
     * @param string $capability
     * @return Setting
     */
    public function capability(string $capability): SettingInterface {
        $this->capability = $capability;
        return $this;
    }

    /**
     * Set settings fields
     *
     * @param  array  $fields  settings fields array
     */
    public function fields(array $section, array $fields): SettingInterface
    {
        $section_id = array_key_first($section);
        $this->settingsSections = array_merge($this->settingsSections, $section);
        $this->fields[$section_id] = $fields;
        return $this;
    }

    /**
     * Add menu to admin for settings
     *
     * @return void
     */
    public function adminSettingMenu() {
        add_options_page(
            $this->pageTitle,
            $this->menuTitle,
            $this->capability,
            $this->menuSlug,
            [$this, 'adminSettingPage']
        );
    }

    /**
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     *
     * Usually this should be called at `admin_init` hook.
     *
     * @return void
     */
    public function adminSettingInit(): void
    {
        $this->registerSection();
        $this->registerField();
        $this->registerSetting();
    }

    /**
     * Initialize and registers the settings sections
     *
     * @return void
     */
    private function registerSection(): void
    {
        foreach ($this->settingsSections as $sectionId => $section) {
            if (get_option($sectionId) == false) {
                add_option($sectionId);
            }

            add_settings_section($sectionId, $section, fn() => null, $sectionId);
        }
    }

    /**
     * Initialize and registers the settings fields
     *
     * @return void
     */
    private function registerField(): void
    {
        foreach ($this->fields as $section => $field) {
            foreach ($field as $option) {
                $validation = $this->validateField($option);
                if (is_wp_error($validation)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log($validation->get_error_message(), E_USER_WARNING);
                    continue;
                }

                // Normalize field with setting context
                $args = $this->normalizeField($option, 'setting', [
                    'section' => $section
                ]);

                // Get callback
                $callback = isset($option['callback']) ? $option['callback'] : [$this, 'callbackField'];

                // Register field with WordPress
                add_settings_field(
                    "{$section}[{$args['field_name']}]",
                    $args['label'],
                    $callback,
                    $section,
                    $section,
                    $args
                );
            }
        }

    }

    /**
     * Register the settings to WordPress
     *
     * @return void
     */
    private function registerSetting(): void
    {
        foreach ($this->settingsSections as $sectionId => $_) {
            register_setting($sectionId, $sectionId, [$this, 'sanitizeSectionOptions']);
        }
    }

    /**
     * Unified callback for all field types
     *
     * @param  array  $args  settings field args
     */
    public function callbackField($args) {
        $value = $this->getOption($args['field_name'], $args['section'], $args['default']);
        $this->renderField($args, $value);
    }

    /**
     * Sanitize callback for Settings API (wrapper for section)
     *
     * @param  array  $options  options to sanitize
     * @return mixed
     */
    public function sanitizeSectionOptions($options) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $section = isset($_POST['option_page']) ? sanitize_text_field(wp_unslash($_POST['option_page'])) : '';
        return $this->sanitizeOptions($options, $section);
    }

    /**
     * Get the value of a settings field
     *
     * @param  string  $option  settings field name
     * @param  string  $section  the section name this field belongs to
     * @param  string  $default  default text if it's not found
     * @return string
     */
    public function getOption($option, $section, $default = '') {
        $options = get_option($section);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    public function showNavigation() {
        $html = '';
        $count = count($this->settingsSections);

        // skip showing the navigation if only one section exists
        if ($count === 1) {
            return;
        }

        foreach ($this->settingsSections as $tabId => $tabTitle) {
            $html .= sprintf('<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tabId, $tabTitle);
        }

        return sprintf(
            '<div class="nav-tab-wrapper">%s</div>',
            $html,
        );
    }

    /**
     * Show the admin setting page
     * This function displays every sections in a different form
     *
     * @return void
     */
    public function adminSettingPage(): void
    {
        ?>
            <div class="wrap">
                <h1><?php esc_html($this->pageTitle) ?></h1>
                <?php echo wp_kses_post($this->showNavigation()); ?>
                <div class="options_group">
                    <?php foreach ($this->settingsSections as $formId => $form) : ?>
                        <div id="<?php echo esc_attr($formId); ?>" class="group <?php echo esc_attr($formId) ?>" style="display: none;">
                            <form method="post" action="options.php">
                                <?php
                                    do_action('opcean_form_top_' . $formId, $form);
                                    settings_fields($formId);
                                    do_settings_sections($formId);
                                    do_action('opcean_form_bottom_' . $formId, $form);
                                    if (isset($this->fields[$formId])) :
                                ?>
                                    <div style="padding-left: 10px">
                                        <?php submit_button(); ?>
                                    </div>
                                <?php endif ?>
                            </form>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        <?php
        $this->tabNavigationScript();
    }

    /**
     * Add tab navigation script
     *
     * @return void
     */
    private function tabNavigationScript() {
        if (self::$tabScriptEnqueue) {
            return;
        }

        wp_add_inline_script('common', '
            const store = s => localStorage?.setItem("activetab", s);
            const get = () => localStorage?.getItem("activetab") || "";
            const qs = s => document.querySelector(s);
            const qsa = s => document.querySelectorAll(s);

            let tab = window.location.hash || get();

            // Hide all groups
            qsa(".group").forEach(el => el.style.display = "none");

            // Show active tab
            if (tab && qs(tab)) {
                qs(tab).style.display = "block";
                store(tab);
            } else {
                qs(".group").style.display = "block";
                tab = qs(".group").id ? "#" + qs(".group").id : "";
            }

            // Handle collapsed sections
            qsa(".group .collapsed").forEach(el => {
                const checked = el.querySelector("input:checked");
                if (checked) {
                    let next = checked.parentElement.parentElement.parentElement.nextElementSibling;
                    while (next) {
                        next.classList.remove("hidden");
                        if (next.classList.contains("last")) break;
                        next = next.nextElementSibling;
                    }
                }
            });

            // Set active tab indicator
            const setActive = (href) => {
                qsa(".nav-tab-wrapper a").forEach(a => a.classList.remove("nav-tab-active"));
                const activeLink = qs(`.nav-tab-wrapper a[href="${href}"]`);
                if (activeLink) activeLink.classList.add("nav-tab-active");
            };

            // Initialize active tab on load
            if (tab && qs(tab)) {
                setActive(tab);
            } else {
                const firstLink = qs(".nav-tab-wrapper a");
                if (firstLink) {
                    const href = firstLink.getAttribute("href");
                    setActive(href);
                }
            }

            // Tab click handler
            qsa(".nav-tab-wrapper a").forEach(link => {
                link.addEventListener("click", (e) => {
                    e.preventDefault();
                    const href = link.getAttribute("href");
                    const target = qs(href);

                    if (target) {
                        setActive(href);
                        qsa(".group").forEach(g => g.style.display = "none");
                        target.style.display = "block";
                        link.blur();
                        store(href);
                    }
                });
            });
        ');

        self::$tabScriptEnqueue = true;
    }

    /**
     * Render settings options
     *
     * @return void
     */
    public function render(): void {
        add_action('admin_init', [$this, 'adminSettingInit']);
        add_action('admin_menu', [$this, 'adminSettingMenu']);
    }

}
