# Cycle ORM — Database Indexing

## Overview

Cycle ORM's `#[Index]` attribute (from `Cycle\Annotated\Annotation\Table\Index`)
declares database indexes directly on the entity class. When `BUILD_DATABASE=true`
the schema builder reads these attributes and creates the corresponding indexes in
MySQL automatically.

## Why Index?

Without indexes, every `WHERE`, `ORDER BY`, or `JOIN` on a non-primary-key column
performs a full table scan. On the `inv` (invoice) index page alone, each page load
issues filter queries on `status_id`, `client_id`, `date_created`, and `date_due`
in addition to join lookups on `client`, `group`, and `user`. At scale these scans
compound: a 50,000-row invoice table with no indexes can make a filtered index page
orders of magnitude slower than the same query with indexes in place.

Cycle ORM creates indexes automatically only for `BelongsTo` foreign-key columns.
Every other column — sort targets, filter dropdowns, unique lookup keys — must be
declared manually.

## General Rules Applied

| Column type | Index? |
|---|---|
| Foreign key (`*_id`) from `BelongsTo` | Auto-created by Cycle ORM |
| Foreign key not backed by a relation | `#[Index]` required |
| Sort target (`Sort::only([...])`) | `#[Index]` |
| Filter dropdown / `WHERE` target | `#[Index]` |
| Guest-access lookup (`url_key`) | `#[Index(unique: true)]` |
| Unique business key (`number`) | `#[Index(unique: true)]` |
| `text` / `longText` columns | Skip — MySQL cannot B-tree index these without a prefix |
| Boolean flags used as filters | `#[Index]` |

## Entities Indexed

### `Inv` (invoice)

```php
#[Index(columns: ['status_id'])]           // filter dropdown + sort
#[Index(columns: ['client_id'])]           // FK + sort + filter
#[Index(columns: ['date_created'])]        // sort + LIKE month filter + group-by
#[Index(columns: ['date_due'])]            // sort
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
#[Index(columns: ['creditinvoice_parent_id'])]
#[Index(columns: ['contract_id'])]
#[Index(columns: ['so_id'])]
#[Index(columns: ['quote_id'])]
```

### `Quote`

```php
#[Index(columns: ['status_id'])]
#[Index(columns: ['client_id'])]
#[Index(columns: ['date_created'])]
#[Index(columns: ['date_expires'])]        // sort target unique to quotes
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
#[Index(columns: ['so_id'])]
#[Index(columns: ['inv_id'])]              // tracks conversion to invoice
#[Index(columns: ['delivery_location_id'])]
#[Index(columns: ['contract_id'])]
```

### `SalesOrder`

```php
#[Index(columns: ['status_id'])]
#[Index(columns: ['client_id'])]
#[Index(columns: ['date_created'])]
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
#[Index(columns: ['quote_id'])]
#[Index(columns: ['inv_id'])]
```

### `Product`

```php
#[Index(columns: ['family_id'])]           // filter dropdown + sort + FK
#[Index(columns: ['tax_rate_id'])]         // sort + FK
#[Index(columns: ['unit_id'])]             // sort + FK
#[Index(columns: ['unit_peppol_id'])]      // nullable FK
```

Note: `product_sku`, `product_name`, and `product_description` are `text`/`longText`
columns and cannot receive a standard B-tree index in MySQL.

### `Client`

```php
#[Index(columns: ['client_active'])]       // All/Active/Inactive toolbar filter
#[Index(columns: ['client_name'])]         // filter dropdown + join from inv/quote/salesorder
#[Index(columns: ['client_surname'])]      // filter dropdown + join
#[Index(columns: ['client_group'])]        // join from inv/index filterClientGroup
#[Index(columns: ['postaladdress_id'])]    // nullable FK
```

`Client` is the join target for `inv`, `quote`, and `salesorder` index pages.
Indexing its filter columns speeds up all three of those pages simultaneously.

### `Family`

```php
#[Index(columns: ['street_sort_order'])]   // ORDER BY for drag-and-drop street ordering
```

## Applying Indexes

After adding or changing `#[Index]` attributes, the schema must be synced:

1. Set `BUILD_DATABASE=true` in `.env`
2. Load the application (e.g. visit `http://localhost/invoice/`)
3. Reset `BUILD_DATABASE=` in `.env`

The `.claude/sync-schema.ps1` hook automates this for any file saved under
`src/Infrastructure/Persistence/`.

## Verifying in MySQL

```sql
SHOW INDEX FROM inv;
SHOW INDEX FROM quote;
SHOW INDEX FROM sales_order;
SHOW INDEX FROM product;
SHOW INDEX FROM client;
SHOW INDEX FROM family;
```

The `Non_unique` column will be `0` for unique indexes and `1` for non-unique.
Nullable columns will show `Null = YES`.
