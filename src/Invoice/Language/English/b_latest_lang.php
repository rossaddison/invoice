<?php

declare(strict_types=1);
// as at 16th February 2025. Note this file has been built using copy paste from resources/messages/en/app.php
// Remember to adjust the app.php file with '$lang =' as seen below
// Note: latest_lang.php has been split into a_latest_lang.php and b_latest_lang.php
// so that the google translation api can handle it.
$lang = [
    'invoice.item.lookup' => 'Invoice Item Lookup',
    'invoice.invoice.recurring.add' => 'Recurring Add',
    'invoice.mark.sent.copy.on' => 'Mark invoices as sent when copying an Invoice is On. It should only be on during development',
    'invoice.merchant' => 'Merchant',
    'invoice.merchant.add' => 'Merchant Add',
    'invoice.merchant.driver' => 'Driver',
    'invoice.merchant.response' => 'Response',
    'invoice.merchant.reference' => 'Reference',
    'invoice.not.available' => 'Not available',
    'invoice.no.file.uploaded' => 'No file uploaded',
    'invoice.online.log' => 'Online Log',
    'invoice.orm' => 'Orm',
    'invoice.payment' => 'Payment',
    'invoice.payment.add' => 'Payment Add',
    'invoice.payment.cannot.delete' => 'Cannot delete payment',
    'invoice.payment.custom' => 'Payment Custom',
    'invoice.payment.custom.add' => 'Payment Custom Add',
    'invoice.payment.deleted' => 'Payment Deleted',
    'invoice.payment.gateway.default.locale' => 'Default Locale e.g en_GB',
    'invoice.payment.gateway.no' => 'No payment gateways have been setup under Settings ... View ... Online Payment',
    'invoice.payment.gateway.mollie.api.key.needs.to.be.setup' => 'Mollie Payment Gateway Test or Live Api Key needs to be setup.',
    'invoice.payment.gateway.mollie.api.key.has.been.setup' => 'Mollie Payment Gateway Test or Live Api Key has been setup.',
    'invoice.payment.gateway.mollie.api.payment.id' => 'Mollie Payment Id: ',
    'invoice.payment.information.amazon.no.omnipay.version' => 'There currrently is no Amazon Pay Omnipay Version. Uncheck Omnipay Version to use the PCI compliant version under Settings View',
    'invoice.payment.information.amazon.payment.session.complete' => 'Amazon Payment Session Complete - Session Id: ',
    'invoice.payment.information.amazon.payment.session.incomplete' => 'Amazon Payment Session Incomplete - Please Try Again',
    'invoice.payment.information.braintree.braintree.no.omnipay.version' => 'There currrently is no Braintree Omnipay Version compatible with Braintree Version 6.9.1. Uncheck Omnipay Version to use the PCI compliant version under Settings View',
    'invoice.payment.information.stripe.api.key' => 'Stripe Payment Gateway Secret Key / Api Key needs to be setup.',
    'invoice.payment.information.no.information.provided' => 'No Information has been provided',
    'invoice.payment.information.invoice.number.not.provided' => 'Invoice number has not been provided',
    'invoice.payment.information.payment.method.required' => 'A payment method is required for this invoice.',
    'invoice.payment.information.none' => 'None',
    'invoice.payment.information.omnipay.driver.being.used' => 'A driver {{$d}} from Omnipay is being used.',
    'invoice.payment.method' => 'Payment Method',
    'invoice.payment.method.add' => 'Payment Method Add',
    'invoice.payment.method.history' => 'Cannot delete. Payment Method history exists.',
    'invoice.payment.no.invoice.sent' => 'No invoices have been sent by us or viewed by the customer.',
    'invoice.paymentpeppol.reference.plural' => 'Payment References using Peppol',
    'invoice.payment.terms.default' => 'Pay within 14 days',
    'invoice.payments' => 'Payments',
    'invoice.payment.term.add.additional.terms.at.setting.repository' => 'Add Additional Terms at Setting Repository getPaymentTerms function.',
    'invoice.payment.term' => 'Payment Terms',
    'invoice.payment.term.0.days' => 'Please use one of the payment methods provided',
    'invoice.payment.term.net.15.days' => 'Net(15): Please pay within 15 days of issue date.',
    'invoice.payment.term.net.30.days' => 'Net(30): Please pay within 30 days of issue date',
    'invoice.payment.term.net.60.days' => 'Net(60): Please pay within 60 days of issue date',
    'invoice.payment.term.net.90.days' => 'Net(90): Please pay within 90 days of issue date',
    'invoice.payment.term.net.120.days' => 'Net(120): Please pay within 120 days of issue date',
    'invoice.payment.term.eom.15.days' => 'EOM(15): Please pay within 15 days of End of Month of issue date',
    'invoice.payment.term.eom.30.days' => 'EOM(30): Please pay within 30 days of End of Month of issue date',
    'invoice.payment.term.eom.60.days' => 'EOM(60): Please pay within 60 days of End of Month of issue date',
    'invoice.payment.term.eom.90.days' => 'EOM(90): Please pay within 90 days of End of Month of issue date',
    'invoice.payment.term.eom.120.days' => 'EOM(120): Please pay within 120 days End of Month of issue date',
    'invoice.payment.term.mfi.15' => 'MFI(15): Please pay on the 15th of the Month Following the Issue-date-month',
    'invoice.payment.term.general' => 'Payment due within 30 days',
    'invoice.payment.term.polite' => 'We appreciate your business. Please send your payment within 30 days of receiving this invoice.',
    'invoice.payment.term.pia' => 'Payment is required in Advance (PIA)',
    'invoice.paymentpeppol' => 'Payments made through Peppol',
    'invoice.peppol' => 'Peppol Universal Business Language (UBL) 2.1 Invoice - Ecosio Validated',
    'invoice.peppol.abbreviation' => 'Peppol',
    'invoice.peppol.allowance.or.charge.inherit' => 'Invoice Allowance Charges and Invoice Item Allowance Charges inherit from a completed Peppol Document Level Allowance Charge',
    'invoice.peppol.client.check' => 'Peppol details relating to this client are insufficient. At least one is missing. Refer to View ... Client ... Options ... Edit Peppol details for e-invoicing',
    'invoice.peppol.client.defaults' => 'Fill Client Peppol Form with OpenPeppol defaults for testing.',
    'invoice.peppol.currency.code.from' => 'From Currency ie. Country of Origin Tax Currency (To change see config/common/params.php TaxCurrencyCode)',
    'invoice.peppol.currency.code.to' => 'To Currency ie. Document Currency: see function get_setting(\'currency_code_to\')',
    'invoice.peppol.currency.from.to' => 'One of From Currency today converts to this of To Currency',
    'invoice.peppol.currency.to.from' => 'One of To Currency today converts to this of From Currency',
    'invoice.peppol.document.reference.null' => 'inv-number-null-inv-id',
    'invoice.peppol.ecosio.validator' => 'Ecosio Validator for OpenPeppol UBL Invoice (3.15.0) (aka BIS Billing 3.0.14)',
    'invoice.peppol.enable' => 'Enable Peppol using Universal Business Language (UBL) 2.1',
    'invoice.peppol.include.delivery.period' => 'Include Delivery Periods',
    'invoice.peppol.invoice.note.not.found' => 'Invoice note not found',
    'invoice.peppol.label.switch.off' => 'Peppol Defaults Enabled',
    'invoice.peppol.label.switch.on' => 'Peppol Defaults Disabled',
    'invoice.peppol.mandatory' => ' (Mandatory)',
    'invoice.peppol.optional' => ' (Optional)',
    'invoice.peppol.stand.in.code' => 'Description code - indicating what basis will be used for the future tax-point date when goods are supplied/paid. If a tax-point can be determined, the description code is mutually excluded in the Invoice Period.',
    'invoice.peppol.stream.toggle' => 'Peppol - \'Stream\' or \'Save to File\' Toggle Button',
    'invoice.performance' => 'Performance',
    'invoice.peppol.tax.category.not.found' => 'Peppol Tax Category code (https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/) missing.',
    'invoice.peppol.tax.category.percent.not.found' => 'Peppol Tax Category percent (https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/) missing.',
    'invoice.peppol.tax.rate.code' => 'Peppol Tax Rate Code',
    'invoice.peppol.trying.to.send.non.pdf.file' => 'Only pdf files are currently being sent with Peppol Invoices',
    'invoice.peppol.unit' => 'Unit Classification using Peppol',
    'invoice.peppol.version' => 'Peppol Version',
    'invoice.peppol.xml.stream' => 'Stream ie. show on screen in browser instead of downloads folder.',
    'invoice.peppol.store.cove.1.1.1' => 'Register for API Access on the Store Cove website',
    'invoice.peppol.store.cove.1.1.2' => 'Create your API key from the Store Cove website and store in Settings...View...Online Payment...Store Cove...Api key',
    'invoice.peppol.store.cove.1.1.3' => 'Make your first API call to get JSON response.',
    'invoice.peppol.store.cove.1.1.4' => 'Send your first test Json invoice that you acquired from clicking on green button World=>DE',
    'invoice.peppol.validator.Invoice.cac.Note' => 'Source: src/Invoice/Ubl/Invoice:  Missing invoice note - Ecosio: Document MUST not contain empty elements.',
    'invoice.peppol.validator.Invoice.cac.AdditionalDocumentReference.cbc.DocumentDescription' => 'Source: src/Invoice/Ubl/Invoice: Missing document description - Ecosio: Document MUST not contain empty elements. Solution: In Invoice View: Options ... Edit ... Document Description',
    'invoice.peppol.validator.Invoice.cac.Party.cbc.EndPointID' => 'src/Invoice/Ubl/Party: Missing email address - Ecosio: Electronic Address MUST be provided. Solution: Client ... View ... Options ... Edit Peppol details for e-invoicing ... EndPointID',
    'invoice.performance.label.switch.on' => 'Slower Write Only Mode - refer to config/common/params.php search \'mode\' ',
    'invoice.performance.label.switch.off' => 'Faster Read and Write Mode - refer to config/common/params.php search \'mode\' ',
    'invoice.permission' => 'You do not have the required permission.',
    'invoice.permission.unauthorised' => 'Ask your administator to create a User Account so that you can view Quotes and Invoices. ' .
    'Your administrator will have to assign the observor role to you as well. ' .
    'Your adminstrator will have to make sure your account is Actve as well.',
    'invoice.permission.authorised.view' => 'You have been authorised to view quotes and invoices. ' .
    'If you cannot see them it is likely that your Administrator has NOT made your user account Active or marked the invoice as Sent.',
    'invoice.permission.authorised.edit' => 'You have been given Administator permissions to create, edit, and update quotes and invoices.',
    'invoice.po.group' => 'Purchase Order Group',
    'invoice.platform' => 'Platform',
    'invoice.platform.editor' => 'Editor',
    'invoice.platform.netbeans.UTF-8' => 'Netbeans UTF-8 encoding',
    'invoice.platform.server' => 'Server',
    'invoice.platform.sqlPath' => 'Sql Path',
    'invoice.platform.mySqlVersion' => 'mySql Version',
    'invoice.platform.PhpVersion' => 'Php Version',
    'invoice.platform.PhpSupport' => 'Php Support',
    'invoice.platform.update' => 'WampServer Files and Addons',
    'invoice.platform.PhpMyAdmin' => 'PhpMyAdmin Version',
    'invoice.platform.windowsVersion' => 'Windows 11 Home Edition',
    'invoice.platform.xdebug' => 'Xdebug Extension',
    'invoice.platform.csrf' => 'Cross Site Forgery Protection',
    'invoice.product' => 'Product',
    'invoice.product.additional.item.property.name' => 'Peppol Additional Property Item Name eg. Colour',
    'invoice.product.additional.item.property.value' => 'Peppol Additional Property Item Value eg. Black',
    'invoice.product.add' => 'Product Add',
    'invoice.product.country.of.origin.code' => 'Peppol Country of Origin Code using ISO-3166-1:Alpha2 Country Codes',
    'invoice.product.custom.fields' => 'Product Custom Fields (eg. Peppol: AdditionalItemProperty multiple choice field colour value black)',
    'invoice.product.error.summary' => 'Product Error Summary',
    'invoice.product.edit' => 'Edit Product',
    'invoice.product.form.tab.required' => 'Required Fields',
    'invoice.product.form.tab.not.required' => 'Not Required Fields',
    'invoice.product.found' => 'Product found',
    'invoice.product.history' => 'Cannot delete. This product is on an invoice or quote.',
    'invoice.product.id' => 'Product Id',
    'invoice.product.image' => 'Image',
    'invoice.product.item.classification.code.scheme.id.not.found' => 'Product Item Classification Code Scheme Id not found',
    'invoice.product.not.found' => 'Product not found',
    'invoice.product.peppol.unit' => 'Unit with Peppol',
    'invoice.product.price.base.quantity' => 'qty in Product',
    'invoice.product.property' => ' Product Property',
    'invoice.product.property.add' => ' Product Property Add',
    'invoice.product.property.edit' => 'Product Property Edit','invoice.product.property.index' => 'Product Property Index',
    'invoice.product.property.name' => 'Name',
    'invoice.product.property.table' => 'Product Property Table',
    'invoice.product.property.value' => 'Value',
    'invoice.product.record.successfully.added' => 'Product Record successfully added',
    'invoice.product.sii.schemeid' => 'Peppol Standard Item Identification schemeid - eg. 0160',
    'invoice.product.sii.id' => 'Peppol Standard Item Identification id associated with the above scheme - eg. 14 digit zero-padded identifier in Global Trade Item Number database (appearing under barcode)',
    'invoice.product.icc.listid' => 'Peppol Item Classificaiton Code List id - eg. SRV',
    'invoice.product.icc.listversionid' => 'Peppol Item Classification Code List Version ID (Optional) - eg. If ItemClassification is provided from Danish suppliers, UNSPSC version 19.0501 should be used.',
    'invoice.product.icc.id' => 'Peppol Item Classification Code ID   eg. 9873242',
    'invoice.product.unit.code.not.found' => 'Product does not have a unit Code associated with it. Product ... Edit ... Unit with Peppol.',
    'invoice.product.view.tab.details' => 'Product Details',
    'invoice.product.view.tab.properties' => 'Product Properties',
    'invoice.product.view.tab.images' => 'Product Images',
    'invoice.product.view.tab.gallery' => 'Product Gallery',
    'invoice.products' => 'Products',
    'invoice.productimage.add' => 'Add a Product Image',
    'invoice.productimage.form' => 'Product Image Form',
    'invoice.productimage.gallery' => 'Images relating to: ',
    'invoice.productimage.index' => 'Product Image Index',
    'invoice.productimage.list' => 'List of images associated with this product',
    'invoice.productimage.no.file.uploaded' => 'No image has been uploaded. Possible duplicate.',
    'invoice.productimage.plural' => 'Product Images',
    'invoice.productimage.possible.file.upload.attack' => 'Possible file upload attack: ',
    'invoice.productimage.upload' => 'Upload Product Image',
    'invoice.productimage.uploaded.to' => 'The image has been uploaded to the following directory: ',
    'invoice.productimage.deleted.from' => 'The image has been successfully deleted from the following directory: ',
    'invoice.productimage.view' => 'Multiple images relating to the product can be added under Product View',
    'invoice.profile.new' => 'Create a profile with a new email address, or mobile number, make it active, ' .
                             'and select the company details you wish to link it to. This information will automatically appear on the documentation eg. quotes and invoices.',
    'invoice.profile.deleted' => 'Profile has been deleted',
    'invoice.profile.not.deleted' => 'Profile has not been deleted',
    'invoice.profile.history' => 'Cannot delete Profile. History exists',
    'invoice.profile.plural' => 'Profiles',
    'invoice.profile.property.label.current' => 'Current',
    'invoice.profile.property.label.company' => 'Company',
    'invoice.profile.property.label.description' => 'Description',
    'invoice.profile.property.label.email' => 'Email',
    'invoice.profile.property.label.mobile' => 'Mobile',
    'invoice.profile.singular' => 'Profile',
    'invoice.project.add' => 'Project Add',
    'invoice.quote' => 'Quote',
    'invoice.quote.approve' => 'Approve',
    'invoice.quote.reject' => 'Reject',
    'invoice.quote.copied.to.invoice' => 'Quote copied to NEW Invoice',
    'invoice.quote.not.copied.to.invoice' => 'Invoice NOT created from Quote! Duplicate Invoice. Copy your Quote to another quote and then copy to invoice. Each quote must have a matching invoice.',
    'invoice.quote.copied.to.quote' => 'Quote copied to NEW Quote!',
    'invoice.quote.copied.to.so' => 'Quote Copied to Sales Order',
    'invoice.quote.disable.flash.messages' => 'Disable Quote Flash Messages',
    'invoice.quote.to.so' => 'Quote to Sales Order',
    'invoice.quote.to.so.password' => 'Sales Order Password',
    'invoice.quote.item' => 'Quote Item',
    'invoice.quote.item.cannot.delete' => 'Cannot delete quote item',
    'invoice.quote.custom' => 'Quote Custom',
    'invoice.quote.custom.add' => 'Quote Custom Add',
    'invoice.quote.delivery.location.none' => 'No delivery location has been linked to this quote.',
    'invoice.quote.label.switch.on' => 'NON VAT Quote',
    'invoice.quote.label.switch.off' => 'VAT Quote',
    'invoice.quote.delete.not' => 'The invoice has not been deleted.',
    'invoice.quote.amount' => 'Quote Amount',
    'invoice.quote.item.amount' => 'Quote Item Amount',
    'invoice.quote.tax.rate' => 'Quote Tax Rate',
    'invoice.quote.tax.rate.saved' => 'Quote Tax Rate has been saved',
    'invoice.quote.tax.rate.incomplete.fields' => 'Incomplete fields: You must include a tax rate. Tip: Include a zero tax rate.',
    'invoice.quote.tax.rate.cannot.delete' => 'Cannot delete Quote Tax Rate',
    'invoice.quote.add' => 'Quote Add',
    'invoice.quote.id' => 'Quote ID ',
    'invoice.quote.item.add' => 'Quote Item Add',
    'invoice.quote.item.amount.add' => 'Quote Item Amount Add',
    'invoice.quote.amount.add' => 'Quote Amount Add',
    'invoice.quote.email.templates.not.configured' => 'Email templates not configured. Settings...Quotes...Quote Templates...Default Email Template',
    'invoice.quote.tax.rate.add' => 'Quote Tax Rate Add',
    'invoice.quote.sales.order.created.from.quote' => 'Sales Order created from Quote and you entered your Purchase Order Number!',
    'invoice.quote.sales.order.not.created.from.quote' => 'Sales Order not created from Quote! Duplicate Sales Order. Copy your Quote to another quote and then copy to sales order. Each quote must have a matching sales order.',
    'invoice.quote.number' => 'Quote Number',
    'invoice.quote.number.status' => 'Quote Number Status',
    'invoice.quote.with.purchase.order.number' => 'Purchase Order Number - to be matched with Sales Order Number',
    'invoice.quote.with.purchase.order.line.number' => 'Purchase Order Line Number (Peppol Requirement) - to be matched with Sales Order Line Number',
    'invoice.quote.with.purchase.order.person' => 'Person/Department placing the order',
    'invoice.quote.vat.quote' => 'VAT Quote',
    'invoice.quotes' => 'Quotes',
    'invoice.read.this.please' => 'Read this please!',
    'invoice.records.no' => 'No Records',
    'invoice.recurring' => 'Recurring',
    'invoice.recurring.original.invoice.date' => 'Original Invoice Date: ',
    'invoice.recurring.frequency' => 'Frequency',
    'invoice.recurring.no.invoices.selected' => 'You have not selected any invoices.',
    'invoice.recurring.status.sent.only' => 'Only invoices with a status of sent can be made recurring',
    'invoice.recurring.tooltip.next' => 'The next date is set by means of the index.',
    'invoice.report.sales.by.product' => 'Sales by Product',
    'invoice.report.sales.by.product.info' => 'This report gives the product sales total along with its item tax. It does not include additional invoice tax related to these products.',
    'invoice.report.sales.by.task' => 'Sales by Task',
    'invoice.report.sales.by.task.info' => 'This report gives the task sales total along with its item tax. It does not include additional invoice tax related to these tasks.',
    'invoice.rules.peppol.en16931.001' => 'Business Process or the Profile ID must be provided. Refer to config/common/params.php search ProfileID',
    'invoice.salesorder' => 'Sales Order',
    'invoice.salesorder.agree.to.terms' => 'Please agree to the Terms that will now be sent to you',
    'invoice.salesorder.assembled.packaged.prepared' => 'Assembled/Packaged/Prepared',
    'invoice.salesorder.clients.purchase.order.number' => 'Client' . "'" . 's Purchase Order Number',
    'invoice.salesorder.clients.purchase.order.person' => 'Client' . "'" . 's Purchase Order Person Handling their order',
    'invoice.salesorder.copied.to.invoice' => 'Sales Order copied to Invoice',
    'invoice.salesorder.copied.to.invoice.not' => 'Invoice NOT created from Sales Order! Duplicate Invoice. Copy your Sales Order to another Sales Order and then copy to invoice. Each Sales Order must have a matching invoice',
    'invoice.salesorder.client.confirmed.terms' => 'Client Confirmed Terms',
    'invoice.salesorder.date.created' => 'Sales Order Date Created',
    'invoice.salesorder.default.group' => 'Sales Order Default Group',
    'invoice.salesorder.goods.services.delivered' => 'Goods/Service Delivered',
    'invoice.salesorder.goods.services.confirmed' => 'Client Confirmed Delivery',
    'invoice.salesorder.invoice.number' => 'Invoice Number',
    'invoice.salesorder.number' => 'Sales Order Number',
    'invoice.salesorder.invoice' => 'Invoice',
    'invoice.salesorder.invoice.generate' => 'Invoice Generate',
    'invoice.salesorder.invoice.generated' => 'Invoice Generated',
    'invoice.salesorder.password' => 'Sales Order Password',
    'invoice.salesorder.payment.terms' => 'Sales Order Payment Terms eg. Please pay within 30 days',
    'invoice.salesorder.recent' => 'Recent Sales Orders',
    'invoice.salesorder.reject' => 'Sales Order Reject',
    'invoice.salesorder.rejected' => 'Sales Order Rejected',
    'invoice.salesorder.cancelled' => 'Sales Order Cancelled',
    'invoice.salesorder.sent.to.customer' => 'Terms Agreement Required',
    'invoice.salesorder.number.status' => 'Sales Order No. Status',
    'invoice.salesorder.to.invoice' => 'Sales Order to Invoice',
    'invoice.salesorder.vat.salesorder' => 'VAT Sales Order',
    'invoice.salesorders' => 'Sales Orders',
    'invoice.security.disable.read.only.empty' => 'The disable read-only setting currently has neither a 0 or a 1 value. Legally set it to 0 by default so that a read-only function is available to prevent invoice deletion.',
    'invoice.security.disable.read.only.warning' => 'Warning: Read Only Functionality for Invoice Protection and Deletion Prevention has been disabled',
    'invoice.setting' => 'Setting',
    'invoice.setting.assets.cleared.at' => 'Assets cleared at ',
    'invoice.setting.assets.were.not.cleared.at' => 'Assets were not cleared at ',
    'invoice.setting.as.a.result.of ' => ' as a result of ',
    'invoice.setting.company' => 'Company Public Details',
    'invoice.setting.company.private' => 'Company Private Details',
    'invoice.setting.company.profile' => 'Changing Profile eg. mobile and email address',
    'invoice.setting.error.on.the.public.assets.folder' => ' error on the public assets folder.',
    'invoice.setting.form' => 'Setting Form',
    'invoice.setting.key' => 'Setting Key',
    'invoice.setting.value' => 'Setting Value',
    'invoice.setting.section' => 'Section',
    'invoice.setting.subsection' => 'Subsection',
    'invoice.setting.translator.key' => 'Translator Key',
    'invoice.setting.you.have.cleared.the.cache' => 'You have cleared the cache.',
    'invoice.setting.you.have.not.cleared.the.cache.due.to.a' => 'You have not cleared the cache  to a ',
    'invoice.stop.logging.in' => 'Stop logging in',
    'invoice.stop.signing.up' => 'Stop signing up',
    'invoice.storecove.advisory.to.developer.field.easily.missed' => 'Field easily missed by customer',
    'invoice.storecove.create.a.sender.legal.entity.country' => '1.1.4. Create a sender - Legal Entity Country',
    'invoice.storecove.invoice.json.encoded' => 'StoreCove Json Encoded Invoice',
    'invoice.storecove.legal' => 'Legal',
    'invoice.storecove.legal.entity.id.for.json' => '1.1.4. Create a sender - Store Cove Legal Entity Id inserted into invoice.json',
    'invoice.storecove.legal.entity.identifier.id.not.found' => 'Config params Accounting Supplier Party Legal Entity Company Id not found.',
    'invoice.storecove.tax.scheme.identifier.id.not.found' => 'Config params Accounting Supplier Party Tax Scheme Company Id not found.',
    'invoice.storecove.no.contract.exists' => 'No contract exists',
    'invoice.storecove.not.available' => 'Not Available',
    'invoice.storecove.purchase.order.item.id.null' => 'po-item-id-null',
    'invoice.storecove.receiver.identifier' => 'Receiver Identifier - see StoreCove 6.3',
    'invoice.storecove.region.country.legal.tax' => 'Region ---------- Country --------- Legal --------- Tax',
    'invoice.storecove.salesorder.number.not.exist' => 'Sales Order Number does not exist',
    'invoice.storecove.sender.identifier' => '6.2 Sender Identifier',
    'invoice.storecove.sender.identifier.basis' => '6.2 Sender Identifier Basis - Legal or Tax',
    'invoice.storecove.supplier.contact.email.not.found' => 'Supplier Contact Email Not Found. Refer to config params array.',
    'invoice.storecove.supplier.contact.name.not.found' => 'Supplier Contact Name Not Found. Refer to config params array.',
    'invoice.storecove.supplier.contact.firstname.not.found' => 'Supplier Contact FirstName Not Found. Refer to config params array.',
    'invoice.storecove.supplier.contact.lastname.not.found' => 'Supplier Contact LastName Not Found. Refer to config params array.',
    'invoice.storecove.supplier.contact.telephone.not.found' => 'Supplier Contact Telephone Not Found. Refer to config params array.',
    'invoice.storecove.tax' => 'Tax',
    'invoice.storecove.tax.rate.code' => 'Storecove Tax Rate Code',
    'invoice.successful' => 'Successful',
    'invoice.sumex' => 'Sumex',
    'invoice.project' => 'Project',
    'invoice.report' => 'Report',
    'invoice.setting.add' => 'Setting Add',
    'invoice.submit' => 'Submit',
    'invoice.sumex.add' => 'Sumex Add',
    'invoice.sumex.edit' => 'Sumex Edit',
    'invoice.task' => 'Task',
    'invoice.task.add' => 'Task Add',
    'invoice.tax.rate' => 'Tax Rate',
    'invoice.tax.rate.add' => 'Tax Rate Add',
    'invoice.tax.rate.edit' => 'Edit Tax Rate',
    'invoice.tax.rate.history.exists' => 'Cannot delete. History already exits',
    'invoice.tax.rate.name' => 'Tax Rate Name eg. Standard',
    'invoice.tax.rate.percent' => 'Tax Rate Percent',
    'invoice.term.add.additional.terms.at.setting.repository' => 'Add Additional Terms at Setting Repository getPaymentTerms function.',
    'invoice.term' => 'Terms And Conditions',
    'invoice.term.1' => 'I have not read the terms and conditions.',
    'invoice.term.2' => 'I have read and agree to the terms and conditions.',
    'invoice.test.data.install' => 'Install Test Data',
    'invoice.test.data.use' => 'Use Test Data',
    'invoice.test.remove' => 'Remove Test Data',
    'invoice.test.remove.tooltip' => 'View..Settings..General..Install Test Data..No and View..Settings..General..Use Test Data..No',
    'invoice.test.reset' => 'Reset Test Data',
    'invoice.test.reset.tooltip' => 'View..Settings..General..Install Test Data..Yes and View..Settings..General..Use Test Data..Yes',
    'invoice.test.reset.setting' => 'Settings Reinstall',
    'invoice.test.reset.setting.tooltip' => 'This will remove all current settings and reinstall the default settings in InvoiceController/install_default_settings_on_first_run',
    'invoice.time.created' => 'Time Created',
    'invoice.time.zone' => 'Time Zone',
    'invoice.unit' => 'Unit',
    'invoice.unit.add' => 'Unit Add',
    'invoice.unit.edit' => 'Edit Unit',
    'invoice.unit.description.not.provided' => 'Description not provided',
    'invoice.unit.history' => 'Cannot delete. History exists.',
    'invoice.unit.peppol' => 'Peppol',
    'invoice.unit.peppol.index' => 'Unit Peppol Index',
    'invoice.unit.peppol.add' => 'Unit Peppol Add',
    'invoice.unit.peppol.edit' => 'Unit Peppol Edit',
    'invoice.unit.peppol.code' => 'Unit Peppol Code',
    'invoice.upload.filename.description' => 'Filename Description',
    'invoice.upload.filename.original' => 'Original Filename',
    'invoice.upload.filename.new' => 'New Filename',
    'invoice.upload.description' => 'Description',
    'invoice.upload.url.key' => 'Url Key',
    'invoice.upload.date' => 'Upload Date',
    'invoice.upload.index' => 'Upload Index',
    'invoice.upload.plural' => 'Uploads',
    'invoice.user.account' => 'Invoice User Account',
    'invoice.user.accounts' => 'Invoice User Accounts',
    'invoice.user.account.clients' => 'Clients with User Accounts',
    'invoice.user.api.list' => 'Pre-Invoice Users - Signed Up Users',
    'invoice.user.client.active.no' => 'You have no clients with active user accounts. Administrators assign client(s) to a signed-up user account.',
    'invoice.user.client.count' => '#',
    'invoice.user.clients.assigned.not' => 'This user has no Clients assigned to it.',
    'invoice.user.inv.active.not' => 'The User Account is not Active',
    'invoice.user.inv.list.limit' => 'Number of records listed per page (Note: Overrides default)',
    'invoice.user.inv.refer.to' => 'The default of 10 records per page may be overwritten by clicking here.',
    'invoice.user.inv.more.than.one.assigned' => 'Invoice Creation Unsuccessful: Consult your Settings ... User Account. More than one user has been assigned to this client.',
    'invoice.user.inv.role.accountant' => 'Accountant',
    'invoice.user.inv.role.accountant.assigned' => 'Accountant Role Assigned',
    'invoice.user.inv.role.accountant.default' => 'The Accountant of a client, by default, can view invoices, pay invoices, view payments of invoices, and edit payments of invoices.',
    'invoice.user.inv.role.administrator' => 'Administrator',
    'invoice.user.inv.role.administrator.assigned' => 'The Administrator role has now been assigned. ',
    'invoice.user.inv.role.administrator.already.assigned' => 'The Administrator role has already been assigned',
    'invoice.user.inv.role.all.new' => 'All new users will by default assume the observer role ie. can view Documentation and not edit Documentation sent to them ie. observe or look at the documentation.',
    'invoice.user.inv.role.observer' => 'Observer',
    'invoice.user.inv.role.observer.assigned' => 'Observer Role Assigned',
    'invoice.user.inv.role.observer.assigned.already' => 'The Observer Role has been assigned already.',
    'invoice.user.inv.role.revoke.all' => 'Revoke All Roles',
    'invoice.user.inv.role.warning.revoke.all' => 'Are you sure you want to revoke all roles',
    'invoice.user.inv.role.warning.role' => 'Are you sure you want to adopt this role?',
    'invoice.user.inv.type.cannot.allocate.administrator.type.to.non.administrator' => 'Cannot allocate dropdown\'s administrator type to a non administrator',
    'invoice.user.inv.type.cannot.allocate.guest.type.to.administrator' => 'Cannot allocate dropdown\'s guest type to an administrator',
    'invoice.utility.assets.clear' => 'Clear Assets Cache',
    'invoice.vendor.nikic.fast-route' => 'Building Faster Routes',
    'invoice.vat' => 'VAT',
    'invoice.view' => 'View',
    'gallery.caption.slide1' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide2' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide3' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide4' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide5' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide6' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide7' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide8' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide9' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide10' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide11' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide12' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide13' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide14' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gallery.caption.slide15' => 'From ../resources/messages/en/app.php you can alter this text.',
    'gridview.api' => 'API',
    'gridview.create.at' => 'Created at',
    'gridview.login' => 'Login',
    'gridview.profile' => 'Profile',
    'gridview.title' => 'List of users',
    'home.caption.slides.location.debug.mode' => 'This location of content: ./resources/views/site/index.php within ./resources/views/layout/.  ... and translation slide location ./resources/messages/app.php',
    'home.caption.slide1' => 'Signup and Login as administrator. No internet ... ignore email error connection.',
    'home.caption.slide2' => 'As administator, signup a user. Email account is legit and internet connection ... verify. User will get client account.',
    'home.caption.slide3' => 'Email account not legit, and no internet connection ... admin log in and user\'s Invoice User Account make active under Settings. Create client account. Assign it to user',
    'layout.add.post' => 'Add post',
    'layout.add.random-content' => 'Add random content',
    'layout.add.tag' => 'Add tag',
    'layout.add' => 'Add',
    'layout.archive.for-year' => 'Archive for {year}',
    'layout.archive' => 'Archive',
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
    'layout.page.not-found' => 'The page {url} could not be found.',
    'layout.page.not-authorised' => 'Not Authorised: Authentication credentials are incorrect.',
    'layout.page.user-cancelled-oauth2' => 'User Cancelled Logging in / Registering via Identity Provider e.g Facebook',
    'layout.pagination-summary' => 'Showing {pageSize} out of {total} posts',
    'layout.password-verify' => 'Confirm your password',
    'layout.password' => 'Password',
    'layout.password-verify.new' => 'Confirm your new password',
    'layout.password.new' => 'New Password',
    'layout.rbac.assign-role' => 'Assign RBAC role to user',
    'layout.remember' => 'Remember me',
    'layout.reset' => 'Reset',
    'layout.show-more' => 'show more',
    'layout.submit' => 'Submit',
    'layout.title' => 'Title',
    'layout.total.posts' => 'Total {count} posts',
    'menu.about' => 'About',
    'menu.accreditations' => 'Accreditations',
    'menu.blog' => 'Blog',
    'menu.comments-feed' => 'Comments Feed',
    'menu.contact.details' => 'Contact Details',
    'menu.contact.us' => 'Contact Us',
    'menu.contact.soon' => 'Thank you for contacting us, we\'ll get in touch with you as soon as possible.',
    'menu.gallery' => 'Gallery',
    'menu.language' => 'Language',
    'menu.login' => 'Login',
    'menu.logout' => 'Logout ({login})',
    'menu.signup' => 'Signup',
    'menu.swagger' => 'Swagger',
    'menu.team' => 'Team',
    'menu.pricing' => 'Pricing',
    'menu.privacy.policy' => 'Privacy Policy',
    'menu.terms.of.service' => 'Terms of Service',
    'menu.testimonial' => 'Testimonial',
    'menu.users' => 'Users',
    'password.reset' => 'Reset Password',
    'password.reset.request.token' => 'Request Password Reset Token',
    'password.change' => 'Change Password',
    'signup' => 'Signup',
    /**
     * @see ..\invoice\src\ViewInjection\CommonViewInjection.php
     */
    'site.soletrader.about.we' => 'We diligently apply our skills to the best of our ability.',
    'site.soletrader.about.choose' => 'Here are some appealing reasons to choose us:',
    'site.soletrader.about.competitive.rates' => 'Competitive Rates',
    'site.soletrader.about.quality' => 'Without sacrificing quality',
    'site.soletrader.about.contemporary' => 'Contemporary skills',
    'site.soletrader.about.trained' => 'Our team is well trained and experienced.',
    'site.soletrader.about.willing' => 'Willing Return Support',
    'site.soletrader.about.dissatisfaction' => 'In the event of service dissatisfaction we will redo the work free of charge.',
    'site.soletrader.about.simply' => 'Simply pick up a phone and we will redo the work.',
    'site.soletrader.about.happy' => 'Happy Customers',
    'site.soletrader.about.solved' => 'Issues Solved',
    'site.soletrader.about.finished' => 'Finished Projects',
    'site.soletrader.about.return' => 'Return Customers',
    'site.soletrader.team.we' => 'We are a group of caring, experienced, and diligent individuals.',
    'site.soletrader.team.coordinator' => 'Coordinator',
    'site.soletrader.team.assistant' => 'Assistant',
    'site.soletrader.pricing.pricing' => ' Our Pricing',
    'site.soletrader.pricing.explore' => 'Explore our flexible pricing to find an excellent fit to run your business.',
    'site.soletrader.pricing.plans' => 'More Plans',
    'site.soletrader.pricing.starter' => 'Starter',
    'site.soletrader.pricing.currencyPerMonth' => 'per Month',
    'site.soletrader.pricing.basic' => 'basic',
    'site.soletrader.pricing.visits' => 'visits',
    'site.soletrader.pricing.pro' => 'Professional',
    'site.soletrader.pricing.proPrice' => 'pro Price',
    'site.soletrader.pricing.special' => 'special',
    'site.soletrader.pricing.choosePlan' => 'Choose Plan',
    'site.soletrader.testimonial.we' => 'These are the testimonials',
    'site.soletrader.testimonial.worker1' => 'This is my testimonial',
    'site.soletrader.testimonial.worker2' => 'This is my testimonial',
    'site.soletrader.testimonial.worker3' => 'This is my testimonial',
    'site.soletrader.contact.touch' => 'Get in touch',
    'site.soletrader.contact.lookout' => 'We are always on the lookout to work with new clients. If you are interested in working with us, ' .
                                         'please get in touch in one of the following ways.',
    'site.soletrader.contact.address' => 'Address',
    'site.soletrader.contact.email' => 'Email',
    'site.soletrader.contact.phone' => 'Phone',
    'site.todays.date' => 'Today\'s date',
    'validator.invalid.login.password' => 'Invalid login or password',
    'validator.password.not.match' => 'Passwords do not match',
    'validator.password.not.match.new' => 'Your new passwords do not match',
    'validator.password.reset' => 'Your Password has been reset',
    'validator.password.change' => 'Your Password has been changed',
    'validator.user.exist' => 'A User with this login already exists',
    'validator.user.exist.not' => 'A User with this login does not exist',
    'view.contact.form.name' => 'Name',
    'view.contact.form.email' => 'Email',
    'view.contact.form.subject' => 'Subject',
    'view.contact.form.body' => 'Body',
];
