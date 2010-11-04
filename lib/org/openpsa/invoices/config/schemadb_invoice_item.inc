'invoice_item' => array
(
    'name' => 'invoice_item',
    'description' => 'invoice item',
    'fields'      => array
    (
         'description' => array
        (
            'title' => 'description',
            'storage'  => 'description',
            'type'  => 'text',
            'widget'  => 'textarea',
            'required' => true,
        ),
        'price_per_unit' => array
        (
            'title' => 'price per unit',
            'storage'  => 'pricePerUnit',
            'type'  => 'number',
            'widget'  => 'text',
            'required' => true,
        ),
        'units' => array
        (
            'title' => 'units',
            'storage'  => 'units',
            'type'  => 'number',
            'widget'  => 'text',
            'required' => true,
        ),
    ),
),
