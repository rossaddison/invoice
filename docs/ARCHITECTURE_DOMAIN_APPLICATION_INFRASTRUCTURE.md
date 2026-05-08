# Architecture Overview: Domain, Application, Infrastructure (Cycle ORM + Psalm)

## Overview

This document explains the intended use of the three core folders introduced:

- `src/Domain`
- `src/Application`
- `src/Infrastructure`

This structure supports a **clean separation of concerns** while still working
 with Cycle ORM and Psalm.

---

# 🧠 High-Level Concept

This architecture is a **layered architecture with DDD-inspired boundaries**:

- **Domain = Business rules**
- **Application = Use cases**
- **Infrastructure = External systems (DB, ORM, APIs)**

---

# 📦 1. src/Domain

## Purpose

The Domain layer contains the **core business logic of the system**.

It is:
- framework-independent
- ORM-independent
- persistence-ignorant

---

## What belongs here

### Entities (domain models)
```php
Client
Invoice
Product
```

### Value Objects
```php
ClientId
Money
Email
```

### Domain rules / invariants
```php
Client cannot have empty name
Invoice total must be >= 0
```

### Domain services (if needed)
```php
InvoiceCalculator
PricingService
```

---

## Rules

- ❌ NO Cycle ORM annotations
- ❌ NO database logic
- ❌ NO HTTP logic
- ✔ Pure PHP logic only

---

# ⚙️ 2. src/Application

## Purpose

The Application layer contains **use cases (business actions)**.

It coordinates domain objects but does NOT contain business rules itself.

---

## What belongs here

### Use cases / actions
```php
CreateClient
DeleteInvoice
UpdateClientAddress
```

### Orchestration logic
- calls domain objects
- calls repositories via interfaces
- manages workflow

---

## Example

```php
final class DeleteClient
{
    public function __invoke(int $clientId): void
    {
        $client = $this->clientRepository->get($clientId);

        $client->markAsDeleted();

        $this->clientRepository->save($client);
    }
}
```

---

## Rules

- ✔ Can depend on Domain
- ✔ Can use repository interfaces
- ❌ No ORM logic
- ❌ No SQL / DB code

---

# 🗄️ 3. src/Infrastructure

## Purpose

The Infrastructure layer contains all **external implementations**.

This is where Cycle ORM lives.

---

## What belongs here

### ORM Entities (Cycle)
```php
App\Infrastructure\Persistence\Cycle\Entity\ClientRecord
```

### Repositories (Cycle implementations)
```php
ClientRepository (Cycle-based)
InvoiceRepository
```

### Database mappings / annotations
```php
#[Entity]
#[Column]
```

### External services
- email providers
- payment gateways
- file storage

---

## Rules

- ✔ Can depend on external libraries
- ✔ Can depend on Cycle ORM
- ❌ No business rules
- ❌ No domain logic

---

# 🔁 Data Flow

## Request lifecycle

```
Application Layer (Use Case)
        ↓
Domain Layer (Business logic)
        ↓
Infrastructure Layer (Cycle ORM / DB)
```

---

# 🔥 How Cycle ORM fits

Cycle ORM entities live in:

> Infrastructure Layer ONLY

They are NOT domain entities.

Instead:

- Cycle entity = persistence model
- Domain entity = business model

Mapping happens between them.

---

# 🔄 Example Flow (Future State)

## 1. Application

```php
DeleteClient::execute($clientId);
```

## 2. Domain

```php
Client::markAsDeleted();
```

## 3. Infrastructure

```php
ClientRecord (Cycle ORM entity)
ClientRepository persists changes
```

---

# 🧠 Key Principles

## 1. Domain is the most important layer
It must never depend on anything else.

---

## 2. Application is orchestration only
It tells the system WHAT to do.

---

## 3. Infrastructure is replaceable
It handles HOW things are stored or communicated.

---

# 🚀 Benefits of this structure

- Clear separation of responsibilities
- Easier testing
- Reduced coupling to Cycle ORM
- Psalm-friendly type boundaries
- Gradual migration to DDD possible

---

# ⚠️ Important Reality Check

This is a **gradual architecture**, not a full rewrite.

You can:
- keep current Cycle entities initially
- migrate slowly into Domain models
- evolve boundaries over time

---

# 🧾 Summary

| Layer | Responsibility |
|------|---------------|
| Domain | Business rules |
| Application | Use cases |
| Infrastructure | Cycle ORM + external systems |

---

This structure enables a controlled evolution from ORM-driven design
to domain-driven design without a full rewrite.
