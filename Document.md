# ERP V3 Upgrade, Refactoring & Development Specification

## Project Overview

This document defines the complete ERP V3 architecture upgrade, database refactoring, codebase restructuring, and development standards based on the existing ERP system.

The objective is not only to upgrade the database architecture but also to standardize the entire application layer including:

* Database Structure
* Models
* Controllers
* Services
* Helpers
* Views (UI Screens)
* Validation Logic
* Business Rules
* Accounting Engine
* Inventory Engine
* Security Layer
* Logging & Auditing

The goal is to transform the current system into a production-grade ERP capable of handling:

* 100,000+ Customers
* 50,000+ Products
* Millions of Accounting Transactions
* Multi Warehouse Operations
* Multi Currency Accounting
* Batch & Expiry Tracking
* Advanced Sales & Purchase Workflows
* Mixed Payment Methods
* Installments
* Checks Management
* Audit Logging
* Approval Workflows

---

# Development & Refactoring Principles

## Mandatory Refactoring Rules

All future development and modifications must follow the same architecture and coding standards.

### Required Actions

* Review every Controller.
* Review every Model.
* Review every View.
* Review every Route.
* Review every Middleware.
* Review every Service Class.
* Review every Helper Function.
* Review every JavaScript Module.

---

## Remove Unused Code

One of the highest priorities of this upgrade is code cleanup.

The following must be removed:

* Unused Controllers
* Unused Models
* Unused Views
* Unused Routes
* Unused Helpers
* Unused JavaScript Files
* Unused CSS Files
* Deprecated Functions
* Duplicate Logic
* Dead Code
* Legacy Features No Longer Used

Purpose:

* Improve Performance
* Reduce Maintenance Cost
* Improve Security
* Simplify Future Development

---

## Controller Refactoring Standards

Every Controller must be reviewed and simplified.

Controllers should only handle:

```text
Request Validation
Authorization
Calling Services
Returning Responses
```

Controllers must NOT contain:

```text
Accounting Logic
Inventory Logic
Posting Logic
Calculation Logic
Complex Queries
```

These responsibilities must be moved into dedicated Service Classes.

---

## Service Layer Architecture

All business logic must be centralized inside Services.

Examples:

```text
AccountingService
JournalPostingService
InventoryService
SalesService
PurchaseService
PaymentService
InstallmentService
CheckManagementService
ApprovalService
```

Benefits:

* Reusable Logic
* Easier Testing
* Cleaner Controllers
* Better Maintainability

---

## Helper Architecture

Reusable functions must be extracted into Helper Classes.

Examples:

```text
CurrencyHelper
TaxHelper
InventoryHelper
AccountingHelper
DocumentNumberHelper
DateHelper
PermissionHelper
```

Purpose:

* Eliminate Duplicate Code
* Standardize Calculations
* Improve Maintainability

---

## View Refactoring Standards

All Views must be reviewed and standardized.

Requirements:

* Unified Layout Structure
* Reusable Components
* Shared Form Components
* Shared Table Components
* Shared Filters
* Shared Modals
* Shared Validation Messages

Examples:

```text
components/forms
components/tables
components/modals
components/filters
components/buttons
```

Benefits:

* Faster Development
* Consistent UI
* Easier Maintenance

---

## UI/UX Review

Every screen must be reviewed for:

```text
Performance
Usability
Consistency
Responsiveness
Accessibility
```

Required Improvements:

* Reduce Duplicate Screens
* Standardize Actions
* Improve Navigation
* Improve Search Experience
* Improve Filtering Experience

---

## Validation Standardization

Validation rules must be centralized.

Avoid:

```text
Repeated Validation Logic
```

Use:

```text
Form Requests
Validation Services
Shared Validation Rules
```

---

## Query Optimization

All database queries must be reviewed.

Requirements:

```text
Remove N+1 Queries
Use Eager Loading
Add Proper Indexes
Optimize Reports
Optimize Dashboard Queries
```

---

## Logging & Monitoring

Every critical operation must be logged.

Examples:

```text
Create
Update
Delete
Approve
Reject
Post
Reverse
Login
Logout
```

---

# Architectural Decisions

## Company Structure

### Removed

```text
companies
company_users
```

Reason:

The system will operate as a Single Company ERP.

No multi-company support is required.

---

# Accounting Architecture

## Keep

```text
account_trees
trans_account_trees
opening_balances
```

## Modify

### account_trees

Add:

```text
is_system
allow_manual_entries
is_active
```

Purpose:

* Protect system accounts
* Prevent manual posting where necessary
* Allow account activation/deactivation

---

### trans_account_trees

This table becomes:

```text
General Ledger
```

Single Source of Truth.

Add:

```text
journal_entry_id
customer_id
supplier_id
cost_center_id

currency_id
exchange_rate

foreign_debit
foreign_credit

transaction_date
```

Purpose:

Support:

* Customer Ledger
* Supplier Ledger
* Multi Currency
* Cost Centers
* Journal Tracking

---

## New Tables

### journal_entries

Stores Journal Entry Headers.

Example:

```text
JV-2026-000001
```

---

### fiscal_years

Stores fiscal years.

---

### accounting_periods

Stores accounting months.

Allows month locking.

---

### cost_centers

Supports cost allocation.

---

### accounting_settings

Stores default accounting accounts.

Example:

```text
Default Customer Account
Default Supplier Account
Default Inventory Account
Default Tax Account
Default Sales Account
Default Purchase Account
```

---

### journal_reversals

Used instead of deleting journal entries.

---

### currency_revaluation_entries

Handles unrealized gains/losses from exchange rate changes.

---

# Currency Management

## New Tables

### currencies

Stores supported currencies.

Examples:

```text
EGP
USD
SAR
AED
```

---

### exchange_rates

Stores daily exchange rates.

---

## Multi Currency Strategy

Every financial transaction stores:

```text
Currency
Exchange Rate
Foreign Amount
Base Amount
```

---

# Customer & Supplier Strategy

## Important Decision

Do NOT create separate Chart of Accounts accounts for every customer or supplier.

Instead:

```text
Main Customers Account
Main Suppliers Account
```

Detailed balances are tracked using:

```text
customer_id
supplier_id
```

inside:

```text
trans_account_trees
```

Reason:

Supports large-scale systems without exploding the chart of accounts.

---

# Inventory Architecture

## Keep

```text
warehouses
inventory_transactions
inventory_items
```

---

## New Tables

### inventory_batches

Supports:

* Batch Numbers
* Expiry Dates
* Manufacturing Dates

---

### stock_counts

Inventory Counting Sessions.

---

### stock_count_items

Actual counted quantities.

---

### stock_adjustments

Inventory adjustment documents.

---

### stock_adjustment_items

Adjustment lines.

---

### inventory_transfers

Warehouse-to-Warehouse transfers.

---

### inventory_transfer_items

Transfer details.

---

## Inventory Costing Method

Selected Method:

```text
FIFO
```

---

# Sales Workflow

Supported Flow:

```text
Quotation
    ↓
Sales Order
    ↓
Sales Invoice
```

System also allows:

```text
Quotation → Invoice

Sales Order → Invoice

Invoice Directly
```

---

## New Tables

### quotations

Quotation Header

### quotation_items

Quotation Lines

### sales_orders

Sales Order Header

### sales_order_items

Sales Order Lines

### sales_invoices

Sales Invoice Header

### sales_invoice_items

Sales Invoice Lines

### sales_returns

Sales Return Header

### sales_return_items

Sales Return Lines

---

# Purchase Workflow

Supported Flow:

```text
Purchase Request
     ↓
Purchase Order
     ↓
Purchase Invoice
```

System also allows:

```text
Purchase Order Directly

Purchase Invoice Directly
```

---

## New Tables

### purchase_requests

### purchase_request_items

### purchase_orders

### purchase_order_items

### purchase_invoices

### purchase_invoice_items

### purchase_returns

### purchase_return_items

---

# Tax System

Supported:

```text
VAT
Withholding Tax
Custom Taxes
```

---

## New Tables

### taxes

Tax Definitions

### tax_groups

Tax Groups

### tax_group_items

Tax Group Details

---

# Payment System

Supports:

```text
Cash
Bank
Wallet
Online Payment
Credit
Mixed Payments
```

Example:

```text
Invoice = 1000

Cash = 500
Bank = 300
Credit = 200
```

---

## New Tables

### payment_methods

Defines available payment methods.

---

### payment_gateways

Online payment providers.

---

### payment_transactions

Stores actual payment transactions.

---

# Installment System

## New Tables

### installments

Installment Contract

### installment_schedules

Installment Due Dates

### installment_payments

Installment Collections

---

# Checks Management

Supports:

```text
Incoming Checks
Outgoing Checks
```

---

## New Tables

### checks

Stores check details.

### check_transactions

Tracks check lifecycle.

Statuses:

```text
received
deposited
collected
bounced

issued
cashed
cancelled
```

---

# Security & Access Control

## New Tables

### roles

### permissions

### role_permissions

### admin_roles

---

# Approval Workflow

## New Tables

### approval_flows

### approval_steps

Supports multi-step approvals.

Example:

```text
Purchase Invoice

Step 1:
Purchasing Manager

Step 2:
Finance Manager
```

---

# Audit & Logging

## New Tables

### audit_logs

Stores before/after data.

---

### activity_logs

Stores user activities.

---

### login_logs

Stores login history.

---

### journal_entry_logs

Stores accounting actions.

Example:

```text
Created
Posted
Cancelled
Reversed
```

---

# System Settings

## New Table

### system_settings

Stores ERP behavior configuration.

Examples:

```text
Pricing Includes Tax

Track Expiry

Track Batches

Allow Negative Inventory

FIFO / Average Cost

Auto Post Journal

Allow Installments
```

---

# Document Numbering

## New Table

### document_sequences

Used for:

```text
Invoices
Orders
Quotations
Journal Entries
Checks
Returns
```

Example:

```text
INV-2026-000001
JV-2026-000001
```

---

# Posting Engine

## New Tables

### posting_rules

### posting_rule_lines

Purpose:

Automatic accounting entries generation.

Examples:

```text
Sales Invoice

Debit:
Customer Account

Credit:
Sales Account

Credit:
Tax Account
```

---

# Production Requirements

Mandatory Indexes:

```text
(account_id, transaction_date)

(customer_id, created_at)

(supplier_id, created_at)

(product_id, warehouse_id)

(batch_no)

(expiry_date)

(invoice_no)

(order_no)

(po_no)
```

---

# Final ERP Architecture

```text
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
Approval Workflow
Audit Logs
Full Journal Engine
Service Layer Architecture
Reusable Helpers
Optimized Controllers
Optimized Views
Clean Codebase
Unused Code Removed
Production Scale ERP
```
