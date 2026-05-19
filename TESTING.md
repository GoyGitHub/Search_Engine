# Testing Guide: E-Commerce Cart & ABC System

## Pre-Testing Checklist
- [ ] PHP server running (XAMPP or equivalent)
- [ ] Database initialized
- [ ] Excel items loaded (via upload page)
- [ ] Logged in as a user
- [ ] JavaScript enabled in browser

## Test Cases

### 1. Add to Cart Functionality
**Test**: Adding an item to cart

Steps:
1. Navigate to Catalog (index.php)
2. Locate any item in the table
3. Click the + button in the Action column
4. Verify modal appears with:
   - Item name
   - Category
   - Brand
   - Unit
   - Price
5. If variations available, select one
6. Enter quantity (try 1, 5, 10)
7. Click "Add to Cart"
8. Verify success message appears
9. Check cart badge in header shows count

Expected Results:
- Modal displays correctly
- Item added to cart
- Cart badge updates
- Success notification shown

---

### 2. Variations Selection
**Test**: Item variations working correctly

Steps:
1. Add item with multiple variations (e.g., NOTEBOOK with A5/A4/A3)
2. Open modal for that item
3. Verify variations dropdown appears
4. Select different variation
5. Add to cart multiple times with different variations
6. Go to cart page
7. Verify items show correct variations

Expected Results:
- Different variations shown as separate cart items
- Variations clearly labeled in cart
- Can add same item with different variations

---

### 3. Cart Management
**Test**: Cart operations (view, update, remove)

Steps:
1. Add 3-5 items to cart
2. Go to Cart page (click Cart in header)
3. Verify items grouped by category
4. Update quantity on one item (click field, change number)
5. Verify update applied immediately
6. Remove one item (click Remove button)
7. Verify item disappears
8. Clear cart (click Clear Cart button, confirm)
9. Verify cart empty

Expected Results:
- Items properly grouped by category
- Quantity updates apply immediately
- Item removal works without page reload
- Clear cart button confirms before clearing
- Empty state shows when cart cleared

---

### 4. ABC Classification
**Test**: ABC classification accuracy

Steps:
1. Add items with varying costs to cart
2. Go to Cart page
3. Scroll to ABC Classification section
4. Verify three cards (A, B, C) displayed
5. Check item count in each category
6. Verify costs are correctly classified
   - A: Should have highest-cost items
   - B: Should have medium-cost items
   - C: Should have lowest-cost items
7. Verify percentages match:
   - A ≈ 80% of total
   - B ≈ 15% of total
   - C ≈ 5% of total

Expected Results:
- ABC cards display correctly
- Items properly classified by cost
- Totals per category accurate
- Visual distinction between categories clear

---

### 5. ABC Document Generation
**Test**: Generate ABC documents

Steps:
1. Add items to cart
2. Go to Cart page
3. Click "Generate ABC Document" button
4. Verify ABC Generator page loads
5. Check document preview shows:
   - Items summary table
   - ABC classification
   - Category breakdown
6. Modify document title
7. Select different document type (ABC 2, ABC 3, ABC 4)
8. Click "Generate Document"
9. Verify success message

Expected Results:
- Generator page loads correctly
- Preview displays all information
- Document type selector works
- Title field editable
- Generation completes successfully

---

### 6. Navigation & Header
**Test**: Uniform header and navigation

Steps:
1. Visit each page:
   - Catalog (index.php)
   - Cart (cart_page.php)
   - ABC Generator (abc_generator.php)
   - Dashboard (dashboard.php)
2. Verify header appears on all pages with:
   - Company logo
   - Page title
   - Navigation links
   - Cart badge (with count)
   - User info
   - Logout link
3. Click nav links to switch pages
4. Verify footer appears on all pages

Expected Results:
- Header consistent across pages
- Navigation works smoothly
- Cart badge updates on all pages
- Footer displays on all pages
- Links navigate correctly

---

### 7. Modal Interactions
**Test**: Modal functionality

Steps:
1. Open Add to Cart modal
2. Try closing with X button
3. Open modal again
4. Try closing with Cancel button
5. Open modal again
6. Try closing by clicking outside modal
7. Verify modal disappears in all cases
8. Verify page content unchanged after closing

Expected Results:
- X button closes modal
- Cancel button closes modal
- Clicking overlay closes modal
- Modal animates in and out smoothly
- Page content not affected

---

### 8. Responsive Design
**Test**: Mobile and tablet views

Desktop:
1. Resize browser to 1024px+
2. Verify full layout displays
3. Verify all columns visible in tables

Tablet:
1. Resize to 768px-1023px
2. Verify layout adjusts
3. Verify 2-column layouts adapt

Mobile:
1. Resize to <768px
2. Verify single column layout
3. Verify buttons stack vertically
4. Test touch interactions
5. Verify header collapse behavior

Expected Results:
- Layouts adapt at breakpoints
- Content readable at all sizes
- Navigation accessible on mobile
- Tables scrollable on small screens
- Touch interactions work smoothly

---

### 9. Form Validation
**Test**: Form inputs and validation

Steps:
1. Try submitting modal without quantity
2. Try entering 0 or negative quantity
3. Try leaving variation empty when required
4. Try very large quantity numbers
5. Generate document without items in cart

Expected Results:
- Form prevents invalid submissions
- Error messages display
- Cart cannot be empty for generation
- Quantities must be positive

---

### 10. Data Persistence
**Test**: Cart data across pages

Steps:
1. Add items to cart
2. Navigate away and back
3. Go to different page and return
4. Verify cart items still present
5. Refresh page
6. Verify cart items still present

Expected Results:
- Cart data persists during session
- Data survives page navigation
- Data survives page refresh

---

## Browser Testing
Test on:
- [ ] Chrome/Edge (Latest)
- [ ] Firefox (Latest)
- [ ] Safari (Latest)
- [ ] Mobile Chrome (iOS/Android)
- [ ] Mobile Safari (iOS)

---

## Performance Testing
Check:
- [ ] Page load time (<2 seconds)
- [ ] Modal response time (<100ms)
- [ ] Cart operations smooth
- [ ] No console errors
- [ ] No memory leaks on prolonged use

---

## Common Issues & Solutions

### Issue: Cart badge not updating
- **Solution**: Check JavaScript enabled, clear cache

### Issue: Modal not appearing
- **Solution**: Check CSS display property, verify JavaScript loaded

### Issue: Classification incorrect
- **Solution**: Verify cost values, check calculation logic

### Issue: Variations not showing
- **Solution**: Check item category matches variations definition

### Issue: Cart clearing unexpectedly
- **Solution**: Check for cache issues, verify session settings

---

## Test Data
Use provided items:
- Art Materials (multiple categories)
- Various brands and costs
- Different unit types

Sample cart:
- 5x NOTEBOOK (A4)
- 10x MARKERS (Standard)
- 3x CRAFT PAPER (Medium)
- 2x CRAYONS (Premium)

Expected totals:
- Total items: 4
- Total quantity: 20
- Total cost: Calculated from items.json

---

## Sign-off
- [ ] All tests passed
- [ ] No console errors
- [ ] Responsive design confirmed
- [ ] Cross-browser tested
- [ ] Ready for production

Tester: _______________  
Date: __________________  
Notes: __________________
