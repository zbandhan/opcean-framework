<?php

namespace Giganteck\Opcean\Core;

/**
 * Field Renderer Class
 *
 * Handles rendering of all field types with consistent HTML output.
 * Can be used across Settings, Metaboxes, and Term Meta.
 */
class Field {
    /**
     * Check if the script is already enqueued
     *
     * @var bool
     */
    private static $mediaScriptEnqueued = false;

    /**
     * Check if the script is alreayd enqueued
     *
     * @var bool
     */
    private static $colorPickerScriptEnqueued = false;

    /**
     * Get field description for display
     *
     * @param array $args field args
     * @return string
     */
    public static function getFieldDescription( $args ) {
        if ( ! isset( $args['desc'] ) || ! is_string( $args['desc'] ) ) {
            return '';
        }

        if ( $args['type'] === 'checkbox') {
            return sprintf( '<span class="description">%s</span>', $args['desc'] );
        }

        return sprintf( '<span class="description" style="display: block">%s</span>', $args['desc'] );
    }

    /**
     * Render a text field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function text( $args, $value = '' ) {
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

        return sprintf(
            '<input type="%1$s" class="%2$s" id="%3$s" name="%4$s" value="%5$s"%6$s/>%7$s',
            esc_attr( $args['type'] ),
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            $placeholder,
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render a URL field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function url( $args, $value = '' ) {
        $args['type'] = 'url';
        return self::text( $args, $value );
    }

    /**
     * Render a number field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function number( $args, $value = '' ) {
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-number';
        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';
        $min = ! empty( $args['min'] ) && $args['min'] !== '' ? ' min="' . esc_attr( $args['min'] ) . '"' : '';
        $max = ! empty( $args['max'] ) && $args['max'] !== '' ? ' max="' . esc_attr( $args['max'] ) . '"' : '';
        $step = ! empty( $args['step'] ) && $args['step'] !== '' ? ' step="' . esc_attr( $args['step'] ) . '"' : '';

        return sprintf(
            '<input type="number" class="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s%6$s%7$s%8$s/>%9$s',
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            $placeholder,
            $min,
            $max,
            $step,
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render a checkbox field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function checkbox( $args, $value = '' ) {
        return sprintf(
            '<input type="hidden" name="%2$s" value="off" />
                <input type="checkbox" class="checkbox" id="%1$s" name="%2$s" value="on" %4$s />
                %3$s',
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            self::getFieldDescription($args),
            checked( $value, 'on', false )
        );
    }

    /**
     * Render a multicheck field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function multicheck( $args, $value = array() ) {
        if ( ! is_array( $value ) ) {
            $value = array();
        }

        $html = '';
        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf(
                '<label for="%1$s[%2$s]">
                    <input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%3$s[%2$s]" value="%2$s" %4$s />
                    %5$s
                </label><br>',
                esc_attr( $args['id'] ),
                esc_attr( $key ),
                esc_attr( $args['name'] ),
                checked( isset( $value[$key] ), true, false ),
                esc_html( $label )
            );
        }

        return sprintf( '<fieldset>
                <input type="hidden" name="%s" value="" />
                %s%s
            </fieldset>',
            esc_attr( $args['name'] ),
            $html,
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render a radio field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function radio( $args, $value = '' ) {
        $html = '';
        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf(
                '<label for="%1$s[%2$s]">
                        <input type="radio" class="radio" id="%1$s[%2$s]" name="%3$s" value="%2$s" %5$s />
                        %4$s
                    </label><br/>',
                esc_attr( $args['id'] ),
                esc_attr( $key ),
                esc_attr( $args['name'] ),
                esc_html( $label ),
                checked( $value, $key, false ),
            );
        }

        return sprintf(
            '<fieldset>%1$s%2$s</fieldset>',
            $html,
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render a select field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function select( $args, $value = '' ) {
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular';
        $html = '';

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }

        return sprintf(
            '<select class="%1$s" name="%2$s" id="%3$s">%4$s</select>%5$s',
            $class,
            esc_attr( $args['name'] ),
            esc_attr( $args['id'] ),
            $html,
            self::getFieldDescription( $args )
        );

    }

    /**
     * Render a textarea field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function textarea( $args, $value = '' ) {
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
        $rows = ! empty( $args['rows'] ) ? $args['rows'] : '4';
        $cols = ! empty( $args['cols'] ) ? $args['cols'] : '50';
        $placeholder = ! empty( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';

        return sprintf(
            '<textarea rows="%1$s" cols="%2$s" class="%3$s" id="%4$s" name="%5$s"%6$s>%7$s</textarea>%8$s',
            $rows,
            $cols,
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            $placeholder,
            esc_textarea( $value ),
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render HTML content
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function html( $args, $value = '' ) {
        return self::getFieldDescription( $args );
    }

    /**
     * Render a WYSIWYG editor field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function wysiwyg( $args, $value = '' ) {
        $size = ! empty( $args['size'] ) ? $args['size'] : '500px';
        $editorSettings = array(
            'teeny'         => true,
            'textarea_name' => $args['name'],
            'textarea_rows' => 10
        );

        if ( ! empty( $args['editor_options'] ) && is_array( $args['editor_options'] ) ) {
            $editorSettings = array_merge( $editorSettings, $args['editor_options'] );
        }

        ob_start();
        wp_editor( $value, $args['id'], $editorSettings );
        $editor = ob_get_clean();

        return sprintf(
            '<div class="opcean-wysiwyg" style="max-width: %s;">%s</div>%s',
            esc_attr( $size ),
            $editor,
            self::getFieldDescription( $args )
        );
    }

    /**
     * Render a file upload field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function file( $args, $value = '' ) {
        self::mediaScript();
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
        $label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File', 'opcean-framework' );

        return sprintf(
            '<input type="text" class="%1$s opcean-file-url" id="%2$s" name="%3$s" value="%4$s"/>
            <input type="button" class="button opcean-file-browse" value="%5$s" />%6$s',
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            esc_attr( $label ),
            self::getFieldDescription( $args )
        );

    }

    /**
     * Render a password field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function password( $args, $value = '' ) {
        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
        return sprintf(
            '<input type="password" class="%1$s" id="%2$s" name="%3$s" value="%4$s"/>%5$s',
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            self::getFieldDescription( $args )
        );

    }

    /**
     * Render a color picker field
     *
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function color( $args, $value = '' ) {
        self::colorPickerScript();

        $class = ! empty( $args['class'] ) ? $args['class'] : 'regular-text';
        return sprintf(
            '<input type="text" class="%1$s wp-color-picker-field" id="%2$s" name="%3$s" value="%4$s" data-default-color="%5$s" />%6$s',
            $class,
            esc_attr( $args['id'] ),
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            esc_attr( $args['default'] ),
            self::getFieldDescription( $args )
        );

    }

    /**
     * Add media script for file browse field
     *
     * @return void
     */
    private static function mediaScript() {
        wp_enqueue_media();

        // Skip repeating enqueue
        if ( self::$mediaScriptEnqueued ) {
            return;
        }

        wp_add_inline_script('media-models', "
            jQuery(function ($) {
                $('.opcean-file-browse').on('click', function (event) {
                    event.preventDefault();

                    var self = $(this);
                    var file_frame = wp.media({
                        title: self.data('uploader_title'),
                        button: { text: self.data('uploader_button_text') },
                        multiple: false
                    });

                    file_frame.on('select', function () {
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        self.prev('.opcean-file-url').val(attachment.url).change();
                    });

                    file_frame.open();
                });
            });
        ");

        self::$mediaScriptEnqueued = true;
    }

    /**
     * Add color script for color picker field
     *
     * @return void
     */
    private static function colorPickerScript() {
        wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

        // Skip repeating enqueue
        if ( self::$colorPickerScriptEnqueued ) {
            return;
        }

        wp_add_inline_script('wp-color-picker', '
            jQuery(function($) {
                $(".wp-color-picker-field").wpColorPicker();
            });
        ');

        self::$colorPickerScriptEnqueued = true;
    }

    /**
     * Render field by type
     *
     * @param string $type field type
     * @param array $args field args
     * @param mixed $value current value
     * @return string
     */
    public static function render( $type, $args, $value = '' ) {
        if ( method_exists( __CLASS__, $type ) ) {
            return self::$type( $args, $value );
        }

        return '';
    }
}
