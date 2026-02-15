<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * JavaScript Analysis FAQ Topic
 * Route: /invoice/faq/javascript_analysis
 * Related: InvoiceController->faq('javascript_analysis')
 *
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('JavaScript Analysis - FAQ');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fa fa-code"></i> JavaScript Analysis
                    </h1>
                </div>
                <div class="card-body">
                    
                    <!-- Navigation -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <nav class="nav nav-pills flex-column flex-sm-row">
                                <a class="flex-sm-fill text-sm-center nav-link active" href="#overview">Overview</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#architecture">Architecture</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#files">Core Files</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#functionality">Functionality</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#selectors">Selectors</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#dependencies">Dependencies</a>
                                <a class="flex-sm-fill text-sm-center nav-link" href="#development">Development</a>
                            </nav>
                        </div>
                    </div>

                    <!-- Overview Section -->
                    <section id="overview" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-eye"></i> Migration Overview
                        </h2>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h5><i class="fa fa-arrow-left"></i> Before (Legacy)</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fa fa-times text-danger"></i> jQuery-dependent JavaScript files</li>
                                            <li><i class="fa fa-times text-danger"></i> Multiple separate .js files</li>
                                            <li><i class="fa fa-times text-danger"></i> No type checking</li>
                                            <li><i class="fa fa-times text-danger"></i> Legacy browser patterns</li>
                                            <li><i class="fa fa-times text-danger"></i> Inconsistent error handling</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h5><i class="fa fa-arrow-right"></i> After (Modern)</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fa fa-check text-success"></i> TypeScript with strict typing</li>
                                            <li><i class="fa fa-check text-success"></i> Single bundled output (~40KB)</li>
                                            <li><i class="fa fa-check text-success"></i> ES2020+ modern features</li>
                                            <li><i class="fa fa-check text-success"></i> No external dependencies</li>
                                            <li><i class="fa fa-check text-success"></i> Comprehensive error handling</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Architecture Section -->
                    <section id="architecture" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-sitemap"></i> Current Architecture
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5><i class="fa fa-cogs"></i> Build System</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Tool:</strong> esbuild</p>
                                        <p><strong>Format:</strong> IIFE (Immediately Invoked Function Expression)</p>
                                        <p><strong>Target:</strong> ES2020</p>
                                        <p><strong>Output:</strong> Minified single file</p>
                                        <p><strong>Global:</strong> <code>InvoiceApp</code></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-dark">
                                        <h5><i class="fa fa-file-code-o"></i> Bundle Output</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>File:</strong> <code>invoice-typescript-iife.js</code></p>
                                        <p><strong>Size:</strong> ~40KB minified</p>
                                        <p><strong>Location:</strong> <code>src/Invoice/Asset/rebuild/js/</code></p>
                                        <p><strong>Loading:</strong> Global scope initialization</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h5><i class="fa fa-puzzle-piece"></i> Module System</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Source:</strong> ES6 modules</p>
                                        <p><strong>Compilation:</strong> IIFE bundle</p>
                                        <p><strong>Imports:</strong> Static analysis</p>
                                        <p><strong>Exports:</strong> Global namespace</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Core Files Section -->
                    <section id="files" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-files-o"></i> Core TypeScript Files Analysis
                        </h2>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>File</th>
                                        <th>Purpose</th>
                                        <th>Key Features</th>
                                        <th>Dependencies</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>index.ts</code></td>
                                        <td>Entry point & initialization</td>
                                        <td>Global setup, handler instantiation</td>
                                        <td>All modules</td>
                                    </tr>
                                    <tr>
                                        <td><code>utils.ts</code></td>
                                        <td>Utility functions</td>
                                        <td>parsedata, getJson, ApiResponse types</td>
                                        <td>None</td>
                                    </tr>
                                    <tr>
                                        <td><code>invoice.ts</code></td>
                                        <td>Invoice operations</td>
                                        <td>CRUD, PDF/HTML export, payments, modals</td>
                                        <td>utils.ts, Bootstrap</td>
                                    </tr>
                                    <tr>
                                        <td><code>client.ts</code></td>
                                        <td>Client management</td>
                                        <td>Modal forms, note operations, AJAX forms</td>
                                        <td>utils.ts, Bootstrap</td>
                                    </tr>
                                    <tr>
                                        <td><code>quote.ts</code></td>
                                        <td>Quote operations</td>
                                        <td>Tax management, quote processing</td>
                                        <td>utils.ts</td>
                                    </tr>
                                    <tr>
                                        <td><code>product.ts</code></td>
                                        <td>Product management</td>
                                        <td>Product CRUD operations</td>
                                        <td>utils.ts</td>
                                    </tr>
                                    <tr>
                                        <td><code>salesorder.ts</code></td>
                                        <td>Sales order functionality</td>
                                        <td>Sales order processing</td>
                                        <td>utils.ts</td>
                                    </tr>
                                    <tr>
                                        <td><code>family.ts</code></td>
                                        <td>Family/category management</td>
                                        <td>Category operations</td>
                                        <td>utils.ts</td>
                                    </tr>
                                    <tr>
                                        <td><code>settings.ts</code></td>
                                        <td>Settings management</td>
                                        <td>Configuration handling</td>
                                        <td>utils.ts</td>
                                    </tr>
                                    <tr>
                                        <td><code>create-credit.ts</code></td>
                                        <td>Credit creation</td>
                                        <td>Credit note functionality</td>
                                        <td>utils.ts</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Functionality Section -->
                    <section id="functionality" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-wrench"></i> Key Functionality Breakdown
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fa fa-exchange"></i> AJAX Operations</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Technology:</strong> Modern fetch API</li>
                                            <li><strong>Replaces:</strong> jQuery.ajax</li>
                                            <li><strong>Features:</strong> Promise-based, async/await</li>
                                            <li><strong>Error Handling:</strong> Try-catch with user feedback</li>
                                            <li><strong>Response Types:</strong> JSON, HTML, redirects</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h5><i class="fa fa-window-maximize"></i> Modal Management</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Framework:</strong> Bootstrap 5 modals</li>
                                            <li><strong>Operations:</strong> Show, hide, instance management</li>
                                            <li><strong>Forms:</strong> Create quote/invoice modals</li>
                                            <li><strong>PDF Viewer:</strong> Modal PDF display</li>
                                            <li><strong>Feedback:</strong> Loading states, success indicators</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h5><i class="fa fa-file-pdf-o"></i> PDF Operations</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Export:</strong> PDF generation and download</li>
                                            <li><strong>HTML Export:</strong> HTML version generation</li>
                                            <li><strong>Modal Viewing:</strong> In-browser PDF display</li>
                                            <li><strong>User Feedback:</strong> Loading indicators</li>
                                            <li><strong>Error Handling:</strong> Graceful failure handling</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-dark">
                                        <h5><i class="fa fa-database"></i> CRUD Operations</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Create:</strong> Form submissions with validation</li>
                                            <li><strong>Read:</strong> Dynamic content loading</li>
                                            <li><strong>Update:</strong> Inline editing capabilities</li>
                                            <li><strong>Delete:</strong> Confirmation dialogs, DOM removal</li>
                                            <li><strong>Entities:</strong> Invoices, quotes, clients, notes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Dependencies Section -->
                    <section id="dependencies" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-link"></i> Dependencies and Integrations
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card text-center mb-3">
                                    <div class="card-body">
                                        <i class="fa fa-bootstrap fa-3x text-primary mb-2"></i>
                                        <h5>Bootstrap 5</h5>
                                        <p class="small">Modal system, UI components, responsive grid</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center mb-3">
                                    <div class="card-body">
                                        <i class="fa fa-font-awesome fa-3x text-success mb-2"></i>
                                        <h5>Font Awesome</h5>
                                        <p class="small">Icons throughout the interface, visual indicators</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center mb-3">
                                    <div class="card-body">
                                        <i class="fa fa-html5 fa-3x text-warning mb-2"></i>
                                        <h5>Browser APIs</h5>
                                        <p class="small">Fetch, FormData, DOM manipulation, modern JavaScript</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center mb-3">
                                    <div class="card-body">
                                        <i class="fa fa-check-circle fa-3x text-info mb-2"></i>
                                        <h5>Self-Contained</h5>
                                        <p class="small">No external libraries, framework-independent core</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Development Section -->
                    <section id="development" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-cog"></i> Development Workflow
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-dark text-white">
                                        <h5><i class="fa fa-terminal"></i> NPM Scripts</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Command</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>npm run build:prod</code></td>
                                                    <td>Production build (minified)</td>
                                                </tr>
                                                <tr>
                                                    <td><code>npm run build:dev</code></td>
                                                    <td>Development build (readable)</td>
                                                </tr>
                                                <tr>
                                                    <td><code>npm run build:watch</code></td>
                                                    <td>Watch mode for development</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fa fa-shield"></i> TypeScript Benefits</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fa fa-check text-success"></i> <strong>Strict typing:</strong> Compile-time error detection</li>
                                            <li><i class="fa fa-check text-success"></i> <strong>IntelliSense:</strong> Better IDE support</li>
                                            <li><i class="fa fa-check text-success"></i> <strong>Refactoring:</strong> Safe code changes</li>
                                            <li><i class="fa fa-check text-success"></i> <strong>Documentation:</strong> Self-documenting interfaces</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fa fa-code"></i> Code Organization Patterns</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6><i class="fa fa-object-group"></i> Class-Based Architecture</h6>
                                        <ul class="small">
                                            <li>Handler classes per domain</li>
                                            <li>Event delegation patterns</li>
                                            <li>Encapsulated functionality</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fa fa-exclamation-triangle"></i> Error Handling</h6>
                                        <ul class="small">
                                            <li>Try-catch blocks</li>
                                            <li>User-friendly messages</li>
                                            <li>Console logging for debugging</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fa fa-puzzle-piece"></i> Modular Design</h6>
                                        <ul class="small">
                                            <li>Separate concerns</li>
                                            <li>Reusable utilities</li>
                                            <li>Type-safe interfaces</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Selector Analysis Section -->
                    <section id="selectors" class="mb-5">
                        <h2 class="text-primary mb-3">
                            <i class="fa fa-search"></i> Class and ID Selector Analysis
                        </h2>
                        
                        <div class="alert alert-info" role="alert">
                            <h5><i class="fa fa-info-circle"></i> Migration Comparison</h5>
                            <p>This section compares the selector usage between the legacy individual JavaScript files and the current TypeScript implementation.</p>
                        </div>

                        <!-- Invoice Selectors -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4><i class="fa fa-file-text"></i> Invoice Module Selectors</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Selector</th>
                                                <th>Legacy Location</th>
                                                <th>Current Location</th>
                                                <th>Purpose</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>#recurring_create_confirm</code></td>
                                                <td>invoice.js click handler</td>
                                                <td>invoice.ts handleRecurring()</td>
                                                <td>Create recurring invoices confirmation</td>
                                            </tr>
                                            <tr>
                                                <td><code>#modal-pdf</code></td>
                                                <td>invoice.js modal handlers</td>
                                                <td>invoice.ts handleModalPdfView()</td>
                                                <td>PDF modal viewer</td>
                                            </tr>
                                            <tr>
                                                <td><code>.pdf-export-btn</code></td>
                                                <td>invoice.js individual handlers</td>
                                                <td>invoice.ts handlePdfExport()</td>
                                                <td>PDF export functionality</td>
                                            </tr>
                                            <tr>
                                                <td><code>.html-export-btn</code></td>
                                                <td>invoice.js individual handlers</td>
                                                <td>invoice.ts handleHtmlExport()</td>
                                                <td>HTML export functionality</td>
                                            </tr>
                                            <tr>
                                                <td><code>#payment_submit</code></td>
                                                <td>invoice.js payment handlers</td>
                                                <td>invoice.ts handlePaymentSubmit()</td>
                                                <td>Payment form submission</td>
                                            </tr>
                                            <tr>
                                                <td><code>.delete-item-btn</code></td>
                                                <td>invoice.js item management</td>
                                                <td>invoice.ts handleDeleteSingleItem()</td>
                                                <td>Delete individual invoice items</td>
                                            </tr>
                                            <tr>
                                                <td><code>#select-all-checkbox</code></td>
                                                <td>invoice.js checkbox handlers</td>
                                                <td>invoice.ts handleSelectAllCheckboxes()</td>
                                                <td>Select all items functionality</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Client Selectors -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h4><i class="fa fa-users"></i> Client Module Selectors</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Selector</th>
                                                <th>Legacy Location</th>
                                                <th>Current Location</th>
                                                <th>Purpose</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>#client_create_confirm</code></td>
                                                <td>client.js click handler</td>
                                                <td>client.ts handleClientCreateConfirm()</td>
                                                <td>Client creation confirmation</td>
                                            </tr>
                                            <tr>
                                                <td><code>#save_client_note_new</code></td>
                                                <td>client.js note handlers</td>
                                                <td>client.ts handleSaveClientNote()</td>
                                                <td>Save new client notes</td>
                                            </tr>
                                            <tr>
                                                <td><code>#QuoteForm</code></td>
                                                <td>client.js form handlers</td>
                                                <td>client.ts handleQuoteFormSubmit()</td>
                                                <td>Quote creation modal form</td>
                                            </tr>
                                            <tr>
                                                <td><code>#InvForm</code></td>
                                                <td>client.js form handlers</td>
                                                <td>client.ts handleInvoiceFormSubmit()</td>
                                                <td>Invoice creation modal form</td>
                                            </tr>
                                            <tr>
                                                <td><code>.client-note-delete-btn</code></td>
                                                <td>Not implemented in legacy</td>
                                                <td>client.ts handleDeleteClientNote()</td>
                                                <td>Delete client notes (new feature)</td>
                                            </tr>
                                            <tr>
                                                <td><code>#modal-add-quote</code></td>
                                                <td>client.js modal management</td>
                                                <td>client.ts Bootstrap modal API</td>
                                                <td>Quote creation modal</td>
                                            </tr>
                                            <tr>
                                                <td><code>#modal-add-inv</code></td>
                                                <td>client.js modal management</td>
                                                <td>client.ts Bootstrap modal API</td>
                                                <td>Invoice creation modal</td>
                                            </tr>
                                            <tr>
                                                <td><code>#modal-add-client</code></td>
                                                <td>client.js modal management</td>
                                                <td>client.ts Bootstrap modal API</td>
                                                <td>Client-originated modals</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Quote Selectors -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h4><i class="fa fa-file-text-o"></i> Quote Module Selectors</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Selector</th>
                                                <th>Legacy Location</th>
                                                <th>Current Location</th>
                                                <th>Purpose</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>#quote_tax_submit</code></td>
                                                <td>quote.js tax handlers</td>
                                                <td>quote.ts handleQuoteTaxSubmit()</td>
                                                <td>Quote tax calculation</td>
                                            </tr>
                                            <tr>
                                                <td><code>.quote-tax-rate</code></td>
                                                <td>quote.js rate selectors</td>
                                                <td>quote.ts tax rate management</td>
                                                <td>Tax rate input fields</td>
                                            </tr>
                                            <tr>
                                                <td><code>#quote_tax_form</code></td>
                                                <td>quote.js form handling</td>
                                                <td>quote.ts form submission</td>
                                                <td>Quote tax form container</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Global/Utility Selectors -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h4><i class="fa fa-cogs"></i> Global & Utility Selectors</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Selector</th>
                                                <th>Legacy Location</th>
                                                <th>Current Location</th>
                                                <th>Purpose</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>.btn[type="submit"]</code></td>
                                                <td>Multiple files, jQuery selectors</td>
                                                <td>utils.ts setButtonLoading()</td>
                                                <td>Submit button state management</td>
                                            </tr>
                                            <tr>
                                                <td><code>.form-control</code></td>
                                                <td>Various files, jQuery val()</td>
                                                <td>utils.ts getFieldValue()</td>
                                                <td>Form field value retrieval</td>
                                            </tr>
                                            <tr>
                                                <td><code>.control-group</code></td>
                                                <td>Validation error handling</td>
                                                <td>client.ts clearValidationErrors()</td>
                                                <td>Form validation styling</td>
                                            </tr>
                                            <tr>
                                                <td><code>.has-error</code></td>
                                                <td>jQuery addClass/removeClass</td>
                                                <td>client.ts showValidationErrors()</td>
                                                <td>Validation error indicators</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Migration Patterns -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h4><i class="fa fa-exchange"></i> Selector Migration Patterns</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-danger"><i class="fa fa-arrow-left"></i> Legacy Patterns</h5>
                                        <ul>
                                            <li><code>$(document).on('click', '#selector', function() {})</code></li>
                                            <li><code>$('#selector').val()</code></li>
                                            <li><code>$('#selector').addClass('class')</code></li>
                                            <li><code>$('#modal').modal('show/hide')</code></li>
                                            <li><code>$.ajax()</code> with callbacks</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-success"><i class="fa fa-arrow-right"></i> Modern Patterns</h5>
                                        <ul>
                                            <li><code>document.addEventListener('click', handler, true)</code></li>
                                            <li><code>document.getElementById('selector').value</code></li>
                                            <li><code>element.classList.add('class')</code></li>
                                            <li><code>bootstrap.Modal.getInstance(modal).show()</code></li>
                                            <li><code>fetch()</code> with async/await</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consolidation Benefits -->
                        <div class="alert alert-success" role="alert">
                            <h5><i class="fa fa-check-circle"></i> Selector Consolidation Benefits</h5>
                            <ul class="mb-0">
                                <li><strong>Event Delegation:</strong> Single event listeners handle multiple selectors efficiently</li>
                                <li><strong>Type Safety:</strong> TypeScript ensures selector usage consistency</li>
                                <li><strong>Performance:</strong> Modern DOM APIs are faster than jQuery</li>
                                <li><strong>Maintainability:</strong> Centralized selector management in class methods</li>
                                <li><strong>New Features:</strong> Enhanced functionality like client note deletion</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Summary Section -->
                    <section id="summary" class="mb-4">
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading"><i class="fa fa-check-circle"></i> Migration Success Summary</h4>
                            <p>The JavaScript codebase has been successfully modernized from legacy jQuery-dependent files to a type-safe, 
                            maintainable TypeScript architecture. This provides:</p>
                            <hr>
                            <ul class="mb-0">
                                <li><strong>Better Developer Experience:</strong> Type safety, IntelliSense, compile-time error detection</li>
                                <li><strong>Improved Performance:</strong> Single bundled file, modern JavaScript features, no jQuery dependency</li>
                                <li><strong>Enhanced Maintainability:</strong> Modular architecture, consistent patterns, comprehensive error handling</li>
                                <li><strong>Future-Proof:</strong> Modern standards, easy to extend, framework-independent core</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Back to FAQ -->
                    <div class="text-center">
                        <a href="<?= $urlGenerator->generate('invoice/faq', ['topic' => 'index']); ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to FAQ Index
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add smooth scrolling for navigation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle navigation clicks
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
                
                // Update active nav item
                document.querySelectorAll('.nav-link').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Update active nav on scroll
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    
    window.addEventListener('scroll', function() {
        let currentSection = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            if (window.pageYOffset >= sectionTop) {
                currentSection = '#' + section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentSection) {
                link.classList.add('active');
            }
        });
    });
});
</script>

<style>
.card-header h5 {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
}

code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 90%;
}

.nav-pills .nav-link {
    border-radius: 0.375rem;
    margin: 0 0.25rem;
}

section {
    scroll-margin-top: 100px;
}

.fa-3x {
    font-size: 2.5em !important;
}

@media (max-width: 768px) {
    .nav-pills {
        flex-direction: column !important;
    }
    
    .nav-pills .nav-link {
        margin: 0.25rem 0;
    }
}
</style>