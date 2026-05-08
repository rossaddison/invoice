# Cycle ORM + Psalm Refactoring: Lifecycle-Safe Entity Pattern

## Overview

This document summarises the refactoring approach applied to Cycle ORM entities in order to:
- improve type safety with Psalm
- remove nullable ID leakage
- enforce ORM lifecycle boundaries
- reduce defensive checks in application services

The approach introduces a **lifecycle-safe identity pattern** rather than changing ORM behaviour.

---

## Problem Statement

Using Cycle ORM entities, the `id` property is:

- `null` before persistence
- `int` after hydration

This created issues:
- Psalm errors (`int|null` vs `int`)
- unsafe casting (`(string)`, `(int)`)
- defensive checks (`strlen`, empty validation)
- inconsistent service logic

---

## Core Design Decision

Instead of forcing `id` to be always non-null (which conflicts with ORM lifecycle), we model the lifecycle explicitly.

### Key Principle

> ORM entities are *not always fully initialised objects* — they become valid after persistence.

---

## Entity State Model

### Before persistence
$id === null

### After persistence
$id === int

---

## Solution: Lifecycle-Safe Accessor

A strict accessor was introduced:

public function reqClientId(): int
{
    if ($this->id === null) {
        throw new \LogicException('Client has no ID (not persisted yet)');
    }

    return $this->id;
}

---

## Design Characteristics

### 1. Explicit Lifecycle Enforcement
- Prevents accessing ID before persistence
- Makes invalid states fail fast

### 2. Psalm-Compatible
- Eliminates int|null leakage in application code
- Allows strict int return type safely

### 3. ORM-Compatible
- Works with Cycle ORM hydration model
- Keeps $id nullable internally

---

## Removed Pattern

The following anti-patterns were eliminated:

// ❌ Unsafe
strlen((string)$id)
if (!$id)

// ❌ Nullable propagation everywhere
function getId(): ?int

---

## Current Entity Pattern

class Client
{
    public ?int $id = null;

    public function reqClientId(): int
    {
        if ($this->id === null) {
            throw new \LogicException('Client has no ID (not persisted yet)');
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }
}

---

## New Usage Pattern

### Before
$clientId = $client->getClientId();

if ($clientId === null) {
    return;
}

### After
$clientId = $client->reqClientId();

---

## Benefits Achieved

### 1. Type Safety
- No nullable ID propagation
- Psalm no longer reports type mismatches

### 2. Cleaner Services
- Removal of defensive checks
- No casting required

### 3. Clear Lifecycle Semantics
- Persisted vs non-persisted state is explicit
- Invalid usage fails immediately

### 4. ORM Compatibility Maintained
- Works correctly with Cycle ORM hydration lifecycle

---

## Architectural Classification

This approach sits in:

- Layered architecture (current system)
- ORM-backed persistence model
- DDD-inspired tactical improvements

Not full DDD, but:

> “Lifecycle-safe ORM entity modelling with DDD-inspired invariants”

---

## Key Insight

Instead of forcing entities to always be valid, we explicitly model their lifecycle:

> Entities are valid *after persistence*, and accessors enforce that boundary.

---

## Summary

- nullable storage internally (?int)
- strict accessor externally (reqClientId())
- explicit lifecycle check (isPersisted())

This resolves Psalm issues without requiring a full architectural rewrite.
