<?php

declare(strict_types=1);

/**
 * @see  Acknowledgement to chatgpt.com
 * @see  Acknowledgement to https://text-html.com/ convert chatgpt text to html
 *
 * Existing url's for a 'terms of service' page, and a 'privacy policy' page,
 * are necessary to develop many of the Oauth2.0 clients
 * @see ViewInjection\CommonViewInjection
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
?>
<p><strong>Terms of Service</strong></p>
<p><strong>Effective Date:</strong> <?php echo $companyStartDate; ?></p>
<p>Welcome to <?php echo $companyName; ?>, a Yii3 PHP framework development company. By accessing or using our services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
<hr />
<h3>1. <strong>Definitions</strong></h3>
<ul>
<li><strong>"Company"</strong> refers to <?php echo $companyName; ?>, the provider of Yii3 PHP framework development services.</li>
<li><strong>"Client"</strong> refers to any individual, company, or entity that engages our services.</li>
<li><strong>"Services"</strong> refer to Yii3 PHP framework development, support, consultation, and related services provided by the Company.</li>
<li><strong>"Agreement"</strong> refers to these Terms of Service, as well as any contracts or agreements entered into between the Client and the Company.</li>
</ul>
<hr />
<h3>2. <strong>Services Provided</strong></h3>
<p>The Company specializes in developing, customizing, and maintaining web applications using the Yii3 PHP framework. Specific services may include, but are not limited to:</p>
<ul>
<li>Custom Yii3 application development.</li>
<li>Yii3 framework installation and configuration.</li>
<li>Troubleshooting and bug fixes.</li>
<li>Consulting and code audits.</li>
<li>Long-term maintenance and updates.</li>
</ul>
<hr />
<h3>3. <strong>Client Responsibilities</strong></h3>
<p>The Client agrees to:</p>
<ol>
<li>Provide accurate and complete project requirements and information.</li>
<li>Ensure timely communication and feedback during the project lifecycle.</li>
<li>Secure all necessary permissions and licenses for any third-party resources provided to the Company.</li>
<li>Make payments in accordance with the agreed-upon terms.</li>
</ol>
<hr />
<h3>4. <strong>Fees and Payment</strong></h3>
<ul>
<li>Fees for services will be outlined in a written agreement or invoice provided to the Client.</li>
<li>Payment terms are net 30 days from the invoice date unless otherwise agreed upon.</li>
<li>Late payments may incur additional charges or suspension of services.</li>
</ul>
<hr />
<h3>5. <strong>Intellectual Property</strong></h3>
<ul>
<li>All code and deliverables developed by the Company remain the property of the Company until full payment is received.</li>
<li>Upon receipt of full payment, ownership of the deliverables is transferred to the Client, except for any pre-existing intellectual property or third-party components.</li>
<li>The Company retains the right to showcase non-confidential deliverables in its portfolio unless otherwise agreed upon in writing.</li>
</ul>
<hr />
<h3>6. <strong>Confidentiality</strong></h3>
<ul>
<li>Both parties agree to keep all confidential information shared during the course of the project secure and not disclose it to third parties without prior written consent.</li>
<li>Confidentiality obligations shall survive the termination of this Agreement.</li>
</ul>
<hr />
<h3>7. <strong>Warranties and Limitations of Liability</strong></h3>
<ul>
<li>The Company will use commercially reasonable efforts to ensure the quality and functionality of the deliverables.</li>
<li>The Company makes no guarantees regarding the performance of third-party tools or services integrated into the project.</li>
<li>To the fullest extent permitted by law, the Company&rsquo;s liability is limited to the fees paid by the Client for the specific service in question.</li>
</ul>
<hr />
<h3>8. <strong>Termination</strong></h3>
<ul>
<li>Either party may terminate the Agreement by providing "30 days" written notice.</li>
<li>Upon termination, the Client agrees to pay for all services rendered up to the date of termination.</li>
</ul>
<hr />
<h3>9. <strong>Dispute Resolution</strong></h3>
<ul>
<li>Any disputes arising under this Agreement shall be resolved through good-faith negotiation.</li>
<li>If unresolved, disputes shall be submitted to binding arbitration under the rules of <?php echo $arbitrationBody; ?> in <?php echo $arbitrationJurisdiction; ?>.</li>
</ul>
<hr />
<h3>10. <strong>Governing Law</strong></h3>
<p>This Agreement shall be governed by and construed in accordance with the laws of <?php echo $arbitrationJurisdiction; ?>.</p>
<hr />
<h3>11. <strong>Amendments</strong></h3>
<p>The Company reserves the right to modify these Terms of Service at any time. Clients will be notified of significant changes via email or a notice on our website.</p>
<hr />
<h3>12. <strong>Contact Information</strong></h3>
<p>For any questions about these Terms of Service, please contact us at:</p>
<p><b><?php echo $companyName; ?></b><br /><?php echo $companyAddress1; ?><br /><?php echo $companyAddress2; ?><br /><?php echo $companyCity; ?><br /><?php echo $companyState; ?><br /><?php echo $companyZip; ?><br /><?php echo $companyCountry; ?>
<br /><?php echo $companyEmail; ?><br /> <?php echo $companyPhone; ?></p>
<hr/>