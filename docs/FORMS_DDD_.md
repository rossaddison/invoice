# Why Forms No Longer Use `__construct()` (DDD Approach)

## Summary

Forms are now pure input models. They no longer depend on entities or
persistence.

## Why constructors were removed

-   Removed entity coupling
-   Removed hidden hydration logic
-   Clarified responsibility boundaries

## New pattern

-   Forms are empty by default
-   Use static function `show(EntityName $entity)` for edit and view hydration only

## DDD mapping

Form = input + validation\
Entity = domain state\
Application = orchestration

## Result

Clear separation between UI and domain layers.
