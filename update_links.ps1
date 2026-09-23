$lines = Get-Content index.html
$links = @{
    "HR Dashboard" = "pages/hr-dashboard.html"
    "Employee<" = "pages/employee.html"
    "Users & Authentication" = "pages/users-auth.html"
    "Attendance Schedule" = "pages/attendance-schedule.html"
    "Attendance Check IN /OUT" = "pages/attendance-checkin.html"
    "Employee Leave / Time Request" = "pages/employee-leave.html"
    "Sales Dashboard" = "pages/sales-dashboard.html"
    "Daily Sales Report" = "pages/daily-sales.html"
    "Payment Report" = "pages/payment-report.html"
    "Reconciliations" = "pages/reconciliations.html"
    "Discount Configuration" = "pages/discount-config.html"
    "Voucher Configuration" = "pages/voucher-config.html"
    "Bundle Promotions" = "pages/bundle-promotions.html"
    "Customer Masterlist" = "pages/customer-masterlist.html"
    "Inventory Dashboard" = "pages/inventory-dashboard.html"
    "Stocks Overview" = "pages/stocks-overview.html"
    "Beg Balance" = "pages/beg-balance.html"
    "Stock In / Receiving" = "pages/stock-in.html"
    "Stock Out / Usage" = "pages/stock-out.html"
    "Stock Adjustment" = "pages/stock-adjustment.html"
    "Waste & Expiry" = "pages/waste-expiry.html"
    "Product / Categories" = "pages/product-categories.html"
    "Recipe / Menu Management" = "pages/recipe-management.html"
    "Purchase Dashboard" = "pages/purchase-dashboard.html"
    "Request for Quotations" = "pages/request-quotations.html"
    "Purchase Orders" = "pages/purchase-orders.html"
    "Vendor Masterlist" = "pages/vendor-masterlist.html"
    "Vendor Bills" = "pages/vendor-bills.html"
    "Business Settings" = "pages/business-settings.html"
    "Account Settings" = "pages/account-settings.html"
    "Tickets / Help Desk" = "pages/tickets.html"
    "Developers" = "pages/developers.html"
}

for ($i = 0; $i -lt $lines.Count; $i++) {
    foreach ($key in $links.Keys) {
        if ($lines[$i] -match "<span>$key") {
            for ($j = $i; $j -ge $i-2; $j--) {
                if ($lines[$j] -match 'href="#"') {
                    $lines[$j] = $lines[$j] -replace 'href="#"', ("href="" + $links[$key] + """)
                    break
                }
            }
        }
    }
}
$lines | Set-Content index.html
