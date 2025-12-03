<?php

namespace Giganteck\Opcean\Core;

use Giganteck\Opcean\Abstracts\FormBuilderBase;
use Giganteck\Opcean\Contracts\TermMetaInterface;

/**
 * Term Meta Class
 *
 * Handles creation and rendering of term meta fields with register_term_meta()
 */
class TermMeta extends FormBuilderBase implements TermMetaInterface {
    /**
     * Registered taxonomies
     *
     * @var array
     */
    protected $taxonomies = array();

   /**
     * Set settings fields
     *
     * @param  array|string  $taxonomies  Taxonomies (can be an array or a single string)
     * @param  array         $fields      Settings fields array
     */
    public function fields($taxonomies, array $fields): TermMetaInterface
    {
        if (is_array($taxonomies)) {
            $this->taxonomies = array_merge($this->taxonomies, $taxonomies);
            array_map(function (string $taxonomy) use ($fields): void {
                if (isset($this->fields[$taxonomy])) {
                    $this->fields[$taxonomy] = array_merge_recursive($this->fields[$taxonomy], $fields);
                } else {
                    $this->fields[$taxonomy] = $fields;
                }
            }, $taxonomies);
        } else {
            $this->taxonomies[] = $taxonomies;
            if (isset($this->fields[$taxonomies])) {
                $this->fields[$taxonomies] = array_merge_recursive($this->fields[$taxonomies], $fields);
            } else {
                $this->fields[$taxonomies] = $fields;
            }
        }

        return $this;
    }

    /**
     * Add a single taxonomy
     *
     * @param string $taxonomy taxonomy name
     * @return $this
     */
    public function addTaxonomy( $taxonomy ) {
        if ( ! in_array( $taxonomy, $this->taxonomies ) ) {
            $this->taxonomies[] = $taxonomy;
        }
        return $this;
    }

    /**
     * Register term meta fields
     * Call this on 'init' hook
     */
    public function registerMeta() {
        foreach ( $this->fields as $taxonomy => $fields ) {
            foreach ( $fields as $field ) {
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

                register_term_meta( $taxonomy, $field['name'], $args );
            }
        }
    }

    /**
     * Initialize term meta hooks
     */
    public function init() {
        foreach ( $this->taxonomies as $taxonomy ) {
            add_action( "{$taxonomy}_add_form_fields", array( $this, 'renderAddFormFields' ), 10, 1 );
            add_action( "{$taxonomy}_edit_form_fields", array( $this, 'renderEditFormFields' ), 10, 2 );
            add_action( "created_{$taxonomy}", array( $this, 'saveTermMeta' ), 10, 1 );
            add_action( "edited_{$taxonomy}", array( $this, 'saveTermMeta' ), 10, 1 );
        }
    }

    /**
     * Render fields for add form
     *
     * @param string $taxonomy taxonomy name
     */
    public function renderAddFormFields( $taxonomy ) {
        if ( ! isset( $this->fields[$taxonomy] ) ) {
            return;
        }

        wp_nonce_field( 'opcean_term_meta_' . $taxonomy, 'opcean_term_meta_nonce_' . $taxonomy );

        foreach ( $this->fields[$taxonomy] as $field ) {
            $validation = $this->validateField( $field );
            if ( is_wp_error( $validation ) ) {
                continue;
            }

            // Get default value
            $value = $this->getDefaultValue( $field );

            // Normalize field with term_meta context
            $args = $this->normalizeField( $field, 'term_meta', [
                'taxonomy' => $taxonomy
            ] );

            echo '<div class="form-field">';
            echo '<label for="' . esc_attr( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label>';

            $this->renderField( $args, $value );

            echo '</div>';
        }
    }

    /**
     * Render fields for edit form
     *
     * @param \WP_Term $term term object
     * @param string $taxonomy taxonomy name
     */
    public function renderEditFormFields( $term, $taxonomy ) {
        if ( ! isset( $this->fields[$taxonomy] ) ) {
            return;
        }

        wp_nonce_field( 'opcean_term_meta_' . $taxonomy, 'opcean_term_meta_nonce_' . $taxonomy );

        foreach ( $this->fields[$taxonomy] as $field ) {
            // Validate field
            $validation = $this->validateField( $field );
            if ( is_wp_error( $validation ) ) {
                continue;
            }

            // Get stored value
            $value = get_term_meta( $term->term_id, $field['name'], true );

            // Use default if no value
            if ( $value === '' || $value === false ) {
                $value = $this->getDefaultValue( $field );
            }

            // Normalize field with term_meta context
            $args = $this->normalizeField( $field, 'term_meta', [
                'taxonomy' => $taxonomy,
                'term_id'  => $term->term_id
            ] );

            echo '<tr class="form-field">';
            echo '<th scope="row"><label for="' . esc_attr( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label></th>';
            echo '<td>';

            $this->renderField( $args, $value );

            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Save term meta data
     *
     * @param int $term_id term ID
     */
    public function saveTermMeta( $term_id ) {
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return;
        }

        $taxonomy = $term->taxonomy;

        // Verify nonce
        $nonceName = 'opcean_term_meta_nonce_' . $taxonomy;
        if ( ! isset( $_POST[$nonceName] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST[$nonceName])), 'opcean_term_meta_' . $taxonomy ) ) {
            return;
        }

        // Check user permissions
        $taxObject = get_taxonomy( $taxonomy );
        if ( ! current_user_can( $taxObject->cap->edit_terms ) ) {
            return;
        }

        // Save fields
        if ( isset( $this->fields[$taxonomy] ) ) {
            foreach ( $this->fields[$taxonomy] as $field ) {
                if ( empty( $field['name'] ) ) {
                    continue;
                }

                $fieldName = $field['name'];

                if ( isset( $_POST[$fieldName] ) ) {
                    $value = sanitize_text_field(wp_unslash($_POST[$fieldName]));

                    // Sanitize
                    $sanitize_callback = $this->getSanitizeCallback( $fieldName, $taxonomy );
                    if ( $sanitize_callback ) {
                        $value = call_user_func( $sanitize_callback, $value );
                    }

                    update_term_meta( $term_id, $fieldName, $value );
                } else {
                    if ( isset( $field['type'] ) && $field['type'] === 'checkbox' ) {
                        update_term_meta( $term_id, $fieldName, 'off' );
                    }
                }
            }
        }
    }

    /**
     * Render the metabox to the taxonomies
     *
     * @return void
     */
    public function render(): void
    {
        add_action( 'init', array( $this, 'registerMeta' ) );
        add_action( 'init', array( $this, 'init' ) );
    }

}
