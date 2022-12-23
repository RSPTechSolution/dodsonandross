# Commerce Choose Price

This module allow you to add a field formatter to Commerce 2.0 order items so
the customers can set their own price for a product.
The price can not be lower than the price of the most expensive product variant.

This is especially useful for products which don't have a direct weight or unit
price such as flower bouquets and an assortment of bits being sold in bulk.

You can even add the allow override price field to your products for an even
more fine grained control of then to show the variant price field. This allows
the product manager to specify if a product has the custom price option or not.


# Usage

1. Go to admin/commerce/config/order-item-types/YOUR_ORDER_ITEM_TYPE/edit/form-display/add_to_cart

2. Set Price field (Default “Unit Price”) to “Choose price”

3. Choose YOUR_ORDER_ITEM_TYPE under “Order item type” in the product variation
/admin/commerce/config/product-variation-types/YOUR_ORDER_ITEM_TYPE/edit

