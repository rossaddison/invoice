[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/) 
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT) 
![stable](https://img.shields.io/static/v1?label=No%20Release&message=0.0.0&color=9cf)  
![Downloads](https://img.shields.io/static/v1?label=Downloads/week&message=185&color=9cf)  
![Build](https://img.shields.io/static/v1?label=Build&message=Passing&color=66ff00)
![Dependency Checker](https://img.shields.io/static/v1?label=Dependency%20Checker&message=Passing&color=66ff00) 
![Static Analysis](https://img.shields.io/static/v1?label=Static%20Analysis&message=Passing&color=66ff00)
![Psalm Level](https://img.shields.io/static/v1?label=Psalm%20Level&message=1&color=66ff00)
[![type-coverage](https://shepherd.dev/github/rossaddison/invoice/coverage.svg)](https://shepherd.dev/github/rossaddison/invoice)
[![PHP-CS-Fixer](https://img.shields.io/badge/php--cs--fixer-enabled-blue?logo=php)](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
![Stats](https://github-readme-stats.vercel.app/api?username=rossaddison)
![Hosted by Vultr](https://img.shields.io/badge/hosting-vultr%20(209.250.232.212)-blue?logo=vultr&style=flat-square)

(Place the contents of this download into the yii3-i invoice folder or run as a separate repository.)

# yii3-i's invoice folder 
Yii3 Invoice

**Features**

* Cycle ORM Interface using Invoiceplane type database schema. 
* Generate VAT invoices using mPDF. 
* Code Generator - Controller to views. 
* PCI-compliant payment gateway interfaces – Braintree Sandbox, Stripe Sandbox, and Amazon Pay integration tested. 
* Generate OpenPeppol UBL 2.1 Invoice 3.0.15 XML invoices – validated with Ecosio. 
* StoreCove API connector with JSON invoice. 
* Invoice cycle – Quote to Sales Order (with client's purchase order details) to Invoice.     
* Multiple language compliant – steps to generate new language files included. 
* Separate Client Console and Company Console. 
* Install with Composer.

**Installing with Composer in Windows**
*````composer update````*

## 🚀 Quick Setup with Interactive Installer

For new installations, use one of these interactive installers:

### Option 1: Standalone Installer (Recommended for first-time setup)
```bash
php install.php
```
This works without any dependencies and guides you through the complete setup.

### Option 2: Full-Featured Installer (After dependencies are installed)
```bash
# Using the convenience script
php install_writable.php

# Or using the yii console directly  
./yii install
```

Both installers will:
- ✅ Perform preflight checks (PHP version, extensions, Composer)
- 📦 Install dependencies with `composer install` (with your confirmation)
- 🗄️ Parse database configuration and create the database if needed
- 📋 Provide a checklist for final manual steps

After running either installer, you'll need to manually:
1. Set `BUILD_DATABASE=true` in your `.env` file
2. Start the application to trigger table creation
3. Reset `BUILD_DATABASE=false` for better performance

## Manual Installation

If you prefer manual setup or encounter issues with the installer:

**Installing npm_modules folder containing bootstrap as mentioned in package.json**
* Step 1: Download node.js at https://nodejs.org/en/download
* Step 2: Ensure C:\ProgramFiles\nodejs is in environment variable path. Search ... edit the system environment variables
* Step 3: Run ````npm i```` in ````c:\wamp64\invoice```` folder. This will install @popperjs, Bootstrap 5, and TypeScript 
          into a new node_modules folder.
* Step 4: Keep your npm up to date by running, for example, ````npm install -g npm@10.8.1```` or just ````npm install -g````.

**Recommended php.ini settings**
* Step 1: Wampserver ... Php {version} ... Php Settings ... xdebug.mode = off
* Step 2:                                               ... Maximum Execution Time = 360

Installing the database in mySql
1. Create a database in mySql called yii3_i.
2. The BUILD_DATABASE=true setting in the config/common/params.php file will ensure a firstrun setup of tables.
3. After the setup of tables, ensure that this setting is changed back to false otherwise you will get performance issues.

The c:\wamp64\yii3-i\config\common\params.php file line approx. 193 will automatically build up the tables under database yii3-i. 

````'mode' => $_ENV['BUILD_DATABASE'] ? PhpFileSchemaProvider::MODE_WRITE_ONLY : PhpFileSchemaProvider::MODE_READ_AND_WRITE,````

** If you adjust any Entity file you will have to always make two adjustments to**
** ensure the database is updated with the new changes and relevant fields: **
* 1. Change the BUILD_DATABASE=false in the .env file at the root to BUILD_DATABASE=true
* 2. Once the changes have been reflected and you have checked them via e.g. phpMyAdmin revert back to the original settings

Signup your first user using **+ Person icon**. This user will automatically be assigned the admin role. If you do not have an internet connection you will receive an email failed message
but you will still be able to login. 

You or your customer, signup the second user as your Client/Customer. They will automatically be assigned the observer role. 
If you do not have an internet connection you will get a failed message but if your admin makes the 'Invoice User Account' status active the user
will be able to log in.

If a user signs up by email, they will automatically be assigned as a client, and automatically be made active. 

**If your user has not signed up by email verification, to enable your signed-up Client to make payments:** 
* Step 1: Make sure you have created a client ie. Client ... View ... New
* Step 2: Create a Settings...Invoice User Account
* Step 3: Use the Assigned Client ... Burger Button ... and assign the New User Account to an existing Client.
* Step 4: Make sure they are active.
* Step 5: Make sure the relevant invoice has the status 'sent' either by manually editing the status of the invoice under Invoice ... View ... Options or by actually sending the invoice to the client by email under Invoice ... View ... Options.

**To install at least a service and a product, and a foreign and a non-foreign client automatically, please follow these steps:**

* Step 1: Settings ... View ... General ... Install Test Data ... Yes  AND   Use Test Date ... Yes
* Step 2: In the settings menu, you will now see 'Test data can now be installed'. Click on it.

**The package by default will not use VAT and will use the traditional Invoiceplane type installation providing both line-item tax and invoice tax** 

**If you require VAT based invoices, ensure VAT is setup by going to  Settings ... Views ... Value Added Tax and use a separate database for this purpose. Only line-item tax will be available.**

**Steps to translate into another language:** 

GeneratorController includes a function google_translate_lang ...          
This function takes the English app_lang.php array auto generated in 

````src/Invoice/Language/English```` 

and translates it into the chosen locale (Settings...View...Google Translate) 

outputting it to ````resources/views/generator/output_overwrite.```` 

* Step 1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder.
* Step 2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?pportedpurview=project
* Step 3: Click on Actions icon and select Manage Keys. 
* Step 4: Add Key.
* Step 5: Choose the JSON File option and download the file to src/Invoice/Google_translate_unique_folder.
* Step 6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency.
* Step 7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}

**Xml electronic invoices - Can be output if the following sequence is followed:**

* a: A logged in Client sets up their Peppol details on their side via Client...View...Options...Edit Peppol Details for e-invoicing.

* b: A quote is created and sent by the Administrator to the Client.

* c: A logged in Client creates a sales order from the quote with their purchase order number, purchase order line number, and their contact person in the modal.

* d: A logged in Client, on each of the sales order line items, inputs their line item purchase order reference number, and their purchase order line number. (Mandatory or else exception will be raised).

* e: A logged in Administrator, requests that terms and conditions be accepted.

* f: A logged in Client accepts the terms and conditions.

* g: A logged in Administrator, updates the status of the sales order from assembled, approved, confirmed, to generate.

* h: A logged in Administrator can generate an invoice if the sales order status is on 'generate'

* i: A logged in Administrator can now generate a Peppol XML Invoice using today's exchange rates set up in Settings...View...Peppol Electronic Invoicing...One of From Currency and one of To Currency.

* j: Peppol exceptions will be raised.

