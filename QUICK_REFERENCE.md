# Quick Reference Guide

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    USER INTERFACE                        │
│  ┌──────────────┬────────────┬──────────────────────┐   │
│  │   Header     │ Navigation │    Cart Badge        │   │
│  │  (Uniform)   │   (Links)  │   (Item Count)       │   │
│  └──────────────┴────────────┴──────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
   ┌─────────┐        ┌──────────┐       ┌─────────────┐
   │ Catalog │        │  Cart    │       │ ABC Generator
   │ (index) │        │ (cart_pg)│       │ (abc_gen)
   │         │        │          │       │
   │ + Button│────┐   │ Qty      │       │ - Select Type
   │ Modal   │    │   │ Update   │       │ - Title
   │         │    │   │ Remove   │       │ - Generate
   └─────────┘    │   │ Summary  │       │
                  │   └──────────┘       │
                  │         │            │
                  └────┬────┴────────────┘
                       │
            ┌──────────▼──────────┐
            │   SESSION CART      │
            │  $_SESSION['cart']  │
            │                     │
            │ - Items             │
            │ - Quantities        │
            │ - Variations        │
            └─────────┬───────────┘
                      │
            ┌─────────▼──────────┐
            │ CART FUNCTIONS     │
            │  (cart.php)        │
            │                    │
            │ - add_to_cart()    │
            │ - remove_from()    │
            │ - get_cart()       │
            │ - get_stats()      │
            │ - get_abc()        │
            └────────────────────┘
                      │
        ┌─────────────┼─────────────┐
        │             │             │
   ┌────────┐  ┌───────────┐  ┌─────────┐
   │ Items  │  │Variations │  │  ABC    │
   │        │  │           │  │ Classes │
   └────────┘  └───────────┘  └─────────┘
```

## File Dependencies

```
index.php
├── auth.php (authentication)
├── cart.php (cart functions)
├── components.php (header/footer)
└── style.css (styling)

cart_page.php
├── auth.php
├── cart.php
├── components.php
└── style.css

abc_generator.php
├── auth.php
├── cart.php
├── components.php
└── style.css

dashboard.php
├── auth.php
├── components.php
└── style.css

components.php
└── cart.php (for badge count)
```

## Data Flow

### Adding Item to Cart
```
User clicks + button
        ↓
Modal shows item details + variations
        ↓
User selects variation + quantity
        ↓
Form submits via POST
        ↓
add_to_cart() processes
        ↓
Item stored in $_SESSION['cart']
        ↓
Redirect to index.php with success message
        ↓
Cart badge updates
```

### Viewing Cart
```
User clicks Cart in header
        ↓
cart_page.php loads
        ↓
get_cart() retrieves items
        ↓
Items grouped by category
        ↓
get_abc_classification() runs
        ↓
ABC cards populated
        ↓
get_cart_stats() calculates totals
        ↓
Summary displayed
```

### Generating ABC Document
```
User clicks "Generate ABC Document"
        ↓
abc_generator.php loads with preview
        ↓
User fills form (title, type)
        ↓
Clicks "Generate Document"
        ↓
generate_abc_document() creates document data
        ↓
Document info displayed/stored
        ↓
Success message shown
```

## Key Functions Reference

### cart.php Functions

```php
// Initialize cart
init_cart()

// Add item with variation
add_to_cart($item, $quantity, $variation)

// Remove from cart
remove_from_cart($item_key)

// Update quantity
update_cart_quantity($item_key, $quantity)

// Get cart
get_cart() → array

// Clear cart
clear_cart()

// Create unique key for item
create_cart_item_key($item, $variation) → string

// Get cart statistics
get_cart_stats() → [
    'total_items' => int,
    'total_cost' => float,
    'categories' => array,
    'item_count' => int
]

// Get variations for item
get_item_variations($item) → array

// Get ABC classification
get_abc_classification($items) → [
    'A' => [...],
    'B' => [...],
    'C' => [...]
]
```

### components.php Functions

```php
// Render page header
render_header($BASE_URL, $currentUser, $pageTitle)

// Render page footer
render_footer($BASE_URL)
```

## CSS Classes

### Layout Classes
```css
.container          /* Main content wrapper */
.header-actions     /* Header navigation area */
.page-enter         /* Page animation */
.glass              /* Frosted glass effect */
```

### Cart Classes
```css
.cart-container     /* Cart page main wrapper */
.cart-section       /* Section in cart page */
.cart-item          /* Individual cart item */
.category-group     /* Category grouping */
.abc-grid           /* ABC classification grid */
.abc-card           /* Individual ABC card */
```

### Button Classes
```css
.button             /* Default button */
.button.primary     /* Primary action */
.button.secondary   /* Secondary action */
.button.danger      /* Destructive action */
.button.ghost       /* Transparent button */
.button.sm          /* Small button */
.button.icon-btn    /* Button with icon */
```

### Modal Classes
```css
.modal              /* Modal container */
.modal-content      /* Modal content box */
.modal-close        /* Close button */
.modal-actions      /* Action buttons */
```

## Color Palette

```
Primary Green:        #12825c
Secondary Green:      #1f9d6c
Dark Emerald:         #0f5c36
Black Forest:         #0b3924
Text Color:           #152e23
Light Background:     #eff6f1
White Surface:        rgba(255, 255, 255, 0.95)

ABC Colors:
A (Green):            #10b981
B (Orange):           #f59e0b
C (Purple):           #8b5cf6
```

## Form Inputs

```php
// Modal form
<form id="addToCartForm" method="POST">
    <input type="hidden" name="add_to_cart" value="1">
    <input type="hidden" name="item_json" id="modalItemJson">
    <input type="hidden" name="variation_json" id="modalVariationJson">
    <select id="variationSelect"></select>
    <input type="number" id="quantityInput" name="quantity" min="1">
</form>

// Cart update form
<form method="POST">
    <input type="hidden" name="action" value="update_quantity">
    <input type="hidden" name="item_key">
    <input type="number" name="quantity" min="1" onchange="submit()">
</form>

// ABC Generator form
<form method="POST">
    <input type="text" name="doc_title">
    <select name="abc_type">
        <option value="ABC_2">ABC 2 - Standard</option>
        <option value="ABC_3">ABC 3 - Detailed</option>
        <option value="ABC_4">ABC 4 - Advanced</option>
    </select>
    <button type="submit" name="generate" value="1">Generate</button>
</form>
```

## Session Data Structure

```php
$_SESSION['cart'] = [
    'md5hash1' => [
        'item' => [
            'item_name' => 'NOTEBOOK',
            'category' => 'Art Materials',
            'brand' => 'Unknown',
            'model' => '',
            'unit' => 'PCS',
            'unit_cost' => '15.0',
            'specs' => ['qty' => '50', 'total_cost' => '750']
        ],
        'variation' => [
            'name' => 'A4 (Medium)',
            'type' => 'medium'
        ],
        'quantity' => 5,
        'added_at' => '2026-05-19 14:30:00'
    ]
]
```

## JavaScript Hooks

```javascript
// Modal interaction
document.getElementById('addToCartModal')     // Modal element
document.querySelectorAll('.add-to-cart-btn') // Add buttons
document.getElementById('addToCartForm')      // Form element

// Element IDs in modal
#modalItemInfo          // Item details display
#modalItemJson          // Item JSON hidden input
#modalVariationJson     // Variation JSON hidden input
#variationSelect        // Variation dropdown
#quantityInput          // Quantity input
#variationsContainer    // Variations section visibility
```

## URL Routes

```
Catalog:        /src/php/index.php
Cart:           /src/php/cart_page.php
ABC Generator:  /src/php/abc_generator.php
Dashboard:      /src/php/dashboard.php
Login:          /src/php/login.php
Logout:         /src/php/logout.php
```

## Quick Troubleshooting

| Issue | Check |
|-------|-------|
| Modal not showing | CSS `display: none`, JavaScript loaded |
| Cart not saving | Session started, no PHP errors |
| Variations empty | Item category matches defined variations |
| ABC wrong | Item cost values, calculation logic |
| Header missing | components.php included, render_header() called |
| Footer missing | components.php included, render_footer() called |
| Styles missing | style.css loaded, CSS variables defined |
| Badge not updating | Page refresh needed, JavaScript enabled |

## Common Code Snippets

```php
// Get all functions
require_once __DIR__ . '/cart.php';

// Include components
require_once __DIR__ . '/components.php';

// Setup header
$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
$currentUser = current_user();

// Render header
render_header($BASE_URL, $currentUser, "Page Title");

// Get cart data
$cart = get_cart();
$stats = get_cart_stats();
$abc = get_abc_classification($cart);

// Render footer
render_footer($BASE_URL);
```

## Deployment Checklist

- [ ] PHP 7.4+ installed
- [ ] MySQL database created
- [ ] Items loaded from Excel
- [ ] Session handling configured
- [ ] File permissions set (readable/writable)
- [ ] CSS file linked correctly
- [ ] JavaScript enabled
- [ ] Images/logo accessible
- [ ] Mobile tested
- [ ] All links working
- [ ] Error handling verified
