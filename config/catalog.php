<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class-specific product fields
    |--------------------------------------------------------------------------
    |
    | Off for now: every product, in every category, is added with the same
    | fields (name, category, brand, price, stock, images, description, the
    | general characteristics). The class attributes, "where to use" tags and
    | the laminate card are being reworked category by category and come back
    | by turning this on — the code, the stored values and the catalog filters
    | built from existing data are untouched while it is off.
    |
    */

    'classifier_fields' => (bool) env('CATALOG_CLASSIFIER_FIELDS', false),

];
