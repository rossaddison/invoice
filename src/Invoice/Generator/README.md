# GeneratorController.php Overview

`GeneratorController.php` is a powerful developer tool for **code generation (scaffolding)** and **language file translation management** within this application.  
It is not part of the regular user-facing flow, but serves as a utility to speed up development and maintenance.

---

## Primary Responsibilities

### 1. CRUD Scaffolding (Code Generation)

Automates the creation of necessary files for new entities, following project conventions—this process is commonly referred to as "scaffolding".

- **Core Idea:**  
  Developers create a "Generator" record in the database (using the Gentor entity), which contains metadata about a database table (table name, class names, route prefixes, etc.).
- **CRUD for Generators:**  
  Standard web interface for managing Gentor records:  
  - `index()`, `add()`, `edit()`, and `delete()` methods.
- **File Generation Methods:**  
  Methods to generate specific types of files:
  - `entity()`: Cycle ORM Entity class
  - `repo()`: Repository class for data access
  - `service()`: Service class for business logic
  - `form()`: Form Model for data validation
  - `controller()`: Controller for web requests
  - `_index()`, `_form()`, `_view()`: View files for listing, editing, viewing records
  - `_route()`: Sample route configuration for the new controller
- **Workflow:**  
  When a developer triggers a generation action, the controller:
  - Reads Gentor configuration
  - Inspects the database table schema
  - Uses template files (`resources/views/invoice/generator/templates_protected/`)
  - Produces and saves the generated PHP code in the correct project directory

---

### 2. Google Translate Integration

Assists developers in translating application language files using the Google Translate API.

- **Main method:**  
  - `google_translate_lang()`
    - `'app'` mode: Translates the main English language file (`resources/messages/en/app.php`) into a new language.
    - `'diff'` mode: Compares English file to target language file (e.g., German `de/app.php`), finds missing phrases, and translates only those.
- **Process:**
  1. Authenticates with Google Cloud Translate API (JSON credentials file).
  2. Reads source English text.
  3. Sends text in small batches to avoid API limits.
  4. Receives translations and matches them with original keys.
  5. Saves new key-value pairs into a PHP file in `resources/views/invoice/generator/output_overwrite/`.
- **Manual Step:**  
  Developer must manually copy generated translations into final language file (e.g., `resources/messages/de/app.php`).

---

## Summary

**GeneratorController.php** is an internal developer assistant that streamlines application development by:

1. **Automating Boilerplate:**  
   Generates repetitive code for new CRUD sections, saving time and reducing errors.
2. **Simplifying Internationalization (i18n):**  
   Automates translation of application text, making it easier to support multiple languages.

---