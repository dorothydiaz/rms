# Universal UOM Dropdown Component Standard

## Directive
In all present and future modules of the RMS ERP application (including Item Master, RFQ, Purchase Orders, BOM / Recipes, Stock Movements, Vendor Catalogs, Waste Tracking, and Configuration), **ALL Unit of Measure (UoM) dropdowns MUST strictly implement the Custom UOM Dropdown Component**. Agents are strictly prohibited from generating raw, unstyled native HTML `<select>` elements for UOM selection.

---

## 1. Required Visual & Functional Capabilities
Every UOM selection interface must provide:
1. **Prominent UOM Acronym (Short Code)**:
   - Rendered as a distinct monospace pill badge (e.g., `PCS`, `KG`, `PORT`, `BOX`, `L`).
   - Tinted with the standardized category color accent.
2. **Full Descriptive Name**:
   - Displays the human-readable unit title (e.g., `Kilogram (Kg)`, `Piece / Each (Single Unit)`, `Portion (Prepped Kitchen Yield)`).
   - Shows multiplier or conversion formula reference (e.g., `= 10 PCS`, `0.001 KG`, `BASE`).
3. **Categorized Sectioning**:
   - Options must be grouped under sticky section headers corresponding to the 8 official ERP categories:
     - `COUNT` (`ph-package` / Blue)
     - `WEIGHT` (`ph-scales` / Emerald)
     - `VOLUME` (`ph-drop` / Sky)
     - `PORTION` (`ph-fork-knife` / Amber)
     - `LENGTH` (`ph-ruler` / Violet)
     - `AREA` (`ph-bounding-box` / Teal)
     - `TIME` (`ph-clock` / Slate)
     - `OVERHEAD` (`ph-lightning` / Purple)
4. **Fuzzy Search Bar (Client-Side)**:
   - Instant search input with quick clear (`×`) button.
   - Typo-tolerant fuzzy matching (Levenshtein distance <= 2, acronym prefix matching, and substring search across code, description, and category).
   - Highlights matching substrings within the option titles.
5. **Quick Category Filter Chips**:
   - Horizontal chip bar (`ALL`, `COUNT`, `WEIGHT`, `VOLUME`, `PORTION`, `LENGTH`, `AREA`, `TIME`, `OVERHEAD`) for 1-click category scoping.
6. **Ergonomic Keyboard Navigation**:
   - `Tab` focuses trigger.
   - `Enter` / `Space` / `ArrowDown` opens menu with auto-focused search.
   - `ArrowUp` / `ArrowDown` navigates options; `Enter` selects; `Escape` dismisses.

---

## 2. Implementation Methods

### Method A: Blade Component (Preferred in Forms & Modals)
```blade
<x-uom-dropdown 
    name="uom_code" 
    id="productUom" 
    :value="$item->uom_code ?? 'PCS'" 
    category="ALL" 
    placeholder="Select Unit of Measure" 
    required />
```

### Method B: Declarative Table Line Item / Dynamic JavaScript Rows
For dynamic line items generated inside table rows (e.g., RFQ lines, PO lines, Recipe ingredients):
```html
<select name="items[][uom_code]" 
        class="form-control" 
        data-uom-select 
        data-uom-category="WEIGHT" 
        data-uom-current="KG">
</select>
```
*Note: Any element with `data-uom-select` is automatically intercepted and hydrated into the custom dropdown by `window.UomRegistry.autoHydrate()`.*

### Method C: Programmatic JavaScript Instantiation
```javascript
// Transform any container or select element on the fly:
const dd = UomRegistry.createCustomDropdown('#myCustomContainer', {
    value: 'KG',
    category: 'WEIGHT',
    placeholder: 'Select Weight Unit...',
    onChange: (code, uom) => {
        console.log('Selected UOM:', code, uom);
    }
});
```

---

## 3. Assets Included Globally in `app.blade.php`
- Stylesheet: `<link rel="stylesheet" href="{{ asset('assets/css/uom-dropdown.css') }}">`
- Engine: `<script src="{{ asset('assets/js/uom-registry.js') }}"></script>`
- Blade Component: `<x-uom-dropdown>` located at `resources/views/components/uom-dropdown.blade.php`.
