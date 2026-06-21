
# ERP V3 FINAL MIGRATION MAP

## KEEP
- account_trees
- trans_account_trees
- customers
- customer_wallets
- suppliers
- supplier_wallets
- products
- product_variants
- product_variant_prices
- warehouses
- orders
- order_items
- purchases
- purchase_items
- banks
- bank_accounts
- bank_transactions
- treasuries
- treasury_transactions
- opening_balances

## MODIFY

### account_trees
+ is_system
+ allow_manual_entries
+ is_active

### trans_account_trees
+ journal_entry_id
+ customer_id
+ supplier_id
+ cost_center_id
+ currency_id
+ exchange_rate
+ foreign_debit
+ foreign_credit
+ transaction_date

### customers
+ credit_limit
+ payment_terms_days
+ preferred_currency_id

### suppliers
+ credit_limit
+ payment_terms_days
+ preferred_currency_id

### bank_accounts
+ account_tree_id
+ currency_id
+ iban
+ swift_code

### treasuries
+ account_tree_id
+ currency_id

### orders / purchases
+ journal_entry_id
+ currency_id
+ exchange_rate

## DROP
- companies
- company_users

## ADD

### Accounting
- journal_entries
- fiscal_years
- accounting_periods
- cost_centers
- accounting_settings
- journal_reversals
- currency_revaluation_entries

### Currency
- currencies
- exchange_rates

### Inventory
- inventory_batches
- stock_counts
- stock_count_items
- stock_adjustments
- stock_adjustment_items
- inventory_transfer_items

### Sales
- quotations
- quotation_items
- sales_orders
- sales_order_items
- sales_invoices
- sales_invoice_items
- sales_returns
- sales_return_items

### Purchases
- purchase_requests
- purchase_request_items
- purchase_orders
- purchase_order_items
- purchase_invoices
- purchase_invoice_items
- purchase_returns
- purchase_return_items

### Payments
- payment_methods
- payment_gateways
- payment_transactions

### Checks
- checks
- check_transactions

### Installments
- installments
- installment_schedules
- installment_payments

### Taxes
- taxes
- tax_groups
- tax_group_items

### Security
- roles
- permissions
- role_permissions
- admin_roles
- audit_logs
- activity_logs
- login_logs
- approval_flows
- approval_steps
- journal_entry_logs

### Settings
- system_settings
- document_sequences
- posting_rules
- posting_rule_lines

## PRODUCTION INDEXES

(account_id, transaction_date)
(customer_id, created_at)
(supplier_id, created_at)
(product_id, warehouse_id)
(batch_no)
(expiry_date)
(invoice_no)
(order_no)
(po_no)

## FINAL RESULT

Target Architecture:
Single Company ERP
Multi Warehouse
Multi Currency
Batch Tracking
Expiry Tracking
FIFO Costing
Mixed Payments
Checks
Installments
VAT + Withholding Tax
Full Journal Engine
Audit Logs
Approval Workflow
