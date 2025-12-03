<?php

namespace Giganteck\Opcean\Core;

use Giganteck\Opcean\Abstracts\FormBuilderBase;
use Giganteck\Opcean\Contracts\MetaboxInterface;

/**
 * Metabox Class
 *
 * Handles creation and rendering of metaboxes with register_meta()
 */
class Metabox extends FormBuilderBase implements MetaboxInterface {
    /**
     * Save id for metabox
     *
     * @var string
     */
    private string $id;

    /**
     * Save title for metabox
     *
     * @var string
     */
    private string $title;

    /**
     * Save screen for metabox
     *
     * @var string|array
     */
    private string|array $screen;

    /**
     * Save context for metabox
     *
     * @var string
     */
    private string $context = 'normal';

    /**
     * Save priority for metabox
     *
     * @var string
     */
    private string $priority = 'high';

    /**
     * Set id for metabox
     *
     * @param string $id Set metabox id
     * @return Metabox
     */
    public function id($id): MetaboxInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set title for metabox
     *
     * @param string $title set metabox title
     * @return Metabox
     */
    public function title($title): MetaboxInterface
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set screen for metabox
     *
     * @param string $screen Set metabox screen
     * @return Metabox
     */
    public function screen($screen): MetaboxInterface
    {
        $this->screen = $screen;
        return $this;
    }

    /**
     * Set context for metabox
     *
     * @param string $context Set metabox context
     * @return Metabox
     */
    public function context($context): MetaboxInterface
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Set priority for metabox
     *
     * @param string $priority Set metabox priority
     * @return Metabox
     */
    public function priority($priority): MetaboxInterface
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Initialize metaboxes
     * Call this on 'addMetaBoxes' hook
     */
    public function addMetaBoxes() {
        add_meta_box(
            $this->id,
            $this->title,
            array( $this, 'renderMetabox' ),
            $this->screen,
            $this->context,
            $this->priority,
            array( 'metaboxId' => $this->id )
        );
    }

    /**
     * Register meta fields
     * Call this on 'init' hook
     */
    public function registerMeta() {
        foreach ( $this->fields[$this->id] as $field ) {
            if ( empty( $field['name'] ) ) {
                continue;
            }

            $args = array(
                'type'              => $this->getMetaType( $field ),
                'description'       => isset( $field['desc'] ) ? $field['desc'] : '',
                'single'            => true,
                'default'           => $this->getTypedDefaultValue( $field ),
                'show_in_rest'      => isset( $field['show_in_rest'] ) ? $field['show_in_rest'] : false,
            );

            if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
                $args['sanitize_callback'] = $field['sanitize_callback'];
            }

            if ( isset( $field['auth_callback'] ) && is_callable( $field['auth_callback'] ) ) {
                $args['auth_callback'] = $field['auth_callback'];
            }

            if ( is_array($this->screen) ) {
                foreach ( $this->screen as $postType ) {
                    register_post_meta( $postType, $field['name'], $args );
                }
            } else {
                register_post_meta( $this->screen, $field['name'], $args );
            }

        }
    }

    /**
     * Render metabox callback
     *
     * @param \WP_Post $post post object
     * @param array $callbackArgs callback args
     */
    public function renderMetabox( $post, $callbackArgs ): void
    {
        $metaboxId = isset( $callbackArgs['args']['metaboxId'] ) ? $callbackArgs['args']['metaboxId'] : '';

        if ( empty( $metaboxId ) || ! isset( $this->fields[$metaboxId] ) ) {
            return;
        }

        // Add nonce field
        wp_nonce_field( 'opcean_metabox_' . $metaboxId, 'opcean_metabox_nonce_' . $metaboxId );

        echo '<table class="form-table">';

        foreach ( $this->fields[$metaboxId] as $field ) {
            $validation = $this->validateField( $field );
            if ( is_wp_error( $validation ) ) {
                continue;
            }

            // Get the stored value
            $value = get_post_meta( $post->ID, $field['name'], true );

            // Use default if no value exists
            if ( $value === '' || $value === false || $value === null ) {
                $value = $this->getDefaultValue( $field );
            }

            // Normalize field with metabox context
            $args = $this->normalizeField( $field, 'metabox', [
                'post_id' => $post->ID
            ] );

            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label></th>';
            echo '<td>';

            $this->renderField( $args, $value );

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Save metabox data
     * Call this on 'save_post' hook
     *
     * @param int $post_id post ID
     * @param \WP_Post $post post object
     */
    public function saveMetabox( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( empty( $this->id ) ) {
            return;
        }

        // Verify nonce
        $nonce_name = 'opcean_metabox_nonce_' . $this->id;
        if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST[$nonce_name])), 'opcean_metabox_' . $this->id ) ) {
            return;
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_posts', $post_id ) ) {
            return;
        }

        if ( isset( $this->fields[$this->id] ) ) {
            foreach ( $this->fields[$this->id] as $field ) {
                if ( empty( $field['name'] ) ) {
                    continue;
                }

                $fieldName = $field['name'];

                if ( isset( $_POST[$fieldName] ) ) {
                    $value = sanitize_text_field(wp_unslash($_POST[$fieldName]));

                    // Sanitize
                    $sanitizeCallback = $this->getSanitizeCallback( $fieldName, $this->id );
                    if ( $sanitizeCallback ) {
                        $value = call_user_func( $sanitizeCallback, $value );
                    }

                    update_post_meta( $post_id, $fieldName, $value );
                } else {
                    if ( isset( $field['type'] ) && $field['type'] === 'checkbox' ) {
                        update_post_meta( $post_id, $fieldName, 'off' );
                    }
                }
            }
        }

    }

    public function fields($fields): MetaboxInterface
    {
        $this->fields[$this->id] = $fields;
        return $this;
    }

    /**
     * Render Metabox in different screens
     *
     * @return void
     */
    public function render(): void
    {
        add_action( 'init', array( $this, 'registerMeta' ) );
        add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
        add_action( 'save_post', array( $this, 'saveMetabox' ), 10, 2 );
    }

}
