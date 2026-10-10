# Product Requirements Document (PRD): Inventory Module - Product Management

## 1. Feature Overview
A high-throughput Product & Category Management interface following the two-layer architecture:
- **Layer 1 (Background):** Grouped Master List with category bands, quick search, item counts, price ranges, and drawer triggers.
- **Layer 2 (Foreground):** Slide-out Detail Drawer featuring multi-tab views (General Info, Specifications, Branches & Stock Sub-table, Pricing), fluid input stacks, and fixed sticky footer controls.

## 2. Gherkin Acceptance Criteria

### Scenario 1: Grouped Master Table Display
```gherkin
Scenario: View products grouped by category
  Given the user navigates to "/inventory/product-categories"
  Then the master table displays categorized group headers (e.g. "Beverages", "Main Course", "Pastries & Desserts")
  And each group header displays the category name, item count badge, and spans all 6 columns
  And rows under each group display SKU, Name, Barcode, Unit, Cost Price, Selling Price, and a "View" action
```

### Scenario 2: Opening the Slide-out Detail Drawer
```gherkin
Scenario: Clicking "View" or "+ Add Product" opens the foreground drawer
  Given the user is on the Product Management page
  When the user clicks the "View" button on an existing product row
  Then the right-hand slide-out drawer animates smoothly into view (35-40% width)
  And the drawer header displays the product title and a close (✕) button
  And the form inputs populate with the product's details
  And the master list background is partially visible behind a translucent overlay
```

### Scenario 3: Switching Sub-Tabs and Viewing Nested Data Table
```gherkin
Scenario: Inspecting branch stock levels inside drawer
  Given the slide-out drawer is open for a product
  When the user navigates to the nested content section's secondary tabs
  And clicks "Branch Stock Levels"
  Then a nested sub-table renders showing Branch Name, On Hand, Reserved, Reorder Point, and Stock Status
```

### Scenario 4: Fast Keyboard Ergonomics
```gherkin
Scenario: Keyboard accessibility
  Given the Product Management page is open
  When the user presses "F2"
  Then the drawer opens in "Add New Product" mode with the SKU / Name input automatically focused
  When the user presses "Escape"
  Then the drawer closes smoothly and focus returns to the search input
```
