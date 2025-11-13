# Advanced Topics

Learn how to extend and customize Laravel Running Number for advanced use cases.

## Table of Contents

1. [Custom Presenters](01-custom-presenters.md) - Create custom formatting logic
2. [Custom Generators](02-custom-generators.md) - Extend the generation process
3. [Integration Patterns](03-integration-patterns.md) - Advanced integration techniques

## Overview

The package is designed to be extensible through:

- **Contracts (Interfaces)**: Define custom behavior
- **Dependency Injection**: Replace default implementations
- **Configuration**: Swap implementations via config

## Key Concepts

### Contracts

The package provides two main contracts:

- `Generator`: Controls how numbers are generated
- `Presenter`: Controls how numbers are formatted

### Extension Points

You can customize:

- Number formatting (Presenter)
- Generation logic (Generator)
- Storage model (RunningNumber)
- Type validation
- Output transformation

## When to Customize

Consider customization when you need:

- Special formatting requirements
- Business-specific logic
- Integration with external systems
- Complex numbering schemes
- Multi-tenant considerations
- Year/period-based resets

## Next Steps

Explore each topic to learn advanced customization techniques.
