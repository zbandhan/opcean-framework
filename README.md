# Opcean Framework
Right now, I am only sharing example code, but I will add detailed instructions later.

## Metabox code example

```
<?php
if ( class_exists(Opcean::class)) {
    Opcean::metabox()
        ->id('product_details')
        ->title(__( 'Product Details', 'my-plugin' ))
        ->screen(array( 'post' ))
        ->fields([
            array(
                'name'              => 'product_price',
                'label'             => __( 'Product Price', 'my-plugin' ),
                'desc'              => __( 'Enter the product price', 'my-plugin' ),
                'type'              => 'number',
                'min'               => 0,
                'step'              => '0.01',
                'default'           => 0.00,
                'sanitize_callback' => 'sanitize_float',
                'show_in_rest'      => true
            ),
            array(
                'name'              => 'product_sku',
                'label'             => __( 'SKU', 'my-plugin' ),
                'desc'              => __( 'Product SKU code', 'my-plugin' ),
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest'      => true
            ),
            array(
                'name'    => 'product_featured',
                'label'   => __( 'Featured Product', 'my-plugin' ),
                'desc'    => __( 'Mark as featured', 'my-plugin' ),
                'type'    => 'checkbox',
                'default' => 'off'
            ),
            array(
                'name'    => 'product_status',
                'label'   => __( 'Product Status', 'my-plugin' ),
                'desc'    => __( 'Select product status', 'my-plugin' ),
                'type'    => 'select',
                'default' => 'in_stock',
                'options' => array(
                    'in_stock'     => __( 'In Stock', 'my-plugin' ),
                    'out_of_stock' => __( 'Out of Stock', 'my-plugin' ),
                    'pre_order'    => __( 'Pre-order', 'my-plugin' )
                )
            ),
            array(
                'name'    => 'product_description',
                'label'   => __( 'Product Description', 'my-plugin' ),
                'desc'    => __( 'Detailed product description', 'my-plugin' ),
                'type'    => 'wysiwyg',
                'default' => ''
            ),
        ])
    ->render();
}
```

## Setting code example

```
<?php
if ( class_exists(Opcean::class)) {
    Opcean::setting()
        ->pageTitle('My Plugin Settings')
        ->menuTitle('My Plugin')
        ->menuSlug('my_plugin_settings')
        ->capability('manage_options')
        ->fields(['my_plugin_general' => 'General Settings'], [
            array(
                'name'              => 'text_val',
                'label'             => __( 'Text Input', 'wedevs' ),
                'desc'              => __( 'Text input description', 'wedevs' ),
                'placeholder'       => __( 'Text Input placeholder', 'wedevs' ),
                'type'              => 'text',
                'default'           => 'Title',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            array(
                'name'              => 'number_input',
                'label'             => __( 'Number Input', 'wedevs' ),
                'desc'              => __( 'Number field with validation callback `floatval`', 'wedevs' ),
                'placeholder'       => __( '1.99', 'wedevs' ),
                'min'               => 0,
                'max'               => 100,
                'step'              => '0.01',
                'type'              => 'number',
                'default'           => 'Title',
                'sanitize_callback' => 'floatval'
            ),
            array(
                'name'        => 'textarea',
                'label'       => __( 'Textarea Input', 'wedevs' ),
                'desc'        => __( 'Textarea description', 'wedevs' ),
                'placeholder' => __( 'Textarea placeholder', 'wedevs' ),
                'type'        => 'textarea'
            ),
            array(
                'name'        => 'html',
                'desc'        => __( 'HTML area description. You can use any <strong>bold</strong> or other HTML elements.', 'wedevs' ),
                'type'        => 'html'
            ),
            array(
                'name'  => 'checkbox',
                'label' => __( 'Checkbox', 'wedevs' ),
                'desc'  => __( 'Checkbox Label', 'wedevs' ),
                'type'  => 'checkbox'
            ),
            array(
                'name'  => 'checkbox1',
                'label' => __( 'Checkbox', 'wedevs' ),
                'desc'  => __( 'Checkbox Label', 'wedevs' ),
                'type'  => 'checkbox'
            ),
            array(
                'name'    => 'radio',
                'label'   => __( 'Radio Button', 'wedevs' ),
                'desc'    => __( 'A radio button', 'wedevs' ),
                'type'    => 'radio',
                'options' => array(
                    'yes' => 'Yes',
                    'no'  => 'No'
                )
            ),
            array(
                'name'    => 'selectbox',
                'label'   => __( 'A Dropdown', 'wedevs' ),
                'desc'    => __( 'Dropdown description', 'wedevs' ),
                'type'    => 'select',
                'default' => 'no',
                'options' => array(
                    'yes' => 'Yes',
                    'no'  => 'No'
                )
            ),
            array(
                'name'    => 'password',
                'label'   => __( 'Password', 'wedevs' ),
                'desc'    => __( 'Password description', 'wedevs' ),
                'type'    => 'password',
                'default' => ''
            ),
            array(
                'name'    => 'file',
                'label'   => __( 'File', 'wedevs' ),
                'desc'    => __( 'File description', 'wedevs' ),
                'type'    => 'file',
                'default' => '',
                'options' => array(
                    'button_label' => 'Choose Image'
                )
            )
        ])

        ->fields(['my_plugin_advanced' => 'Advanced Settings'], [
            array(
                'name'    => 'color',
                'label'   => __( 'Color', 'wedevs' ),
                'desc'    => __( 'Color description', 'wedevs' ),
                'type'    => 'color',
                'default' => ''
            ),
            array(
                'name'    => 'password',
                'label'   => __( 'Password', 'wedevs' ),
                'desc'    => __( 'Password description', 'wedevs' ),
                'type'    => 'password',
                'default' => ''
            ),
            array(
                'name'    => 'wysiwyg',
                'label'   => __( 'Advanced Editor', 'wedevs' ),
                'desc'    => __( 'WP_Editor description', 'wedevs' ),
                'type'    => 'wysiwyg',
                'default' => ''
            ),
            array(
                'name'    => 'multicheck',
                'label'   => __( 'Multile checkbox', 'wedevs' ),
                'desc'    => __( 'Multi checkbox description', 'wedevs' ),
                'type'    => 'multicheck',
                'default' => array('one' => 'one', 'four' => 'four'),
                'options' => array(
                    'one'   => 'One',
                    'two'   => 'Two',
                    'three' => 'Three',
                    'four'  => 'Four'
                )
            ),
        ])
    ->render();
}
```

## Term Metabox

```
<?php
if ( class_exists(Opcean::class)) {
    Opcean::termMeta()
        ->fields(['category', 'post_tag'], [
            array(
                'name'              => 'category_icon',
                'label'             => __( 'Category Icon', 'my-plugin' ),
                'desc'              => __( 'Upload an icon for this category', 'my-plugin' ),
                'type'              => 'file',
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
                'show_in_rest'      => true,
                'options'           => array(
                    'button_label' => __( 'Upload Icon', 'my-plugin' )
                )
            ),
            array(
                'name'              => 'category_color',
                'label'             => __( 'Category Color', 'my-plugin' ),
                'desc'              => __( 'Choose a color for this category', 'my-plugin' ),
                'type'              => 'color',
                'default'           => '#0073aa',
                'sanitize_callback' => 'sanitize_hex_color'
            ),
            array(
                'name'    => 'featured_category',
                'label'   => __( 'Featured Category', 'my-plugin' ),
                'desc'    => __( 'Mark as featured', 'my-plugin' ),
                'type'    => 'checkbox',
                'default' => 'off'
            ),
            array(
                'name'        => 'category_description_extra',
                'label'       => __( 'Extended Description', 'my-plugin' ),
                'desc'        => __( 'Additional description text', 'my-plugin' ),
                'type'        => 'textarea',
                'default'     => '',
                'rows'        => 5
            ),
        ])
        ->fields('post_tag', [
            array(
                'name'              => 'tag_color',
                'label'             => __( 'Tag Color', 'my-plugin' ),
                'desc'              => __( 'Choose a color for this tag', 'my-plugin' ),
                'type'              => 'color',
                'default'           => '#999999',
                'sanitize_callback' => 'sanitize_hex_color'
            ),
            array(
                'name'    => 'tag_importance',
                'label'   => __( 'Tag Importance', 'my-plugin' ),
                'desc'    => __( 'Select importance level', 'my-plugin' ),
                'type'    => 'select',
                'default' => 'normal',
                'options' => array(
                    'low'    => __( 'Low', 'my-plugin' ),
                    'normal' => __( 'Normal', 'my-plugin' ),
                    'high'   => __( 'High', 'my-plugin' )
                )
            ),
        ])
        ->fields('product_cat', [
            array(
                'name'              => 'product_cat_banner',
                'label'             => __( 'Category Banner', 'my-plugin' ),
                'desc'              => __( 'Upload a banner image', 'my-plugin' ),
                'type'              => 'file',
                'default'           => '',
                'options'           => array(
                    'button_label' => __( 'Choose Banner', 'my-plugin' )
                )
            ),
        ])
    ->render();
}
```
