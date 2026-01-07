<?php

declare(strict_types=1);

// Note this is the only file that gets adjusted during development.
// Use: Copied to the English folder located under src/Invoice/Language/English as app_lang.php
//      by function GeneratorController google_translate_lang during translating
//      If the google_translate_lang route is 'diff' and not 'app' the
//      the desired translation locale folder e.g 'de' is compared with
//      app_lang and any translation key-value pairs that the 'de' folder
//      does not have is output to the outputoverwrite folder.
return [
  'account.information' => 'Account Information',
  'active' => 'Active',
  'add' => 'Add',
  'add.invoice' => 'Add Invoice',
  'add.client' => 'Add Client',
  'add.family' => 'Add Family',
  'add.files' => 'Add Files...',
  'add.invoice.tax' => 'Add Invoice Tax',
  'add.new.row' => 'Add new row',
  'add.note' => 'Add Note',
  'add.notes' => 'Add Notes',
  'add.payment.provider' => 'Add a Payment Provider',
  'add.product' => 'Add product',
  'add.quote' => 'Add Quote',
  'add.quote.tax' => 'Add Quote Tax',
  'add.task' => 'Add task',
  'add.unit' => 'Add Unit',
  'address' => 'Address',
  'administrator' => 'Administrator',
  'after.amount' => 'After Amount',
  'after.amount.space' => 'After Amount with'
    . ' nonbreaking space',
  'aging' => 'Invoice Aging',
  'aging.1.15' => '1 - 15 Days',
  'aging.16.30' => '16 - 30 Days',
  'aging.above.30' => 'Above 30 Days',
  'alert.no.client.assigned' => 'No client assigned'
    . ' to this project.',
  'alert.no.tasks.found' =>
    'No tasks found for this project.',
  'alert.task.delete' =>
    'Caution! You want to delete a task that was used'
    . ' to generate an invoice.',
  'all' => 'All',
  'allowance.or.charge' =>
    'Peppol Allowance or Charge'
    . '  for Invoice Overall A/C or Invoice Line Item A/C',
  'allowance.or.charge.add' =>
    'Add Parent Document Level Allowance'
    . ' or Charge (See options on Invoice View for Adding'
    . ' Inv Allowance or Charges)',
  'allowance.or.charge.allowance' =>
    'Allowance',
  'allowance.or.charge.allowance.vat' =>
    'VAT on allowance',
  'allowance.or.charge.allowance.tax' =>
    'Tax on allowance',
  'allowance.or.charge.amount' =>
    'Amount (mfn x base | fixed)',
  'allowance.or.charge.base.amount' =>
    'Base Amount (Must exist if MFN > 1)',
  'allowance.or.charge.charge' => 'Charge',
  'allowance.or.charge.charge.vat' => 'Vat on charge',
  'allowance.or.charge.charge.tax' => 'Tax on charge',
  'allowance.or.charge.edit.allowance' => 'Edit',
  'allowance.or.charge.edit.charge' => 'Edit',
  'allowance.or.charge.index' => 'Index of Allowance or'
    . ' Charges relative to line item',
  'allowance.or.charge.inv' => 'Invoice Allowance'
    . ' or Charge',
  'allowance.or.charge.quote' => 'Quote Allowance'
    . ' or Charge',  
  'allowance.or.charge.inv.add' => 'Add Invoice Allowance'
    . ' or Charge',
  'allowance.or.charge.quote.add' => 'Add Quote Allowance'
    . ' or Charge',
  'allowance.or.charge.item.invoice' => 'Invoice Item'
    . ' Allowance or Charge',
  'allowance.or.charge.item.quote' => 'Quote Item'
    . ' Allowance or Charge',
  'allowance.or.charge.item.add' => 'Allowance/Charge'
    . ' Add',
  'allowance.or.charge.level' => 'Level (⬅ ️Overall,'
    . ' Line Item ➡)',
  'allowance.or.charge.multiplier.factor.numeric' =>
    'Multiplier Factor Numeric: greater than 1 =>'
    . ' used, 0 or 1 => not used',
  'allowance.or.charge.reason' => 'Reason',
  'allowance.or.charge.reason.code' => 'Reason Code',
  'allowance.or.charge.shipping.handling.packaging' =>
    'Shipping/Handling/Packaging',
  'allowance.or.charge.shipping.handling.packaging.vat' =>
    'VAT on Shipping/Handling/Packaging',
  'allowance.or.charge.shipping.handling.packaging.tax' =>
    'Tax on Shipping/Handling/Packaging',
  'already.paid' => 'This invoice was already paid.',
  'amount.payment' => 'Payment Amount to be paid in full',
  'amount.inv.item' => 'Invoice Item Amount',
  'amount.quote.item' => 'Quote Item Amount',
  'amount.add' => 'Invoice Item Amount Add',
  'amount.due' => 'Amount Due',
  'amount.no' => 'There is no Invoice Amount',
  'amount.settings' => 'Amount Settings',
  'amounts' => 'Invoice Amounts',
  'any.family' => 'Any family',
  'apply.after.item.tax' => 'Apply After Item Tax',
  'apply.before.item.tax' => 'Apply Before Item Tax',
  'approve' => 'Approve',
  'approve.this.quote' => 'Approve This Quote',
  'approved' => 'Approved',
  'archive' => 'Archive the pdf at Uploads/Archive',
  'assign.client' => 'Assign Client',
  'assign.client.on.signup' => 'Assign a client to'
    . ' user upon signing up.',
  'assign.client.on.signup.default.age.minimum.eighteen' =>
    'Assign a client with default minimum'
    . ' age of eighteen to the user upon signing up.',
  'assign.client.on.signup.done' => 'Assigned a client'
    . ' to user upon signing up.',
  'assigned.clients' => 'Assigned Clients',
  'attachment.list' => 'Attachment List',
  'attachments' => 'Attachments',
  'automatic.email.on.recur' => 'Automatically Email'
    . ' recurring invoices',
  'back' => 'Back',
  'balance' => 'Balance',
  'balance.does.not.equal.zero' => 'Balance does not'
    . ' equal zero. Status is Paid therefore '
    . 'Balance should be zero. ',
  'base.invoice' => 'Base Invoice',
  'bcc' => 'BCC',
  'bcc.mails.to.admin' => 'Send all outgoing emails'
    . ' as BCC to the admin account',
  'bcc.mails.to.admin.hint' => 'The admin account'
    . ' is the account that was created while'
    . ' installing InvoicePlane.',
  'before.amount' => 'Before Amount',
  'bill.to' => 'Bill To',
  'birthdate' => 'Birthdate',
  'body' => 'Body',
  'boolean' => 'Boolean',
  'bootstrap5' => 'Bootstrap 5',
  'bootstrap5.alert.close.button.font.size' =>
    'Alert Close Button Font Size',
  'bootstrap5.alert.message.font' => 'Alert Message Font',
  'bootstrap5.alert.message.font.size' =>
    'Alert Message Font Size',
  'bootstrap5.layout.invoice.navbar.font' =>
    'Layout Invoice Navbar Font',
  'bootstrap5.layout.invoice.navbar.font.size' =>
    'Layout Invoice Navbar Font Size',
  'bootstrap5.offcanvas.enable' =>
    'Enable Offcanvas',
  'bootstrap5.offcanvas.placement' =>
    'Offcanvas Placement e.g. top, bottom,'
    . ' start i.e left, end i.e right',
  'breadcrumb.product.index' => 'Product Index',
  'breadcrumb.product.property.index' => 'Product Property Index',
  'calculate.discounts' => 'Calculate Discounts',
  'calendar' => 'Calendar',
  'calendar.day.1' => '1 Day',
  'calendar.day.15' => '15 Days',
  'calendar.day.2' => '2 Days',
  'calendar.day.3' => '3 Days',
  'calendar.day.30' => '30 Days',
  'calendar.day.4' => '4 Days',
  'calendar.day.5' => '5 Days',
  'calendar.day.6' => '6 Days',
  'calendar.month.1' => '1 Month',
  'calendar.month.10' => '10 Months',
  'calendar.month.11' => '11 Months',
  'calendar.month.2' => '2 Months',
  'calendar.month.3' => '3 Months',
  'calendar.month.4' => '4 Months',
  'calendar.month.5' => '5 Months',
  'calendar.month.6' => '6 Months',
  'calendar.month.7' => '7 Months',
  'calendar.month.8' => '8 Months',
  'calendar.month.9' => '9 Months',
  'calendar.week.1' => '1 Week',
  'calendar.week.2' => '2 Weeks',
  'calendar.week.3' => '3 Weeks',
  'calendar.week.4' => '4 Weeks',
  'calendar.year.1' => '1 Year',
  'calendar.year.2' => '2 Years',
  'calendar.year.3' => '3 Years',
  'calendar.year.4' => '4 Years',
  'calendar.year.5' => '5 Years',
  'can.be.changed' => 'Can be changed',
  'cancel' => 'Cancel',
  'canceled' => 'Cancelled',
  'case.date' => 'Case Date',
  'case.number' => 'Case Number',
  'cash.discount' => 'Cash Discount',
  'category.primary' => 'Category Primary',
  'category.secondary' => 'Category Secondary',
  'caution.delete.invoices' => 'Testing Only:'
    . ' Delete all invoices and related records.'
    . ' (See function inv/flush)',
  'caution.deleted.invoices' => 'Testing Only:'
    . ' All invoice and related fields'
    . ' have been deleted.',
  'cc' => 'CC',
  'change.client' => 'Change Client',
  'change.password' => 'Change Password',
  'checking.for.news' => 'Checking for News...',
  'checking.for.updates' => 'Checking for Updates...',
  'city' => 'City',
  'claim' => 'Started a Legal Claim',
  'cldr' => 'en',
  'client' => 'Client',
  'client.access' => 'Client Access',
  'client.add' => 'Client Add',
  'client.age' => 'Age',
  'client.age.hint' => 'This field is required'
    . ' and the client should legally'
    . ' be at least 16 years of age',
  'client.already.exists' => 'Client already exists!',
  'client.birthdate.hint' => 'If the birthdate'
    . ' is not known, enter 01/01/1901',
  'client.building.number' => 'Client Building Number',
  'client.contract.period.end' => 'Period End',
  'client.contract.period.start' => 'Period Start',
  'client.contract.reference' => 'Contract Reference',
  'client.custom' => 'Client Custom',
  'client.custom.add' => 'Client Custom Add',
  'client.deactivate.warning' =>
    'Warning: Invoices and related source documentation, will not be viewable'
    . ' if the client is not active.',
  'client.delete.history.exits.no' =>
    'Cannot delete. Client History exists.',
  'client.detail.changes' =>
    'Please send us an email if this detail changes.',
  'client.error.summary' => 'Error Summary',
  'client.form' => 'Client Form',
  'client.frequency' => 'Client Frequency',
  'client.group' => 'Client Group',
  'client.has.not.assigned' =>
    'These clients have not been assigned'
    . ' to a user account.'
    . ' One or more clients per user account.',
  'client.has.not.user.account' => 'This client'
    . ' does not have a user account or is not currently assigned to a user.'
    . '1. Ensure Client is Signed up i.e'
    . 'has a user account or assign this'
    . 'client to a user currently registered.'
    . '2. Settings ... Invoice User Account ... Add a User Account'
    . '3. Assign this client to the user account'
    . '4. Invoices and Quotes will not be able'
    . 'to be created for this Client in this current state.',
  'client.has.user.account' => 'User Account',
  'client.import.list.blank' => 'Client Import List Blank',
  'client.name' => 'Client Name',
  'client.not.allocated.to.user' => 'Client not'
    . ' allocated to user',
  'client.not.found' => 'Client not found',
  'client.note' => 'Client Note',
  'client.note.add' => 'Client Note Add',
  'client.note.date' => 'Client Date',
  'client.note.view' => 'Client Note View',
  'client.notes' => 'Client Notes',
  'client.number' => 'Client Number',
  'client.peppol' => 'Client Peppol Details',
  'client.peppol.accounting.cost' => 'Client Accounting'
    . ' Cost Code for Bookkeeping',
  'client.peppol.add' => 'Add Peppol details for e-Invoicing',
  'client.peppol.buyer.reference.default' =>
    'Buyer Reference:'
    . ' If no Client Purchase Order Contact Person'
    . ' is specified by the client, this is the'
    . ' default Buyer Reference or person Ordering.',
  'client.peppol.buyer.reference.example' =>
    'eg. name of the person normally ordering,'
    . ' employee number of the person normally'
    . ' ordering or a code identifying'
    . ' this person or department/group',
  'client.peppol.clientpeppols.form' =>
    'Accounting Client/Customer Party Form',
  'client.peppol.edit' => 'Edit Peppol details for e-Invoicing',
  'client.peppol.endpointid' => 'End Point ID: Email Address',
  'client.peppol.endpointid.schemeid' =>
    'End Point ID -  schemeID based on '
    . 'EAS (Electonric Address Scheme) 4 digit code eg. 0192',
  'client.peppol.financial.institution.branchid' =>
    'Financial Institution Branch Id',
  'client.peppol.identificationid' => 'Identification ID',
  'client.peppol.identificationid.schemeid' =>
    'Identification ID - schemeID',
  'client.peppol.legal.entity.company.legal.form' =>
    'Legal Entity Company Legal Form -'
    . ' Additional Legal Information'
    . ' relevant to the Seller eg. Share Capital',
  'client.peppol.legal.entity.companyid' =>
    'Legal Entity Company ID',
  'client.peppol.legal.entity.companyid.schemeid' =>
    'Legal Entity Company ID - schemeID',
  'client.peppol.legal.entity.registration.name' =>
    'Legal Entity Registration Name',
  'client.peppol.not.found' =>
    'The client or customer has not setup'
    . ' their Peppol details.',
  'client.peppol.not.found.accounting.cost' =>
    'Not Found: Accounting Cost or a textual value'
    . ' that specifies where to book the relevant'
    . ' data into the Buyers financial accounts.',
  'client.peppol.not.found.delivery.location' =>
    'Delivery location Country Name not found',
  'client.peppol.not.found.id' => 'The client'
    . ' has not filled in their Account Id'
    . ' under their Client Peppol details online.',
  'client.peppol.not.found.id.supplier.assigned' =>
    'Client Peppol Supplier Assigned Account Id Not Found',
  'client.peppol.not.found.invoice' => 'No Linked Invoice Found',
  'client.peppol.not.found.purchase.order' => 'The Sales Order'
    . ' has no Purchase Order Number associated with it',
  'client.peppol.not.found.purchase.order.item.number' =>
    'The Sales Order Line Item has no matching'
    . ' Purchase Order Line Item Identification'
    . ' Number (Buyers Item Identification)'
    . ' associated with it. Administrator:'
    . ' Edit the Invoice\'s Sales Order Item Number.',
  'client.peppol.not.found.purchase.order.line.number' =>
    'The Sales Order Line Number has no matching'
    . ' Purchase Order Line Number'
    . ' (OrderLineReference LineID)associated with it.'
    . ' Administrator: Edit the Invoice\'s Sales Order Line Number. ',
  'client.peppol.not.found.sales.order' => 'Sales Order does not exist',
  'client.peppol.payee.financial.account.name' =>
    'Payee Financial Account Name',
  'client.peppol.payee.financial.accountid' =>
    'Payee Financial Account ID',
  'client.peppol.record.updated.successfully' =>
    'Peppol record updated successfully',
  'client.peppol.supplier.assigned.account.id' =>
    'Supplier Assigned Account Id',
  'client.peppol.taxschemecompanyid' => 'Tax Scheme Company ID',
  'client.peppol.taxschemeid' => 'Tax Scheme ID',
  'client.postaladdress' => 'Postal Address',
  'client.postaladdress.add' => 'Add a Client Postal Address',
  'client.postaladdress.additional.street.name' =>
    'Additional Street Name',
  'client.postaladdress.available' => 'Available Postal Addresses',
  'client.postaladdress.building.number' => 'Building Number',
  'client.postaladdress.city.name' => 'City Name',
  'client.postaladdress.country' => 'Country',
  'client.postaladdress.countrysubentity' => 'Country Sub Entity',
  'client.postaladdress.none' => 'The Client does not'
    . ' have a postaladdress',
  'client.postaladdress.postalzone' => 'Postalzone',
  'client.postaladdress.street.name' => 'Street Name',
  'client.purchase.order.number' => 'Client Purchase Order Number',
  'client.streets' => 'Streets',
  'client.surname' => 'Client Surname',
  'client.surname.optional' => 'Client Surname (Optional)',
  'client.title' => 'Client Title (Mr/Mrs/Miss/Dr/Prof)',
  'client.title.doctor' => 'Dr',
  'client.title.miss' => 'Miss',
  'client.title.mr' => 'Mr',
  'client.title.mrs' => 'Mrs',
  'client.title.professor' => 'Professor',
  'client.view' => 'Client View',
  'clients' => 'Clients',
  'close' => 'Close',
  'closed' => 'Closed',
  'column' => 'Column',
  'common.date.created' => 'Date created',
  'common.date.modified' => 'Date modified',
  'common.name' => 'Name',
  'company' => 'Company',
  'company.deleted' => 'Company has been deleted',
  'company.not.deleted' => 'Company has not been'
    . ' deleted because you have a Company Profile attached to it.',
  'company.private' => 'Company Private',
  'company.private.logo' => 'Company Logo',
  'company.private.logo.height' => 'Logo Height',
  'company.private.logo.margin' => 'Logo Margin',
  'company.private.logo.width' => 'Logo Width',
  'company.private.logo.will.be.removed.from.uploads.and.public.folder' =>
    'Logo will be deleted from'
    . ' uploads and public folder',
  'company.public' => 'Company Public',
  'complete' => 'Complete',
  'confirm' => 'Confirm',
  'confirm.deletion' => 'Confirm deletion',
  'contact.information' => 'Contact Information',
  'continue' => 'Continue',
  'continue.with.developersandboxhmrc' => 'Continue'
    . ' with Developer Gov Sandbox UK',
  'continue.with.facebook' => 'Continue with Facebook',
  'continue.with.github' => 'Continue with Github',
  'continue.with.google' => 'Continue with Google',
  'continue.with.govuk' => 'Continue with Gov Uk',
  'continue.with.linkedin' => 'Continue with LinkedIn',
  'continue.with.microsoftonline' => 'Continue with MicrosoftOnline',
  'continue.with.oidc' => 'Continue with Open Id Connect',
  'continue.with.openbanking' => 'Continue with Open Banking',
  'continue.with.vkontakte' => 'Continue with VKontakte',
  'continue.with.x' => 'Continue with X',
  'continue.with.yandex' => 'Continue with Yandex',
  'contract' => 'Contract',
  'contract.add' => 'Add a Contract',
  'contract.contracts' => 'Contracts',
  'contract.create' => 'Create your Contracts via.'
    . ' Invoice...View...Options...Edit. A contract'
    . ' will be created for the client that the'
    . ' Invoice is being made out to.'
    . ' Link this contract to future invoices.',
  'contract.index.button.list' => 'Invoices',
  'contract.name' => 'Name',
  'contract.none' => 'Reminder: No Contract'
    . ' has been setup for this invoice',
  'contract.period.end' => 'Period End',
  'contract.period.start' => 'Period Start',
  'contract.reference' => 'Reference',
  'contracts' => 'Contracts',
  'converted.to.invoice' => 'Converted to Invoice',
  'converted.to.so' => 'Converted to Sales Order',
  'copy.invoice' => 'Copy Invoice',
  'copy.quote' => 'Copy Quote',
  'count' => 'Invoice Count',
  'country' => 'Country',
  'create' => 'Create',
  'create.credit.invoice' => 'Create credit invoice',
  'create.credit.invoice.alert' => 'Creating a credit'
    . ' invoice will make the current invoice'
    . ' <em>read-only</em> which means you will not be'
    . ' able to edit the invoice anymore. The credit'
    . ' invoice will contain the current state with'
    . ' all items but with negative amounts and balances.',
  'create.invoice' => 'Create Invoice',
  'create.new.client' => 'Create a new Client',
  'create.product' => 'Create product',
  'create.project' => 'Create Project',
  'create.quote' => 'Create Quote',
  'create.recurring' => 'Create Recurring',
  'create.task' => 'Create Task',
  'created' => 'Created',
  'creation.unsuccessful' => 'Invoice Creation Unsuccessful',
  'credit.invoice' => 'Credit Invoice',
  'credit.invoice.date' => 'Credit invoice date',
  'credit.invoice.details' => 'Credit invoice details',
  'credit.invoice.for.invoice' => 'Credit Note',
  'credit.note.creation.successful' => 'Credit Note Creation Successful',
  'credit.note.creation.unsuccessful' =>
    'Credit Note or Credit Memo Creation,'
    . ' to cancel the Invoice, was unsuccessful',
  'creditcard.cvv' => 'CVV / CSC',
  'creditcard.details' => 'Credit Card details',
  'creditcard.expiry.month' => 'Expiry Month',
  'creditcard.expiry.year' => 'Expiry Year',
  'creditcard.number' => 'Credit Card Number',
  'cron.key' => 'CRON Key',
  'curl.store.cove.api.get.legal.entity.id.successful' =>
    'Get Legal Entity Successful',
  'curl.store.cove.api.setup.legal.entity.successful' =>
    'Store Cove Setup Api Call - Legal Entity Successful',
  'curl.store.cove.api.setup.successful' =>
    'Store Cove Setup Api Call Successful',
  'currency' => 'Currency',
  'currency.code' => 'Currency Code',
  'currency.symbol' => 'Currency Symbol',
  'currency.symbol.placement' => 'Currency Symbol Placement',
  'current.day' => 'Current day',
  'current.month' => 'Current month',
  'current.version' => 'Current Version',
  'current.year' => 'Current year',
  'current.yy' => 'Current year (2-digit format)',
  'custom' => 'Invoice Custom',
  'custom.field' => 'Custom Field',
  'custom.field.add' => 'Custom Field Add',
  'custom.field.edit' => 'Custom Field Edit',
  'custom.field.form' => 'Custom Field Form',
  'custom.field.location' => 'Document Position',
  'custom.field.number' => 'Number',
  'custom.field.required' => 'Required i.e.'
    . ' If the user enters nothing in this field, '
  . 'the user will be required to enter at least something.',
  'custom.fields' => 'Custom Fields',
  'custom.invoice.add' => 'Custom Invoice Add',
  'custom.title' => 'Custom Title',
  'custom.value' => 'Custom Value',
  'custom.value.delete' => 'Delete Custom Value First',
  'custom.value.new' => 'Custom Value New',
  'custom.values' => 'Custom Values',
  'custom.values.edit' => 'Edit Custom Value',
  'custom.values.new' => 'New Custom Value',
  'dashboard' => 'Dashboard',
  'database' => 'Database',
  'database.properly.configured' => 'The database is properly configured',
  'date' => 'Invoice Date',
  'date.actual.delivery' => 'Date of Actual Delivery',
  'date.applied' => 'Date Applied',
  'date.created' => 'Date Created',
  'date.format' => 'Date Format',
  'date.issued' => 'Date Issued / Created',
  'date.supplied' => 'Date Supplied',
  'dates' => 'Invoice Dates',
  'datetime.immutable.date.created' => 'Create',
  'datetime.immutable.date.created.mySql.format.year.month.filter' => 'Y-m',
  'datetime.immutable.date.modified' => 'Mod',
  'datetime.immutable.time.created' => 'Time',
  'days' => 'Days',
  'debug' => 'Debug Mode On',
  'decimal.point' => 'Decimal Point',
  'default' => 'Default',
  'default.country' => 'Default country',
  'default.email.template' => 'Default Email Template',
  'default.hourly.rate' => 'Default hourly rate',
  'default.invoice.group' => 'Default Invoice Group',
  'default.invoice.tax.rate' => 'Default Invoice Tax Rate',
  'default.invoice.tax.rate.placement' =>
    'Default Invoice Tax Rate Placement',
  'default.item.tax.rate' => 'Default Item Tax Rate',
  'default.list.limit' => 'Number of Items in Lists',
  'default.notes' => 'Default Notes',
  'default.payment.method' => 'Default Payment Method',
  'default.pdf.template' => 'Default PDF Template',
  'default.public.template' => 'Default Public Template',
  'default.quote.group' => 'Default Quote Group',
  'default.terms' => 'Default Terms',
  'delete' => 'Delete',
  'delete.attachment.warning' =>
    'Are you sure you wish to delete this attachment?',
  'delete.client' => 'Delete Client',
  'delete.client.warning' =>
    'If you delete this client you will also'
    . ' delete any invoices, quotes and payments'
    . ' related to this client. Are you sure you'
    . ' want to permanently delete this client?',
  'delete.invoice' => 'Delete Invoice',
  'delete.invoice.warning' => 'If you delete this'
    . ' invoice you will not be able to recover'
    . ' it later. Are you sure you want'
    . ' to permanently delete this invoice?',
  'delete.quote' => 'Delete Quote',
  'delete.quote.warning' => 'If you delete this quote'
    . ' you will not be able to recover it later.'
    . ' Are you sure you want to permanently delete this quote?',
  'delete.quote.single' => 'This quote can be'
    . ' deleted because no Sales Order'
    . ' or Invoice is associated with it',
  'delete.quote.derived' => 'Either a Sales Order'
    . ' or an Invoice is linked'
    . ' to this Quote and it cannot'
    . ' therefore be deleted.',
  'delete.record.warning' =>
    'Are you sure you wish to delete this record?',
  'delete.sent' => 'Cannot delete - invoice sent',
  'delete.tax.warning' => 'Are you sure you'
    . ' wish to delete this tax?',
  'delete.user.client.warning' =>
    'Are you sure you wish to'
    . ' unassign this client from this user?',
  'deleted' => 'Deleted',
  'deletion.forbidden' => 'Deleting invoices'
    . ' is forbidden.'
    . ' Please contact the administrator'
    . ' or consult the documentation.',
  'delivery' => 'Delivery Details:'
    . ' Invoice/Delivery Period Start/End Dates',
  'delivery.actual.delivery.date' => 'Actual Delivery Date',
  'delivery.add' => 'Add Delivery',
  'delivery.date.created' => 'Date Created',
  'delivery.date.modified' => 'Delivery Date Modified',
  'delivery.end.date' => 'End Date of Delivery/Invoice Period',
  'delivery.location' => 'Delivery Location',
  'delivery.location.add' => 'Delivery Location Add',
  'delivery.location.add.in.invoice' => 'Add the delivery'
    . ' location under Invoice'
    . ' ... View ... Options ... Edit',
  'delivery.location.building.number' => 'Building Number',
  'delivery.location.client' => 'Delivery Locations of Client',
  'delivery.location.delete' => 'Delivery Location Delete',
  'delivery.location.edit' => 'Delivery Location Edit',
  'delivery.location.electronic.address.scheme' =>
    'Electronic Address Scheme (Code List) Default:'
    . ' 0088 European Article Numbering '
    . '(EAN) Location Code a.k.a '
    . '(GLN) Global Location Numbers',
  'delivery.location.global.location.number' =>
    'Global Location Number (13 digits)',
  'delivery.location.id.not.found' =>
    'Delivery Location Global Location Number ID Not Found',
  'delivery.location.index.button.list' => 'Invoices',
  'delivery.location.none' =>
    'No delivery location has'
    . ' been linked to this invoice',
  'delivery.location.peppol.output' => 'There is no'
    . ' delivery location associated'
    . ' with this invoice. Therefore no Peppol output',
  'delivery.location.plural' => 'Delivery Locations',
  'delivery.location.view' => 'Delivery Location View',
  'delivery.party' => 'Delivery Party',
  'delivery.party.add' => 'Delivery Party Add',
  'delivery.party.edit' => 'Delivery Party Edit',
  'delivery.party.name' => ' Delivery Party Name',
  'delivery.party.view' => 'Delivery Party View',
  'delivery.start.date' => 'Start Date of Delivery/Invoice Period',
  'description' => 'Description',
  'description.document' => 'Document Description',
  'details' => 'Details',
  'development.progress' => 'Development Progress',
  'development.schema' => 'Schema',
  'disable.flash.messages' => 'Disable Flash Messages',
  'disable.quickactions' => 'Disable the Quickactions',
  'disable.sidebar' => 'Disable the Sidebar',
  'discount' => 'Discount',
  'discount.amount' => 'Discount Amount',
  'discount.percent' => 'Discount Percent',
  'discount.percentage' => 'Discount Percentage',
  'document.description' => 'Peppol Document Description',
  'documentation' => 'Documentation',
  'download' => 'Download',
  'download.pdf' => 'Download PDF',
  'draft' => 'Draft',
  'draft.guest' => 'Draft invoices are not viewable by Clients.',
  'draft.number.off' => 'New draft Invoices'
    . ' will have no Invoice Number. Mark as sent to get Invoice Number',
  'draft.number.on' => 'New draft Invoices will have an Invoice Number',
  'drop.files.here' => 'Drop files here!',
  'due.date' => 'Due Date',
  'early.settlement.cash.discount' => 'Early Settlement Cash Discount',
  'edit' => 'Edit',
  'elements' => 'Elements',
  'email' => 'Email',
  'email.address' => 'Email Address',
  'email.date' => 'Date Emailed',
  'email.default' => 'Default',
  'email.default.none.set' => 'No default has been set',
  'email.exception' => 'Emailing Exception.',
  'email.from.dropdown' => 'From Email Dropdown Email'
    . ' Addresses to be included in MailerQuote'
    . ' Form and MailerInv Form',
  'email.include' => 'Include',
  'email.invoice' => 'Email Invoice',
  'email.link.click.confirm' => 'Please confirm'
    . ' your email address by clicking this link',
  'email.log' => 'Invoices Emailed Log',
  'email.logs' => 'Invoices Emailed Logs',
  'email.logs.with.filter' => 'Specific emails for this invoice',
  'email.logs.table' => 'A table of email logs specific to this invoice',
  'email.not.configured' => 'Before you can send Email,'
    . ' you have to configure your'
    . ' Email settings in the System Settings area.',
  'email.not.sent.successfully' => 'The email was NOT sent successfully',
  'email.pdf.attachment' => 'Attach Quote/Invoice on email?',
  'email.quote' => 'Email Quote',
  'email.send.method' => 'Email Sending Method',
  'email.send.method.phpmail' => 'PHP Mail',
  'email.send.method.sendmail' => 'Sendmail',
  'email.send.method.smtp' => 'SMTP',
  'email.settings' => 'Email Settings',
  'email.source.email.template' =>
    ' Retrieved from Settings ... Email Template',
  'email.source.user.account' =>
    ' Retrieved from Settings ... Invoice User Account',
  'email.successfully.sent' => 'Email successfully sent',
  'email.template' => 'Email Template',
  'email.template.add' => 'Email Template Add',
  'email.template.already.exists' => 'Email Template already exists!',
  'email.template.form' => 'Email Template Form',
  'email.template.from.email.leave.blank' =>
    ': If you leave this field blank,'
    . ' the User\'s account email address'
    . ' will be inserted as an editable value'
    . ' on the mailer form.  eg.'
    . ' An accountant\'s server email address.'
    . ' Preferably use a server related email address here.',
  'email.template.from.source' => 'Email Source',
  'email.template.from.source.admin.email' =>
    'Adminstrator\'s Email Address'
    . ' (config/common/params)',
  'email.template.from.source.froms.email' =>
    'From Table Default (settings...From Email'
    . ' Dropdown Email Addressess)',
  'email.template.from.source.sender.email' =>
    'Sender\'s Email Address (config/common/params)',
  'email.template.not.configured' =>
    'Email templates not configured. '
    . 'Settings...Invoices...'
    . 'Invoice Templates...Default Email Template',
  'email.template.overdue' => 'Overdue Email Template',
  'email.template.paid' => 'Paid Email Template',
  'email.template.successfully.added' =>
    'Email Template Successfully Added',
  'email.template.successfully.deleted' =>
    'Email Template Successfully Deleted',
  'email.template.successfully.edited' =>
    'Email Template Successfully Edited',
  'email.template.tags' => 'Email Template Tags',
  'email.template.tags.instructions' =>
    'Template tags can be used to add'
    . ' dynamic information like the'
    . ' client name or an invoice number'
    . ' to the email template.'
    . ' Click on the Body textfield and then'
    . ' select a tag from the drop down.'
    . ' It will be automatically inserted into the textfield.',
  'email.template.type' => 'Email Template Type',
  'email.templates' => 'Email Templates',
  'email.to.address.missing' => 'You have to specify'
    . ' an email address the email should be sent to.',
  'email.warning.draft' => 'Draft invoices must first'
    . ' be marked sent so they appear on the client\'s'
    . ' side and then they can be emailed.',
  'enable.debug.mode' => 'Enable the Debug Mode',
  'enable.online.payments' => 'Enable Online Payments',
  'enable.permissive.search.clients' => 'Enable permissive search',
  'enable.projects' => 'Enable the Projects module',
  'enable.vat' => 'Enable VAT',
  'enable.vat.message' => 'Display VAT reminder message'
    . ' above options button on views',
  'enable.vat.warning.line.1' => '1. With VAT enabled,'
    . ' only individual line items on the invoice'
    . ' are taxed with vat. ie. Quote/Invoice'
    . ' Taxes will not appear on the invoice'
    . ' and will be reduced to 0.',
  'enable.vat.warning.line.2' => '2. With VAT enabled,'
    . ' no non-line-item taxes,'
    . ' as mentioned above, make up the tax total. ',
  'enable.vat.warning.line.3' => '3. Create VAT'
    . ' quotes/invoices on a separate database.',
  'enable.vat.warning.line.4' => '4. All new VAT'
    . ' invoice line items are flagged'
    . ' with the belongs_to_vat_invoice flag.',
  'enabled' => 'Enabled',
  'end' => 'End',
  'end.date' => 'End Date',
  'enforcement' => 'Enforcement Officer Attending Address',
  'enter' => 'Enter',
  'enter.payment' => 'Enter Payment',
  'error.duplicate.file' =>
    'Error: Duplicate file name, please change it!',
  'errors' => 'Errors',
  'every' => 'Every',
  'example' => 'Example',
  'expired' => 'Expired',
  'expires' => 'Expires',
  'extended' => 'Extended',
  'extended.language' => 'Extended Language',
  'extra.information' => 'Extra information',
  'failure' => 'Failure',
  'false' => 'False',
  'families' => 'Families',
  'family' => 'Family',
  'family.add' => 'Family Add',
  'family.already.exists' => 'Family already exists!',
  'family.history' => 'Family History exists. Cannot delete',
  'family.comma.list' => 'Product Number List e.g. 1, 2, 5, 7 for Product Generator',
  'family.product.prefix' => 'Product Number List Prefix e.g. House',
  'family.name' => 'Family name',
  'family.search' => 'Family Search',
  'faq' => 'FAQ\'s',
  'faq.ai.callback.session' =>
    'Copilot: How can I include '
    . 'https://github.com/rossaddison/yii-auth-client/'
    . 'blob/master/src/StateStorage/SessionStateStorage.php'
    . ' to improve the state management '
    . 'in the `callbackGithub` function?',
  'faq.business.rules' => 'What are the Peppol Business Rules?',
  'faq.gov.developer.sandbox.hmrc' => 'How do I connect'
    . ' this repository to the HMRC Developer Sandbox?',
  'faq.lamp.alpine' => 'How can I setup this'
    . ' repository onto Linux Alpine,'
    . ' Apache2, mySql/mariadb and php?',
  'faq.oauth2' => 'How do I setup an OAuth2 Identity'
    . ' Provider e.g. signing-up and logging-in with Facebook',
  'faq.payment.provider' => 'How do I setup an Online Payment Provider?',
  'faq.php.info.all' => 'All',
  'faq.php.info.configuration' => 'Configuration',
  'faq.php.info.credits' => 'Credits',
  'faq.php.info.details' => 'Php Info Details',
  'faq.php.info.environment' => 'Environment',
  'faq.php.info.general' => 'General',
  'faq.php.info.licence' => 'License',
  'faq.php.info.modules' => 'Modules',
  'faq.php.info.variables' => 'Variables',
  'faq.shared.hosting' => 'How do I host yii3i on shared hosting?',
  'faq.taxpoint' => 'How to determine what the Tax Point is?',
  'faq.yii.requirement.checker' => 'Yii Application Requirement Checker',
  'fax' => 'Fax',
  'fax.abbr' => 'F',
  'fax.number' => 'Fax Number',
  'field' => 'Field',
  'file' => 'File',
  'filter.clients' => 'Filter Clients',
  'filter.invoices' => 'Filter Invoices',
  'filter.payments' => 'Filter Payments',
  'filter.quotes' => 'Filter Quotes',
  'first' => 'First',
  'first.day.of.week' => 'First day of week',
  'first.reset' => 'First delete the test quotes'
    . ' and invoices that you created for testing.'
    . ' Then the test data can be deleted.',
  'flash.messages.appear.here' => 'Flash messages appear here.',
  'footer' => 'Footer',
  'forgot.your.password' => 'I forgot my password',
  'form.error' => 'Form errors',
  'from.date' => 'From Date',
  'from.default.in.dropdown' => 'Default email'
    . ' address in the dropdown list',
  'from.email' => 'From Email',
  'from.email.address' => 'From Email Addresses: Choose a default',
  'from.include.in.dropdown' => 'Include this email in the dropdown list',
  'from.name' => 'From Name',
  'front.page' => 'Front Page',  
  'gateway.online.payment' =>
    'Online Payment',
  'gender' => 'Gender',
  'gender.female' => 'Female',
  'gender.male' => 'Male',
  'gender.other' => 'Other',
  'general' => 'General',
  'general.no' => 'No',
  'general.settings' => 'General Settings',
  'general.yes' => 'Yes',
  'generate' => 'Generate',
  'generate.copy' => 'Generate Copy',
  'generate.invoice.number.for.draft' =>
    'Generate the invoice number for draft invoices',
  'generate.quote.number.for.draft' =>
    'Generate the quote number for draft quotes',
  'generate.sumex' => 'Generate Sumex PDF',
  'generator' => 'Generator',
  'generator.add' => 'Generator Add',
  'generator.camelcase.capital.name' =>
    'Camelcase Capital Name used in'
    . ' Controller and Repository names eg.'
    . ' TaxRate. Use \'Product\' if using above example of \'product\'',
  'generator.camelcase.capital.name.product' => 'Product',
  'generator.controller.and.repository' => 'Controller and Repository',
  'generator.controller.layout.directory' => 'Controller'
    . ' Layout Directory eg. dirname(dirname(__DIR__))'
    . ' that appears just after controller construct.'
    . ' The Controller file sits in (__DIR__) and'
    . ' is two directories below \'src\' directory'
    . ' which wil be used as a \'base\' to append'
    . ' a path to Layout directory.',
  'generator.controller.layout.directory.dot.path' =>
    'Controller Layout Directory Dot Path eg.'
    . ' \'/views/layout/invoice.php\' that appears'
    . ' just after controller construct'
    . ' (exclude the apostrophe\'s) and is appended'
    . ' to the above src directory location.',
  'generator.controller.layout.directory.dot.path.placeholder' =>
    'Controller Layout Directory Dot Path',
  'generator.controller.layout.directory.placeholder' =>
    'Controller Layout Directory eg. dirname(dirname(__DIR__))',
  'generator.controller.path.layout' =>
    'Path to Layout File',
  'generator.created.at.include' =>
    'Include created_at field in Entity',
  'generator.deleted.at.include' =>
    'Include deleted_at field in Entity',
  'generator.external.entity' =>
    'External Entity used in this Entity. '
    . 'The Setting Entity is a simple key =>'
    . ' value pair id indexed database.',
  'generator.external.entity.default' =>
    'External Entity eg. MyEntity exclusive'
    . ' of path. Path built in Generator.'
    . ' Default: Setting',
  'generator.external.entity.placeholder' =>
    'External Entity eg. MyEntity exclusive of path.'
    . ' Path built in Generator. Default:'
    . ' Setting. Additional Repository eg.'
    . ' Setting Repository in addition to main repository.',
  'generator.file.type.not.found' => 'File type not found.',
  'generator.flash.include' => 'Include Flash Message'
    . ' in Add/Edit/View/Delete function'
    . ' in Controller',
  'generator.generate' => 'Generate',
  'generator.generated' => ' generated at ',
  'generator.google.translate.any' =>
    'Translate English\\any_lang.php',
  'generator.google.translate.app' =>
    'Translate English\\app_lang.php',
  'generator.google.translate.common' =>
    'Translate English\\common_lang.php',
  'generator.google.translate.diff' =>
    'Translate English\\diff_lang.php',
  'generator.google.translate.gateway' =>
    'Translate English\\gateway_lang.php',
  'generator.google.translate.info' =>
    'Translate Info Documentation (invoice.php)',
  'generator.google.translate.ip' =>
    'Translate English\\ip_lang.php',
  'generator.google.translate.latest.a' =>
    'Translate English\\a_latest_lang.php',
  'generator.google.translate.latest.b' =>
    'Translate English\\b_latest_lang.php',
  'generator.headerline.include' =>
    'Include Headerline if Ajax required',
  'generator.history' =>
    'This record has existing Generator Relations'
    . ' so it cannot be deleleted. Delete these relations first.',
  'generator.modified.at.include' => 'Include modified_at field in Entity',
  'generator.namespace' => 'Namespace',
  'generator.namespace.before.entity' =>
    'Namespace before Entity Path eg.'
    . ' App\\Invoice (NOT App\\Invoice\\Entity)',
  'generator.relation.form' => 'Generator Relation Form',
  'generator.relation.form.camelcase.name' =>
    'Camelcase name excluding id '
    . '(eg. tax_rate_id \'foreign key/relation\' in'
    . ' Product table simplified to TaxRate'
    . ' AND is the name of an Entity)',
  'generator.relation.form.entity.generator' => 'Entity Generator',
  'generator.relation.form.lowercase.name' =>
    'Lowercase name excluding id'
    . ' (eg. tax_rate_id \'foreign key/relation\' in'
    . ' Product table simplified to tax.rate) ',
  'generator.relation.form.view.field.name' => 'View Field Name',
  'generator.relations' => 'Generator Relations',
  'generator.relations.add' => 'Generators Relation Add',
  'generator.route.prefix' => 'Route Prefix eg.'
    . ' invoice in \'invoice/product\' that'
    . ' will appear after the controller construct.',
  'generator.route.suffix' => 'Route suffix eg.'
    . ' product in \'invoice/product\' that'
    . ' will appear after the controller construct.',
  'generator.small.plural.name' => 'Small plural'
    . ' name used in Controller for index'
    . ' controller function to list all'
    . ' entity generators. Normally the'
    . ' above value with an s on the end.',
  'generator.small.plural.name.placeholder' => 'Small Plural Name',
  'generator.small.plural.name.products' => 'products',
  'generator.small.singular.name' => 'Small singular name'
    . ' used in Controller for edit, and view controller'
    . ' functions. Normally the same as the Route Suffix. eg. product',
  'generator.small.singular.name.placeholder' => 'Small Singular Name',
  'generator.small.singular.name.product' => 'product',
  'generator.table' => 'Table',
  'generator.table.used.to.generate.entity.controller.repository' =>
    'Table used to generate Entity, Controller'
    . ' Add Edit Delete View, Repository, Service, Mapper',
  'generator.updated.at.include' => 'Include updated.at field in Entity',
  'generators' => 'Generators',
  'generators.relation' => 'Generators Relation',
  'gln' => 'GLN',
  'gov.developer.sandbox' => 'Developer Sandbox',
  'gov.developer.sandbox.uk' => 'Hmrc',
  'grand.fathered' => 'Grand Fathered',
  'gridview.api' => 'API',
  'gridview.create.at' => 'Created at',
  'gridview.login' => 'Login',
  'gridview.profile' => 'Profile',
  'gridview.title' => 'List of users',
  'group' => 'Group',
  'group.add' => 'Group Add',
  'group.by' => 'Group by',
  'group.document.number' => 'Document Number Not Generated. Check Groups.',
  'group.form' => 'Group Form',
  'group.history' => 'Group History exists. Cannot delete',
  'grouping' => 'Grouping',
  'grouping.none' => 'No Grouping',
  'groups' => 'Groups',
  'guest.account.denied' => 'This account is not configured.'
    . ' Please contact the system administrator.',
  'guest.read.only' => 'Guest (Read Only)',
  'guest.url' => 'Guest URL',
  'hide.or.unhide.columns' => 'Hide or unhide columns',
  'hint.greater.than.zero.please' => 'Greater than 0.00 please!',
  'hint.this.field.is.not.required' => 'This field is not required',
  'hint.this.field.is.required' => 'This field is required',
  'home' => 'Home',
  'home.caption.slide1' => 'Signup and Login'
    . ' as administrator. No internet ...'
    . ' ignore email error connection.',
  'home.caption.slide2' => 'As administator,'
    . ' signup a user. Email account is legit and'
    . ' internet connection ... verify.'
    . ' User will get client account.',
  'home.caption.slide3' => 'Email account not legit,'
    . ' and no internet connection ... admin'
    . ' log in and user\'s Invoice User Account make'
    . ' active under Settings.'
    . ' Create client account. Assign it to user',
  'home.caption.slides.location.debug.mode' =>
    'This location of content: ./resources/views/site/index.php'
    . ' within ./resources/views/layout/.  ... and'
    . ' translation slide location ./resources/messages/app.php',
  'hostname' => 'Hostname',
  'html.sumex.no' => 'Html without Sumex',
  'html.sumex.yes' => 'Html with Sumex',
  'id' => 'ID',
  'identifier.format' => 'Identifier formatting',
  'identifier.format.template.tags' =>
    'Template tags for the Identifier',
  'identifier.format.template.tags.instructions' =>
    'Template tags can be used to add dynamic'
    . ' information like the client name'
    . ' or an invoice number to the email template.'
    . ' Click on the <b>Identifier formatting</b>'
    . ' field and then select a tag from the drop down.'
    . ' It will be automatically inserted into the textfield.',
  'identity.provider.authentication.successful' =>
    'You have been successfully authenticated'
    . ' through your chosen Identity Provider,'
    . ' signed up, and allocated a client account.'
    . ' Click here within the next hour to'
    . ' make your account active. You have an'
    . ' hour to first-time login.',
  'image.overdue' => '  Location of image:'
    . ' ./invoice/public/img/overdue.png',
  'image.paid' => '  Location of image:'
    . ' ./invoice/public/img/paid.png',
  'import' => 'Import',
  'import.data' => 'Import Data',
  'import.from.csv' => 'Import from CSV',
  'in.progress' => 'In progress',
  'inactive' => 'Inactive',
  'index.checkbox.add.some.items.to.enable' =>
    'Invoice has no items. Add items to enable checkbox',
  'index.footer.showing' => 'Showing %s out of %s',
  'index.showing' => 'Showing %s out of %s',
  'info.task.readonly' => 'This task cannot'
    . ' be altered anymore because it is already invoiced.',
  'install.test.data' => 'Test data can now be installed',
  'install.test.data.exists.already' => 'Debug mode'
    . ' (Non-production) '
    . 'ON: Invoice Test Data exists already.'
    . ' This message will disappear if in production.',
  'install.test.data.goto.tab.index' =>
    'Goto Settings ... General ... Install Test Data',
  'interface' => 'Interface',
  'invalid.amount' => 'Invalid Amount',
  'invalid.subscriber.number' => 'Invalid Subscriber Number',
  'invoice' => 'Invoice',
  'invoice.created.from.quote' => 'Invoice created from quote',
  'invoice.validation.errors' => 'Invoice Validation Errors',
  'invoiced' => 'Invoiced',
  'invoiceplane' => 'InvoicePlane',
  'invoiceplane.clients' => 'Clients inserted',
  'invoiceplane.database.name' => 'Database Name',
  'invoiceplane.database.password' => 'Password',
  'invoiceplane.database.username' => 'Username',
  'invoiceplane.families' => 'Families inserted',
  'invoiceplane.import' => 'Test Connection',
  'invoiceplane.import.complete.connection.closed' =>
    'Import complete! Connection closed!',
  'invoiceplane.import.connected' => 'You have a Connection!',
  'invoiceplane.import.proceed' =>
    'Proceed with the import process',
  'invoiceplane.import.proceed.alert' =>
    'Are you sure you want to proceed?',
  'invoiceplane.imported' => 'Tables have been imported',
  'invoiceplane.news' => 'InvoicePlane News',
  'invoiceplane.no.connection' => 'No Connection',
  'invoiceplane.no.username.or.password' =>
    'Please save your Database name, Username,'
    . ' and Password for InvoicePlane',
  'invoiceplane.products' => 'Products inserted',
  'invoiceplane.tables' =>
    'Import InvoicePlane Tables Client, Product,
        Unit, Family, TaxRate into blank tables.
There should be no existing data in these tables
prior to importing and no existing documents e.g. Invoices.',
  'invoiceplane.tables.not.empty' =>
    'Your Client, Product, Unit, Family,'
    . ' and TaxRate Tables must be empty before importing',
  'invoiceplane.taxrates' => 'Tax Rates inserted'
    . ' with mandatory Zero Tax Rate'
    . ' and Standard Tax Rate.',
  'invoiceplane.units' => 'Units inserted',
  'invoiceplane.yes.connection' => 'Yes You have'
    . ' a Connection to your Invoiceplane database!',
  'invoices' => 'Invoices',
  'invoices.due.after' => 'Invoices Due After (Days)',
  'is.not.writable' => 'is not writable',
  'is.writable' => 'is writable',
  'item' => 'Item',
  'item.add' => 'Item Add',
  'item.allowance' => 'Item Allowance',
  'item.charge' => 'Item Charge',
  'item.date' => 'Item Date',
  'item.discount' => 'Item Discount',
  'item.lookup' => 'Item Lookup',
  'item.lookup.form' => 'Item Lookup Form',
  'item.lookups' => 'Item Lookups',
  'item.name' => 'Item Name',
  'item.tax' => 'Item Tax',
  'item.tax.excluded' => 'SubTotal x Tax'
    . ' Percentage (Item Tax has been excluded)',
  'item.tax.included' => '(SubTotal + Item Tax)'
    . ' x Tax Percentage',
  'item.tax.rate' => 'Item Tax Rate',
  'items' => 'Items',
  'judgement' => 'Judgment Obtained',
  'label' => 'Label',
  'label.switch.off' => 'VAT Invoice',
  'label.switch.on' => 'NON VAT Invoice',
  'language' => 'Language',
  'last' => 'Last',
  'last.month' => 'Last Month',
  'last.quarter' => 'Last Quarter',
  'last.year' => 'Last Year',
  'layout.add' => 'Add',
  'layout.add.post' => 'Add post',
  'layout.add.random-content' => 'Add random content',
  'layout.add.tag' => 'Add tag',
  'layout.archive' => 'Archive',
  'layout.archive.for-year' => 'Archive for {year}',
  'layout.blog' => 'Blog',
  'layout.change-language' => 'Change language',
  'layout.console' => 'Console',
  'layout.content' => 'Content',
  'layout.create.new-user' => 'Create new user',
  'layout.db.schema' => 'DB Schema',
  'layout.go.home' => 'Go Back Home',
  'layout.login' => 'Login',
  'layout.migrations' => 'Migrations',
  'layout.no-records' => 'No records',
  'layout.not-found' => 'Not found',
  'layout.page.not-authorised' => 'Not Authorised:'
    . ' Authentication credentials are incorrect.',
  'layout.page.not-found' => 'The page {url}'
    . ' could not be found.',
  'layout.page.user-cancelled-oauth2' => 'User'
    . ' Cancelled Logging in / Registering'
    . ' via Identity Provider e.g Facebook',
  'layout.pagination-summary' => 'Showing'
    . ' {pageSize} out of {total} posts',
  'layout.password' => 'Password',
  'layout.password-verify' => 'Confirm your password',
  'layout.password-verify.new' => 'Confirm your new password',
  'layout.password.new' => 'New Password',
  'layout.password.otp.6' => 'OTP Password (6 digits)',
  'layout.password.otp.6.8' => 'OTP Password (6 digits)'
    . ' / Backup Recovery Codes (8 digits)',
  'layout.rbac.assign-role' => 'Assign RBAC role to user',
  'layout.remember' => 'Remember me',
  'layout.reset' => 'Reset',
  'layout.show-more' => 'show more',
  'layout.submit' => 'Submit',
  'layout.title' => 'Title',
  'layout.total.posts' => 'Total {count} posts',
  'left.pad' => 'Left Pad',
  'letter' => '7 day Letter Before Action sent',
  'loading.error' => 'It seems that the'
    . ' application stuck because of an error.',
  'loading.error.help' => 'Get Help',
  'login' => 'Login',
  'login.logo' => 'Login Logo',
  'loginalert.credentials.incorrect' => 'Email'
    . ' or Password incorrect.',
  'loginalert.no.password' => 'Please enter a password.',
  'loginalert.user.inactive' => 'This user is'
    . ' marked as inactive. Please contact'
    . ' the system administrator.',
  'loginalert.user.not.found' => 'There is no'
    . ' account registered with this'
    . ' Email address.',
  'loginalert.wrong.auth.code' => 'Password reset'
    . ' denied. You provided an'
    . ' invalid auth token.',
  'logo' => 'Invoice Logo',
  'logout' => 'Logout',
  'loss' => 'Written off',
  'mark.invoices.sent.copy' => 'Mark invoices'
    . ' as sent when copying an invoice',
  'mark.invoices.sent.pdf' => 'Mark invoices'
    . ' as sent when PDF is generated',
  'mark.quotes.sent.pdf' => 'Mark quotes'
    . ' as sent when PDF is generated',
  'mark.sent.copy.on' => 'Mark invoices'
    . ' as sent when copying an Invoice'
    . ' is On. It should only be on during development',
  'mark.sent.off' => 'Mark invoices'
    . ' as sent on copying invoices '
    . '... is currently OFF. '
    . 'Only set to ON during development. '
    . 'Click here to turn it ON',
  'mark.sent.on' => 'Mark invoices'
    . ' as sent on copying invoices '
    . '... is currently ON. '
    . 'Always have it on OFF during production.'
    . ' Click here to turn it OFF',
  'max' => 'Max',
  'max.quantity' => 'Maximum Quantity',
  'menu' => 'Menu',
  'menu.about' => 'About',
  'menu.accreditations' => 'Accreditations',
  'menu.blog' => 'Blog',
  'menu.comments-feed' => 'Comments Feed',
  'menu.contact.details' => 'Contact Details',
  'menu.contact.soon' => 'Thank you for contacting us,'
    . ' we\'ll get in touch with'
    . ' you as soon as possible.',
  'menu.contact.us' => 'Contact Us',
  'menu.gallery' => 'Gallery',
  'menu.language' => 'Language',
  'menu.login' => 'Login',
  'menu.logout' => 'Logout ({login})',
  'menu.pricing' => 'Pricing',
  'menu.privacy.policy' => 'Privacy Policy',
  'menu.signup' => 'Signup',
  'menu.swagger' => 'Swagger',
  'menu.team' => 'Team',
  'menu.terms.of.service' => 'Terms of Service',
  'menu.testimonial' => 'Testimonial',
  'menu.users' => 'Users',
  'merchant' => 'Merchant',
  'merchant.add' => 'Merchant Add',
  'merchant.driver' => 'Driver',
  'merchant.reference' => 'Reference',
  'merchant.response' => 'Response',
  'min.quantity' => 'Minimal Quantity',
  'mobile' => 'Mobile',
  'mobile.number' => 'Mobile Number',
  'monday' => 'Monday',
  'monospaced.font.for.amounts' =>
    'Use a Monospace font for amounts',
  'month' => 'Month',
  'month.prefix' => 'Month Prefix',
  'mpdf' => 'Mpdf',
  'mpdf.allow.charset.conversion' =>
    'Allow Character-set Conversion',
  'mpdf.auto.arabic' => 'Auto Arabic',
  'mpdf.auto.language.to.font' => 'Auto Language to Font',
  'mpdf.auto.script.to.lang' => 'Auto Script to Language',
  'mpdf.auto.vietnamese' => 'Auto Vietnamese',
  'mpdf.cjk' => 'Chinese, Japanese, Korean Font',
  'mpdf.ltr' => 'Left to right',
  'mpdf.show.image.errors' => 'Show image errors',
  'mtd' => 'Making Tax Digital',
  'mtd.fph' => 'Fraud Prevention Headers',
  'mtd.fph.all.valid' => 'All Fraud Prevention Headers Valid',
  'mtd.fph.generate' => 'Generate',
  'mtd.fph.no.provided' => 'No Fraud Prevention Headers Provided',
  'mtd.fph.record.alert' =>
    'Are you sure you want to record'
    . ' new Fraud Prevention Header details?'
    . ' Previous details will be overwritten if saved!',
  'mtd.fph.screen.timestamp' => 'Timestamp',
  'mtd.fph.some.advisories' =>
    'At least one header is potentially invalid',
  'mtd.fph.some.invalid' => 'At least one header is invalid',
  'mtd.gov.client.browser.do.not.track' =>
    'A value that indicates whether'
    . ' the Do Not Track option is turned on in the browser',
  'mtd.gov.client.browser.do.not.track.eg' => 'e.g. false',
  'mtd.gov.client.browser.js.user.agent' => 'Gov-Client-Browser-JS-User-Agent',
  'mtd.gov.client.browser.js.user.agent.eg' => 'e.g. Mozilla/5.0 '
    . '(iPad; U; CPU OS 3 2.1 like Mac OS X; en-us) '
    . '(KHTML, like Gecko) Mobile/7B405',
  'mtd.gov.client.browser.plugins' => 'A list of browser'
    . ' plug-ins on the originating device',
  'mtd.gov.client.browser.plugins.eg' => 'e.g. Shockwave%20F1ash'
    . ' Chromium%20PDF%20Viewer',
  'mtd.gov.client.connection.method' => 'Gov-Client-Connection-Method',
  'mtd.gov.client.device.id' => 'Device Id',
  'mtd.gov.client.device.id.eg' => 'e.g. beec798b-b366-47fa-b1f8-92cede14a1ce',
  'mtd.gov.client.local.ips' => 'Local Ips',
  'mtd.gov.client.multi.factor' => 'Multi Factor',
  'mtd.gov.client.multi.factor.eg' => 'e.g.'
    . ' type=OTP;status=success,type=SMS;'
    . 'status=failure,type=Biometric, type=SMS;'
    . ' timestamp=2023-04-01T12:34:56Z;'
    . ' unique-reference=abc123xyz',
  'mtd.gov.client.multi.factor.otp' => 'One Time Password (OTP): ',
  'mtd.gov.client.public.ip' => 'Public Ip',
  'mtd.gov.client.public.port' => 'Public Port'
    . ' (not http 80, and not https 443 e.g 57961)',
  'mtd.gov.client.screens' => 'Gov-Client-Screens',
  'mtd.gov.client.screens.colour.depth' => 'Colour Depth',
  'mtd.gov.client.screens.height' => 'Screen Height',
  'mtd.gov.client.screens.pixels' => 'pixels',
  'mtd.gov.client.screens.scaling.factor' => 'Scaling Factor',
  'mtd.gov.client.screens.scaling.factor.bits' => 'bits',
  'mtd.gov.client.screens.width' => 'Screen Width',
  'mtd.gov.client.timezone' => 'Timezone',
  'mtd.gov.client.user.ids' => 'User Ids',
  'mtd.gov.client.user.ids.uuid' => 'uuid',
  'mtd.gov.client.window.size' => 'Window Size',
  'mtd.gov.client.window.size.pixels' => 'pixels',
  'mtd.gov.vendor.forwarded' => 'A list that details'
    . ' the hops over the internet between services'
    . ' that terminate Transport Layer Security (TLS)',
  'mtd.gov.vendor.license.ids' => 'A key-value data'
    . ' structure of hashed license keys'
    . ' that are related to the vendor'
    . ' software that initiated the API'
    . ' request on the originating device',
  'mtd.gov.vendor.public.ip' => 'The public IP address'
    . ' of the servers that the originating'
    . ' device sent its requests to',
  'mtd.gov.vendor.version' => 'A key-value data structure'
    . ' of the software versions that'
    . ' are involved in handling a request',
  'multiple.choice' => 'Multiple Choice',
  'name' => 'Name',
  'new' => 'New',
  'new.password' => 'New password',
  'new.product' => 'New product',
  'new.task' => 'New task',
  'next' => 'Next',
  'next.date' => 'Next Date',
  'next.id' => 'Next ID',
  'no' => 'No',
  'no.attachments' => 'No attachments',
  'no.client' => 'No client',
  'no.file.uploaded' => 'No file uploaded',
  'no.overdue.invoices' => 'No overdue Invoices',
  'no.quotes.requiring.approval' => 'There are no quotes requiring approval.',
  'no.records' => 'No records',
  'no.updates.available' => 'No updates available.',
  'none' => 'None',
  'not.available' => 'Not available',
  'not.found' => 'Invoice not found',
  'not.set' => '⌛',
  'not.started' => 'Not started',
  'note' => 'Note',
  'notes' => 'Notes',
  'number' => '#',
  'number.format' => 'Number Format',
  'number.format.compact.comma' =>
    '1000000,00 (Compact format with decimal comma)',
  'number.format.compact.point' =>
    '1000000.00 (Compact format with decimal point)',
  'number.format.european' =>
    '1.000.000,00 (European format)',
  'number.format.iso.80k.1' =>
    '1 000 000.00 (ISO 80000-1)',
  'number.format.iso80k1.comma' =>
    '1 000 000,00 (ISO 80000-1 with decimal comma)',
  'number.format.iso80k1.point' =>
    '1 000 000.00 (ISO 80000-1 with decimal point)',
  'number.format.us.uk' =>
    '1,000,000.00 (US/UK format)',
  'number.missing.therefore.use.invoice.id' =>
    'invoice-number-missing-therefore-invoice-id-',
  'number.no' => 'No Invoice Number',
  'oauth2' => 'OAuth 2.0',
  'oauth2.account.locked' => 'Account Locked',
  'oauth2.backup.recovery.codes' =>
    'Backup recovery codes. Keep in a safe place.',
  'oauth2.backup.recovery.codes.regenerate' =>
    'Regenerate Backup Recovery Codes',
  'oauth2.consent.required' => 'Consent required',
  'oauth2.default' => 'Default oauth2.0 configuration used',
  'oauth2.google.people.api.v1.client.id' => 'Client Id',
  'oauth2.google.people.api.v1.client.secret' => 'Client Secret',
  'oauth2.invalid.request' => 'Invalid Request',
  'oauth2.invalid.client' => 'Invalid Client',
  'oauth2.invalid.grant' => 'Invalid Grant',
  'oauth2.login.required' => 'Login required',
  'oauth2.missing.authentication.code.or.state.parameter' =>
    'Missing authentication code or state parameter.',
  'oauth2.missing.state.parameter.possible.csrf.attack' =>
    'State Parameter missing. Possible csrf attack',
  'oauth2.server.error' => 'Server error',
  'oauth2.temporarily.unavailable' => 'Temporarily unavailable',
  'oauth2.test.user.creation.not.allowed.prod.env' =>
    'Test user creation not allowed in production environment.',
  'oauth2.unauthorized.client' => 'Unauthorized client',
  'oauth2.unsupported.response.type' => 'Unsupported Response Type',
  'oauth2.unsupported.grant.type' => 'Unsupported Grant Type',
  'online.log' => 'Online Log',
  'online.payment' => 'Online Payment',
  'online.payment.3dauth.redirect' =>
    'Please wait while we redirect you'
    . ' to your card issuer for authentication...',
  'online.payment.accessKey' => 'Access Key',
  'online.payment.accessToken' => 'Access Token',
  'online.payment.accountId' => 'Account Id',
  'online.payment.accountNumber' => 'Account Number',
  'online.payment.apiKey' => 'Api Key',
  'online.payment.apiLoginId' => 'Api Login Id',
  'online.payment.appId' => 'App Id',
  'online.payment.appSecret' => 'App Secret',
  'online.payment.callbackPassword' => 'Callback Password',
  'online.payment.card.invalid' => 'This credit card is'
    . ' invalid. Please check the provided information.',
  'online.payment.clientId' => 'Client Id',
  'online.payment.clientSecret' => 'Client Secret',
  'online.payment.creditcard.hint' => 'If you want to pay'
    . ' via credit card please enter'
    . ' the information below.<br/>The'
    . ' credit card information are not'
    . ' stored on our servers and will be'
    . ' transferred to the online payment'
    . ' gateway using a secure connection.',
  'online.payment.developerMode' => 'Developer Mode',
  'online.payment.for' => 'Online Payment for',
  'online.payment.for.invoice' => 'Online Payment for Invoice',
  'online.payment.installationId' => 'Installation Id',
  'online.payment.merchantAccessCode' => 'Merchant Access Code',
  'online.payment.merchantId' => 'Merchant Id',
  'online.payment.merchantKey' => 'Merchant Key',
  'online.payment.method' => 'Online Payment Method',
  'online.payment.partner' => 'Partner',
  'online.payment.partnerID' => 'Partner ID',
  'online.payment.password' => 'Password',
  'online.payment.payment.cancelled' => 'Payment cancelled.',
  'online.payment.payment.failed' => 'Payment failed. Please try again.',
  'online.payment.payment.redirect' =>
    'Please wait while we redirect you to the payment page...',
  'online.payment.payment.successful' => 'Payment for Invoice %s successful!',
  'online.payment.pdtKey' => 'Pdt Key',
  'online.payment.privateKey' => 'Private Key',
  'online.payment.profileID' => 'Profile ID',
  'online.payment.profileId' => 'Profile Id',
  'online.payment.publicKey' => 'Public Key',
  'online.payment.publicKeyId' => 'Public Key Id',
  'online.payment.publishableKey' => 'Publishable Key',
  'online.payment.pxPostPassword' => 'Px Post Password',
  'online.payment.pxPostUsername' => 'Px Post Username',
  'online.payment.referrerId' => 'Referrer Id',
  'online.payment.region' => 'Region',
  'online.payment.returnUrl' => 'Return Url',
  'online.payment.sandbox' => 'Sandbox',
  'online.payment.sandboxId' => 'Sandbox Id',
  'online.payment.secret' => 'Secret',
  'online.payment.secretKey' => 'Secret Key',
  'online.payment.secretWord' => 'Secret Word',
  'online.payment.secureHash' => 'Secure Hash',
  'online.payment.sharedSecret' => 'Shared Secret',
  'online.payment.signature' => 'Signature',
  'online.payment.siteCode' => 'Site Code',
  'online.payment.siteId' => 'Site Id',
  'online.payment.storeId' => 'Store Id',
  'online.payment.storePassword' => 'Store Password',
  'online.payment.subAccountId' => 'Sub Account Id',
  'online.payment.testMode' => 'Test Mode',
  'online.payment.testOrLiveApiKey' =>
    'Test or Live Api Key i.e starts with test_ or live_',
  'online.payment.apiToken' => 'Api Token',
  'online.payment.thirdPartyProvider' =>
    'Third Party Provider e.g. Wonderful',
  'online.payment.transactionKey' => 'Transaction Key',
  'online.payment.transactionPassword' => 'Transaction Password',
  'online.payment.username' => 'Username',
  'online.payment.vendor' => 'Vendor',
  'online.payment.version' =>
    'Omnipay Version (checked) / '
    . 'PCI Compliant (No credit card details'
    . ' stored on this database) (unchecked)',
  'online.payment.webhookId' => 'Webhook Id',
  'online.payment.websiteKey' => 'Website Key',
  'online.payments' => 'Online Payments',
  'open.banking.pay.with' => 'Pay with Open Banking: ',
  'open.banking.not.configured' =>
    'Open Banking is not configured.'
    . ' Please contact support.',
  'open' => 'Open',
  'open.invoices' => 'Open Invoices',
  'open.quotes' => 'Open Quotes',
  'open.reports.in.new.tab' => 'Open Reports in a new Browser Tab',
  'optional' => 'Optional',
  'options' => 'Options',
  'order' => 'Order',
  'orm' => 'Orm',
  'other.settings' => 'Other Settings',
  'overdue' => 'Overdue',
  'overdue.invoices' => 'Overdue Invoices',
  'overview' => 'Invoice Overview',
  'overview.period' => 'Invoice Overview Period',
  'page' => 'Page',
  'paid' => 'Paid',
  'password' => 'Password',
  'password.change' => 'Change Password',
  'password.changed' => 'Password successfully changed',
  'password.reset' => 'Reset Password',
  'password.reset.email' =>
    'You requested a new password for your'
    . ' installation. Please click the link'
    . ' in your inbox to reset your password.',
  'password.reset.failed' => 'An error occurred'
    . ' while trying to send your password reset email.'
    . ' Please review the application logs'
    . ' or contact the system administrator.',
  'password.reset.info' => 'You will get an Email'
    . ' with a link to reset your password.',
  'password.reset.request.token' => 'Request Password Reset Token',
  'past.month' => 'Past Month',
  'past.quarter' => 'Past Quarter',
  'past.year' => 'Past Year',
  'pay.now' => 'Pay Now',
  'payment' => 'Payment',
  'payment.add' => 'Payment Add',
  'payment.cannot.delete' => 'Cannot delete payment',
  'payment.cannot.exceed.balance' => 'Payment amount'
    . ' cannot exceed invoice balance.',
  'payment.custom' => 'Payment Custom',
  'payment.custom.add' => 'Payment Custom Add',
  'payment.date' => 'Payment Date',
  'payment.deleted' => 'Payment Deleted',
  'payment.description' => 'Payment for Invoice %s',
  'payment.form' => 'Payment Form',
  'payment.gateway.default.locale' =>
    'Default Locale e.g en.GB',
  'payment.gateway.mollie.api.key.has.been.setup' =>
    'Mollie Payment Gateway Test '
    . 'or Live Api Key has been setup.',
  'payment.gateway.mollie.api.key.needs.to.be.setup' =>
    'Mollie Payment Gateway Test '
    . 'or Live Api Key needs to be setup.',
  'payment.gateway.mollie.api.payment.id' => 'Mollie Payment Id: ',
  'payment.gateway.no' => 'No payment gateways'
    . ' have been setup under Settings ... View ... Online Payment',
  'payment.history' => 'Payment History',
  'payment.information.amazon.no.omnipay.version' =>
    'There currrently is no Amazon Pay Omnipay Version.'
    . ' Uncheck Omnipay Version to use the '
    . 'PCI compliant version under Settings View',
  'payment.information.amazon.payment.session.complete' =>
    'Amazon Payment Session Complete - Session Id: ',
  'payment.information.amazon.payment.session.incomplete' =>
    'Amazon Payment Session Incomplete - Please Try Again',
  'payment.information.braintree.braintree.no.omnipay.version' =>
    'There currrently is no Braintree Omnipay Version'
    . ' compatible with Braintree Version 6.9.1.'
    . ' Uncheck Omnipay Version to use the'
    . ' PCI compliant version under Settings View',
  'payment.information.invoice.number.not.provided' =>
    'Invoice number has not been provided',
  'payment.information.no.information.provided' =>
    'No Information has been provided',
  'payment.information.none' => 'None',
  'payment.information.omnipay.driver.being.used' =>
    'A driver {{$d}} from Omnipay is being used.',
  'payment.information.payment.method.required' =>
    'A payment method is required for this invoice.',
  'payment.information.stripe.api.key' =>
    'Stripe Payment Gateway Secret Key / Api Key needs to be setup.',
  'payment.logs' => 'Payment Logs',
  'payment.method' => 'Payment Method',
  'payment.method.add' => 'Payment Method Add',
  'payment.method.already.exists' => 'Payment Method already exists!',
  'payment.method.form' => 'Payment Method Form',
  'payment.method.history' =>
    'Cannot delete. Payment Method history exists.',
  'payment.methods' => 'Payment Methods',
  'payment.no.invoice.sent' =>
    'No invoices have been sent'
    . ' by us or viewed by the customer.',
  'payment.provider' => 'Payment Provider',
  'payment.term' => 'Payment Terms',
  'payment.term.0.days' =>
    'Please use one of the payment methods provided',
  'payment.term.add.additional.terms.at.setting.repository' =>
    'Add Additional Terms at'
    . ' Setting Repository getPaymentTerms function.',
  'payment.term.eom.120.days' =>
    'EOM(120): Please pay within 120 days End of Month of issue date',
  'payment.term.eom.15.days' =>
    'EOM(15): Please pay within 15 days of End of Month of issue date',
  'payment.term.eom.30.days' =>
    'EOM(30): Please pay within 30 days of End of Month of issue date',
  'payment.term.eom.60.days' =>
    'EOM(60): Please pay within 60 days of End of Month of issue date',
  'payment.term.eom.90.days' =>
    'EOM(90): Please pay within 90 days of End of Month of issue date',
  'payment.term.general' => 'Payment due within 30 days',
  'payment.term.mfi.15' => 'MFI(15):'
    . ' Please pay on the 15th'
    . ' of the Month Following the Issue-date-month',
  'payment.term.net.120.days' => 'Net(120):'
    . ' Please pay within 120 days of issue date',
  'payment.term.net.15.days' => 'Net(15):'
    . ' Please pay within 15 days of issue date.',
  'payment.term.net.30.days' => 'Net(30):'
    . ' Please pay within 30 days of issue date',
  'payment.term.net.60.days' => 'Net(60):'
    . ' Please pay within 60 days of issue date',
  'payment.term.net.90.days' => 'Net(90):'
    . ' Please pay within 90 days of issue date',
  'payment.term.pia' => 'Payment is required in Advance (PIA)',
  'payment.term.polite' => 'We appreciate your business.'
    . ' Please send your payment'
    . ' within 30 days of receiving this invoice.',
  'payment.terms.default' => 'Pay within 14 days',
  'paymentpeppol' => 'Payments made through Peppol',
  'paymentpeppol.reference.plural' =>
    'Payment References using Peppol',
  'payments' => 'Payments',
  'paymentterm' => 'Payment Term',
  'pdf' => 'PDF',
  'pdf.archived.no' => 'Pdf NOT Archived'
    . ' at Uploads/Archive/Invoice',
  'pdf.archived.yes' => 'Pdf Archived'
    . ' at Uploads/Archive/Invoice',
  'pdf.include.zugferd' => 'Include ZUGFeRD',
  'pdf.include.zugferd.help' => 'Enabling this option'
    . ' will include ZUGFeRD XML'
    . ' in invoice PDFs, which is '
    . 'an XML standard for invoices. '
    . '<a href="https://www.ferd-net.de/">More information</a>',
  'pdf.invoice.footer' => 'PDF Footer',
  'pdf.invoice.footer.hint' =>
    'You can enter any HTML here which'
    . ' will be displayed on the bottom'
    . ' of your PDF invoices.',
  'pdf.modal' => 'Modal Pdf',
  'pdf.quote.footer' => 'Quote footer',
  'pdf.quote.footer.hint' => 'You can enter '
    . 'any HTML here which will be displayed'
    . ' on the bottom of your PDF quotes.',
  'pdf.settings' => 'PDF Settings',
  'pdf.template' => 'PDF Template',
  'pdf.template.overdue' => 'Overdue PDF Template',
  'pdf.template.paid' => 'Paid PDF Template',
  'pdf.watermark' => 'Enable PDF Watermarks',
  'peppol' => 'Peppol Universal Business Language'
    . ' (UBL) 2.1 Invoice - Ecosio Validated',
  'peppol.abbreviation' => 'Peppol',
  'peppol.allowance.or.charge.inherit.inv' =>
    'Invoice Allowance Charges and Invoice'
    . ' Item Allowance Charges inherit from a'
    . ' completed '
    . 'Peppol Document Level Allowance Charge',
  'peppol.allowance.or.charge.inherit.quote' =>
    'Quote Allowance Charges and Quote'
    . ' Item Allowance Charges inherit from a'
    . ' completed '
    . 'Peppol Document Level Allowance Charge',
  'peppol.client.check' => 'Peppol details'
    . ' relating to this client are insufficient.'
    . ' At least one is missing.'
    . ' Refer to View '
    . '... Client '
    . '... Options '
    . '... Edit Peppol details for e-invoicing',
  'peppol.client.defaults' =>
    'Fill Client Peppol Form'
    . ' with OpenPeppol defaults for testing.',
  'peppol.currency.code.from' =>
    'From Currency ie. '
    . 'Country of Origin Tax Currency'
    . ' (To change see '
    . 'config/common/params.php TaxCurrencyCode)',
  'peppol.currency.code.to' =>
    'To Currency ie. Document Currency:'
    . ' see function get.setting(\'currency.code.to\')',
  'peppol.currency.from.to' =>
    'One of From Currency today'
    . ' converts to this of To Currency',
  'peppol.currency.to.from' =>
    'One of To Currency today converts to this of From Currency',
  'peppol.document.reference.null' => 'inv-number-null-inv-id',
  'peppol.ecosio.validator' =>
    'Ecosio Validator for OpenPeppol'
    . ' UBL Invoice (3.15.0) (aka BIS Billing 3.0.14)',
  'peppol.electronic.invoicing' =>
    'Peppol Electronic Invoicing',
  'peppol.enable' =>
    'Enable Peppol using'
    . ' Universal Business Language (UBL) 2.1',
  'peppol.include.delivery.period' => 'Include Delivery Periods',
  'peppol.invoice.note.not.found' => 'Invoice note not found',
  'peppol.label.switch.off' => 'Peppol Defaults Enabled',
  'peppol.label.switch.on' => 'Peppol Defaults Disabled',
  'peppol.mandatory' => ' (Mandatory)',
  'peppol.optional' => ' (Optional)',
  'peppol.stand.in.code' =>
    'Description code -'
    . ' indicating what basis will be used'
    . ' for the future tax-point date'
    . ' when goods are supplied/paid.'
    . ' If a tax-point can be determined,'
    . ' the description code is mutually excluded'
    . ' in the Invoice Period.',
  'peppol.store.cove.1.1.1' => 'Register for API Access'
    . ' on the Store Cove website',
  'peppol.store.cove.1.1.2' => 'Create your API key'
    . ' from the Store Cove website'
    . ' and store in Settings'
    . '...View'
    . '...Online Payment'
    . '...Store Cove'
    . '...Api key',
  'peppol.store.cove.1.1.3' => 'Make your first API call'
    . ' to get JSON response.',
  'peppol.store.cove.1.1.4' => 'Send your first'
    . ' test Json invoice that you acquired'
    . ' from clicking on green button World=>DE',
  'peppol.stream.toggle' => 'Peppol - \'Stream\''
    . ' or \'Save to File\''
    . ' Toggle Button',
  'peppol.tax.category.not.found' =>
    'Peppol Tax Category code'
    . ' (https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/)'
    . ' missing.',
  'peppol.tax.category.percent.not.found' =>
    'Peppol Tax Category percent'
    . ' (https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/)'
    . ' missing.',
  'peppol.tax.rate.code' => 'Peppol Tax Rate Code',
  'peppol.trying.to.send.non.pdf.file' =>
    'Only pdf files are currently'
    . ' being sent with Peppol Invoices',
  'peppol.unit' => 'Unit Classification using Peppol',
  'peppol.validator.Invoice.cac.AdditionalDocumentReference.cbc.DocumentDescription' =>
    'Source: src/Invoice/Ubl/Invoice:'
    . ' Missing document description - Ecosio:'
    . ' Document MUST not contain empty elements.'
    . ' Solution: In Invoice View: '
    . 'Options '
    . '... Edit '
    . '... Document'
    . ' Description',
  'peppol.validator.Invoice.cac.Note' =>
    'Source: src/Invoice/Ubl/Invoice:'
    . '  Missing invoice note '
    . '- Ecosio: Document MUST not contain empty elements.',
  'peppol.validator.Invoice.cac.Party.cbc.EndPointID' =>
    'src/Invoice/Ubl/Party:'
    . ' Missing email address - Ecosio:'
    . ' Electronic Address MUST be provided. Solution:'
    . ' Client '
    . '... View '
    . '... Options '
    . '... Edit Peppol details for e-invoicing ... EndPointID',
  'peppol.version' => 'Peppol Version',
  'peppol.xml.stream' => 'Stream ie.'
    . ' show on screen in browser'
    . ' instead of downloads folder.',
  'per.item' => 'per Item',
  'per.page.total' => ' per page. Total ',
  'performance' => 'Performance',
  'performance.label.switch.off' =>
    'Faster Read and Write Mode '
    . '- refer to config/common/params.php search \'mode\' ',
  'performance.label.switch.on' =>
    'Slower Write Only Mode - '
    . 'refer to config/common/params.php'
    . ' search \'mode\' ',
  'period' => 'Period',
  'permission' => 'You do not have the required permission.',
  'personal.information' => 'Personal Information',
  'phone' => 'Phone',
  'phone.abbr' => 'P',
  'phone.number' => 'Phone Number',
  'php.timezone.fail' => 'There seems to be no'
    . ' timezone configured.'
    . ' Please check date.timezone'
    . ' in your php configuration.'
    . ' Otherwise <strong>%s</strong> will be selected.',
  'php.timezone.success' => 'A valid timezone is configured.',
  'php.version.fail' =>
    'PHP version %s is installed'
    . ' but InvoicePlane requires'
    . ' PHP version %s or higher',
  'php.version.success' => 'PHP appears to'
    . ' meet the installation requirement',
  'platform' => 'Platform',
  'platform.PhpMyAdmin' => 'PhpMyAdmin Version',
  'platform.PhpSupport' => 'Php Support',
  'platform.PhpVersion' => 'Php Version',
  'platform.csrf' => 'Cross Site Forgery Protection',
  'platform.editor' => 'Editor',
  'platform.mySqlVersion' => 'mySql Version',
  'platform.netbeans.UTF-8' => 'Netbeans UTF-8 encoding',
  'platform.server' => 'Server',
  'platform.sqlPath' => 'Sql Path',
  'platform.update' => 'WampServer Files and Addons',
  'platform.windowsVersion' => 'Windows 11 Home Edition',
  'platform.xdebug' => 'Xdebug Extension',
  'please.enable.js' => 'Please enable'
    . ' Javascript to use InvoicePlane',
  'po.group' => 'Purchase Order Group',
  'port' => 'Port',
  'position' => 'Position',
  'possible.file.upload.attack' => 'Possible file upload attack: ',
  'pre.password' => 'Invoice standard PDF password (optional)',
  'prefix' => 'Prefix',
  'prev' => 'Prev',
  'preview' => 'Preview',
  'price' => 'Price',
  'product' => 'Product',
  'product.add' => 'Product Add',
  'product.additional.item.property.name' =>
    'Peppol Additional Property Item Name eg. Colour',
  'product.additional.item.property.value' =>
    'Peppol Additional Property Item Value eg. Black',
  'product.country.of.origin.code' =>
    'Peppol Country of Origin Code using'
    . ' ISO-3166-1:Alpha2 Country Codes',
  'product.custom.fields' =>
    'Product Custom Fields '
    . '(eg. Peppol: AdditionalItemProperty multiple'
    . ' choice field colour value black)',
  'product.description' => 'Product description',
  'product.edit' => 'Edit Product',
  'product.error.summary' => 'Product Error Summary',
  'product.families' => 'Product families',
  'product.form.tab.category' => 'Category',
  'product.form.tab.not.required' => 'Not Required Fields',
  'product.form.tab.required' => 'Required Fields',
  'product.found' => 'Product found',
  'product.history' => 'Cannot delete.'
    . ' This product is on an invoice or quote.',
  'product.icc.id' => 'Peppol Item'
    . ' Classification Code ID   eg. 9873242',
  'product.icc.listid' => 'Peppol Item'
    . ' Classificaiton Code List id - eg. SRV',
  'product.icc.listversionid' => 'Peppol Item'
    . ' Classification Code List Version ID (Optional)'
    . ' - eg. If ItemClassification is provided'
    . ' from Danish suppliers, UNSPSC version '
    . '19.0501 should be used.',
  'product.id' => 'Product Id',
  'product.image' => 'Image',
  'product.item.classification.code.scheme.id.not.found' =>
    'Product Item Classification Code Scheme Id not found',
  'product.name' => 'Product name',
  'product.not.found' => 'Product not found',
  'product.peppol.unit' => 'Unit with Peppol',
  'product.price' => 'Price',
  'product.price.base.quantity' => 'qty in Product',
  'product.property' => ' Product Property',
  'product.property.add' => ' Product Property Add',
  'product.property.edit' => 'Product Property Edit',
  'product.property.index' => 'Product Property Index',
  'product.property.name' => 'Name',
  'product.property.table' => 'Product Property Table',
  'product.property.value' => 'Value',
  'product.record.successfully.added' =>
    'Product Record successfully added',
  'product.sii.id' => 'Peppol Standard Item Identification'
    . ' id associated with the above scheme - eg.'
    . ' 14 digit zero-padded identifier in'
    . ' Global Trade Item Number database'
    . ' (appearing under barcode)',
  'product.sii.schemeid' => 'Peppol Standard Item'
    . ' Identification schemeid - eg. 0160',
  'product.sku' => 'SKU',
  'product.tariff' => 'Tariff',
  'product.unit' => 'Product Unit',
  'product.unit.code.not.found' => 'Product does not'
    . ' have a unit Code associated with it.'
    . ' Product ... Edit ... Unit with Peppol.',
  'product.units' => 'Product Units',
  'product.view.tab.details' => 'Product Details',
  'product.view.tab.gallery' => 'Product Gallery',
  'product.view.tab.images' => 'Product Images',
  'product.view.tab.properties' => 'Product Properties',
  'productimage.add' => 'Add a Product Image',
  'productimage.deleted.from' => 'The image has been'
    . ' successfully deleted from'
    . ' the following directory: ',
  'productimage.form' => 'Product Image Form',
  'productimage.gallery' => 'Images relating to: ',
  'productimage.index' => 'Product Image Index',
  'productimage.list' => 'List of images associated'
    . ' with this product',
  'productimage.no.file.uploaded' => 'No image has been'
    . ' uploaded. Possible duplicate.',
  'productimage.plural' => 'Product Images',
  'productimage.possible.file.upload.attack' =>
    'Possible file upload attack: ',
  'productimage.upload' => 'Upload Product Image',
  'productimage.uploaded.to' => 'The image has been'
    . ' uploaded to the following directory: ',
  'productimage.view' => 'Multiple images relating'
    . ' to the product can be added under Product View',
  'products' => 'Products',
  'products.remaining.after.this' => 'Products remaining after this one',
  'products.form' => 'Product Form',
  'profile.deleted' => 'Profile has been deleted',
  'profile.history' => 'Cannot delete Profile. History exists',
  'profile.new' => 'Create a profile with a new email address,'
    . ' or mobile number, make it active,'
    . ' and select the company details you wish to link it to.'
    . ' This information will automatically appear'
    . ' on the documentation eg. quotes and invoices.',
  'profile.not.deleted' => 'Profile has not been deleted',
  'profile.plural' => 'Profiles',
  'profile.property.label.company' => 'Company',
  'profile.property.label.current' => 'Current',
  'profile.property.label.description' => 'Description',
  'profile.property.label.email' => 'Email',
  'profile.property.label.mobile' => 'Mobile',
  'profile.singular' => 'Profile',
  'project' => 'Project',
  'project.add' => 'Project Add',
  'project.name' => 'Project name',
  'projects' => 'Projects',
  'projects.form' => 'Projects',
  'properties' => 'Properties',
  'provider.name' => 'Provider Name',
  'provider.response' => 'Provider Response',
  'purchase.price' => 'Purchase price',
  'Q1' => 'Q1',
  'Q2' => 'Q2',
  'Q3' => 'Q3',
  'Q4' => 'Q4',
  'qr.absolute.url' => 'Absolute Url used to build above Qr Code',
  'qr.code' => 'QR Code',
  'qr.code.1' => 'QR Code 1',
  'qr.code.details' => 'QR Code Details',
  'qr.code.level.1' => 'Level of coding is Basic',
  'qr.code.level.2' => 'Level of coding is Intermediate',
  'qr.code.level.3' => 'Level of coding is Advanced',
  'qr.code.settings' => 'QR Code Settings',
  'qr.code.settings.bic' => 'BIC',
  'qr.code.settings.effect' =>
    'What effect do the above settings have on this Qr Code?',
  'qr.code.settings.effect.explanation' =>
    'In the actual code ... none.'
    . ' All the defaults are used because'
    . ' the very basic actual code above is used.',
  'qr.code.settings.enable' =>
    'Enable QR Code',
  'qr.code.settings.enable.hint' =>
    'Enabling this option'
    . ' will include a QR code for invoice PDFs.'
    . ' You have to fill out the recipient,'
    . ' IBAN and BIC below to work correctly.'
    . ' Otherwise the QR code will not be displayed.',
  'qr.code.settings.iban' => 'IBAN',
  'qr.code.settings.recipient' => 'Recipient',
  'qr.code.settings.remittance.text' => 'Remittance Text',
  'qr.code.settings.remittance.text.tags' => 'Remittance Text Tags',
  'qr.code.source' => 'Qr Code Source',
  'qr.code.source.path' =>
    'resources\\views\\invoice\\setting\\company.logo.and.address.php',
  'qr.code.type' => 'QR Code Type',
  'qr.code.type.absolute.url' => 'Absolute (the whole) Url Based',
  'qr.code.widget.used' => 'Widget used',
  'qr.ecc.level' => 'QR Code Ecc Level (Default: L)',
  'qr.height.and.width' => 'QR Code Height and Width (Default: 60)',
  'qr.meaning' => 'Meaning',
  'qr.meaning.benefit' =>
    'Scan and return to view '
    . '(without typing in url) '
    . 'where invoice can be printed'
    . ' in pdf format.'
    . ' Applicable to all users'
    . ' with active client account.',
  'qr.output.type' => 'QR Code Output Type'
    . ' (e.g. .svg, .png) (Default: png)',
  'qr.version' => 'QR Code Version Number'
    . ' (earliest 0 latest 40) (Default: 40)',
  'qty' => 'Qty',
  'quantity' => 'Quantity',
  'quarter' => 'Quarter',
  'quick.actions' => 'Quick Actions',
  'quote' => 'Quote',
  'quote.add' => 'Quote Add',
  'quote.amount' => 'Quote Amount',
  'quote.amount.add' => 'Quote Amount Add',
  'quote.amounts' => 'Quote Amounts',
  'quote.approve' => 'Approve',
  'quote.approved' => 'This quote has been approved',
  'quote.copied.to.invoice' => 'Quote copied to NEW Invoice',
  'quote.copied.to.quote' => 'Quote copied to NEW Quote!',
  'quote.copied.to.so' => 'Quote Copied to Sales Order',
  'quote.custom' => 'Quote Custom',
  'quote.custom.add' => 'Quote Custom Add',
  'quote.date' => 'Quote Date',
  'quote.dates' => 'Quote Dates',
  'quote.delete.not' => 'The invoice has not been deleted.',
  'quote.delivery.location.index.button.list' => 'Quotes',
  'quote.delivery.location.none' =>
    'No delivery location has been linked to this quote.',
  'quote.email.templates.not.configured' =>
    'Email templates not configured.'
    . ' Settings'
    . '...Quotes'
    . '...Quote Templates'
    . '...Default Email Template',
  'quote.group' => 'Quote Group',
  'quote.id' => 'Quote ID ',
  'quote.item' => 'Quote Item',
  'quote.item.add' => 'Quote Item Add',
  'quote.item.amount' => 'Quote Item Amount',
  'quote.item.amount.add' => 'Quote Item Amount Add',
  'quote.item.cannot.delete' => 'Cannot delete quote item',
  'quote.label.switch.off' => 'VAT Quote',
  'quote.label.switch.on' => 'NON VAT Quote',
  'quote.not.copied.to.invoice' => 
    'Invoice NOT created from Quote!'
    . ' Duplicate Invoice. '
    . 'Copy your Quote'
    . ' to another quote and then copy to invoice. '
    . 'Each quote must have a matching invoice.',
  'quote.number' => 'Quote Number',
  'quote.number.status' => 'Quote Number Status',
  'quote.overview' => 'Quote Overview',
  'quote.overview.period' => 'Quote Overview Period',
  'quote.password' => 'Quote PDF password (optional)',
  'quote.pre.password' => 'Quote standard PDF password (optional)',
  'quote.reject' => 'Reject',
  'quote.rejected' => 'This quote has been rejected',
  'quote.sales.order.created.from.quote' =>
    'Sales Order created from '
    . 'Quote and'
    . ' you entered your Purchase Order Number!',
  'quote.sales.order.not.created.from.quote' =>
    'Sales Order not created from Quote!'
    . ' Duplicate Sales Order.'
    . ' Copy your Quote to another quote'
    . ' and then copy to sales order.'
    . ' Each quote must have a matching sales order.',
  'quote.status.email.body' =>
    'The client %1$s has %2$s the quote %3$sLink to Quote: %4$s',
  'quote.status.email.subject' => 'Client %1$s %2$s quote %3$s',
  'quote.tax' => 'Quote Tax',
  'quote.tax.rate' => 'Quote Tax Rate',
  'quote.tax.rate.add' => 'Quote Tax Rate Add',
  'quote.tax.rate.cannot.delete' => 'Cannot delete Quote Tax Rate',
  'quote.tax.rate.incomplete.fields' =>
    'Incomplete fields:'
    . ' You must include a tax rate.'
    . ' Tip: Include a zero tax rate.',
  'quote.tax.rate.saved' => 'Quote Tax Rate has been saved',
  'quote.template' => 'Quote Template',
  'quote.templates' => 'Quote Templates',
  'quote.to.invoice' => 'Quote to Invoice',
  'quote.to.so' => 'Quote to Sales Order',
  'quote.to.so.password' => 'Sales Order Password',
  'quote.vat.quote' => 'VAT Quote',
  'quote.with.purchase.order.line.number' =>
    'Purchase Order Line Number'
    . ' (Peppol Requirement) '
    . '- to be matched '
    . 'with Sales Order Line Number',
  'quote.with.purchase.order.number' =>
    'Purchase Order Number'
    . ' - to be matched with Sales Order Number',
  'quote.with.purchase.order.person' =>
    'Person/Department placing the order',
  'quotes' => 'Quotes',
  'quotes.expire.after' => 'Quotes Expire After (Days)',
  'quotes.requiring.approval' => 'Quotes Requiring Approval',
  'radiolist.choice' => 'Radio List Choice',
  'read.only' => 'Read only',
  'read.this.please' => 'Read this please!',
  'reason' => 'Reason',
  'reason.accident' => 'Accident',
  'reason.birthdefect' => 'Birth defect',
  'reason.disease' => 'Disease',
  'reason.maternity' => 'Maternity',
  'reason.prevention' => 'Prevention',
  'reason.unknown' => 'Unknown',
  'recent.clients' => 'Recent Clients',
  'recent.invoices' => 'Recent Invoices',
  'recent.payments' => 'Recent Payments',
  'recent.quotes' => 'Recent Quotes',
  'record.successfully.created' =>
    'Record successfully created',
  'record.successfully.deleted' =>
    'Record successfully deleted',
  'record.successfully.updated' =>
    'Record successfully updated',
  'records.no' => 'No Records',
  'recurring' => 'Recurring',
  'recurring.add' => 'Recurring Add',
  'recurring.deleted' => 'Deleted',
  'recurring.frequency' => 'Frequency',
  'recurring.invoices' => 'Recurring Invoices',
  'recurring.no.invoices.selected' =>
    'You have not selected any invoices.',
  'recurring.original.invoice.date' =>
    'Original Invoice Date: ',
  'recurring.status.sent.only' =>
    'Only invoices with a status of sent can be made recurring',
  'recurring.tooltip.next' =>
    'The next date is set by means of the index.',
  'region' => 'Region',  
  'reject' => 'Reject',
  'reject.this.quote' => 'Reject This Quote',
  'rejected' => 'Rejected',
  'reminder' => 'Reminder sent',
  'remove' => 'Remove',
  'remove.logo' => 'Remove Logo',
  'report' => 'Report',
  'report.options' => 'Report Options',
  'report.sales.by.product' => 'Sales by Product',
  'report.sales.by.product.info' =>
    'This report'
    . ' gives the product sales total'
    . ' along with its item tax.'
    . ' It does not include additional'
    . ' invoice tax related to these products.',
  'report.sales.by.task' => 'Sales by Task',
  'report.sales.by.task.info' => 'This report'
    . ' gives the task sales total'
    . ' along with its item tax.'
    . ' It does not include additional invoice tax'
    . ' related to these tasks.',
  'report.test.fraud.prevention.headers.api' =>
    'Test Fraud Prevention Headers API',
  'reports' => 'Reports',
  'reset' => 'Reset',
  'reset.password' => 'Reset password',
  'rules.peppol.en16931.001' => 'Business Process'
    . ' or the Profile ID'
    . ' must be provided.'
    . ' Refer to config/common/params.php'
    . ' search ProfileID',
  'run.report' => 'Run Report',
  'sales' => 'Sales',
  'sales.by.client' => 'Sales by Client',
  'sales.by.date' => 'Sales by Date',
  'sales.with.tax' => 'Sales with Tax',
  'salesorder' => 'Sales Order',
  'salesorder.agree.to.terms' =>
    'Please agree to the'
    . ' Terms that will now be sent to you',
  'salesorder.assembled.packaged.prepared' =>
    'Assembled/Packaged/Prepared',
  'salesorder.cancelled' => 'Sales Order Cancelled',
  'salesorder.client.confirmed.terms' =>
    'Client Confirmed Terms',
  'salesorder.clients.purchase.order.number' =>
    'Client\'s Purchase Order Number',
  'salesorder.clients.purchase.order.person' =>
    'Client\'s Purchase Order Person Handling their order',
  'salesorder.copied.to.invoice' =>
    'Sales Order copied to Invoice',
  'salesorder.copied.to.invoice.not' => 'Invoice'
    . ' NOT created from Sales Order!'
    . ' Duplicate Invoice.'
    . ' Copy your Sales Order'
    . ' to another Sales Order'
    . ' and then copy to invoice.'
    . ' Each Sales Order'
    . ' must have a matching invoice',
  'salesorder.date.created' => 'Sales Order Date Created',
  'salesorder.default.group' => 'Sales Order Default Group',
  'salesorder.goods.services.confirmed' =>
    'Client Confirmed Delivery',
  'salesorder.goods.services.delivered' =>
    'Goods/Service Delivered',
  'salesorder.invoice' => 'Invoice',
  'salesorder.invoice.generate' => 'Invoice Generate',
  'salesorder.invoice.generated' => 'Invoice Generated',
  'salesorder.invoice.number' => 'Invoice Number',
  'salesorder.number' => 'Sales Order Number',
  'salesorder.number.status' => 'Sales Order No. Status',
  'salesorder.password' => 'Sales Order Password',
  'salesorder.payment.terms' => 'Sales Order Payment Terms eg.'
    . ' Please pay within 30 days',
  'salesorder.quote' => 'Quote',
  'salesorder.recent' => 'Recent Sales Orders',
  'salesorder.reject' => 'Sales Order Reject',
  'salesorder.rejected' => 'Sales Order Rejected',
  'salesorder.sent.to.customer' => 'Terms Agreement Required',
  'salesorder.to.invoice' => 'Sales Order to Invoice',
  'salesorder.vat.salesorder' => 'VAT Sales Order',
  'salesorders' => 'Sales Orders',
  'save' => 'Save',
  'save.item.as.lookup' => 'Save item as lookup',
  'script' => 'Script',
  'search.family' => 'Search Family',
  'search.product' => 'Search product',
  'security.disable.read.only.empty' =>
    'The disable read-only setting'
    . ' currently has neither a 0 or a 1 value.'
    . ' Legally set it to 0 by default '
    . 'so that a read-only function is available'
    . ' to prevent invoice deletion.',
  'security.disable.read.only.info' =>
    'Warning:'
    . ' Read Only Functionality'
    . ' for Invoice Protection and Deletion Prevention'
    . ' is enabled. '
    . 'Disable the \'disable.read.only\' to allow sent invoices to be reset to draft',
  'security.disable.read.only.success' =>
    'Success:'
    . ' Read Only Functionality for'
    . ' Invoice Protection and Deletion Prevention'
    . ' has been re-enabled',
  'security.disable.read.only.true.draft.check.and.mark' =>
    'Warning:'
    . ' You are editing a draft'
    . ' with the disable read-only setting on true',
  'security.disable.read.only.true.sent.check.and.mark' =>
    'Warning:'
    . ' You are editing a sent invoice'
    . ' with the disable read-only setting on true',
  'security.disable.read.only.warning' =>
    'Warning:'
    . ' Read Only Functionality'
    . ' for Invoice Protection and Deletion Prevention'
    . ' has been disabled',
  'select.existing.client' => 'Select the existing client',
  'select.family' => 'Select family',
  'select.payment.method' => 'Select the Payment Method',
  'select.project' => 'Select project',
  'select.unit' => 'Select unit',
  'send' => 'Send',
  'send.email' => 'Send Email',
  'sent' => 'Sent',
  'set.new.password' => 'Set a new password',
  'set.to.read.only' => 'Set the Invoice to read-only on',
  'setting' => 'Setting',
  'setting.add' => 'Setting Add',
  'setting.as.a.result.of ' => ' as a result of ',
  'setting.assets.cleared.at' => 'Assets cleared at ',
  'setting.assets.were.not.cleared.at' => 'Assets were not cleared at ',
  'setting.company' => 'Company Public Details',
  'setting.company.private' => 'Company Private Details',
  'setting.company.profile' => 'Changing Profile eg.'
    . ' mobile and email address',
  'setting.duplicate.key' => 'Could not complete the save.'
    . ' You have a duplicate with the following key: ',
  'setting.error.on.the.public.assets.folder' =>
    ' error on the public assets folder.',
  'setting.form' => 'Setting Form',
  'setting.key' => 'Setting Key',
  'setting.section' => 'Section',
  'setting.subsection' => 'Subsection',
  'setting.translator.key' => 'Translator Key',
  'setting.value' => 'Setting Value',
  'setting.you.have.cleared.the.cache' => 'You have cleared the cache.',
  'setting.you.have.not.cleared.the.cache.due.to.a' =>
    'You have not cleared the cache  to a ',
  'settings' => 'Settings',
  'settings.successfully.saved' => 'Settings successfully saved',
  'setup.create.user' => 'Create User Account',
  'setup.db.username.info' => 'Username associated with the database.',
  'showing.of' => 'Showing %s out of %s ',
  'signup' => 'Signup',
  'single.choice' => 'Single Choice',
  'site.soletrader.about.choose' =>
    'Here are some appealing reasons to choose us:',
  'site.soletrader.about.competitive.rates' => 'Competitive Rates',
  'site.soletrader.about.contemporary' => 'Contemporary skills',
  'site.soletrader.about.dissatisfaction' =>
    'In the event of service dissatisfaction'
    . ' we will redo the work free of charge.',
  'site.soletrader.about.finished' => 'Finished Projects',
  'site.soletrader.about.happy' => 'Happy Customers',
  'site.soletrader.about.quality' => 'Without sacrificing quality',
  'site.soletrader.about.return' => 'Return Customers',
  'site.soletrader.about.simply' => 'Simply pick up'
    . ' a phone and we will redo the work.',
  'site.soletrader.about.solved' => 'Issues Solved',
  'site.soletrader.about.trained' => 'Our team is well'
    . ' trained and experienced.',
  'site.soletrader.about.we' => 'We diligently apply'
    . ' our skills to the best of our ability.',
  'site.soletrader.about.willing' => 'Willing Return Support',
  'site.soletrader.contact.address' => 'Address',
  'site.soletrader.contact.email' => 'Email',
  'site.soletrader.contact.lookout' => 'We are always'
    . ' on the lookout'
    . ' to work with new clients.'
    . ' If you are interested in working with us,'
    . ' please get in touch in one of the following ways.',
  'site.soletrader.contact.phone' => 'Phone',
  'site.soletrader.contact.touch' => 'Get in touch',
  'site.soletrader.pricing.basic' => 'basic',
  'site.soletrader.pricing.choosePlan' => 'Choose Plan',
  'site.soletrader.pricing.currencyPerMonth' => 'per Month',
  'site.soletrader.pricing.explore' => 'Explore our'
    . ' flexible pricing'
    . ' to find an excellent fit'
    . ' to run your business.',
  'site.soletrader.pricing.plans' => 'More Plans',
  'site.soletrader.pricing.pricing' => ' Our Pricing',
  'site.soletrader.pricing.pro' => 'Professional',
  'site.soletrader.pricing.proPrice' => 'pro Price',
  'site.soletrader.pricing.special' => 'special',
  'site.soletrader.pricing.starter' => 'Starter',
  'site.soletrader.pricing.visits' => 'visits',
  'site.soletrader.team.assistant' => 'Assistant',
  'site.soletrader.team.coordinator' => 'Coordinator',
  'site.soletrader.team.we' =>
    'We are a group of caring,'
    . ' experienced,'
    . ' and diligent individuals.',
  'site.soletrader.testimonial.we' => 'These are the testimonials',
  'site.todays.date' => 'Today\'s date',
  'six.months' => 'Six Months',
  'smtp.mail.from' => 'SMTP Sender Address for system emails',
  'smtp.password' => 'SMTP Password',
  'smtp.port' => 'SMTP Port',
  'smtp.requires.authentication' => 'Requires Authentication',
  'smtp.security' => 'Security',
  'smtp.server.address' => 'SMTP Server Address',
  'smtp.ssl' => 'SSL',
  'smtp.tls' => 'TLS',
  'smtp.username' => 'SMTP Username',
  'smtp.verify.certs' => 'Verify SMTP certificates',
  'sql.file' => 'SQL File',
  'start' => 'Start',
  'start.date' => 'Start Date',
  'state' => 'State',
  'status' => 'Status',
  'stop' => 'Stop',
  'stop.logging.in' => 'Stop logging in',
  'stop.signing.up' => 'Stop signing up',
  'storecove' => 'Storecove',
  'storecove.advisory.to.developer.field.easily.missed'
    => 'Field easily missed by customer',
  'storecove.create.a.sender.legal.entity.country'
    => '1.1.4. Create a sender - Legal Entity Country',
  'storecove.invoice.json.encoded' =>
    'StoreCove Json Encoded Invoice',
  'storecove.legal' => 'Legal',
  'storecove.legal.entity.id.for.json' =>
    '1.1.4. Create a sender '
    . '- Store Cove Legal Entity Id inserted into invoice.json',
  'storecove.legal.entity.identifier.id.not.found' =>
    'Config params'
    . ' Accounting Supplier'
    . ' Party Legal Entity Company Id not found.',
  'storecove.no.contract.exists' => 'No contract exists',
  'storecove.not.available' => 'Not Available',
  'storecove.purchase.order.item.id.null' => 'po-item-id-null',
  'storecove.receiver.identifier' =>
    'Receiver Identifier - see StoreCove 6.3',
  'storecove.region.country.legal.tax' =>
    'Region ---------- Country --------- Legal --------- Tax',
  'storecove.salesorder.number.not.exist' =>
    'Sales Order Number does not exist',
  'storecove.sender.identifier' => '6.2 Sender Identifier',
  'storecove.sender.identifier.basis' =>
    '6.2 Sender Identifier Basis - Legal or Tax',
  'storecove.supplier.contact.email.not.found' =>
    'Supplier Contact Email Not Found.'
    . ' Refer to config params array.',
  'storecove.supplier.contact.firstname.not.found' =>
    'Supplier Contact FirstName Not Found.'
    . ' Refer to config params array.',
  'storecove.supplier.contact.lastname.not.found' =>
    'Supplier Contact LastName Not Found.'
    . ' Refer to config params array.',
  'storecove.supplier.contact.name.not.found' =>
    'Supplier Contact Name Not Found.'
    . ' Refer to config params array.',
  'storecove.supplier.contact.telephone.not.found' =>
    'Supplier Contact Telephone Not Found.'
    . ' Refer to config params array.',
  'storecove.tax' => 'Tax',
  'storecove.tax.rate.code' => 'Storecove Tax Rate Code',
  'storecove.tax.scheme.identifier.id.not.found' =>
    'Config params'
    . ' Accounting Supplier Party Tax Scheme Company Id not found.',
  'stream' => 'Stream Pdf in Browser / Modal',
  'street.address' => 'Street Address',
  'street.address.2' => 'Street Address (cont.)',
  'subject' => 'Subject',
  'submenu' => 'Submenu',
  'submit' => 'Submit',
  'subtotal' => 'Subtotal',
  'success' => 'Success',
  'successful' => 'Successful',
  'suggested.from.previous.selection' => 'Suggested from previous selection',
  'sumex' => 'Sumex',
  'sumex.add' => 'Sumex Add',
  'sumex.canton' => 'Canton',
  'sumex.diagnosis' => 'Diagnosis',
  'sumex.edit' => 'Sumex Edit',
  'sumex.help' => 'This options adds a menu entry'
    . ' in invoices to generate a'
    . ' TARMED / SUMEX1 semi compatible invoice.'
    . ' TARMED / SUMEX1 is a swiss standard for healthcares.'
    . ' <a href="http://sumex1.net/">More Info</a>',
  'sumex.information' => 'Sumex Information',
  'sumex.insurednumber' => 'Insured Number',
  'sumex.observations' => 'Observations',
  'sumex.place' => 'Sumex Place',
  'sumex.place.association' => 'Association',
  'sumex.place.company' => 'Company',
  'sumex.place.hospital' => 'Hospital',
  'sumex.place.lab' => 'Lab',
  'sumex.place.practice' => 'Practice',
  'sumex.rcc' => 'RCC',
  'sumex.role' => 'Sumex Role',
  'sumex.role.chiropractor' => 'Chiropractor',
  'sumex.role.dentaltechnician' => 'Dental Technician',
  'sumex.role.dentist' => 'Dentist',
  'sumex.role.druggist' => 'Druggist',
  'sumex.role.ergotherapist' => 'Ergotherapist',
  'sumex.role.hospital' => 'Hospital',
  'sumex.role.labtechnician' => 'Lab Technician',
  'sumex.role.logotherapist' => 'Logotherapist',
  'sumex.role.midwife' => 'Midwife',
  'sumex.role.naturopathicdoctor' => 'Naturopathicdoctor',
  'sumex.role.naturopathictherapist' => 'Naturopathictherapist',
  'sumex.role.nursingstaff' => 'Nursing Staff',
  'sumex.role.nutritionist' => 'Nutritionist',
  'sumex.role.other' => 'Other',
  'sumex.role.othertechnician' => 'Other Technician',
  'sumex.role.pharmacist' => 'Pharmacist',
  'sumex.role.physician' => 'Physician',
  'sumex.role.physiotherapist' => 'Physiotherapist',
  'sumex.role.psychologist' => 'Psychologist',
  'sumex.role.transport' => 'Transport',
  'sumex.role.wholesaler' => 'Wholesaler',
  'sumex.settings' => 'Sumex Settings',
  'sumex.sliptype' => 'Sumex Slip Type',
  'sumex.sliptype-esr9' => 'ESR 9 (Orange Slip)',
  'sumex.sliptype-esrRed' => 'Red Slip',
  'sumex.sliptype.help' => 'This option will change'
    . ' the slip type in Sumex.'
    . ' Please note that'
    . ' if you select the Orange slip'
    . ' you need a subscriber number'
    . ' that starts with "01-"',
  'sumex.ssn' => 'AVS',
  'sumex.veka' => 'VEKA',
  'sunday' => 'Sunday',
  'system.settings' => 'System Settings',
  'table' => 'Table',
  'task' => 'Task',
  'task.add' => 'Task Add',
  'task.description' => 'Task description',
  'task.finish.date' => 'Finish date',
  'task.name' => 'Task name',
  'task.price' => 'Task price',
  'tasks' => 'Tasks',
  'tasks.form' => 'Task form',
  'tax' => 'Tax',
  'tax.code' => 'Taxes Code',
  'tax.code.short' => 'Tax Code',
  'tax.information' => 'Taxes Information',
  'tax.point' => 'Date Tax Point',
  'tax.rate' => 'Tax Rate',
  'tax.rate.active.not' => 'Warning:'
    . ' No Tax Rates have been made active.'
    . ' Activate at least one tax rate.'
    . ' Settings ... Tax Rate',
  'tax.rate.add' => 'Tax Rate Add',
  'tax.rate.code' => 'Invoice Tax Rate Code',
  'tax.rate.decimal.places' => 'Tax Rate Decimal Places',
  'tax.rate.edit' => 'Edit Tax Rate',
  'tax.rate.form' => 'Tax Rate Form',
  'tax.rate.history.exists' => 'Cannot delete. History already exits',
  'tax.rate.name' => 'Tax Rate Name eg. Standard',
  'tax.rate.percent' => 'Tax Rate Percent',
  'tax.rate.placement' => 'Tax Rate Placement',
  'tax.rates' => 'Tax Rates',
  'taxes' => 'Taxes',
  'telegram' => 'Telegram',
  'telegram.bot.api.chat.id' =>
    'Telegram: Non-bot:'
    . ' Personal Account:'
    . ' Hello World:'
    . ' Test Message:'
    . ' Message Recipient:'
    . ' <b>Chat Id</b>',
  'telegram.bot.api.chat.id.not.set' =>
    'Chat Id of non-bot'
    . ' personal telegram account holder'
    . ' that sent the bot its first message'
    . ' and is to receive the \'Hello\' \'World\''
    . ' test message has not been setup.',
  'telegram.bot.api.current.status' =>
    '<b>Current use:</b>'
    . ' There is no need'
    . ' for a webhook'
    . ' because clients do not have chat ids'
    . ' and are not sending messages to the bot.',
  'telegram.bot.api.enable' => 'Enable Telegram',
  'telegram.bot.api.enabled.not' => 'Telegram Not Enabled',
  'telegram.bot.api.future.use' =>
    '<b>Future use:</b>'
    . ' Clients with chat ids'
    . ' get sent a telegram invoice'
    . ' and they confirm receipt'
    . ' by sending back a confirmation to the webhook',
  'telegram.bot.api.general.purpose' =>
    '<b>Registered Bot,'
    . ' identified by token,'
    . ' sends Customer Payment Notifications'
    . ' to below Registered Chat Id'
    . ' (usually the admins personal non-bot telegram account).</b>',
  'telegram.bot.api.get.updates.failed' =>
    'Your Telegram Updates failed.',
  'telegram.bot.api.get.updates.success' =>
    'Your Telegram Updates succeeded'
    . ' using the api method getUpdates.',
  'telegram.bot.api.hello.world.test.message' =>
    'Hello World from Telegram Bot Api.'
    . ' Thank you yiisoft developers!',
  'telegram.bot.api.hello.world.test.message.sent' =>
    'Hello World message sent.',
  'telegram.bot.api.hello.world.test.message.sent.not' =>
    'Hello World message NOT sent.',
  'telegram.bot.api.hello.world.test.message.use' =>
    'Send a basic test message'
    . ' \'Hello World from Telegram Bot Api\''
    . ' to an external chat id',
  'telegram.bot.api.payment.notification.success' =>
    'Telegram Payment Notification Successful',
  'telegram.bot.api.payment.notifications' =>
    'Telegram Payment Notifications',
  'telegram.bot.api.token' => 'Telegram Bot Api Token',
  'telegram.bot.api.token.not.set' => 'Telegram Bot Api Token Not Set',
  'telegram.bot.api.webhook.delete' => 'Delete the Webhook',
  'telegram.bot.api.webhook.deleted' =>
    'Webhook has just been disabled'
    . ' so that manual api get method getUpdates'
    . ' can be used.',
  'telegram.bot.api.webhook.secret.token' =>
    'Webhook Secret Token'
    . ' for additional security'
    . ' (Used as a parameter in the'
    . ' setWebhook function and not as a queryParameter)',
  'telegram.bot.api.webhook.setup' =>
    'The webhook is setup.',
  'telegram.bot.api.webhook.setup.already' =>
    'The webhook has been setup already.',
  'telegram.bot.api.webhook.url.this.site' =>
    'This site\'s Telegram Webhook Url'
    . ' used in TelegramHelper setWebhook function.',
  'template' => 'Invoice Template',
  'templates' => 'Invoice Templates',
  'term' => 'Terms And Conditions',
  'term.1' => 'I have not read the terms and conditions.',
  'term.2' => 'I have read and agree to the terms and conditions.',
  'term.add.additional.terms.at.setting.repository' =>
    'Add Additional Terms'
    . ' at Setting Repository getPaymentTerms function.',
  'terms' => 'Terms',
  'test.data.install' => 'Install Test Data',
  'test.data.use' => 'Use Test Data',
  'test.remove' => 'Remove Test Data',
  'test.remove.tooltip' => 'View'
    . '..Settings'
    . '..General'
    . '..Install Test Data'
    . '..No and View'
    . '..Settings'
    . '..General'
    . '..Use Test Data'
    . '..No',
  'test.reset' => 'Reset Test Data',
  'test.reset.setting' => 'Settings Reinstall',
  'test.reset.setting.tooltip' =>
    'This will remove all current settings'
    . ' and reinstall the default settings'
    . ' in InvoiceController/install.default.settings.on.first.run',
  'test.reset.tooltip' =>
    'View'
    . '..Settings'
    . '..General'
    . '..Install Test Data'
    . '..Yes and View'
    . '..Settings'
    . '..General'
    . '..Use Test Data'
    . '..Yes',
  'text' => 'Text',
  'textarea' => 'Text Area',
  'theme' => 'Theme',
  'this.month' => 'This Month',
  'this.quarter' => 'This Quarter',
  'this.year' => 'This Year',
  'thousands.separator' => 'Thousands Separator',
  'time.created' => 'Time Created',
  'time.zone' => 'Time Zone',
  'title' => 'Title',
  'to.date' => 'To Date',
  'to.email' => 'To Email',
  'total' => 'Total',
  'total.balance' => 'Total Balance',
  'total.billed' => 'Total Billed',
  'total.paid' => 'Total Paid',
  'transaction.reference' => 'Transaction Reference',
  'transaction.successful' => 'Transaction successful',
  'treatment' => 'Treatment',
  'treatment.end' => 'End of Treatment',
  'treatment.start' => 'Start of Treatment',
  'true' => 'True',
  'try.again' => 'Try Again',
  'two.factor.authentication' => 'Two Factor Authentication',
  'two.factor.authentication.attempt.failure' =>
    'Two Factor Authentication Attempt Failure',
  'two.factor.authentication.attempt.failure.must.setup' =>
    'Two Factor Authentication Attempt Failure:'
    . ' You must setup a new qr code'
    . ' with secret (+)'
    . ' and choose the overwrite previous entry when prompted',
  'two.factor.authentication.attempt.success' =>
    'Two Factor Authentication Attempt Success',
  'two.factor.authentication.disabled' =>
    'Two Factor Authentication'
    . ' has now been disabled for additional security.',
  'two.factor.authentication.enable' =>
    'Enable Two Factor Authentication',
  'two.factor.authentication.enabled.with.disabling' =>
    'Two Factor Authentication is currently enabled'
    . ' for additional security'
    . ' and is disabled'
    . ' after successful authentication'
    . ' for an additional layer of security'
    . ' until the next login.'
    . ' Compulsory scanning of Qr code,'
    . ' after each login, for a new secret. + '
    . '... Scan the Qr Code '
    . '... save '
    . '... delete previous entry.'
    . ' (Not recommended)',
  'two.factor.authentication.enabled.without.disabling' =>
    'Two Factor Authentication is currently enabled'
    . ' for additional security'
    . ' and is not disabled after successful authentication'
    . ' The Qr code will not be seen again for scanning,'
    . ' after logging in, after having setup two factor authentication.'
    . ' (Recommended)',
  'two.factor.authentication.error' => 'Two Factor Authentication Error',
  'two.factor.authentication.form.verify.login' => 'Verify Login',
  'two.factor.authentication.invalid.code.format' =>
    'Invalid code format.'
    . ' Please enter the 6-digit code from your app.',
  'two.factor.authentication.invalid.backup.recovery.code' =>
    'Invalid 8 digit backup recovery code',
  'two.factor.authentication.invalid.totp.code' =>
    'Invalid 6 digit timed one-time authentication code',
  'two.factor.authentication.missing.code.or.secret' =>
    'Missing authentication code or 2FA secret.',
  'two.factor.authentication.new.six.digit.code' =>
    'Please enter a new 6-digit authentication code'
    . ' (different to the setup code) from your app.',
  'two.factor.authentication.no.secret.generated' =>
    'No secret generated. Please restart setup.',
  'two.factor.authentication.qr.code.enter.manually' =>
    'Or enter this code into the android app manually: ',
  'two.factor.authentication.rate.limit.reached' =>
    'Rate Limit reached. Please wait 10 seconds.',
  'two.factor.authentication.scan' =>
    'Scan this QR code with your Aegis app:',
  'two.factor.authentication.setup' =>
    'Setup Two Factor Authentication',
  'type' => 'Type',
  'unit' => 'Unit',
  'unit.add' => 'Unit Add',
  'unit.already.exists' => 'Unit already exists!',
  'unit.description.not.provided' => 'Description not provided',
  'unit.edit' => 'Edit Unit',
  'unit.history' => 'Cannot delete. History exists.',
  'unit.name' => 'Unit Name',
  'unit.name.plrl' => 'Unit Name (plural form)',
  'unit.peppol' => 'Peppol',
  'unit.peppol.add' => 'Unit Peppol Add',
  'unit.peppol.code' => 'Unit Peppol Code',
  'unit.peppol.edit' => 'Unit Peppol Edit',
  'unit.peppol.index' => 'Unit Peppol Index',
  'units' => 'Units',
  'unknown' => 'Unknown',
  'unpaid' => 'Unpaid',
  'updatecheck' => 'Updatecheck',
  'updatecheck.failed' =>
    'Updatecheck failed! Check your network connection.',
  'updates' => 'Updates',
  'updates.available' => 'Updates available!',
  'upload.date' => 'Upload Date',
  'upload.description' => 'Description',
  'upload.filename.description' => 'Filename Description',
  'upload.filename.new' => 'New Filename',
  'upload.filename.original' => 'Original Filename',
  'upload.index' => 'Upload Index',
  'upload.plural' => 'Uploads',
  'upload.url.key' => 'Url Key',
  'url' => 'Url',
  'use.system.language' => 'Use System language',
  'user' => 'User',
  'user.account' => 'Invoice User Account',
  'user.account.clients' => 'Clients with User Accounts',
  'user.accounts' => 'Invoice User Accounts',
  'user.all.clients' => 'Add all customers',
  'user.all.clients.text' =>
    '* If this option is checked,'
    . ' the user will be able to see all the clients,'
    . ' including the ones that are added later.',
  'user.api.list' => 'Pre-Invoice Users - Signed Up Users',
  'user.client.active.no' => 'You have no clients'
    . ' with active user accounts.'
    . ' Administrators assign client(s) to a signed-up user account.',
  'user.client.count' => '#',
  'user.client.no.account' =>
    'This client'
    . ' has no user account associated with it'
    . ' and therefore this document cannot be created.',
  'user.clients.assigned.not' => 'This user has no Clients assigned to it.',
  'user.form' => 'User Form',
  'user.iban' => 'IBAN',
  'user.inv.active.not' => 'The User Account is not Active',
  'user.inv.list.limit' => 'Number of records'
    . ' listed per page (Note: Overrides default)',
  'user.inv.more.than.one.assigned' => 'Invoice Creation Unsuccessful:'
    . ' Consult your Settings '
    . '... User Account.'
    . ' More than one user has been assigned to this client.',
  'user.inv.refer.to' => 'The default of 10 records'
    . ' per page'
    . ' may be overwritten by clicking here.',
  'user.inv.role.accountant' => 'Accountant',
  'user.inv.role.accountant.assigned' => 'Accountant Role Assigned',
  'user.inv.role.accountant.default' => 'The Accountant of a client,'
    . ' by default,'
    . ' can view invoices,'
    . ' pay invoices,'
    . ' view payments of invoices,'
    . ' and edit payments of invoices.',
  'user.inv.role.administrator' => 'Administrator',
  'user.inv.role.administrator.already.assigned' =>
    'The Administrator role'
    . ' has already been assigned',
  'user.inv.role.administrator.assigned' =>
    'The Administrator role'
    . ' has now been assigned. ',
  'user.inv.role.all.new' => 'All new users'
    . ' will by default'
    . ' assume the observer role ie.'
    . ' can view Documentation'
    . ' and not edit Documentation sent to them ie.'
    . ' observe or look at the documentation.',
  'user.inv.role.observer' => 'Observer',
  'user.inv.role.observer.assigned' => 'Observer Role Assigned',
  'user.inv.role.observer.assigned.already' =>
    'The Observer Role has been assigned already.',
  'user.inv.role.revoke.all' => 'Revoke All Roles',
  'user.inv.role.warning.revoke.all' =>
    'Are you sure you want to revoke all roles',
  'user.inv.role.warning.role' =>
    'Are you sure you want to adopt this role?',
  'user.inv.type.cannot.allocate.administrator.type.to.non.administrator' =>
    'Cannot allocate dropdown\'s'
    . ' administrator type to a non administrator',
  'user.inv.type.cannot.allocate.guest.type.to.administrator' =>
    'Cannot allocate dropdown\'s'
    . ' guest type to an administrator',
  'user.signup.please' => 'Please signup!',
  'user.subscriber.number' => 'Subscriber Number',
  'user.type' => 'User Type',
  'username' => 'Username',
  'users' => 'Users',
  'validator.fail' => 'Unable to process field %s: %s',
  'validator.invalid.login.password' => 'Invalid login or password',
  'validator.password.change' => 'Your Password has been changed',
  'validator.password.not.match' => 'Passwords do not match',
  'validator.password.not.match.new' => 'Your new passwords do not match',
  'validator.password.reset' => 'Your Password has been reset',
  'validator.user.exist' => 'A User with this login already exists',
  'validator.user.exist.not' => 'A User with this login does not exist',
  'value' => 'Value',
  'values' => 'Values',
  'values.with.taxes' => 'Values with taxes',
  'variant' => 'Variant',
  'vat' => 'VAT',
  'vat.abbreviation' => 'VAT',
  'vat.break.down' => 'VAT Summary',
  'vat.id' => 'VAT ID',
  'vat.id.short' => 'VAT',
  'vat.invoice' => 'VAT INVOICE',
  'vat.rate' => 'VAT Rate',
  'vat.reg.no' => 'VAT Reg No',
  'vat.registered' => 'VAT Registered',
  'vendor.nikic.fast-route' => 'Building Faster Routes',
  'verify.password' => 'Verify Password',
  'version.history' => 'Version History',
  'view' => 'View',
  'view.all' => 'View All',
  'view.client' => 'View Client',
  'view.clients' => 'View Clients',
  'view.contact.form.body' => 'Body',
  'view.contact.form.email' => 'Email',
  'view.contact.form.name' => 'Name',
  'view.contact.form.subject' => 'Subject',
  'view.invoices' => 'View Invoices',
  'view.payment.logs' => 'View Online Payment Logs',
  'view.payments' => 'View Payments',
  'view.product.families' => 'View Product Families',
  'view.product.units' => 'View Product Units',
  'view.products' => 'View Products',
  'view.projects' => 'View Projects',
  'view.quotes' => 'View Quotes',
  'view.recurring.invoices' => 'View Recurring Invoices',
  'view.tasks' => 'View Tasks',
  'viewed' => 'Viewed',
  'warning' => 'Warning',
  'web' => 'Web',
  'web.address' => 'Web Address',
  'welcome' => 'Welcome',
  'wrong.passwordreset.token' =>
    'No user'
    . ' found for the provided reset token.'
    . ' If you think this is an error,'
    . ' contact your administrator.',
  'year' => 'Year',
  'year.prefix' => 'Year Prefix',
  'years' => 'Years',
  'yes' => 'Yes',
  'zip' => 'Post Code',
  'zip.code' => 'Post Code',
  
  // Quote approval workflow translations
  'approval.required' => 'Approval Required',
  'quote.approval.required' => 'Quote approval required',
  'quote.must.be.approved.first' =>
    'Quote must be approved before conversion',
  
  // === ENHANCED TRANSLATION KEYS ===
  // === VALIDATION MESSAGES ===
  'validation.invoice.number.required' => 'Invoice number is required',
  'validation.invoice.date.invalid' => 'Please provide a valid invoice date', 
  'validation.client.email.format' => 'Client email must be a valid email address',
  'validation.amount.positive' => 'Amount must be greater than zero',
  'validation.currency.supported' => 'Currency {currency} is not supported',
  
  // === BUSINESS LOGIC ERRORS ===
  'business.error.invoice.already_paid' => 'Invoice #{invoice_number} is already marked as paid',
  'business.error.insufficient_stock' => 'Insufficient stock for product {product_name}. Available: {available}, Required: {required}',
  'business.error.client.credit_limit' => 'Client {client_name} has exceeded credit limit of {limit}',
  'business.error.payment.gateway_failed' => 'Payment processing failed: {error_message}',
  
  // === SUCCESS MESSAGES ===
  'success.create.invoice' => 'Invoice #{invoice_number} created successfully',
  'success.update.client' => 'Client {client_name} updated successfully',
  'success.send.invoice' => 'Invoice #{invoice_number} sent to {client_email}',
  'success.payment.received' => 'Payment of {amount} received for invoice #{invoice_number}',
  
  // === EMAIL TEMPLATES ===
  'email.invoice.created.subject' => 'New Invoice #{invoice_number} from {company_name}',
  'email.invoice.reminder.subject' => 'Payment Reminder - Invoice #{invoice_number}',
  'email.invoice.overdue.subject' => 'OVERDUE: Invoice #{invoice_number} - Immediate Attention Required',
  
  'email.invoice.created.body' => '
        <h2>Dear {client_name},</h2>
        <p>We have created a new invoice for you:</p>
        <ul>
            <li><strong>Invoice Number:</strong> #{invoice_number}</li>
            <li><strong>Date:</strong> {invoice_date}</li>
            <li><strong>Due Date:</strong> {due_date}</li>
            <li><strong>Amount:</strong> {amount}</li>
        </ul>
        <p><a href="{view_link}">View Invoice</a> | <a href="{payment_link}">Pay Now</a></p>
        <p>Payment Terms: {payment_terms}</p>
        <p>Best regards,<br>{company_name}</p>
    ',
  
  // === DASHBOARD MESSAGES ===
  'dashboard.overview.title' => 'Financial Overview',
  'dashboard.total_revenue' => 'Total Revenue This Month',
  'dashboard.outstanding_invoices' => 'Outstanding Invoices',
  'dashboard.overdue_amount' => 'Overdue Amount',
  'dashboard.recent_payments' => 'Recent Payments',
  
  // === INVOICE STATUS ===
  'status.invoice.draft' => 'Draft',
  'status.invoice.sent' => 'Sent', 
  'status.invoice.viewed' => 'Viewed',
  'status.invoice.partial' => 'Partially Paid',
  'status.invoice.paid' => 'Paid',
  'status.invoice.overdue' => 'Overdue',
  'status.invoice.cancelled' => 'Cancelled',
  
  // === PAYMENT TERMS ===
  'payment.terms.immediate' => 'Payment due immediately',
  'payment.terms.net15' => 'Payment due within 15 days',
  'payment.terms.net30' => 'Payment due within 30 days',
  'payment.terms.net60' => 'Payment due within 60 days',
  
  // === CURRENCY & NUMBERS ===
  'currency.symbol.USD' => '$',
  'currency.symbol.EUR' => '€',
  'currency.symbol.GBP' => '£',
  'currency.name.USD' => 'US Dollars',
  'currency.name.EUR' => 'Euros',
  'currency.name.GBP' => 'British Pounds',
  
  // === TAX MESSAGES ===
  'tax.vat.rate' => 'VAT Rate: {rate}%',
  'tax.total.amount' => 'Total Tax: {amount}',
  'tax.exempt.notice' => 'This transaction is tax-exempt',
  
  // === REPORT LABELS ===
  'report.aging.title' => 'Accounts Receivable Aging Report',
  'report.revenue.title' => 'Revenue Analysis Report', 
  'report.client.statement' => 'Client Account Statement',
  'report.period.from_to' => 'Period: {from_date} to {to_date}',
  
  // === API RESPONSES ===
  'api.error.unauthorized' => 'Authentication required to access this resource',
  'api.error.forbidden' => 'You do not have permission to access this resource',
  'api.error.not_found' => 'The requested {resource} was not found',
  'api.error.validation_failed' => 'Validation failed. Please check your input.',
  'api.success.created' => '{resource} created successfully',
  'api.success.updated' => '{resource} updated successfully',
  'api.success.deleted' => '{resource} deleted successfully',
  
  // === INVOICE ORIGIN TRACKING ===
  'invoice.origin' => 'Origin',
  'invoice.created.from.quote' => 'Created from Quote',
  
  // === PROMETHEUS MONITORING ===
  'monitoring.health.database' => 'Database Connection',
  'monitoring.health.cache' => 'Cache System',
  'monitoring.health.storage' => 'File Storage',
  'monitoring.metrics.requests' => 'Total Requests',
  'monitoring.metrics.errors' => 'Error Rate',
  'monitoring.metrics.response_time' => 'Average Response Time',
];

