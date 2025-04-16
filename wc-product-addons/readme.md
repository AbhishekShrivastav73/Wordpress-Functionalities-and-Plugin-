# WooCommerce Product Add-Ons Plugin Documentation

## Overview

The **WooCommerce Product Add-Ons Plugin** allows store owners to add customizable add-ons to WooCommerce products. Customers can select these add-ons on the product page, and the total price (base price + add-ons) updates dynamically, including quantity changes. Add-ons are saved to the cart, displayed in checkout, and included in order details.

**Key Features**:
- **Admin Interface**: Add multiple add-on types (Checkbox, Radio, Dropdown) per product with name and price.
- **Frontend Display**: Type-wise add-ons shown on product page (e.g., Checkbox for Type 1, Radio for Type 2).
- **Dynamic Pricing**: Real-time total price updates based on add-ons and quantity.
- **Cart/Checkout Integration**: Add-ons saved and displayed in cart, checkout, and orders.
- **Flexible**: Supports multiple add-on groups, ensuring at least one add-on per type.

This documentation explains the plugin’s structure, functionality, and how to modify it for future needs.

---

## Plugin Details

- **Name**: WooCommerce Product Add-Ons
- **Version**: 1.0.1
- **Requires**: WooCommerce 3.0+
- **Tested Up To**: WordPress 6.4, WooCommerce 8.5
- **Purpose**: Enable product-specific add-ons with dynamic pricing and order integration.
- **Author**: [Your Name/Team]

---

## Folder and File Structure

The plugin follows a clear structure to separate admin and frontend logic.

```
/wc-product-addons/
├── /admin/                    # Admin-related files
│   ├── admin.php              # Admin logic (tab, fields, save)
│   ├── /js/
│   │   ├── admin.js           # Admin JavaScript (dynamic UI)
│   ├── /css/
│   │   ├── admin.css          # Admin styling
├── /frontend/                 # Frontend-related files
│   ├── frontend.php           # Frontend logic (display, cart, orders)
│   ├── /js/
│   │   ├── frontend.js        # Frontend JavaScript (total price)
│   ├── /css/
│   │   ├── frontend.css       # Frontend styling
├── wc-product-addons.php      # Main plugin file (activation, includes)
├── readme.txt                 # Plugin metadata
```

### File Descriptions

1. **wc-product-addons.php**:
   - Main plugin file.
   - Includes admin and frontend files.
   - Defines plugin metadata and activation hooks.

2. **admin/admin.php**:
   - Adds “Add-Ons” tab in WooCommerce product editor.
   - Handles add-on groups (type, name, price) and saving to `_product_addon_groups` meta.
   - Enqueues `admin.js` and `admin.css`.

3. **admin/js/admin.js**:
   - Dynamic UI for adding/removing add-on types and add-ons.
   - Ensures at least one add-on per type.
   - Handles events for buttons (Add Type, Remove Type, Add Add-On, Remove Add-On).

4. **admin/css/admin.css**:
   - Styles admin UI (add-on groups, buttons, inputs).
   - Ensures clean layout for add-on management.

5. **frontend/frontend.php**:
   - Displays add-ons on product page (type-wise: Checkbox, Radio, Dropdown).
   - Saves add-ons to cart (`addon_groups`).
   - Updates cart prices and displays add-ons in cart/checkout/orders.
   - Enqueues `frontend.js` and `frontend.css`.

6. **frontend/js/frontend.js**:
   - Calculates and updates total price on product page.
   - Listens for add-on changes (checkbox, radio, select) and quantity changes.

7. **frontend/css/frontend.css**:
   - Styles frontend UI (add-on groups, total price display).
   - Ensures responsive and clean layout.

8. **readme.txt**:
   - Plugin description, installation, FAQ, and changelog for WordPress.org.

---

## Installation

1. **Upload**:
   - Copy the `wc-product-addons` folder to `/wp-content/plugins/`.
2. **Activate**:
   - Go to WordPress Admin > Plugins > Activate “WooCommerce Product Add-Ons”.
3. **Requirements**:
   - WooCommerce must be installed and active.
4. **Verify**:
   - Edit a product; check for “Add-Ons” tab.
   - View product page; confirm add-ons display.

---

## Functionality

### 1. Admin Interface

**Location**: Product Edit > Add-Ons Tab

**Features**:
- **Add-On Types**:
  - Create multiple groups (e.g., Type 1: Checkbox, Type 2: Radio).
  - Each type has a display option: Checkbox, Radio, or Dropdown.
- **Add-Ons**:
  - Add name and price for each add-on (e.g., Cold Drink, ₹2).
  - Minimum one add-on per type (enforced by JavaScript).
- **Actions**:
  - **Add More Addon Type**: Adds new group.
  - **Remove Type**: Deletes group (min 1 required).
  - **Add Another Add-On**: Adds new add-on row.
  - **Remove Add-On**: Deletes add-on (min 1 per type).
- **Save**:
  - Data saved to `_product_addon_groups` meta as:
    ```php
    [
        [
            'display_type' => 'checkbox',
            'addons' => [
                ['name' => 'Cold Drink', 'price' => 2],
                ['name' => 'Veggies', 'price' => 3]
            ]
        ],
        [
            'display_type' => 'radio',
            'addons' => [
                ['name' => 'Sauce', 'price' => 1]
            ]
        ]
    ]
    ```

**Files Involved**:
- `admin/admin.php`: Tab and fields rendering, saving logic.
- `admin/js/admin.js`: Dynamic UI (add/remove).
- `admin/css/admin.css`: Styling.

**Hooks Used**:
- `woocommerce_product_data_tabs`: Add tab.
- `woocommerce_product_data_panels`: Render fields.
- `woocommerce_process_product_meta`: Save data.
- `admin_enqueue_scripts`: Enqueue scripts/styles.

---

### 2. Frontend Display

**Location**: Product Page (before Add to Cart button)

**Features**:
- **Type-wise Display**:
  - Each add-on group shown separately:
    - Checkbox: Multiple selections (e.g., Cold Drink, Veggies).
    - Radio: Single selection or “None” (e.g., Sauce).
    - Dropdown: Single selection or “None” (e.g., Fries).
  - Example UI:
    ```
    Extra Items (Type 1)
    [ ] Cold Drink (+₹2.00)
    [ ] Veggies (+₹3.00)
    
    Extra Items (Type 2)
    ( ) None
    ( ) Sauce (+₹1.00)
    
    Total Price: ₹50.00
    ```
- **Dynamic Total Price**:
  - Updates on:
    - Add-on selection/unselection.
    - Quantity change (e.g., 2 → Total: 2 × (Base + Add-ons)).
  - Formula: `(Base Price + Add-Ons Total) × Quantity`.
- **Form Data**:
  - Add-ons sent as `addon_groups` array:
    ```php
    addon_groups[0][addons][0][selected] = 1 // Cold Drink
    addon_groups[1][selected] = 0 // Sauce
    ```

**Files Involved**:
- `frontend/frontend.php`: Renders add-ons, handles cart/orders.
- `frontend/js/frontend.js`: Total price calculation.
- `frontend/css/frontend.css`: Styling.

**Hooks Used**:
- `woocommerce_before_add_to_cart_button`: Display add-ons.
- `wp_enqueue_scripts`: Enqueue scripts/styles.

---

### 3. Cart Integration

**Features**:
- Add-ons saved to cart as `addon_groups` in `$cart_item_data`.
- Displayed in cart:
  - Format: “Name (Type X): ₹Y” (e.g., Cold Drink (Type 1): ₹2).
- Price calculation:
  - Total = (Base Price + Add-Ons Total) × Quantity.
  - Handled by WooCommerce core for quantity.

**Files Involved**:
- `frontend/frontend.php`: Cart logic.

**Hooks Used**:
- `woocommerce_add_cart_item_data`: Save add-ons.
- `woocommerce_get_item_data`: Display add-ons in cart.
- `woocommerce_before_calculate_totals`: Update cart price.

---

### 4. Checkout and Orders

**Features**:
- Add-ons carried to checkout and saved in order.
- Displayed in:
  - Checkout page (same as cart).
  - Order details (frontend: My Account, admin: Orders).
  - Format: “Name (Type X): ₹Y”.
- Price remains consistent (no double-counting).

**Files Involved**:
- `frontend/frontend.php`: Order logic.

**Hooks Used**:
- `woocommerce_checkout_create_order_line_item`: Save add-ons to order.
- `woocommerce_order_item_name`: Display in order details.
- `woocommerce_admin_order_item_headers/values`: Admin order table.

---

## How It Works (Flow)

1. **Admin Setup**:
   - Go to Products > Edit Product > Add-Ons.
   - Add types (e.g., Checkbox, Radio).
   - Add add-ons (e.g., Cold Drink ₹2, Sauce ₹1).
   - Save product → Stored in `_product_addon_groups`.

2. **Frontend**:
   - Product page loads add-ons from `_product_addon_groups`.
   - User selects add-ons → Total price updates (JavaScript).
   - Quantity changes → Total recalculates.
   - Add to Cart → `addon_groups` sent to cart.

3. **Cart**:
   - Add-ons shown with type and price.
   - Price calculated: (Base + Add-ons) × Quantity.

4. **Checkout**:
   - Add-ons carried over, displayed, and saved to order.
   - Order total matches cart.

5. **Orders**:
   - Admin and customer see add-ons in order details.


---