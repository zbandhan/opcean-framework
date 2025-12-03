<?php

namespace Giganteck\Opcean\Abstracts;

use Giganteck\Opcean\Core\Field;

/**
 * Abstract FormBuilderBase Class for Field Management
 *
 * Provides common functionality for Settings, Metaboxes, and Term Meta
 */
abstract class FormBuilderBase {
    /**
     * Registered fields
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Set fields
     *
     * @param array $fields fields array
     * @return $this
     */
    public function setFields( $fields ) {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     * @param string $section section identifier
     * @return mixed string or bool false
     */
    protected function getSanitizeCallback( $slug = '', $section = '' ) {
        if ( empty( $slug ) ) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        if ( isset( $this->fields[$section] ) ) {
            foreach ( $this->fields[$section] as $option ) {
                if ( $option['name'] != $slug ) {
                    continue;
                }

                // Return the callback name
                return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] )
                    ? $option['sanitize_callback']
                    : false;
            }
        }

        return false;
    }

    /**
     * Sanitize options
     *
     * @param array $options options to sanitize
     * @param string $section section identifier
     * @return array
     */
    protected function sanitizeOptions( $options, $section = '' ) {
        if ( ! $options ) {
            return $options;
        }

        foreach( $options as $option_slug => $option_value ) {
            $sanitize_callback = $this->getSanitizeCallback( $option_slug, $section );

            // If callback is set, call it
            if ( $sanitize_callback ) {
                $options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
                continue;
            }
        }

        return $options;
    }

    /**
     * Render a field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return void
     */
    protected function renderField( $args, $value = '' ) {
        $type = isset( $args['type'] ) ? $args['type'] : 'text';

        // Check for custom callback
        if ( isset( $args['callback'] ) && is_callable( $args['callback'] ) ) {
            call_user_func( $args['callback'], $args, $value );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Field::render( $type, $args, $value );
    }

    /**
     * Get field default value
     *
     * @param array $field field configuration
     * @return mixed
     */
    protected function getDefaultValue( $field ) {
        return isset( $field['default'] ) ? $field['default'] : '';
    }

    /**
     * Get meta type from field type
     *
     * @param array $field field configuration
     * @return string
     */
    protected function getMetaType( $field ) {
        $type = isset( $field['type'] ) ? $field['type'] : 'text';

        $typeMapping = array(
            'number'     => 'number',
            'checkbox'   => 'string',
            'multicheck' => 'array',
            'text'       => 'string',
            'textarea'   => 'string',
            'wysiwyg'    => 'string',
            'select'     => 'string',
            'radio'      => 'string',
            'color'      => 'string',
            'file'       => 'string',
            'url'        => 'string',
            'password'   => 'string',
        );

        return isset( $typeMapping[$type] ) ? $typeMapping[$type] : 'string';
    }

    /**
     * Get properly typed default value for meta registration
     *
     * @param array $field field configuration
     * @return mixed
     */
    protected function getTypedDefaultValue( $field ) {
        $default = $this->getDefaultValue( $field );
        $metaType = $this->getMetaType( $field );

        // Type cast default value to match meta type
        switch ( $metaType ) {
            case 'number':
                if ( isset( $field['step'] ) && strpos( $field['step'], '.' ) !== false ) {
                    return (float) $default;
                }
                return (int) $default;

            case 'array':
                return is_array( $default ) ? $default : array();

            case 'boolean':
                return (bool) $default;

            case 'string':
            default:
                return (string) $default;
        }
    }

    /**
     * Sanitize field value based on field type
     *
     * @param array $field Field configuration.
     * @param mixed $value Value to sanitize.
     * @return mixed Sanitized value.
     */
    protected function sanitize( $field, $value ) {
        $type = isset( $field['type'] ) ? $field['type'] : 'text';

        switch ( $type ) {
            case 'text':
            case 'password':
                return sanitize_text_field( $value );

            case 'textarea':
                return sanitize_textarea_field( $value );

            case 'email':
                return sanitize_email( $value );

            case 'url':
                return esc_url_raw( $value );

            case 'number':
                if ( isset( $field['step'] ) && strpos( $field['step'], '.' ) !== false ) {
                    return floatval( $value );
                }
                return absint( $value );

            case 'checkbox':
                return $value === 'on' || $value === '1' || $value === 1 || $value === true ? 'on' : 'off';

            case 'multicheck':
            case 'select':
            case 'radio':
                if ( is_array( $value ) ) {
                    return array_map( 'sanitize_text_field', $value );
                }
                return sanitize_text_field( $value );

            case 'wysiwyg':
                return wp_kses_post( $value );

            case 'color':
                return sanitize_hex_color( $value );

            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Normalize field configuration with defaults
     *
     * This method standardizes field arrays across all components,
     * ensuring consistent structure and providing sensible defaults.
     *
     * @param array $field Raw field configuration
     * @param string $context Context: 'metabox', 'setting', 'term_meta'
     * @param array $contextData Additional context data (section, post_id, etc.)
     * @return array Normalized field configuration
     */
    protected function normalizeField( array $field, string $context = 'default', array $contextData = [] ): array {
        if ( empty( $field['name'] ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'Field name is required', E_USER_WARNING );
            return [];
        }

        $name = $field['name'];
        $type = isset( $field['type'] ) ? $field['type'] : 'text';

        // Base normalized structure
        $normalized = [
            // Core identifiers
            'name'       => $name,
            'id'         => isset( $field['id'] ) ? $field['id'] : $name,
            'type'       => $type,

            // Labels and descriptions
            'label'      => isset( $field['label'] ) ? $field['label'] : '',
            'desc'       => isset( $field['desc'] ) ? $field['desc'] : '',
            'placeholder' => isset( $field['placeholder'] ) ? $field['placeholder'] : '',

            // Styling and classes
            'class'      => isset( $field['class'] ) ? $field['class'] : '',
            'wrapper_class' => isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '',

            // Values and defaults
            'default'    => isset( $field['default'] ) ? $field['default'] : '',
            'value'      => isset( $field['value'] ) ? $field['value'] : '',

            // Options for select, radio, checkbox, etc.
            'options'    => isset( $field['options'] ) ? $field['options'] : [],

            // HTML5 attributes
            'min'        => isset( $field['min'] ) ? $field['min'] : '',
            'max'        => isset( $field['max'] ) ? $field['max'] : '',
            'step'       => isset( $field['step'] ) ? $field['step'] : '',
            'pattern'    => isset( $field['pattern'] ) ? $field['pattern'] : '',
            'required'   => isset( $field['required'] ) ? $field['required'] : false,
            'readonly'   => isset( $field['readonly'] ) ? $field['readonly'] : false,
            'disabled'   => isset( $field['disabled'] ) ? $field['disabled'] : false,

            // Textarea specific
            'rows'       => isset( $field['rows'] ) ? $field['rows'] : 5,
            'cols'       => isset( $field['cols'] ) ? $field['cols'] : 55,

            // Size attribute
            'size'       => isset( $field['size'] ) ? $field['size'] : null,

            // WYSIWYG specific
            'editor_options' => isset( $field['editor_options'] ) ? $field['editor_options'] : [],

            // Callbacks
            'sanitize_callback' => isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : '',
            'validate_callback' => isset( $field['validate_callback'] ) ? $field['validate_callback'] : '',
            'render_callback'   => isset( $field['render_callback'] ) ? $field['render_callback'] : '',
            'callback'          => isset( $field['callback'] ) ? $field['callback'] : '',

            // REST API
            'show_in_rest' => isset( $field['show_in_rest'] ) ? $field['show_in_rest'] : false,

            // Context data
            'context'    => $context,
        ];

        // Context-specific normalization
        switch ( $context ) {
            case 'setting':
                if ( ! empty( $contextData['section'] ) ) {
                    $section = $contextData['section'];
                    $normalized['section'] = $section;
                    $normalized['field_name'] = $name; // Original name
                    $normalized['name'] = "{$section}[{$name}]"; // Namespaced
                    $normalized['label_for'] = "{$section}[{$name}]";
                }
                break;

            case 'metabox':
                $normalized['name'] = $name;
                $normalized['id'] = $name;
                if ( ! empty( $contextData['post_id'] ) ) {
                    $normalized['post_id'] = $contextData['post_id'];
                }
                break;

            case 'term_meta':
                if ( ! empty( $contextData['term_id'] ) ) {
                    $normalized['term_id'] = $contextData['term_id'];
                }
                if ( ! empty( $contextData['taxonomy'] ) ) {
                    $normalized['taxonomy'] = $contextData['taxonomy'];
                }
                break;
        }

        // Merge any additional field data that wasn't normalized
        foreach ( $field as $key => $value ) {
            if ( ! isset( $normalized[$key] ) ) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Get field property with default fallback
     *
     * @param array $field Field configuration
     * @param string $property Property name
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getFieldProperty( array $field, string $property, $default = '' ) {
        return isset( $field[$property] ) ? $field[$property] : $default;
    }

    /**
     * Validate field configuration
     *
     * @param array $field Field configuration
     * @return bool|\WP_Error True if valid, WP_Error if invalid
     */
    protected function validateField( array $field ) {
        if ( empty( $field['name'] ) ) {
            return new \WP_Error( 'missing_field_name', __( 'Field name is required', 'opcean-framework' ) );
        }

        if ( empty( $field['type'] ) ) {
            return new \WP_Error( 'missing_field_type', __( 'Field type is required', 'opcean-framework' ) );
        }

        // Validate field type
        $validTypes = [
            'text',
            'textarea',
            'email',
            'url',
            'number',
            'password',
            'checkbox',
            'radio',
            'select',
            'multicheck',
            'wysiwyg',
            'color',
            'date',
            'time',
            'datetime',
            'file',
            'image',
            'hidden',
            'html',
            'title',
            'sectionend'
        ];

        if ( ! in_array( $field['type'], $validTypes ) ) {
            return new \WP_Error(
            'invalid_field_type',
            sprintf(
                    /* translators: %s is the invalid field type provided. */
                __( 'Invalid field type: %s', 'opcean-framework' ),
                $field['type']
            ));
        }

        return true;
    }

}
