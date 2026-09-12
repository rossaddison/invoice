<?php

declare(strict_types=1);

/**
 * @link  Acknowledgement to chatgpt.com
 * @link  Acknowledgement to https://text-html.com/ convert chatgpt text to html
 * @link  Rewritten 2026-09-12 in plain language -- shorter sentences, no
 *   nested clauses, consistent terms -- both to ease translation into
 *   this app's other locales and to move closer to GDPR Article 12's
 *   own "clear and plain language" requirement for legal notices (see
 *   privacypolicy.php's identical note). Legal meaning unchanged from
 *   the original wording; a genuine review by the site owner/legal
 *   advisor is still needed before this replaces the original text in
 *   production, same as privacypolicy.php.
 *
 * Existing url's for a 'terms of service' page, and a 'privacy policy' page,
 * are necessary to develop many of the Oauth2.0 clients
 *
 * Related logic: see ViewInjection\CommonViewInjection
 *
 * @var string $arbitrationBody
 * @var string $arbitrationJurisdiction
 * @var string $companyAddress1
 * @var string $companyAddress2
 * @var string $companyCity
 * @var string $companyEmail
 * @var string $companyName
 * @var string $companyPhone
 * @var string $companyStartDate
 * @var string $companyState
 * @var string $companyCountry
 * @var string $companyWeb
 * @var string $companyZip
 */

// Arbitration body/jurisdiction come from the current Company record's
// own "Arbitration Body"/"Arbitration Jurisdiction" fields (see
// CompanyFormFields.php) -- both empty by default, unfilled here would
// render as literal blanks in the Dispute Resolution/Governing Law
// sentences below (confirmed live). Jurisdiction falls back to the
// Company's own registered state/country (already configured data,
// not a made-up placeholder) rather than a specific country's law --
// this app has no way to know which country's law is actually
// intended until the site owner sets it explicitly. Arbitration body
// has no equivalent real fallback, so it gets an honest generic
// description instead of naming a specific body (e.g. the AAA or the
// ICC) that may not be the one the site owner actually intends.
$displayArbitrationBody = $arbitrationBody !== ''
    ? $arbitrationBody
    : 'an arbitration body recognized in the Company\'s jurisdiction';
$companyLocation = trim(
    $companyState . ($companyState !== '' && $companyCountry !== '' ? ', ' : '')
        . $companyCountry,
);
$fallbackJurisdiction = $companyLocation !== ''
    ? $companyLocation
    : 'the Company\'s registered jurisdiction';
$displayArbitrationJurisdiction = $arbitrationJurisdiction !== ''
    ? $arbitrationJurisdiction
    : $fallbackJurisdiction;
?>
<p><strong>Terms of Service</strong></p>
<p><strong>Effective Date:</strong> <?= $companyStartDate; ?></p>
<p>
Welcome to <?= $companyName; ?>, a Yii3 PHP framework development
company. By using our services, you agree to these Terms of Service.
If you do not agree, please do not use our services.
</p>
<hr />
<h3>1. <strong>Definitions</strong></h3>
<ul>
<li>
<strong>"Company"</strong> means <?= $companyName ?>, the provider of
Yii3 PHP framework development services.
</li>
<li>
<strong>"Client"</strong> means any person, company, or other entity
that uses our services.
</li>
<li>
<strong>"Services"</strong> means Yii3 PHP framework development,
support, consultation, and related services we provide.
</li>
<li>
<strong>"Agreement"</strong> means these Terms of Service, together
with any contract you sign with us.
</li>
</ul>
<hr />
<h3>2. <strong>Services Provided</strong></h3>
<p>
We build, customize, and maintain web applications using the Yii3 PHP
framework. Our services may include:
</p>
<ul>
<li>Custom Yii3 application development.</li>
<li>Yii3 framework installation and configuration.</li>
<li>Troubleshooting and bug fixes.</li>
<li>Consulting and code audits.</li>
<li>Long-term maintenance and updates.</li>
</ul>
<hr />
<h3>3. <strong>Client Responsibilities</strong></h3>
<p>You agree to:</p>
<ol>
<li>Give us accurate, complete project requirements and information.</li>
<li>Respond to us quickly and give feedback throughout the project.</li>
<li>
Get any permissions or licenses needed for third-party material you
give us.
</li>
<li>Pay us on the agreed terms.</li>
</ol>
<hr />
<h3>4. <strong>Fees and Payment</strong></h3>
<ul>
<li>We will state our fees in a written agreement or invoice.</li>
<li>
Payment is due within 30 days of the invoice date, unless we agree
otherwise.
</li>
<li>Late payment may lead to extra charges or a pause in our services.</li>
</ul>
<hr />
<h3>5. <strong>Intellectual Property</strong></h3>
<ul>
<li>
We own all code and deliverables we develop until we receive full
payment.
</li>
<li>
Once we receive full payment, ownership passes to you -- except for
our pre-existing intellectual property and any third-party
components.
</li>
<li>
We may show non-confidential work in our portfolio, unless we agree
otherwise in writing.
</li>
</ul>
<hr />
<h3>6. <strong>Confidentiality</strong></h3>
<ul>
<li>
Both sides agree to keep confidential information shared during the
project secure, and not share it with others without written
consent.
</li>
<li>This duty of confidentiality continues after the Agreement ends.</li>
</ul>
<hr />
<h3>7. <strong>Warranties and Limitations of Liability</strong></h3>
<ul>
<li>
We will make reasonable efforts to ensure our work is high-quality
and functions correctly.
</li>
<li>
We do not guarantee the performance of third-party tools or services
used in the project.
</li>
<li>
Where the law allows, our liability is limited to the fees you paid
for that specific service.
</li>
</ul>
<hr />
<h3>8. <strong>Termination</strong></h3>
<ul>
<li>Either side may end this Agreement by giving 30 days' written notice.</li>
<li>
If the Agreement ends, you agree to pay for all work done up to that
date.
</li>
</ul>
<hr />
<h3>9. <strong>Dispute Resolution</strong></h3>
<ul>
<li>We will first try to resolve any dispute through good-faith discussion.</li>
<li>
If that does not work, the dispute goes to binding arbitration under
the rules of <?= $displayArbitrationBody ?> in
<?= $displayArbitrationJurisdiction ?>.
</li>
</ul>
<hr />
<h3>10. <strong>Governing Law</strong></h3>
<p>The laws of <?= $displayArbitrationJurisdiction ?> govern this Agreement.</p>
<hr />
<h3>11. <strong>Amendments</strong></h3>
<p>
We may change these Terms of Service at any time. We will tell you
about significant changes by email or a notice on our website.
</p>
<hr />
<h3>12. <strong>Contact Information</strong></h3>
<p>For any questions about these Terms of Service, please contact us at:</p>
<p>
<b><?= $companyName ?></b><br />
<?= $companyAddress1 ?><br />
<?= $companyAddress2 ?><br />
<?= $companyCity ?><br />
<?= $companyState ?><br />
<?= $companyZip ?><br />
<?= $companyCountry ?><br />
<?= $companyEmail ?><br />
<?= $companyPhone ?>
</p>
<hr/>
