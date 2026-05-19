# E-Commerce Cart & ABC Document System

## New Features Added

### 1. Shopping Cart System
- **Add to Cart Button**: Click the `+` button on the rightmost column of each item in the catalog
- **Cart Management**: View, update quantities, and remove items from the cart
- **Session-based Storage**: Cart persists during your session
- **Organized by Category**: Items in cart are grouped by category for easy review

### 2. Item Variations
Different variations available based on item type:
- **Art Materials**: Size/type variations (Standard, Premium, Bulk)
- **Office Supplies**: Standard and Premium options
- Custom variations can be added based on category

### 3. ABC Classification
Automatic classification of cart items based on cost:
- **A (Always)**: High-priority items (top 80% of cost)
- **B (Better)**: Medium-priority items (next 15% of cost)  
- **C (Critical)**: Low-priority items (remaining 5%)

Visible in both the cart view and ABC document generator.

### 4. ABC Document Generator
Generate procurement documents with multiple templates:
- **ABC 2**: Standard procurement (Item, Quantity, Cost)
- **ABC 3**: Detailed procurement (Includes Brand & Model)
- **ABC 4**: Advanced with classification breakdown

Features:
- Document title customization
- Cart summary before generation
- Items table with all details
- ABC classification breakdown
- Category breakdown analysis

## File Structure

### New Files Created:
```
src/php/
  ├── cart.php              # Cart management functions
  ├── components.php        # Reusable header/footer components
  ├── cart_page.php         # Cart view and management page
  ├── abc_generator.php     # ABC document generation page
  
static/
  └── style.css             # Updated with new component styles
```

### Updated Files:
```
src/php/
  ├── index.php             # Added + button and modal for adding items
  └── dashboard.php         # Updated to use uniform header/footer
```

## How to Use

### Adding Items to Cart:
1. Navigate to the Catalog page
2. Click the `+` button on any item in the rightmost column
3. A modal will appear showing item details
4. Select a variation if available (or use default)
5. Enter quantity
6. Click "Add to Cart"

### Viewing Cart:
1. Click the `🛒 Cart` link in the header
2. View all items organized by category
3. Update quantities directly or remove items
4. See ABC classification breakdown
5. View cart summary with totals

### Generating ABC Documents:
1. Add items to cart
2. Click the `📊 ABC Documents` link in the header
3. Fill in document title and select document type
4. Review preview showing:
   - Items summary table
   - ABC classification breakdown
   - Category breakdown
5. Click "Generate Document" to create the document

## UI/UX Features

### Uniform Design:
- **Consistent Header**: All pages use the same header with navigation
- **Consistent Footer**: All pages include uniform footer
- **Color Scheme**: Green theme (#12825c primary color)
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Glass Morphism**: Modern frosted glass effect on containers

### Navigation:
- **Cart Badge**: Shows number of items in cart (when > 0)
- **Quick Links**: Easy access to Catalog, Cart, ABC Documents, Dashboard
- **Breadcrumb Actions**: Back buttons on all pages

### Responsive Tables:
- Horizontal scrolling on small screens
- Mobile-optimized layout
- Touch-friendly buttons

## CSS Variables
All pages use consistent CSS variables:
- `--jungle-green`: #12825c (Primary color)
- `--medium-jungle`: #1f9d6c (Secondary color)
- `--dark-emerald`: #0f5c36 (Dark variant)
- `--text`: #152e23 (Text color)
- `--bg`: #eff6f1 (Background)
- `--surface`: rgba(255, 255, 255, 0.95) (Container)

## Cart Data Structure
Items stored in `$_SESSION['cart']`:
```php
[
    'unique_key' => [
        'item' => [...item data...],
        'variation' => [...variation data...] or null,
        'quantity' => 5,
        'added_at' => '2026-05-19 14:30:00'
    ]
]
```

## ABC Classification Algorithm
1. Calculate total cost for each unique item
2. Sort items by total cost (descending)
3. Classify into A, B, C based on cumulative cost:
   - A: First items up to 80% of total
   - B: Next items up to 95% of total
   - C: Remaining items

## Features Working Together

### Complete Workflow:
1. User browses items in Catalog
2. User clicks `+` to add items with desired variations and quantities
3. User navigates to Cart to review selections
4. System automatically classifies items as A, B, or C
5. User can remove items or update quantities
6. User generates ABC document with classifications
7. Document includes category breakdown and cost analysis

## Browser Compatibility
- Chrome/Edge (Latest)
- Firefox (Latest)
- Safari (Latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Notes
- Cart data is session-based (cleared when browser closes)
- Item variations are predefined by category
- ABC classification is automatic and real-time
- All pages maintain consistent styling and navigation
- Responsive design adapts to all screen sizes
