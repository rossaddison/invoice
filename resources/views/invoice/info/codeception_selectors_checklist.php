<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

// Container
echo Html::openTag('div', ['class' => 'container-fluid', 'style' => 'max-width: 1400px; padding: 20px;']);

// Header Row
echo Html::openTag('div', ['class' => 'row']);
echo Html::openTag('div', ['class' => 'col-12']);
echo Html::openTag('h1', ['class' => 'mb-4']);
echo 'Codeception Testing Selectors Checklist';
echo Html::closeTag('h1');
echo Html::openTag('p', ['class' => 'lead']);
echo 'Comprehensive mapping of JavaScript selectors to manual testing scenarios for Codeception test development.';
echo Html::closeTag('p');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Navigation Row
echo Html::openTag('div', ['class' => 'row mb-4']);
echo Html::openTag('div', ['class' => 'col-12']);
echo Html::openTag('nav', ['class' => 'nav nav-pills nav-fill']);
echo Html::openTag('a', ['class' => 'nav-link active', 'href' => '#client-selectors']);
echo 'Client Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#invoice-selectors']);
echo 'Invoice Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#quote-selectors']);
echo 'Quote Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#product-selectors']);
echo 'Product Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#family-selectors']);
echo 'Family Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#settings-selectors']);
echo 'Settings Module';
echo Html::closeTag('a');
echo Html::openTag('a', ['class' => 'nav-link', 'href' => '#modal-selectors']);
echo 'Modal Operations';
echo Html::closeTag('a');
echo Html::closeTag('nav');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Client Module Selectors Section
echo Html::openTag('div', ['id' => 'client-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Client Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);

// Table Header
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');

// Table Body
echo Html::openTag('tbody');

// Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_create_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → "Create Client" button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Opens client creation modal, validates form data';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Core functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#save_client_note_new';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Add Note → Save button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Saves client note via AJAX, updates note list';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - User interaction';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.btn-delete-client-note';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Note List → Delete button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows confirmation, deletes note via AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Data modification';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 4
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#payment_method';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Change Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Payment Method dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Updates client payment preferences';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Form interaction';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 5
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.card-body .row';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dynamic Content';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Client details display';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows client information in card layout';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - UI verification';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 6
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_tax_code';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client Form → Tax Code field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Accepts and validates tax code format';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Validation testing';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 7 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_name';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client Form → Name field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client name input used in create_confirm AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Required field';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 8 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_surname';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client Form → Surname field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client surname input used in create_confirm AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Required field';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 9 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_email';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client Form → Email field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client email input used in create_confirm AJAX';
echo Html::closeTag('td');
echo Html::closeTag('td');
echo 'High - Contact validation';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 10 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Hidden Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Client ID field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Used in save_client_note_new AJAX calls';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Data integrity';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 11 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_note';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Textarea Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Note textarea';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Note content field, cleared after successful save';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Note functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Row 12 - ACTUAL from client.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#notes_list';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dynamic Container';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Client View → Notes list container';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Reloaded with updated notes after save';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Dynamic content';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Invoice Module Selectors Section
echo Html::openTag('div', ['id' => 'invoice-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Invoice Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);

// Table Header
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');

// Table Body
echo Html::openTag('tbody');

// Invoice Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#btn-mark-as-sent';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice View → "Mark as Sent" button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Updates invoice status to sent, shows confirmation';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Status management';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.create_recurring_confirm_multiple';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice View → Create Recurring → Confirm button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Creates recurring invoice series, validates settings';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Complex workflow';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#btn_generate_number';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Generate Number button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Auto-generates next invoice number';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Number sequence';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 4 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#user_all_clients';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'User Client → All Clients checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows/hides client list based on selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - UI control';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 5 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#list_client';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Container';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'User Client → Client list container';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shown/hidden based on all_clients checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Conditional display';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 6 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.btn_delete_item';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Line Item → Delete button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Removes line item via AJAX or DOM removal';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Data integrity';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 7 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.delete-items-confirm-inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice → Multiple Delete → Confirm button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Deletes multiple selected items via AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Bulk operations';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 8 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo 'input[name="item_ids[]"]';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Checkbox Group';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice → Item selection checkboxes';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Selected items for bulk delete operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Selection logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 9 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '[name="checkbox-selection-all"]';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Master Checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice → Select All checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Selects/deselects all checkboxes';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Bulk selection';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 10 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#create_recurring_confirm_multiple';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice → Create Recurring → Confirm button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Creates recurring invoices from selected invoices';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Complex workflow';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 11 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#table-invoice';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Data Table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Index → Main invoice table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Contains invoice checkboxes for bulk operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Core table';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 12 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#recur_frequency';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Select Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Recurring Modal → Frequency dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sets recurring frequency for multiple invoices';
echo Html::closeTag('td');
echo Html::closeTag('td');
echo 'High - Recurring logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 13 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#recur_start_date';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Date Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Recurring Modal → Start date field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sets recurring start date for multiple invoices';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Recurring logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 14 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#recur_end_date';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Date Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Recurring Modal → End date field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sets recurring end date for multiple invoices';
echo Html::closeTag('td');
echo Html::closeTag('td');
echo 'High - Recurring logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 15 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#inv_discount_amount';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Discount Amount field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Disables percent field when used, applies fixed discount';
echo Html::closeTag('td');
echo Html::closeTag('td');
echo 'Medium - Calculation logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Invoice Row 16 - ACTUAL from inv.js
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#inv_discount_percent';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Discount Percentage field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Disables amount field when used, applies percentage discount';
echo Html::closeTag('td');
echo Html::closeTag('td');
echo 'Medium - Calculation logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Quote Module Selectors Section
echo Html::openTag('div', ['id' => 'quote-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Quote Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Quote Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.btn_delete_item';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Item → Delete button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Deletes quote item via AJAX or removes row';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Data modification';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.delete-items-confirm-quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Items → Bulk Delete Confirm';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Deletes multiple selected quote items';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Bulk operations';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo 'input[name="item_ids[]"]';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Items → Select for bulk operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Selects items for bulk delete operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Selection logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 4
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_tax_submit';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Tax → Submit button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Saves quote tax rate settings';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Tax calculations';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 5
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_create_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Create Quote Modal → Confirm button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Creates new quote with specified parameters';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Quote creation';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 6
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_to_invoice_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote → Convert to Invoice button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Converts quote to invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Workflow conversion';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 7
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_to_so_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote → Convert to Sales Order button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Converts quote to sales order';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Workflow conversion';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 8
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_discount_amount';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Discount Amount field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Disables percent field when amount entered';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Field interactions';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 9
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#quote_discount_percent';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Discount Percent field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Disables amount field when percent entered';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Field interactions';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 10
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#datepicker';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Date Picker';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Date fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Opens jQuery UI datepicker with configuration';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Date selection';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 11
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.taggable';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Focus';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Template tag fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Tracks last focused field for tag insertion';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Low - Template functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Quote Row 12
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.tag-select';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Select2 Dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Template tag selector';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Inserts selected tag into focused field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Low - Template functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Sales Order Module Selectors Section
echo Html::openTag('div', ['id' => 'salesorder-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Sales Order Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Sales Order Row 1 - PDF Export with Custom Fields
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#salesorder_to_pdf_confirm_with_custom_fields';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Export PDF with Custom Fields button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Opens PDF export in new window/tab with custom fields included';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - PDF export functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 2 - PDF Export without Custom Fields
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#salesorder_to_pdf_confirm_without_custom_fields';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Export PDF without Custom Fields button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Opens PDF export in new window/tab without custom fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - PDF export functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 3 - Convert to Invoice
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#so_to_invoice_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Convert to Invoice button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Converts sales order to invoice with AJAX validation';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Critical - Workflow conversion';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 4 - Convert Button Class
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.so_to_invoice_confirm';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Button Class';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Convert to Invoice button element';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows loading spinner during conversion process';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - UI feedback during conversion';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 5 - SO ID Field
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#so_id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Hidden Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order Form → Sales Order ID field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Required for SO-to-Invoice conversion process';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Critical - Conversion data';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 6 - Client ID Field
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#client_id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Hidden Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order Form → Client ID field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Required for SO-to-Invoice conversion process';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Critical - Conversion data';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 7 - Group ID Field
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#group_id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Hidden Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order Form → Group ID field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Required for SO-to-Invoice conversion process';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Critical - Conversion data';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 8 - Password Field
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#password';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Password Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order Form → Password field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Used in SO-to-Invoice conversion for authentication';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Security validation';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 9 - Modal Opener
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.open-salesorder-modal';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Open Modal buttons';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Opens modal dialogs for sales order operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Modal functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 10 - Save Button
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.salesorder-save';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Save Form buttons';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Submits sales order form data';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Form submission';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 11 - Form Element
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#salesorder_form';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Form Element';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order → Main form container';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Target for form submission operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Form handling';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Sales Order Row 12 - Simple Select
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.simple-select';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'TomSelect Dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sales Order Form → Select dropdowns';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Enhanced select dropdowns with TomSelect library';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Form controls';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Product Module Selectors Section
echo Html::openTag('div', ['id' => 'product-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Product Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Product Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#product_filters_submit';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product List → Filter → Submit button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Applies filters, refreshes product list';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Search functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.select-items-confirm-quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Product Modal → Confirm Selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Adds selected products to quote';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Modal integration';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.select-items-confirm-inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Product Modal → Confirm Selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Adds selected products to invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Modal integration';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 4
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo 'input[name="product_ids[]"]';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Checkbox Group';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product Modal → Product Selection checkboxes';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Enables/disables confirm button based on selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Selection logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 5
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter_product_quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Search Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Product Filter field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Filters products by name/description';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Search filtering';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 6
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter_family_quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dropdown Filter';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Family Filter dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Filters products by family category';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Category filtering';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 7
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#product-reset-button-quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Product Modal → Reset button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Clears all filters, shows all products';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Filter reset';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 8
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#product-reset-button-inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Product Modal → Reset button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Clears all filters, shows all products for invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Filter reset';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 9
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter-button-quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Product Filter → Search button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Applies product search filters for quote';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Search functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 10
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter-button-inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Product Filter → Search button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Applies product search filters for invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Search functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 11
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter_product_inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Search Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Product Filter field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Filters products by name/description for invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Search filtering';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 12
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter_family_inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dropdown Filter';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Family Filter dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Filters products by family category for invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Category filtering';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 13
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter_product_sku';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Search Input';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product List → SKU Filter field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Filters products by SKU in main product list';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - SKU search';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 14
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#table-product';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product List → Main products table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Container for product listing and filtering';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Data display';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 15
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#product-lookup-table';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product Modal → Product lookup table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Container for modal product selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Modal data display';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Product Row 16
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.product';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Table Row';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product Modal → Individual product rows';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Toggles checkbox when row is clicked';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Row interaction';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Family Module Selectors Section
echo Html::openTag('div', ['id' => 'family-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Family Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Family Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#family-category-primary-id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Family Form → Primary Category dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Sets primary category for product family';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Category management';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Family Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#family-category-secondary-id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dropdown Change Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Family Form → Secondary Category dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Loads family names based on secondary category via AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Cascading dropdown logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Family Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#family-name';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Family Form → Family Name dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Populated dynamically based on category selections';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Dynamic data loading';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Settings Module Selectors Section
echo Html::openTag('div', ['id' => 'settings-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Settings Module Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Settings Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#email_send_method';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Change Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Email → Send Method dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows/hides SMTP settings based on selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Configuration logic';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#div-smtp-settings';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Conditional Display';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Email → SMTP settings section';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Visible only when SMTP method selected';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Conditional UI';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#btn_fph_generate';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → FPH → Generate button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Generates fingerprint data via AJAX';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - AJAX operations';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 4
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#btn_generate_cron_key';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Cron → Generate Key button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Generates new cron authentication key';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Security features';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 5
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#online-payment-select';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Change Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Payment → Gateway dropdown';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows relevant gateway settings';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Payment configuration';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 6
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.cron_key';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Cron → Key field';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Populated with generated cron key value';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Low - Display field';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 7
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#btn-submit';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings Form → Submit button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Submits the settings form';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Form submission';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 8
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#form-settings';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Form';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Main settings form';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Container for all settings fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Form container';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Settings Row 9
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.gateway-settings';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Conditional Display';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Settings → Payment → Gateway specific sections';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows/hides based on selected payment gateway';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Conditional UI';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Modal Operations Selectors Section
echo Html::openTag('div', ['id' => 'modal-selectors', 'class' => 'section']);
echo Html::openTag('h2');
echo 'Modal Operations Selectors';
echo Html::closeTag('h2');
echo Html::openTag('div', ['class' => 'table-responsive']);
echo Html::openTag('table', ['class' => 'table table-striped table-hover']);
echo Html::openTag('thead', ['class' => 'table-dark']);
echo Html::openTag('tr');
echo Html::openTag('th');
echo 'Selector';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Type';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Manual Test Scenario';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Expected Behavior';
echo Html::closeTag('th');
echo Html::openTag('th');
echo 'Codeception Test Priority';
echo Html::closeTag('th');
echo Html::closeTag('tr');
echo Html::closeTag('thead');
echo Html::openTag('tbody');

// Modal Row 1
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.select-items-confirm-task';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Task Modal → Confirm Selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Adds selected tasks to invoice';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Task integration';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 2
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo 'input[name="task_ids[]"]';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Checkbox Group';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Task Modal → Task Selection checkboxes';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Enables confirm button when tasks selected';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Selection validation';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 3
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#product-lookup-table';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Dynamic Table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product Modal → Product listing table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Displays filtered product results';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - Table operations';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 4
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.product';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Row Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Product Modal → Product row click';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Toggles product selection checkbox';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - UI interaction';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 5
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter-button-quote';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Quote Form → Product Modal → Filter button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Applies product filters, updates table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Filter functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 6
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#filter-button-inv';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Product Modal → Filter button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Applies product filters, updates table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - Filter functionality';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 7
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#tasks_table';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Table Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Task Modal → Tasks table';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Enables row click to toggle task selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - UI interaction';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 8
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.item-task-id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Input Fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Invoice Form → Existing task ID fields';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Tracks already selected tasks to prevent duplicates';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Low - State tracking';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 9
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.modal-task-id';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Modal Elements';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Task Modal → Task ID elements';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Used to hide already selected tasks in modal';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Low - UI state management';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 10
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#task-modal-submit';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Submit Button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Task Modal → Submit button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Hidden when no tasks available for selection';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - UI state management';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 11
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.ajax-loader';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Global → AJAX loading elements';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Shows fullpage loader during AJAX operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - User feedback';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 12
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '#fullpage-loader';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Loading Overlay';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Global → Fullpage loading overlay';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Provides visual feedback during long operations';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'High - User experience';
echo Html::closeTag('td');
echo Html::closeTag('tr');

// Modal Row 13
echo Html::openTag('tr');
echo Html::openTag('td');
echo Html::openTag('code');
echo '.fullpage-loader-close';
echo Html::closeTag('code');
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Click Handler';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Global → Close loader button';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Manually closes the fullpage loader';
echo Html::closeTag('td');
echo Html::openTag('td');
echo 'Medium - User control';
echo Html::closeTag('td');
echo Html::closeTag('tr');

echo Html::closeTag('tbody');
echo Html::closeTag('table');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Testing Priority Legend
echo Html::openTag('div', ['class' => 'row mt-5']);
echo Html::openTag('div', ['class' => 'col-12']);
echo Html::openTag('h3');
echo 'Testing Priority Legend';
echo Html::closeTag('h3');
echo Html::openTag('div', ['class' => 'alert alert-info']);
echo Html::openTag('ul', ['class' => 'mb-0']);
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'High Priority:';
echo Html::closeTag('strong');
echo ' Core business logic, data integrity, critical user workflows';
echo Html::closeTag('li');
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'Medium Priority:';
echo Html::closeTag('strong');
echo ' User interface interactions, validation logic, secondary features';
echo Html::closeTag('li');
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'Low Priority:';
echo Html::closeTag('strong');
echo ' Convenience features, aesthetic elements, minor enhancements';
echo Html::closeTag('li');
echo Html::closeTag('ul');
echo Html::closeTag('div');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Implementation Guidelines
echo Html::openTag('div', ['class' => 'row mt-4']);
echo Html::openTag('div', ['class' => 'col-12']);
echo Html::openTag('h3');
echo 'Codeception Implementation Guidelines';
echo Html::closeTag('h3');
echo Html::openTag('div', ['class' => 'card']);
echo Html::openTag('div', ['class' => 'card-body']);
echo Html::openTag('h5');
echo 'Test Structure Recommendations:';
echo Html::closeTag('h5');
echo Html::openTag('ol');
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'Acceptance Tests:';
echo Html::closeTag('strong');
echo ' Focus on high-priority user workflows using these selectors';
echo Html::closeTag('li');
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'Functional Tests:';
echo Html::closeTag('strong');
echo ' Test form submissions and AJAX operations';
echo Html::closeTag('li');
echo Html::openTag('li');
echo Html::openTag('strong');
echo 'Unit Tests:';
echo Html::closeTag('strong');
echo ' Validate individual JavaScript functions and calculations';
echo Html::closeTag('li');
echo Html::closeTag('ol');

echo Html::openTag('h5', ['class' => 'mt-4']);
echo 'Selector Usage in Tests:';
echo Html::closeTag('h5');
echo Html::openTag('ul');
echo Html::openTag('li');
echo 'Use CSS selectors directly in Codeception: ';
echo Html::openTag('code');
echo '$I->click(\'#btn-mark-as-sent\');';
echo Html::closeTag('code');
echo Html::closeTag('li');
echo Html::openTag('li');
echo 'Test both positive and negative scenarios for each selector';
echo Html::closeTag('li');
echo Html::openTag('li');
echo 'Verify expected state changes after interactions';
echo Html::closeTag('li');
echo Html::openTag('li');
echo 'Include wait conditions for AJAX operations';
echo Html::closeTag('li');
echo Html::closeTag('ul');

echo Html::openTag('h5', ['class' => 'mt-4']);
echo 'Data Setup Requirements:';
echo Html::closeTag('h5');
echo Html::openTag('ul');
echo Html::openTag('li');
echo 'Create test clients, products, and invoices before running selector tests';
echo Html::closeTag('li');
echo Html::openTag('li');
echo 'Use fixtures for consistent test data across scenarios';
echo Html::closeTag('li');
echo Html::openTag('li');
echo 'Clean up test data after each test suite';
echo Html::closeTag('li');
echo Html::closeTag('ul');

echo Html::closeTag('div');
echo Html::closeTag('div');
echo Html::closeTag('div');
echo Html::closeTag('div');

// Close main container
echo Html::closeTag('div');

// CSS Styles
$cssArray = [
    '.section' => [
        'margin-bottom' => '3rem',
        'padding-top' => '2rem',
    ],
    '.table th' => [
        'white-space' => 'nowrap',
        'font-size' => '0.9rem',
    ],
    '.table td' => [
        'font-size' => '0.85rem',
        'vertical-align' => 'middle',
    ],
    '.table code' => [
        'font-size' => '0.8rem',
        'background-color' => '#f8f9fa',
        'padding' => '2px 4px',
        'border-radius' => '3px',
    ],
    '.nav-pills .nav-link' => [
        'font-size' => '0.9rem',
        'padding' => '0.5rem 1rem',
    ],
    '.table-responsive' => [
        'border-radius' => '0.375rem',
        'box-shadow' => '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)',
    ],
];

echo '<style>';
foreach ($cssArray as $selector => $properties) {
    echo $selector . ' { ';
    foreach ($properties as $property => $value) {
        echo $property . ': ' . $value . '; ';
    }
    echo '} ';
}

// Add media query manually since it needs special handling
echo '@media (max-width: 768px) { ';
echo '.table th, .table td { font-size: 0.75rem; padding: 0.5rem; } ';
echo '.nav-pills .nav-link { font-size: 0.8rem; padding: 0.375rem 0.75rem; } ';
echo '} ';

echo '</style>';

// JavaScript for navigation
echo Html::script("
document.addEventListener('DOMContentLoaded', function() {
    // Handle navigation clicks
    const navLinks = document.querySelectorAll('.nav-pills .nav-link');
    const sections = document.querySelectorAll('.section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active nav link
            navLinks.forEach(nl => nl.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide sections
            const targetId = this.getAttribute('href').substring(1);
            sections.forEach(section => {
                if (section.id === targetId) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        });
    });
    
    // Initially hide all sections except the first
    sections.forEach((section, index) => {
        if (index !== 0) {
            section.style.display = 'none';
        }
    });
});
")->render();
